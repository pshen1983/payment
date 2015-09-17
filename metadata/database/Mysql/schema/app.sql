DROP TABLE IF EXISTS confone.app;
CREATE TABLE confone.app
(
    id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    account_id INT(10) UNSIGNED,
    pk VARCHAR(61),
    sk VARCHAR(61),
    name VARCHAR(128),
    mobile_enable VARCHAR(2),
    create_time DATETIME,

    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

CREATE INDEX app_account_id ON confone.app (account_id);
CREATE INDEX app_pk ON confone.app (pk(60));
CREATE INDEX app_sk ON confone.app (sk(60));
CREATE INDEX app_mobile_enable ON confone.app (mobile_enable(1));

INSERT INTO confone.app (account_id, pk, sk, name, mobile_enable, create_time)
VALUES
(1, 'pk_602462ad54628d27b1e18eaf3dfe9224', 'sk_2804018465ff86f97d67276731be678e', 'Confone', 'Y', NOW());