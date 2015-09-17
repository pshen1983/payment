DROP TABLE IF EXISTS confone.plan;
CREATE TABLE confone.plan
(
    id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    code TINYINT UNSIGNED,
    str_code VARCHAR(16),
    amount INT(10) UNSIGNED,
    limit_month INT(10) UNSIGNED,
    active VARCHAR(1),

    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

CREATE INDEX plan_code ON confone.plan (code);

INSERT INTO confone.plan (code, str_code, amount, limit_month, active)
VALUES
(10, 'trial', 0, 0, 'Y'),
(20, 'starter', 900, 100, 'Y'),
(30, 'growth', 5900, 1000, 'Y'),
(40, 'enterprise', 29900, 0, 'Y');