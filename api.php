<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function request_json(): array
{
    $raw = file_get_contents('php://input') ?: '';
    if ($raw === '') {
        return [];
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        throw new InvalidArgumentException('Invalid JSON body.');
    }

    return $data;
}

function require_csrf(): void
{
    $token = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if ($token === '' || !hash_equals((string) $_SESSION['csrf_token'], $token)) {
        json_response(['ok' => false, 'error' => 'Invalid request token. Refresh the page and try again.'], 403);
    }
}

function ensure_column(PDO $pdo, string $table, string $column, string $definition): void
{
    $allowedTables = ['players', 'teams'];
    if (!in_array($table, $allowedTables, true)) {
        throw new InvalidArgumentException('Invalid schema check.');
    }

    $stmt = $pdo->prepare(
        'SELECT COUNT(*) AS column_count
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = :table_name
            AND COLUMN_NAME = :column_name'
    );
    $stmt->execute([
        'table_name' => $table,
        'column_name' => $column,
    ]);

    if ((int) $stmt->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE `$table` ADD COLUMN $definition");
    }
}

function ensure_runtime_schema(PDO $pdo): void
{
    ensure_column($pdo, 'teams', 'budget_total', 'budget_total DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER name');

    ensure_column($pdo, 'players', 'base_bid', 'base_bid DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER image_path');
    ensure_column($pdo, 'players', 'current_bid', 'current_bid DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER base_bid');
    ensure_column($pdo, 'players', 'is_sold', 'is_sold TINYINT(1) NOT NULL DEFAULT 0 AFTER is_active');
    ensure_column($pdo, 'players', 'sold_amount', 'sold_amount DECIMAL(12,2) NULL AFTER is_sold');
    ensure_column($pdo, 'players', 'sold_at', 'sold_at DATETIME NULL AFTER sold_amount');

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS team_leaderboard (
            team_id INT UNSIGNED NOT NULL,
            amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            bid_count INT UNSIGNED NOT NULL DEFAULT 0,
            sold_count INT UNSIGNED NOT NULL DEFAULT 0,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (team_id),
            KEY idx_team_leaderboard_amount (amount),
            CONSTRAINT fk_team_leaderboard_team
                FOREIGN KEY (team_id) REFERENCES teams(id)
                ON UPDATE CASCADE
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS bid_events (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            player_id INT UNSIGNED NULL,
            team_id INT UNSIGNED NULL,
            amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            source VARCHAR(20) NOT NULL DEFAULT 'manual',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_bid_events_player_created (player_id, created_at),
            KEY idx_bid_events_team_amount (team_id, amount),
            KEY idx_bid_events_created (created_at),
            CONSTRAINT fk_bid_events_player
                FOREIGN KEY (player_id) REFERENCES players(id)
                ON UPDATE CASCADE
                ON DELETE SET NULL,
            CONSTRAINT fk_bid_events_team
                FOREIGN KEY (team_id) REFERENCES teams(id)
                ON UPDATE CASCADE
                ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    backfill_current_bids($pdo);
    refresh_all_team_leaderboard($pdo);
}

function normalize_amount(mixed $value): float
{
    if (!is_int($value) && !is_float($value) && !is_string($value)) {
        throw new InvalidArgumentException('Bid amount is required.');
    }

    $amount = (float) $value;
    if (!is_finite($amount) || $amount < 0 || $amount > 999999999) {
        throw new InvalidArgumentException('Bid amount is outside the allowed range.');
    }

    return round($amount, 2);
}

function normalize_player_id(mixed $value): int
{
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!$id) {
        throw new InvalidArgumentException('Valid player is required.');
    }

    return (int) $id;
}

function normalize_team_id(mixed $value): int
{
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!$id) {
        throw new InvalidArgumentException('Valid team is required.');
    }

    return (int) $id;
}

