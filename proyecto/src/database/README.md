# Database - SQL Scripts

Place your database initialization scripts here. These will be automatically executed when the database container starts for the first time.

## Structure

```
database/
├── 01_init.sql         # Initial schema
├── 02_seed.sql         # Sample data
└── 03_procedures.sql   # Stored procedures
```

## Naming Convention

Files are executed in alphabetical order. Use numbered prefixes for clear execution order:
- `01_` - Schema creation
- `02_` - Data seeding
- `03_` - Procedures, functions, triggers

## Example Script

```sql
-- 01_init.sql
CREATE TABLE users (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) UNIQUE NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE donations (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  amount DECIMAL(10, 2) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
);
```

## Important Notes

- Scripts are only executed on first database creation
- To re-run scripts, use: `docker-compose down -v && docker-compose up -d`
- Use `if not exists` clauses to make scripts idempotent
