-- Datos de ejemplo. Idempotente: no falla si el correo ya existe.
-- Contraseña de demo (solo local): demo1234
-- Hash generado con: password_hash('demo1234', PASSWORD_DEFAULT)
-- Los usuarios demo ya tienen email_confirmed = 1 (pueden iniciar sesión sin el flujo de email).

INSERT IGNORE INTO users (
  first_name, last_name, email, password_hash, role, active, email_confirmed, blood_type
) VALUES
  ('Mariela', 'Suarez', 'marias60679@ufide.ac.cr', '$2y$12$gKR6uwOWhmvxk8gBxkiyu.6VjxtIoe5oAd37wA0Bqnujcjf7GxO0.', 'donor', 1, 1, 'A+'),
  ('Joyner', 'Gonzalez', 'jarce80641@ufide.ac.cr', '$2y$12$gKR6uwOWhmvxk8gBxkiyu.6VjxtIoe5oAd37wA0Bqnujcjf7GxO0.', 'bank', 1, 1, 'B+'),
  ('Alex', 'Lopez', 'alopez49218@ufide.ac.cr', '$2y$12$gKR6uwOWhmvxk8gBxkiyu.6VjxtIoe5oAd37wA0Bqnujcjf7GxO0.', 'donor', 0, 1, 'O-'),
  ('Oscar', 'Garita', 'ogarita60081@ufide.ac.cr', '$2y$12$gKR6uwOWhmvxk8gBxkiyu.6VjxtIoe5oAd37wA0Bqnujcjf7GxO0.', 'admin', 1, 1, 'O+');
