ALTER TABLE players
  MODIFY team_id INT UNSIGNED NULL COMMENT 'Current or winning bidding team; null before a team bids',
  MODIFY base_bid DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Starting auction amount for this player',
  MODIFY current_bid DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Current highest bid amount for this player';

UPDATE players
SET team_id = NULL,
    current_bid = COALESCE(base_bid, 0)
WHERE COALESCE(is_sold, 0) = 0;

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
