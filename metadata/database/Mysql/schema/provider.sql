DROP TABLE IF EXISTS control.account_provider;
CREATE TABLE control.account_provider
(
    id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    account_id INT(10) UNSIGNED,
    type TINYINT UNSIGNED,
    external_id VARCHAR(41),
    access_token TEXT,
    active VARCHAR(2),
    create_time DATETIME,

    UNIQUE(account_id, type, external_id),
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

CREATE INDEX account_provider_account ON control.account_provider (account_id);
CREATE INDEX account_provider_type ON control.account_provider (type);
CREATE INDEX account_provider_external ON control.account_provider (external_id(40));
CREATE INDEX account_provider_active ON control.account_provider (active(1));