function bidding_team(PDO $pdo, int $teamId): array
{
    $stmt = $pdo->prepare('SELECT id, name, budget_total FROM teams WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $teamId]);
    $team = $stmt->fetch();

    if (!$team) {
        throw new RuntimeException('Team is not available for bidding.');
    }

    return $team;
}

function team_account(PDO $pdo, int $teamId, bool $forUpdate = false): array
{
    $lock = $forUpdate ? ' FOR UPDATE' : '';
    $stmt = $pdo->prepare('SELECT id, name, budget_total FROM teams WHERE id = :id LIMIT 1' . $lock);
    $stmt->execute(['id' => $teamId]);
    $team = $stmt->fetch();

    if (!$team) {
        throw new RuntimeException('Team is not available for bidding.');
    }

    $spentStmt = $pdo->prepare(
        'SELECT COALESCE(SUM(GREATEST(COALESCE(sold_amount, 0), COALESCE(current_bid, 0), COALESCE(base_bid, 0))), 0)
         FROM players
         WHERE team_id = :team_id AND is_active = 1 AND COALESCE(is_sold, 0) = 1'
    );
    $spentStmt->execute(['team_id' => $teamId]);
    $spent = (float) $spentStmt->fetchColumn();

    $reservedStmt = $pdo->prepare(
        'SELECT COALESCE(SUM(GREATEST(COALESCE(current_bid, 0), COALESCE(base_bid, 0))), 0)
         FROM players
         WHERE team_id = :team_id AND is_active = 1 AND COALESCE(is_sold, 0) = 0'
    );
    $reservedStmt->execute(['team_id' => $teamId]);
    $reserved = (float) $reservedStmt->fetchColumn();
    $budget = (float) $team['budget_total'];
    $available = $budget - $spent - $reserved;

    return [
        'id' => (int) $team['id'],
        'name' => (string) $team['name'],
        'budgetTotal' => (int) round($budget),
        'spentAmount' => (int) round($spent),
        'reservedAmount' => (int) round($reserved),
        'availableAmount' => (int) round(max(0, $available)),
        'rawAvailableAmount' => $available,
    ];
}

function current_player(PDO $pdo, int $playerId, bool $forUpdate = false): array
{
    $lock = $forUpdate ? ' FOR UPDATE' : '';
    $stmt = $pdo->prepare(
        'SELECT p.id, p.team_id, p.base_bid, p.current_bid, t.name AS team_name
         FROM players p
         LEFT JOIN teams t ON t.id = p.team_id
         WHERE p.id = :id AND p.is_active = 1 AND COALESCE(p.is_sold, 0) = 0
         LIMIT 1' . $lock
    );
    $stmt->execute(['id' => $playerId]);
    $player = $stmt->fetch();

    if (!$player) {
        throw new RuntimeException('Player is not available for bidding.');
    }

    return $player;
}

function insert_bid_event(PDO $pdo, int $playerId, ?int $teamId, float $amount, string $source): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO bid_events (player_id, team_id, amount, source)
         VALUES (:player_id, :team_id, :amount, :source)'
    );
    $stmt->execute([
        'player_id' => $playerId,
        'team_id' => $teamId,
        'amount' => $amount,
        'source' => $source,
    ]);
}

function winning_bid(PDO $pdo, int $playerId, bool $forUpdate = false): array
{
    $lock = $forUpdate ? ' FOR UPDATE' : '';
    $stmt = $pdo->prepare(
        "SELECT be.team_id, be.amount, t.name AS team_name
         FROM bid_events be
         INNER JOIN teams t ON t.id = be.team_id
         WHERE be.player_id = :player_id AND be.team_id IS NOT NULL AND be.source <> 'close'
         ORDER BY be.amount DESC, be.id DESC
         LIMIT 1" . $lock
    );
    $stmt->execute(['player_id' => $playerId]);
    $bid = $stmt->fetch();

    if (!$bid) {
        throw new InvalidArgumentException('At least one team bid is required before closing.');
    }

    return $bid;
}

