-- Datos de ejemplo. Idempotente: no falla si el correo/código ya existe.
-- Contraseña de demo (solo local): demo1234
-- Hash generado con: password_hash('demo1234', PASSWORD_DEFAULT)
-- Los usuarios demo ya tienen email_confirmed = 1 (pueden iniciar sesión sin el flujo de email).
-- Tras 01_init.sql los IDs demos suelen ser: 1=donante, 2=banco, 3=admin, centro=1.

-- ---------------------------------------------------------------------------
-- Usuarios
-- ---------------------------------------------------------------------------

INSERT IGNORE INTO users (
  first_name, last_name, email, password_hash, role, active, email_confirmed
) VALUES
  ('Donante', 'Donante', 'donante@test.com', '$2y$12$gKR6uwOWhmvxk8gBxkiyu.6VjxtIoe5oAd37wA0Bqnujcjf7GxO0.', 'donor', 1, 1),
  ('Banco', 'Banco', 'banco@test.com', '$2y$12$gKR6uwOWhmvxk8gBxkiyu.6VjxtIoe5oAd37wA0Bqnujcjf7GxO0.', 'bank', 1, 1),
  ('Admin', 'Admin', 'admin@test.com', '$2y$12$gKR6uwOWhmvxk8gBxkiyu.6VjxtIoe5oAd37wA0Bqnujcjf7GxO0.', 'admin', 1, 1);

-- ---------------------------------------------------------------------------
-- Centro + perfiles
-- ---------------------------------------------------------------------------

INSERT IGNORE INTO donation_centers (
  id, code, name, description, address, province, canton, region,
  lat, lng, contact_name, contact_phone, contact_email,
  open_time, close_time, open_days, daily_capacity, process_minutes,
  accept_walk_ins, active
) VALUES (
  1, 'BK-001', 'Hospital Regional - Centro de Sangre',
  'Centro de extracción con estacionamiento y acceso para personas con movilidad reducida.',
  'Paseo Colón, San José', 'San José', 'San José', 'San José',
  9.9333000, -84.0833000, 'María Solano', '2257-0000', 'centro@hospitalregional.cr',
  '08:00:00', '16:00:00', 'Lunes a viernes', 24, 45,
  1, 1
);

INSERT IGNORE INTO donor_profiles (
  user_id, blood_type, birth_date, phone, province, canton, address,
  medical_history, eligible, last_donation_at,
  notify_nearby, notify_appointments, notify_blood_match
)
SELECT
  u.id, 'A+', '1995-06-15', '8888-0000', 'San José', 'San José', 'Barrio Escalante',
  NULL, 1, DATE_SUB(CURDATE(), INTERVAL 90 DAY),
  1, 1, 1
FROM users u
WHERE u.email = 'donante@test.com';

INSERT IGNORE INTO bank_profiles (user_id, center_id)
SELECT u.id, 1
FROM users u
WHERE u.email = 'banco@test.com';

-- ---------------------------------------------------------------------------
-- Institución médica
-- ---------------------------------------------------------------------------

INSERT IGNORE INTO medical_institutions (
  id, name, contact_name, contact_phone, contact_email, address, active
) VALUES (
  1, 'Hospital Nacional', 'Dr. Carlos Vargas', '2222-1000',
  'urgencias@hospitalnacional.cr', 'San José centro', 1
);

-- ---------------------------------------------------------------------------
-- Citas y donaciones
-- ---------------------------------------------------------------------------

INSERT IGNORE INTO appointments (
  id, code, donor_id, center_id, scheduled_at, status, notes
)
SELECT
  1, 'CT-2041', u.id, 1,
  DATE_ADD(NOW(), INTERVAL 3 DAY), 'confirmed',
  'Cita demo confirmada'
FROM users u
WHERE u.email = 'donante@test.com';

INSERT IGNORE INTO appointments (
  id, code, donor_id, center_id, scheduled_at, status, notes
)
SELECT
  2, 'CT-1988', u.id, 1,
  DATE_SUB(NOW(), INTERVAL 90 DAY), 'completed',
  'Cita demo completada'
FROM users u
WHERE u.email = 'donante@test.com';

INSERT IGNORE INTO appointments (
  id, code, donor_id, center_id, scheduled_at, status, notes
)
SELECT
  3, 'CT-2105', u.id, 1,
  DATE_ADD(NOW(), INTERVAL 10 DAY), 'pending',
  'Cita demo pendiente'
