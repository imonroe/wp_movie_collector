# Docker Setup for WP Movie Collector

This setup provides a containerized WordPress development environment with the WP Movie Collector plugin installed.

## Requirements

- Docker
- Docker Compose

## Quick Start

1. Clone this repository
2. Navigate to the repository directory
3. Run the following command:

```bash
docker compose up -d
```

4. Access WordPress at http://localhost:8080
5. During WordPress setup:
   - Set up your admin account
   - Activate the WP Movie Collector plugin from the Plugins menu

## Services

- **WordPress**: http://localhost:8080
- **PHPMyAdmin**: http://localhost:8081 (login with username: wordpress, password: wordpress)

## Container Details

- **WordPress Container**: Contains WordPress with the plugin mounted from your local directory
- **MySQL Container**: Database server (MySQL 5.7)
- **PHPMyAdmin Container**: Database management interface

## Development Workflow

The plugin code is mounted as a volume, so any changes you make to the plugin files in your local directory will be immediately reflected in the running WordPress container.

### Running Build Commands

To run build commands (as specified in CLAUDE.md):

```bash
# Enter the WordPress container
docker exec -it wp_movie_collector_wordpress bash

# Navigate to the plugin directory
cd wp-content/plugins/wp-movie-collector

# Run commands (examples)
composer install
npm install
npm run build
```

## Database

The database data is persisted in a Docker volume. If you want to start with a clean database, you can remove the volumes:

```bash
docker compose down -v
```

## Customization

You can modify the `docker-compose.yml` file to change ports, add services, or adjust configurations as needed.