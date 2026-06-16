<?php
declare(strict_types=1);

require_once __DIR__ . '/../db.php';

function migration_base_column_exists(PDO $pdo, string $table, string $column): bool
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

function migration_base_add_column(PDO $pdo, string $table, string $column, string $definition): void
{
    if (!migration_base_column_exists($pdo, $table, $column)) {
        $pdo->exec("ALTER TABLE `$table` ADD COLUMN $definition");
    }
}

$pdo = db();

migration_base_add_column($pdo, 'players', 'base_bid', 'base_bid DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER image_path');
migration_base_add_column($pdo, 'players', 'current_bid', 'current_bid DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER base_bid');

$pdo->exec(
    'UPDATE players
     SET current_bid = GREATEST(COALESCE(current_bid, 0), COALESCE(base_bid, 0), COALESCE(sold_amount, 0))'
);

$hasLeaderboard = (int) $pdo->query(
    "SELECT COUNT(*)
     FROM INFORMATION_SCHEMA.TABLES
     WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'team_leaderboard'"
)->fetchColumn() > 0;

$hasBidEvents = (int) $pdo->query(
    "SELECT COUNT(*)
     FROM INFORMATION_SCHEMA.TABLES
     WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'bid_events'"
)->fetchColumn() > 0;

if ($hasLeaderboard && $hasBidEvents) {
    $pdo->exec(
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
         GROUP BY t.id
         ON DUPLICATE KEY UPDATE
            amount = VALUES(amount),
            bid_count = VALUES(bid_count),
            sold_count = VALUES(sold_count)'
    );
}

echo "Player base amount migration complete.\n";
