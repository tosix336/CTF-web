CREATE TABLE users (
  id INT PRIMARY KEY AUTO_INCREMENT,
  username VARCHAR(64) NOT NULL UNIQUE,
  password VARCHAR(128) NOT NULL,
  role VARCHAR(32) NOT NULL
);
CREATE TABLE secrets (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(64) NOT NULL,
  value VARCHAR(255) NOT NULL
);
INSERT INTO users(username,password,role) VALUES
  ('admin','admin','admin'),
  ('operator','winter2025','operator');
INSERT INTO secrets(name,value) VALUES
  ('diag_key','diag-console-4b71'),
  ('upload_key','vault-upload-9f2c'),
  ('build_note','the upload service accepts legacy extensions');
