-- ================================================================
-- Pet Feeder Database Setup SQL
-- Database Name: bitesnbowls_db
-- ================================================================

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS bitesnbowls_db;
USE bitesnbowls_db;

-- ================================================================
-- USERS TABLE
-- Stores user login credentials
-- ================================================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ================================================================
-- DEVICE SETTINGS TABLE
-- Stores device configuration and status
-- ================================================================
CREATE TABLE IF NOT EXISTS device_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) UNIQUE NOT NULL,
    setting_value TEXT,
    description VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ================================================================
-- SCHEDULES TABLE
-- Stores feeding schedules
-- ================================================================
CREATE TABLE IF NOT EXISTS schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    schedule_name VARCHAR(100),
    interval_type VARCHAR(20) COMMENT 'hourly, daily, weekly, custom',
    start_time TIME NOT NULL,
    end_time TIME NULL,
    rounds INT DEFAULT 1 CHECK (rounds >= 1 AND rounds <= 10),
    frequency VARCHAR(20) COMMENT 'once, repeat',
    custom_days VARCHAR(100) COMMENT 'Comma-separated day numbers',
    is_active TINYINT(1) DEFAULT 1,
    is_manual TINYINT(1) DEFAULT 0 COMMENT '1 if created from Arduino device, 0 if from web app',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_active (is_active),
    INDEX idx_start_time (start_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ================================================================
-- HISTORY TABLE
-- Stores feeding history and logs
-- ================================================================
CREATE TABLE IF NOT EXISTS history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    feed_date DATE NOT NULL,
    feed_time TIME NOT NULL,
    rounds INT NOT NULL,
    type VARCHAR(20) NOT NULL COMMENT 'Manual, Scheduled, Remote',
    status VARCHAR(20) DEFAULT 'Success' COMMENT 'Success, Failed, Partial',
    weight_before DECIMAL(10,2) NULL COMMENT 'Weight before feeding in grams',
    weight_after DECIMAL(10,2) NULL COMMENT 'Weight after feeding in grams',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_feed_date (feed_date),
    INDEX idx_type (type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ================================================================
-- ALERTS TABLE
-- Stores system alerts and notifications
-- ================================================================
CREATE TABLE IF NOT EXISTS alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alert_type VARCHAR(50) NOT NULL COMMENT 'Low Food, Disturbed, Connection Lost, Error',
    message TEXT NOT NULL,
    severity VARCHAR(20) DEFAULT 'info' COMMENT 'info, warning, error, critical',
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at),
    INDEX idx_alert_type (alert_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ================================================================
-- DEVICE LOGS TABLE
-- Stores detailed device activity logs
-- ================================================================
CREATE TABLE IF NOT EXISTS device_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    log_type VARCHAR(50) NOT NULL COMMENT 'heartbeat, weight_update, feed, alert, error',
    log_message TEXT,
    data JSON NULL COMMENT 'Additional data in JSON format',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_log_type (log_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ================================================================
-- INSERT DEFAULT DEVICE SETTINGS
-- ================================================================
INSERT INTO device_settings (setting_key, setting_value, description) VALUES
('is_connected', '0', 'Device connection status (0=offline, 1=online)'),
('current_weight', '0', 'Current food weight in grams'),
('last_heartbeat', NOW(), 'Last communication timestamp'),
('wifi_ssid', '', 'Connected WiFi network name'),
('wifi_rssi', '0', 'WiFi signal strength'),
('device_name', 'Pet Feeder', 'Device display name'),
('firmware_version', '1.0.0', 'Current firmware version'),
('calibration_factor', '80.0', 'Load cell calibration factor'),
('dispense_duration', '1500', 'Servo activation duration in milliseconds'),
('low_food_threshold', '100', 'Alert threshold for low food in grams'),
('timezone', 'UTC', 'Device timezone'),
('auto_sync_time', '1', 'Auto-sync time from server (0=off, 1=on)')
ON DUPLICATE KEY UPDATE setting_value=setting_value;

-- ================================================================
-- INSERT DEFAULT ADMIN USER
-- Username: admin
-- Password: 1234
-- ================================================================
INSERT INTO users (username, password) VALUES
('admin', '1234')
ON DUPLICATE KEY UPDATE username=username;


-- ================================================================
-- CREATE VIEWS FOR EASY DATA ACCESS
-- ================================================================

-- View: Recent feeding history
CREATE OR REPLACE VIEW recent_feeds AS
SELECT 
    h.*,
    DATE_FORMAT(h.feed_date, '%M %d, %Y') as formatted_date,
    DATE_FORMAT(h.feed_time, '%h:%i %p') as formatted_time
FROM history h
ORDER BY h.feed_date DESC, h.feed_time DESC
LIMIT 50;

-- View: Active schedules
CREATE OR REPLACE VIEW active_schedules AS
SELECT 
    s.*,
    DATE_FORMAT(s.start_time, '%h:%i %p') as formatted_time
FROM schedules s
WHERE s.is_active = 1
ORDER BY s.start_time;

-- View: Unread alerts
CREATE OR REPLACE VIEW unread_alerts AS
SELECT 
    a.*,
    DATE_FORMAT(a.created_at, '%M %d, %Y %h:%i %p') as formatted_date
FROM alerts a
WHERE a.is_read = 0
ORDER BY a.created_at DESC;

-- View: Device status summary
CREATE OR REPLACE VIEW device_status AS
SELECT 
    MAX(CASE WHEN setting_key = 'is_connected' THEN setting_value END) as is_connected,
    MAX(CASE WHEN setting_key = 'current_weight' THEN setting_value END) as current_weight,
    MAX(CASE WHEN setting_key = 'last_heartbeat' THEN setting_value END) as last_heartbeat,
    MAX(CASE WHEN setting_key = 'wifi_rssi' THEN setting_value END) as wifi_rssi,
    MAX(CASE WHEN setting_key = 'device_name' THEN setting_value END) as device_name
FROM device_settings
WHERE setting_key IN ('is_connected', 'current_weight', 'last_heartbeat', 'wifi_rssi', 'device_name');

-- ================================================================
-- CREATE STORED PROCEDURES
-- ================================================================

-- Procedure: Clean old logs (keep last 30 days)
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS clean_old_logs()
BEGIN
    DELETE FROM device_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
    DELETE FROM history WHERE feed_date < DATE_SUB(CURDATE(), INTERVAL 90 DAY);
    DELETE FROM alerts WHERE is_read = 1 AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
END//
DELIMITER ;

-- Procedure: Get feeding statistics for date range
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS get_feeding_stats(IN start_date DATE, IN end_date DATE)
BEGIN
    SELECT 
        feed_date,
        COUNT(*) as feed_count,
        SUM(rounds) as total_rounds,
        GROUP_CONCAT(DISTINCT type) as feed_types
    FROM history
    WHERE feed_date BETWEEN start_date AND end_date
    GROUP BY feed_date
    ORDER BY feed_date DESC;
END//
DELIMITER ;

-- ================================================================
-- CREATE TRIGGERS
-- ================================================================

-- Trigger: Update last_heartbeat on device_settings update
DELIMITER //
CREATE TRIGGER IF NOT EXISTS update_heartbeat_timestamp
BEFORE UPDATE ON device_settings
FOR EACH ROW
BEGIN
    IF NEW.setting_key = 'is_connected' AND NEW.setting_value = '1' THEN
        SET NEW.updated_at = NOW();
    END IF;
END//
DELIMITER ;

-- ================================================================
-- GRANT PERMISSIONS (if using specific database user)
-- ================================================================
-- Uncomment and modify if you want to create a dedicated user
-- CREATE USER IF NOT EXISTS 'petfeeder_user'@'localhost' IDENTIFIED BY 'your_secure_password';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON bitesnbowls_db.* TO 'petfeeder_user'@'localhost';
-- FLUSH PRIVILEGES;

-- ================================================================
-- DATABASE SETUP COMPLETE
-- ================================================================

SELECT 'Database setup completed successfully!' as Status;
SELECT COUNT(*) as 'Users Created' FROM users;
SELECT COUNT(*) as 'Device Settings' FROM device_settings;
SELECT COUNT(*) as 'Schedules' FROM schedules;
SELECT COUNT(*) as 'History Records' FROM history;
SELECT COUNT(*) as 'Alerts' FROM alerts;
