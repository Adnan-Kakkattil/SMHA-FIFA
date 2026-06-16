SET @budget_total_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'teams'
    AND COLUMN_NAME = 'budget_total'
);

SET @budget_total_sql := IF(
  @budget_total_exists = 0,
  'ALTER TABLE teams ADD COLUMN budget_total DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT ''Auction wallet allotted to this team'' AFTER name',
  'SELECT 1'
);

PREPARE budget_total_stmt FROM @budget_total_sql;
EXECUTE budget_total_stmt;
DEALLOCATE PREPARE budget_total_stmt;

UPDATE teams t
LEFT JOIN (
  SELECT
    team_id,
    COALESCE(SUM(
      CASE
        WHEN is_sold = 1 THEN GREATEST(COALESCE(sold_amount, 0), COALESCE(current_bid, 0), COALESCE(base_bid, 0))
        ELSE GREATEST(COALESCE(current_bid, 0), COALESCE(base_bid, 0))
      END
    ), 0) AS committed_amount
  FROM players
  WHERE team_id IS NOT NULL AND is_active = 1
  GROUP BY team_id
) committed ON committed.team_id = t.id
SET t.budget_total = GREATEST(t.budget_total, COALESCE(committed.committed_amount, 0));

DELETE FROM team_leaderboard;

INSERT INTO team_leaderboard (team_id, amount, bid_count, sold_count)
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
GROUP BY t.id;
