-- Pulso Solidario — esquema MySQL (desarrollo local con Docker)
-- Se ejecuta automáticamente en el primer arranque del contenedor db
-- (volumen mysql_data vacío). También se puede aplicar con ./provision.sh
-- Convención: nombres de tablas y columnas en español.

DROP TABLE IF EXISTS users; -- legado (inglés); se elimina si aún existe
DROP TABLE IF EXISTS usuarios;
CREATE TABLE usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  tipo_sangre VARCHAR(3) NULL,
  creado_el TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
  actualizado_el TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
