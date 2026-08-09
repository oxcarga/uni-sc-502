-- Pulso Solidario — esquema MySQL (desarrollo local con Docker)
-- Se ejecuta automáticamente en el primer arranque del contenedor db
-- (volumen mysql_data vacío). También se puede aplicar con ./provision.sh
-- Convención: nombres de tablas y columnas en inglés.
--
-- Orden: DROP hijos → padres; CREATE padres → hijos.
-- Tipos de sangre: O+, O-, A+, A-, B+, B-, AB+, AB- (CHECK, no tabla lookup).

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS donor_achievements;
DROP TABLE IF EXISTS achievements;
DROP TABLE IF EXISTS audit_log;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS donation_policies;
DROP TABLE IF EXISTS alerts;
DROP TABLE IF EXISTS inventory_movements;
DROP TABLE IF EXISTS requests;
DROP TABLE IF EXISTS inventory;
DROP TABLE IF EXISTS blood_units;
DROP TABLE IF EXISTS donations;
DROP TABLE IF EXISTS appointments;
DROP TABLE IF EXISTS medical_institutions;
DROP TABLE IF EXISTS bank_profiles;
DROP TABLE IF EXISTS donor_profiles;
DROP TABLE IF EXISTS donation_centers;
DROP TABLE IF EXISTS email_verification_tokens;
DROP TABLE IF EXISTS tokens_verificacion_correo; -- legado (español)
DROP TABLE IF EXISTS usuarios; -- legado (español)
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------------
-- Cuentas
-- ---------------------------------------------------------------------------

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

-- ---------------------------------------------------------------------------
-- Centros y perfiles
-- ---------------------------------------------------------------------------

