
DROP TABLE IF EXISTS users;
CREATE TABLE users (
  id              CHAR(16) NOT NULL,
  username        VARCHAR(100) NOT NULL,
  email           VARCHAR(150) DEFAULT NULL,
  password        VARCHAR(255) NOT NULL,
  thumbnail       VARCHAR(255) NULL,
  info            JSON NULL,
  level           VARCHAR(10) NOT NULL,
  entity_id       CHAR(16) NULL,
  authorized      TINYINT(1) DEFAULT '0',
  block_expires   DATETIME DEFAULT NULL,
  login_attempts  INT(10) UNSIGNED DEFAULT '0',
  created_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY email (email(100)),
  KEY level (level)
);

INSERT INTO users (id, email, password, level, authorized, block_expires, login_attempts)
VALUES
	('ci27flk5w0002adx5dhyvzye2','admin@admin.com','$2y$10$QS.T0VwA5/a78y2s6m2Yiulu2bhGPzH/W1ay7QslX830PO4RTCVj.','Admin',1,NULL,0);

DROP TABLE IF EXISTS users_access;
CREATE TABLE users_access (
  id        INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  email     VARCHAR(150) DEFAULT NULL,
  ip        VARCHAR(250) DEFAULT NULL,
  browser   TEXT,
  date      DATETIME DEFAULT NULL,
  PRIMARY KEY (id)
);

DROP TABLE IF EXISTS entity_categories;
CREATE TABLE entity_categories (
  id          INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  slug        VARCHAR(255) NOT NULL,
  title       VARCHAR(255) NOT NULL,
  thumbnail   VARCHAR(255) NULL,
  description TEXT,
  created_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY (slug),
);

DROP TABLE IF EXISTS entities;
CREATE TABLE entities (
  id            CHAR(16) NOT NULL,
  keywords      TEXT NULL,
  slug          VARCHAR(160) NOT NULL,
  name          VARCHAR(160) NOT NULL,
  about         TEXT NULL,
  thumbnail     TEXT DEFAULT NULL,
  info          JSON NULL,
  city_id       INT(10) NULL,
  category_id   INT(10) NULL,
  created_at    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  FOREIGN KEY (category_id) REFERENCES entity_categories(id) ON DELETE CASCADE
);

DROP TABLE IF EXISTS entity_person;
CREATE TABLE entity_person (
  id         INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  entity_id  CHAR(16) NOT NULL,
  user_id    CHAR(16) NOT NULL,
  PRIMARY KEY (id),
  FOREIGN KEY (entity_id) REFERENCES entities(id) ON DELETE CASCADE
);

DROP TABLE IF EXISTS entity_news;
CREATE TABLE entity_news (
  id            INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  slug          VARCHAR(255) NOT NULL,
  entity_id     CHAR(16) NOT NULL,
  thumbnail     VARCHAR(160) DEFAULT NULL,
  title         VARCHAR(255) NOT NULL,
  content       LONGTEXT NOT NULL,
  created_at    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  FOREIGN KEY (entity_id) REFERENCES entities(id) ON DELETE CASCADE
);

DROP TABLE IF EXISTS entity_gallery;
CREATE TABLE entity_gallery (
  id            INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  entity_id     CHAR(16) NOT NULL,
  title         VARCHAR(255) DEFAULT NULL,
  url           VARCHAR(255) NOT NULL,
  created_at    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  FOREIGN KEY (entity_id) REFERENCES entities(id) ON DELETE CASCADE
);

DROP TABLE IF EXISTS entity_balance;
CREATE TABLE entity_balance (
  id            INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  entity_id     CHAR(16) NOT NULL,
  title         VARCHAR(255) NOT NULL,
  content       LONGTEXT NOT NULL,
  created_at    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  FOREIGN KEY (entity_id) REFERENCES entities(id) ON DELETE CASCADE
);

DROP TABLE IF EXISTS entity_reports;
CREATE TABLE entity_reports (
  id            INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  entity_id     CHAR(16) NOT NULL,
  title         VARCHAR(255) NOT NULL,
  content       LONGTEXT NOT NULL,
  created_at    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  FOREIGN KEY (entity_id) REFERENCES entities(id) ON DELETE CASCADE
);

DROP TABLE IF EXISTS entity_messages;
CREATE TABLE entity_messages (
  id            INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  entity_id     CHAR(16) NOT NULL,
  user_id       CHAR(16) NOT NULL,
  title         VARCHAR(255) NOT NULL,
  content       LONGTEXT NOT NULL,
  created_at    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (entity_id) REFERENCES entities(id) ON DELETE CASCADE
);

DROP TABLE IF EXISTS donations;
CREATE TABLE donations (
  id          INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id     CHAR(16) NOT NULL,
  entity_id   CHAR(16) NOT NULL,
  amount      INT(11) NOT NULL,
  created_at        DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (entity_id) REFERENCES entities(id) ON DELETE CASCADE
);

DROP TABLE IF EXISTS user_follow;
CREATE TABLE user_follow (
  id          INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id     CHAR(16) NOT NULL,
  entity_id   CHAR(16) NOT NULL,
  PRIMARY KEY (id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (entity_id) REFERENCES entities(id) ON DELETE CASCADE
);