FROM users u
WHERE u.email = 'donante@test.com';

INSERT IGNORE INTO donations (
  id, donor_id, center_id, appointment_id, blood_type, units, donated_at, certificate_code
)
SELECT
  1, u.id, 1, 2, 'A+', 1,
  DATE_SUB(NOW(), INTERVAL 90 DAY), 'CERT-DEMO-001'
FROM users u
WHERE u.email = 'donante@test.com';

INSERT IGNORE INTO blood_units (
  id, code, donation_id, center_id, blood_type, status, collected_at, expires_at
) VALUES (
  1, 'BU-DEMO-001', 1, 1, 'A+', 'available',
  DATE_SUB(NOW(), INTERVAL 90 DAY), DATE_ADD(CURDATE(), INTERVAL 35 DAY)
);

-- ---------------------------------------------------------------------------
-- Inventario (mixto; O- en crítico <50)
-- ---------------------------------------------------------------------------

INSERT IGNORE INTO inventory (center_id, blood_type, units) VALUES
  (1, 'O+', 120),
  (1, 'O-', 28),
  (1, 'A+', 95),
  (1, 'A-', 70),
  (1, 'B+', 110),
  (1, 'B-', 55),
  (1, 'AB+', 80),
  (1, 'AB-', 40);

INSERT IGNORE INTO inventory_movements (
  center_id, type, blood_type, quantity, donation_id, blood_unit_id, user_id, detail
)
SELECT
  1, 'receipt', 'A+', 1, 1, 1, u.id, 'Recepción demo por donación completada'
FROM users u
WHERE u.email = 'banco@test.com';

-- ---------------------------------------------------------------------------
-- Solicitudes y alertas
-- ---------------------------------------------------------------------------

INSERT IGNORE INTO requests (
  id, code, institution_id, center_id, blood_type, quantity,
  priority, status, notes, requested_at
) VALUES (
  1, 'RE-9082', 1, 1, 'O-', 4,
  'critical', 'pending', 'Solicitud demo de urgencia', NOW()
);

INSERT IGNORE INTO alerts (
  id, center_id, request_id, blood_type, priority, status, message
) VALUES (
  1, 1, 1, 'O-', 'critical', 'active',
  'Stock crítico de O- en Hospital Regional'
);

-- ---------------------------------------------------------------------------
-- Políticas globales (center_id NULL)
-- ---------------------------------------------------------------------------

INSERT IGNORE INTO donation_policies (center_id, key_name, value_text, description, active) VALUES
  (NULL, 'inventory_healthy_min', '101', 'Umbral mínimo de stock saludable (unidades)', 1),
  (NULL, 'inventory_moderate_min', '50', 'Umbral mínimo de stock moderado (unidades)', 1),
  (NULL, 'inventory_critical_max', '49', 'Umbral máximo de stock crítico (unidades)', 1),
  (NULL, 'donor_interval_days', '56', 'Días mínimos entre donaciones de sangre completa', 1);

-- ---------------------------------------------------------------------------
-- Logros
-- ---------------------------------------------------------------------------

INSERT IGNORE INTO achievements (code, name, description, criteria_type, criteria_value, active) VALUES
  ('first_donation', 'Fundador', 'Completa tu primera donación', 'donation_count', 1, 1),
  ('hero_5', 'Héroe', 'Completa 5 donaciones', 'donation_count', 5, 1),
  ('legend_10', 'Leyenda', 'Completa 10 donaciones', 'donation_count', 10, 1);

INSERT IGNORE INTO donor_achievements (user_id, achievement_id, progress, unlocked_at)
SELECT u.id, a.id, 1, DATE_SUB(NOW(), INTERVAL 90 DAY)
FROM users u
JOIN achievements a ON a.code = 'first_donation'
WHERE u.email = 'donante@test.com';

-- ---------------------------------------------------------------------------
-- Notificación demo
-- ---------------------------------------------------------------------------

INSERT IGNORE INTO notifications (user_id, type, title, body, related_type, related_id)
SELECT
  u.id, 'appointment_reminder', 'Próxima cita de donación',
  'Tu cita confirmada es en 3 días en Hospital Regional.',
  'appointment', 1
FROM users u
WHERE u.email = 'donante@test.com';
