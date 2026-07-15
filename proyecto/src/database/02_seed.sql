-- Datos de ejemplo. Idempotente: no falla si el correo ya existe.
-- Contraseña de demo (solo local): demo1234
-- Hash generado con: password_hash('demo1234', PASSWORD_DEFAULT)

INSERT IGNORE INTO usuarios (nombre, apellido, email, password_hash, rol, activo, tipo_sangre) VALUES
  ('Mariela', 'Suarez', 'marias60679@ufide.ac.cr', '$2y$12$gKR6uwOWhmvxk8gBxkiyu.6VjxtIoe5oAd37wA0Bqnujcjf7GxO0.', 'donante', 1, 'A+'),
  ('Joyner', 'Gonzalez', 'jarce80641@ufide.ac.cr', '$2y$12$gKR6uwOWhmvxk8gBxkiyu.6VjxtIoe5oAd37wA0Bqnujcjf7GxO0.', 'banco', 1, 'B+'),
  ('Alex', 'Lopez', 'alopez49218@ufide.ac.cr', '$2y$12$gKR6uwOWhmvxk8gBxkiyu.6VjxtIoe5oAd37wA0Bqnujcjf7GxO0.', 'donante', 0, 'O-'),
  ('Oscar', 'Garita', 'ogarita60081@ufide.ac.cr', '$2y$12$gKR6uwOWhmvxk8gBxkiyu.6VjxtIoe5oAd37wA0Bqnujcjf7GxO0.', 'admin', 1, 'O+');