function backfill_current_bids(PDO $pdo): void
{
    $pdo->exec(
        'UPDATE players p
         LEFT JOIN (
            SELECT player_id, MAX(amount) AS max_amount
            FROM bid_events
            GROUP BY player_id
         ) be ON be.player_id = p.id
         SET p.current_bid = GREATEST(
            COALESCE(p.current_bid, 0),
            COALESCE(p.base_bid, 0),
            COALESCE(p.sold_amount, 0),
            COALESCE(be.max_amount, 0)
         )'
    );
}

function refresh_team_leaderboard(PDO $pdo, ?int $teamId): void
{
    if ($teamId === null) {
        return;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO team_leaderboard (team_id, amount, bid_count, sold_count)
         SELECT
            t.id,
            COALESCE(SUM(
                CASE
                    WHEN p.is_sold = 1 THEN GREATEST(COALESCE(p.sold_amount, 0), COALESCE(p.current_bid, 0), COALESCE(p.base_bid, 0))
                    ELSE 0
                END
            ), 0) AS amount,
            COALESCE((
                SELECT COUNT(*)
                FROM bid_events be
                WHERE be.team_id = t.id
            ), 0) AS bid_count,
            COALESCE(SUM(CASE WHEN p.is_sold = 1 THEN 1 ELSE 0 END), 0) AS sold_count
         FROM teams t
         LEFT JOIN players p ON p.team_id = t.id AND p.is_active = 1
         WHERE t.id = :team_id
         GROUP BY t.id
         ON DUPLICATE KEY UPDATE
            amount = VALUES(amount),
            bid_count = VALUES(bid_count),
            sold_count = VALUES(sold_count)'
    );
    $stmt->execute(['team_id' => $teamId]);
}

function refresh_all_team_leaderboard(PDO $pdo): void
{
    $teamIds = $pdo->query('SELECT id FROM teams')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($teamIds as $teamId) {
        refresh_team_leaderboard($pdo, (int) $teamId);
    }
}

function auction_players(PDO $pdo): array
{
    $stmt = $pdo->query(
        'SELECT p.id, p.team_id, p.name, p.image_path, p.base_bid,
            GREATEST(p.base_bid, p.current_bid) AS current_bid,
            t.name AS team_name
         FROM players p
         LEFT JOIN teams t ON t.id = p.team_id
         WHERE p.is_active = 1 AND COALESCE(p.is_sold, 0) = 0
         ORDER BY p.created_at ASC, p.id ASC'
    );

    $players = [];
    foreach ($stmt->fetchAll() as $player) {
        $teamName = (string) ($player['team_name'] ?? '');
        $players[] = [
            'id' => (int) $player['id'],
            'teamId' => $player['team_id'] !== null ? (int) $player['team_id'] : null,
            'teamName' => $teamName !== '' ? $teamName : null,
            'name' => (string) $player['name'],
            'role' => $teamName !== '' ? 'LEADING | ' . $teamName : 'AVAILABLE | AUCTION',
            'image' => (string) $player['image_path'],
            'baseBid' => (int) round((float) $player['base_bid']),
            'currentBid' => (int) round((float) $player['current_bid']),
        ];
    }

    return $players;
}

