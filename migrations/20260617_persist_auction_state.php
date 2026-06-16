<?php
declare(strict_types=1);

require_once __DIR__ . '/../db.php';

function migration_column_exists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*)
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = :table_name
            AND COLUMN_NAME = :column_name'
    );
    $stmt->execute([
        'table_name' => $table,
        'column_name' => $column,
    ]);

    return (int) $stmt->fetchColumn() > 0;
}

function migration_add_column(PDO $pdo, string $table, string $column, string $definition): void
{
    if (!migration_column_exists($pdo, $table, $column)) {
        $pdo->exec("ALTER TABLE `$table` ADD COLUMN $definition");
    }
}

function migration_refresh_team(PDO $pdo, int $teamId): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO team_leaderboard (team_id, amount, bid_count, sold_count)
         SELECT
            t.id,
            COALESCE(SUM(
                CASE
                    WHEN p.is_sold = 1 THEN GREATEST(COALESCE(p.sold_amount, 0), COALESCE(p.current_bid, 0), COALESCE(p.base_bid, 0))
                    ELSE GREATEST(COALESCE(p.current_bid, 0), COALESCE(p.base_bid, 0))
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

$pdo = db();

migration_add_column($pdo, 'players', 'current_bid', 'current_bid DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER base_bid');
migration_add_column($pdo, 'players', 'is_sold', 'is_sold TINYINT(1) NOT NULL DEFAULT 0 AFTER is_active');
migration_add_column($pdo, 'players', 'sold_amount', 'sold_amount DECIMAL(12,2) NULL AFTER is_sold');
migration_add_column($pdo, 'players', 'sold_at', 'sold_at DATETIME NULL AFTER sold_amount');

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

$teamIds = $pdo->query('SELECT id FROM teams')->fetchAll(PDO::FETCH_COLUMN);
foreach ($teamIds as $teamId) {
    migration_refresh_team($pdo, (int) $teamId);
}

echo "Auction state migration complete.\n";
