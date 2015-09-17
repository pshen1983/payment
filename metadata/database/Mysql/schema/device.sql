DROP TABLE IF EXISTS confone.device;
CREATE TABLE confone.device
(
    id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    account_id INT(10) UNSIGNED,
    name VARCHAR(41),
    platform TINYINT UNSIGNED,
    active VARCHAR(2),
    push_token TEXT,
    last_use_time DATETIME,

    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

CREATE INDEX device_account ON confone.device (account_id);
CREATE INDEX device_name ON confone.device (name(40));
CREATE INDEX device_platform ON confone.device (platform);
CREATE INDEX device_active ON confone.device (active(1));
CREATE INDEX device_last_use ON confone.device (last_use_time);