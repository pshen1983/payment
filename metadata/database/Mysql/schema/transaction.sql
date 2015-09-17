DROP TABLE IF EXISTS control.transaction;
CREATE TABLE control.transaction
(
    id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    account_id INT(10) UNSIGNED,
    provider INT(10) UNSIGNED,
    type VARCHAR(17),
    status VARCHAR(17),
    method VARCHAR(17),
    data VARCHAR(17),
    amount INT(10),
    currency VARCHAR(4),
    time INT(10) UNSIGNED,

    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

CREATE INDEX transaction_account ON control.transaction (account_id);
CREATE INDEX transaction_provider ON control.transaction (provider);
CREATE INDEX transaction_type ON control.transaction (type(16));
CREATE INDEX transaction_status ON control.transaction (status(16));
CREATE INDEX transaction_method ON control.transaction (method(16));
CREATE INDEX transaction_data ON control.transaction (data(16));
CREATE INDEX transaction_currency ON control.transaction (currency(3));
CREATE INDEX transaction_time ON control.transaction (time);