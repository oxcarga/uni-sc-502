# Quick Start - Docker Setup

## One-Command Setup

To start your entire development environment:

```bash
docker-compose up -d
```

That's it! Your project is now running:
- 🌐 Frontend: http://localhost:8000
- 🗄️ phpMyAdmin: http://localhost:8080
- 🔌 MySQL: localhost:3306

## Stop Everything

```bash
docker-compose down
```

## Before You Start

1. (Optional) Copy environment file:
   ```bash
   cp .env.example .env
   ```

## Project Structure

```
./
├── docker-compose.yml      # Docker configuration
├── Dockerfile              # PHP container build
├── docker-entrypoint.sh    # Container startup script
├── .env.example            # Environment variables template
├── DOCKER.md              # Full documentation
├── frontend/              # HTML, CSS, JavaScript
├── backend/               # PHP files
└── database/              # SQL initialization scripts
```

## Common Commands

| Command | Purpose |
|---------|---------|
| `docker-compose up -d` | Start all services |
| `docker-compose down` | Stop all services |
| `docker-compose logs -f` | View live logs |
| `docker-compose exec web bash` | Access PHP container |
| `docker-compose exec db mysql -u pulso_user -p pulso_solidario` | Access MySQL |
| `docker-compose ps` | Show running containers |

## Next Steps

1. Add HTML files to `frontend/`
2. Add PHP files to `backend/`
3. Create SQL scripts in `database/`
4. Read `DOCKER.md` for detailed documentation

For full documentation, see **DOCKER.md**