CREATE TABLE donation_centers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(20) NULL,
  name VARCHAR(150) NOT NULL,
  description TEXT NULL,
  address VARCHAR(255) NOT NULL,
  province VARCHAR(80) NULL,
  canton VARCHAR(80) NULL,
  region VARCHAR(80) NULL,
  lat DECIMAL(10, 7) NULL,
  lng DECIMAL(10, 7) NULL,
  contact_name VARCHAR(120) NULL,
  contact_phone VARCHAR(40) NULL,
  contact_email VARCHAR(120) NULL,
  open_time TIME NULL,
  close_time TIME NULL,
  open_days VARCHAR(120) NULL,
  daily_capacity INT NULL,
  process_minutes INT NULL,
  accept_walk_ins TINYINT(1) NOT NULL DEFAULT 1,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_donation_centers_code (code),
  INDEX idx_donation_centers_active (active),
  INDEX idx_donation_centers_region (region)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE donor_profiles (
  user_id INT NOT NULL PRIMARY KEY,
  blood_type VARCHAR(3) NULL,
  birth_date DATE NULL,
  phone VARCHAR(40) NULL,
  province VARCHAR(80) NULL,
  canton VARCHAR(80) NULL,
  address VARCHAR(255) NULL,
  medical_history TEXT NULL,
  eligible TINYINT(1) NOT NULL DEFAULT 1,
  last_donation_at DATE NULL,
  notify_nearby TINYINT(1) NOT NULL DEFAULT 1,
  notify_appointments TINYINT(1) NOT NULL DEFAULT 1,
  notify_blood_match TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_donor_profiles_blood_type (blood_type),
  INDEX idx_donor_profiles_eligible (eligible),
  CONSTRAINT chk_donor_profiles_blood_type
    CHECK (blood_type IS NULL OR blood_type IN ('O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-')),
  CONSTRAINT fk_donor_profiles_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE bank_profiles (
  user_id INT NOT NULL PRIMARY KEY,
  center_id INT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_bank_profiles_center (center_id),
  CONSTRAINT fk_bank_profiles_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_bank_profiles_center
    FOREIGN KEY (center_id) REFERENCES donation_centers(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE medical_institutions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  contact_name VARCHAR(120) NULL,
  contact_phone VARCHAR(40) NULL,
  contact_email VARCHAR(120) NULL,
  address VARCHAR(255) NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Citas y donaciones
-- ---------------------------------------------------------------------------

CREATE TABLE appointments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(20) NULL,
  donor_id INT NOT NULL,
  center_id INT NOT NULL,
  scheduled_at DATETIME NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'pending',
  notes TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_appointments_code (code),
  INDEX idx_appointments_donor (donor_id),
  INDEX idx_appointments_center (center_id),
  INDEX idx_appointments_scheduled_at (scheduled_at),
  INDEX idx_appointments_status (status),
  CONSTRAINT chk_appointments_status
    CHECK (status IN ('pending', 'confirmed', 'completed', 'cancelled', 'no_show')),
  CONSTRAINT fk_appointments_donor
    FOREIGN KEY (donor_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_appointments_center
    FOREIGN KEY (center_id) REFERENCES donation_centers(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE donations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  donor_id INT NOT NULL,
  center_id INT NOT NULL,
  appointment_id INT NULL,
  blood_type VARCHAR(3) NOT NULL,
  units INT NOT NULL DEFAULT 1,
  donated_at DATETIME NOT NULL,
  certificate_code VARCHAR(40) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_donations_appointment (appointment_id),
  UNIQUE KEY uq_donations_certificate (certificate_code),
  INDEX idx_donations_donor (donor_id),
  INDEX idx_donations_center (center_id),
  INDEX idx_donations_donated_at (donated_at),
  CONSTRAINT chk_donations_blood_type
    CHECK (blood_type IN ('O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-')),
  CONSTRAINT chk_donations_units CHECK (units > 0),
  CONSTRAINT fk_donations_donor
    FOREIGN KEY (donor_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_donations_center
    FOREIGN KEY (center_id) REFERENCES donation_centers(id) ON DELETE RESTRICT,
  CONSTRAINT fk_donations_appointment
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE blood_units (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(40) NOT NULL,
  donation_id INT NOT NULL,
  center_id INT NOT NULL,
  blood_type VARCHAR(3) NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'available',
  collected_at DATETIME NOT NULL,
  expires_at DATE NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_blood_units_code (code),
  INDEX idx_blood_units_donation (donation_id),
  INDEX idx_blood_units_center (center_id),
  INDEX idx_blood_units_status (status),
  CONSTRAINT chk_blood_units_blood_type
    CHECK (blood_type IN ('O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-')),
  CONSTRAINT chk_blood_units_status
    CHECK (status IN ('available', 'assigned', 'discarded', 'expired')),
  CONSTRAINT fk_blood_units_donation
    FOREIGN KEY (donation_id) REFERENCES donations(id) ON DELETE CASCADE,
  CONSTRAINT fk_blood_units_center
    FOREIGN KEY (center_id) REFERENCES donation_centers(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Inventario
-- ---------------------------------------------------------------------------

CREATE TABLE inventory (
  id INT AUTO_INCREMENT PRIMARY KEY,
  center_id INT NOT NULL,
  blood_type VARCHAR(3) NOT NULL,
  units INT NOT NULL DEFAULT 0,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_inventory_center_blood (center_id, blood_type),
  CONSTRAINT chk_inventory_blood_type
    CHECK (blood_type IN ('O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-')),
  CONSTRAINT chk_inventory_units CHECK (units >= 0),
  CONSTRAINT fk_inventory_center
    FOREIGN KEY (center_id) REFERENCES donation_centers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(20) NULL,
  institution_id INT NOT NULL,
  center_id INT NULL,
  blood_type VARCHAR(3) NOT NULL,
  quantity INT NOT NULL,
  priority VARCHAR(20) NOT NULL DEFAULT 'normal',
  status VARCHAR(20) NOT NULL DEFAULT 'pending',
  notes TEXT NULL,
  requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  completed_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_requests_code (code),
  INDEX idx_requests_institution (institution_id),
  INDEX idx_requests_center (center_id),
  INDEX idx_requests_status (status),
  CONSTRAINT chk_requests_blood_type
    CHECK (blood_type IN ('O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-')),
  CONSTRAINT chk_requests_quantity CHECK (quantity > 0),
  CONSTRAINT chk_requests_priority
    CHECK (priority IN ('low', 'normal', 'critical')),
  CONSTRAINT chk_requests_status
    CHECK (status IN ('pending', 'assigned', 'in_transit', 'completed', 'cancelled')),
  CONSTRAINT fk_requests_institution
    FOREIGN KEY (institution_id) REFERENCES medical_institutions(id) ON DELETE RESTRICT,
  CONSTRAINT fk_requests_center
    FOREIGN KEY (center_id) REFERENCES donation_centers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE inventory_movements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  center_id INT NOT NULL,
  type VARCHAR(20) NOT NULL,
  blood_type VARCHAR(3) NOT NULL,
  quantity INT NOT NULL,
  donation_id INT NULL,
  request_id INT NULL,
  blood_unit_id INT NULL,
  user_id INT NULL,
  detail VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_inventory_movements_center (center_id),
  INDEX idx_inventory_movements_type (type),
  INDEX idx_inventory_movements_created (created_at),
  CONSTRAINT chk_inventory_movements_type
    CHECK (type IN ('receipt', 'assignment', 'adjustment', 'discard')),
  CONSTRAINT chk_inventory_movements_blood_type
    CHECK (blood_type IN ('O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-')),
  CONSTRAINT chk_inventory_movements_quantity CHECK (quantity > 0),
  CONSTRAINT fk_inventory_movements_center
    FOREIGN KEY (center_id) REFERENCES donation_centers(id) ON DELETE CASCADE,
  CONSTRAINT fk_inventory_movements_donation
    FOREIGN KEY (donation_id) REFERENCES donations(id) ON DELETE SET NULL,
  CONSTRAINT fk_inventory_movements_request
    FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE SET NULL,
  CONSTRAINT fk_inventory_movements_blood_unit
    FOREIGN KEY (blood_unit_id) REFERENCES blood_units(id) ON DELETE SET NULL,
  CONSTRAINT fk_inventory_movements_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Alertas, notificaciones, políticas y auditoría
-- ---------------------------------------------------------------------------

CREATE TABLE alerts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  center_id INT NOT NULL,
  request_id INT NULL,
  blood_type VARCHAR(3) NOT NULL,
  priority VARCHAR(20) NOT NULL DEFAULT 'critical',
  status VARCHAR(20) NOT NULL DEFAULT 'active',
  message VARCHAR(255) NULL,
  resolved_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_alerts_center (center_id),
  INDEX idx_alerts_status (status),
  CONSTRAINT chk_alerts_blood_type
    CHECK (blood_type IN ('O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-')),
  CONSTRAINT chk_alerts_priority
    CHECK (priority IN ('low', 'normal', 'critical')),
  CONSTRAINT chk_alerts_status
    CHECK (status IN ('active', 'resolved')),
  CONSTRAINT fk_alerts_center
    FOREIGN KEY (center_id) REFERENCES donation_centers(id) ON DELETE CASCADE,
  CONSTRAINT fk_alerts_request
    FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE donation_policies (
  id INT AUTO_INCREMENT PRIMARY KEY,
  center_id INT NULL,
  key_name VARCHAR(80) NOT NULL,
  value_text VARCHAR(255) NOT NULL,
  description VARCHAR(255) NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_donation_policies_center_key (center_id, key_name),
  CONSTRAINT fk_donation_policies_center
    FOREIGN KEY (center_id) REFERENCES donation_centers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  type VARCHAR(40) NOT NULL,
  title VARCHAR(150) NOT NULL,
  body TEXT NULL,
  related_type VARCHAR(40) NULL,
  related_id INT NULL,
  read_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_notifications_user (user_id),
  INDEX idx_notifications_read (read_at),
  CONSTRAINT fk_notifications_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  action VARCHAR(80) NOT NULL,
  entity_type VARCHAR(40) NULL,
  entity_id INT NULL,
  detail TEXT NULL,
  ip_address VARCHAR(45) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_audit_log_user (user_id),
  INDEX idx_audit_log_created (created_at),
  CONSTRAINT fk_audit_log_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Logros (gamificación)
-- ---------------------------------------------------------------------------

CREATE TABLE achievements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(40) NOT NULL,
  name VARCHAR(100) NOT NULL,
  description VARCHAR(255) NULL,
  criteria_type VARCHAR(40) NOT NULL,
  criteria_value INT NOT NULL DEFAULT 1,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_achievements_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE donor_achievements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  achievement_id INT NOT NULL,
  progress INT NOT NULL DEFAULT 0,
  unlocked_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_donor_achievements_user_achievement (user_id, achievement_id),
  INDEX idx_donor_achievements_user (user_id),
  CONSTRAINT fk_donor_achievements_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_donor_achievements_achievement
    FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
