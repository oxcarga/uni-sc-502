-- Datos de ejemplo. Idempotente: no falla si el correo ya existe.
INSERT IGNORE INTO users (name, email, blood_type) VALUES
  ('Mariela Suarez', 'marias60679@ufide.ac.cr', 'A+'),
  ('Joyner Gonzalez', 'jarce80641@ufide.ac.cr', 'B+'),
  ('Alex Lopez', 'alopez49218@ufide.ac.cr', 'O-'),
  ('Oscar Garita', 'ogarita60081@ufide.ac.cr', 'O+');