function leaderboard(PDO $pdo, ?int $limit = 5): array
{
    $sql = 'SELECT t.id, t.name, COALESCE(tl.amount, 0) AS amount,
            COALESCE(tl.bid_count, 0) AS bid_count,
            COALESCE(tl.sold_count, 0) AS sold_count,
            COALESCE(t.budget_total, 0) AS budget_total,
            COALESCE(reserved.reserved_amount, 0) AS reserved_amount
         FROM teams t
         LEFT JOIN team_leaderboard tl ON tl.team_id = t.id
         LEFT JOIN (
            SELECT team_id, COALESCE(SUM(GREATEST(COALESCE(current_bid, 0), COALESCE(base_bid, 0))), 0) AS reserved_amount
            FROM players
            WHERE is_active = 1 AND COALESCE(is_sold, 0) = 0 AND team_id IS NOT NULL
            GROUP BY team_id
         ) reserved ON reserved.team_id = t.id
         ORDER BY amount DESC, t.created_at ASC, t.id ASC
    ';

    if ($limit !== null) {
        $sql .= ' LIMIT ' . max(1, (int) $limit);
    }

    $stmt = $pdo->query($sql);

    $teams = [];
    foreach ($stmt->fetchAll() as $index => $team) {
        $spent = (float) $team['amount'];
        $reserved = (float) $team['reserved_amount'];
        $budget = (float) $team['budget_total'];
        $available = max(0, $budget - $spent - $reserved);
        $teams[] = [
            'rank' => $index + 1,
            'id' => (int) $team['id'],
            'name' => (string) $team['name'],
            'amount' => (int) round($spent),
            'budgetTotal' => (int) round($budget),
            'spentAmount' => (int) round($spent),
            'reservedAmount' => (int) round($reserved),
            'availableAmount' => (int) round($available),
            'bidCount' => (int) $team['bid_count'],
            'soldCount' => (int) $team['sold_count'],
        ];
    }

    return $teams;
}

function state_payload(PDO $pdo): array
{
    return [
        'ok' => true,
        'players' => auction_players($pdo),
        'leaderboard' => leaderboard($pdo),
        'teams' => leaderboard($pdo, null),
    ];
}

function velocity_payload(PDO $pdo): array
{
    $stmt = $pdo->query(
        'SELECT TIMESTAMPDIFF(MINUTE, created_at, NOW()) AS age_minutes
         FROM bid_events
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 70 MINUTE)
         ORDER BY created_at ASC'
    );

    $bucketCount = 7;
    $values = array_fill(0, $bucketCount, 0);
    $dbNowValue = (string) $pdo->query('SELECT NOW()')->fetchColumn();
    $now = new DateTimeImmutable($dbNowValue !== '' ? $dbNowValue : 'now');
    $start = $now->sub(new DateInterval('PT60M'));

    foreach ($stmt->fetchAll() as $event) {
        $ageMinutes = (int) ($event['age_minutes'] ?? 999);
        if ($ageMinutes < 0 || $ageMinutes >= 70) {
            continue;
        }

        $bucket = ($bucketCount - 1) - (int) floor($ageMinutes / 10);
        if ($bucket >= 0 && $bucket < $bucketCount) {
            $values[$bucket]++;
        }
    }

    $max = max($values);
    $percentages = [];
    foreach ($values as $count) {
        $percentages[] = $max > 0 ? (int) max(8, round(($count / $max) * 100)) : 0;
    }

    $labels = [];
    for ($i = 0; $i < $bucketCount; $i++) {
        $labels[] = $start->add(new DateInterval('PT' . ($i * 10) . 'M'))->format('H:i');
    }

    $peakIndex = 0;
    foreach ($values as $index => $count) {
        if ($count >= $values[$peakIndex]) {
            $peakIndex = $index;
        }
    }

    return [
        'ok' => true,
        'labels' => $labels,
        'values' => $percentages,
        'counts' => $values,
        'peakIndex' => $max > 0 ? $peakIndex : null,
        'live' => $percentages[$bucketCount - 1],
        'hasData' => $max > 0,
    ];
}

