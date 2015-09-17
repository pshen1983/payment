DROP TABLE IF EXISTS confone.account;
CREATE TABLE confone.account
(
    id INT(10) UNSIGNED AUTO_INCREMENT,
    email VARCHAR(128),
	password VARCHAR(41),
	fname VARCHAR(61),
	lname VARCHAR(61),
	phone VARCHAR(16),
	company VARCHAR(40),
	status VARCHAR(2),
    plan TINYINT UNSIGNED,

    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

CREATE INDEX customer_email ON confone.account (email(127));
CREATE INDEX customer_plan ON confone.account (plan);
CREATE INDEX customer_status ON confone.account (status(1));

INSERT INTO confone.account (email) VALUES ('admin@confone.com');