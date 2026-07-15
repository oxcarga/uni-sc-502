-- Pulso Solidario — esquema MySQL (desarrollo local con Docker)
-- Se ejecuta automáticamente en el primer arranque del contenedor db
-- (volumen mysql_data vacío). También se puede aplicar con ./provision.sh
-- Convención: nombres de tablas y columnas en español.

DROP TABLE IF EXISTS tokens_verificacion_correo;
DROP TABLE IF EXISTS users; -- legado (inglés); se elimina si aún existe
DROP TABLE IF EXISTS usuarios;

CREATE TABLE usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  apellido VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  rol VARCHAR(20) NOT NULL DEFAULT 'donante',
  activo TINYINT(1) NOT NULL DEFAULT 1,
  correo_confirmado TINYINT(1) NOT NULL DEFAULT 0,
  correo_confirmado_el TIMESTAMP NULL DEFAULT NULL,
  tipo_sangre VARCHAR(3) NULL,
  creado_el TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_el TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_usuarios_email (email),
  INDEX idx_usuarios_rol (rol),
  CONSTRAINT chk_usuarios_rol CHECK (rol IN ('donante', 'banco', 'admin'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tokens_verificacion_correo (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  token_hash VARCHAR(255) NOT NULL,
  expira_el TIMESTAMP NOT NULL,
  usado_el TIMESTAMP NULL DEFAULT NULL,
  creado_el TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_tokens_token_hash (token_hash),
  INDEX idx_tokens_usuario (usuario_id),
  CONSTRAINT fk_tokens_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
