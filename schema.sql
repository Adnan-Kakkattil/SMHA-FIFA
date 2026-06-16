CREATE DATABASE IF NOT EXISTS smha_fifa
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE smha_fifa;

CREATE TABLE IF NOT EXISTS teams (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_teams_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS players (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  team_id INT UNSIGNED NULL COMMENT 'Current or winning bidding team; null before a team bids',
  name VARCHAR(140) NOT NULL,
  image_path VARCHAR(255) NOT NULL,
  base_bid DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Starting auction amount for this player',
  current_bid DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Current highest bid amount for this player',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  is_sold TINYINT(1) NOT NULL DEFAULT 0,
  sold_amount DECIMAL(12,2) NULL,
  sold_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_players_team_id (team_id),
  KEY idx_players_active_created (is_active, is_sold, created_at),
  CONSTRAINT fk_players_team
    FOREIGN KEY (team_id) REFERENCES teams(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS team_leaderboard (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bid_events (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