try {
    $pdo = db();
    ensure_runtime_schema($pdo);

    $action = (string) ($_GET['action'] ?? $_POST['action'] ?? '');
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'GET' && $action === 'state') {
        json_response(state_payload($pdo));
    }

    if ($method === 'GET' && $action === 'velocity') {
        json_response(velocity_payload($pdo));
    }

    if ($method === 'POST') {
        require_csrf();
        $body = request_json();

        if ($action === 'bid') {
            $playerId = normalize_player_id($body['player_id'] ?? null);
            $teamId = normalize_team_id($body['team_id'] ?? null);
            $amount = normalize_amount($body['amount'] ?? null);
            $source = (string) ($body['source'] ?? 'manual');
            $allowedSources = ['manual', 'place'];

            if (!in_array($source, $allowedSources, true)) {
                $source = 'manual';
            }

            $pdo->beginTransaction();
            $player = current_player($pdo, $playerId, true);
            $current = max((float) $player['base_bid'], (float) $player['current_bid']);
            if ($amount <= $current) {
                throw new InvalidArgumentException('Bid amount must be higher than the current bid.');
            }

            $previousTeamId = $player['team_id'] !== null ? (int) $player['team_id'] : null;
            $teamAccount = team_account($pdo, $teamId, true);
            $reservedCredit = $previousTeamId === $teamId ? $current : 0.0;
            $maximumAllowedBid = (float) $teamAccount['rawAvailableAmount'] + $reservedCredit;

            if ($amount > $maximumAllowedBid) {
                throw new InvalidArgumentException(
                    sprintf(
                        '%s has only %s available for this bid.',
                        $teamAccount['name'],
                        '₹' . number_format(max(0, $maximumAllowedBid), 0)
                    )
                );
            }

            insert_bid_event($pdo, $playerId, $teamId, $amount, $source);

            $stmt = $pdo->prepare(
                'UPDATE players
                 SET team_id = :team_id, current_bid = :current_bid
                 WHERE id = :id AND is_active = 1 AND COALESCE(is_sold, 0) = 0'
            );
            $stmt->execute([
                'team_id' => $teamId,
                'current_bid' => $amount,
                'id' => $playerId,
            ]);

            refresh_team_leaderboard($pdo, $previousTeamId);
            refresh_team_leaderboard($pdo, $teamId);
            $pdo->commit();
            json_response(state_payload($pdo) + ['amount' => (int) round($amount)]);
        }

        if ($action === 'close') {
            $playerId = normalize_player_id($body['player_id'] ?? null);
            $pdo->beginTransaction();
            $player = current_player($pdo, $playerId, true);
            $winner = winning_bid($pdo, $playerId, true);
            $current = max((float) $player['base_bid'], (float) $player['current_bid']);
            $soldAmount = max((float) $winner['amount'], $current);
            $teamId = (int) $winner['team_id'];
            $previousTeamId = $player['team_id'] !== null ? (int) $player['team_id'] : null;

            insert_bid_event($pdo, $playerId, $teamId, $soldAmount, 'close');

            $stmt = $pdo->prepare(
                'UPDATE players
                 SET team_id = :team_id, current_bid = :current_bid, is_sold = 1, sold_amount = :sold_amount, sold_at = NOW()
                 WHERE id = :id AND is_active = 1 AND COALESCE(is_sold, 0) = 0'
            );
            $stmt->execute([
                'team_id' => $teamId,
                'current_bid' => $soldAmount,
                'sold_amount' => $soldAmount,
                'id' => $playerId,
            ]);

            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException('Player was already closed.');
            }

            refresh_team_leaderboard($pdo, $previousTeamId);
            refresh_team_leaderboard($pdo, $teamId);
            $pdo->commit();
            json_response(state_payload($pdo) + [
                'sold' => true,
                'soldAmount' => (int) round($soldAmount),
                'winningTeam' => [
                    'id' => $teamId,
                    'name' => (string) ($winner['team_name'] ?? ''),
                ],
            ]);
        }
    }

    json_response(['ok' => false, 'error' => 'Unknown API action.'], 404);
} catch (PDOException $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log($exception->getMessage());
    json_response(['ok' => false, 'error' => 'Server error. Check database setup and try again.'], 500);
} catch (InvalidArgumentException | RuntimeException $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response(['ok' => false, 'error' => $exception->getMessage()], 422);
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log($exception->getMessage());
    json_response(['ok' => false, 'error' => 'Server error. Check database setup and try again.'], 500);
}
