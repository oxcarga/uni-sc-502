-- Pulso Solidario — esquema MySQL (desarrollo local con Docker)
-- Se ejecuta automáticamente en el primer arranque del contenedor db
-- (volumen mysql_data vacío). También se puede aplicar con ./provision.sh
-- Convención: nombres de tablas y columnas en inglés.

DROP TABLE IF EXISTS email_verification_tokens;
DROP TABLE IF EXISTS tokens_verificacion_correo; -- legado (español)
DROP TABLE IF EXISTS usuarios; -- legado (español)
DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(20) NOT NULL DEFAULT 'donor',
  active TINYINT(1) NOT NULL DEFAULT 1,
  email_confirmed TINYINT(1) NOT NULL DEFAULT 0,
  email_confirmed_at TIMESTAMP NULL DEFAULT NULL,
  blood_type VARCHAR(3) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_users_email (email),
  INDEX idx_users_role (role),
  CONSTRAINT chk_users_role CHECK (role IN ('donor', 'bank', 'admin'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE email_verification_tokens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token_hash VARCHAR(255) NOT NULL,
  expires_at TIMESTAMP NOT NULL,
  used_at TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_email_tokens_token_hash (token_hash),
  INDEX idx_email_tokens_user (user_id),
  CONSTRAINT fk_email_tokens_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
