# Docker Compose CLI Command Cheatsheet

Quick reference guide for Docker Compose commands used in the Kinky-Thots project.

---

## Understanding the Basics (Important!)

### Where Do Containers Read Files From?

**Yes, the Docker web container reads directly from `/var/www/kinky-thots/`!**

```yaml
# In docker-compose.yml
volumes:
  - ./:/var/www/html:ro  # Host → Container mapping
```

| Host Path | Container Path | Mode | What It Means |
|-----------|---------------|------|---------------|
| `/var/www/kinky-thots/` | `/var/www/html/` | `:ro` (read-only) | Container reads website files from host |
| `/var/www/kinky-thots/uploads` | `/var/www/html/uploads` | `:rw` (read-write) | Container can read/write uploads |
| `/var/www/kinky-thots/.backend` | N/A | Not mapped | Backend code is copied into container at build time |

**Key Points:**
- ✅ Changes to HTML/PHP/CSS/JS files are **immediately reflected** - no restart needed
- ✅ You edit files on the host, container serves them instantly
- ❌ Container cannot modify main files (`:ro` = read-only protection)
- ✅ Container can write to `uploads/`, `hls/`, `logs/` directories

### Check What's Mounted

```bash
# View volume mappings for a service
docker-compose config | grep -A 10 "volumes:"

# Inspect actual mounts in running container
docker inspect kinky-web | grep -A 20 "Mounts"

# Check if file exists in container
docker-compose exec web ls -la /var/www/html/index.html

# Compare file on host vs container
ls -la /var/www/kinky-thots/index.html
docker-compose exec web ls -la /var/www/html/index.html
```

### Port Mappings Explained

```yaml
ports:
  - "80:80"      # Host:Container
  - "3002:3001"  # External port 3002 → Container port 3001
```

**What this means:**
- `localhost:80` on your machine → Port 80 inside `kinky-web` container
- `localhost:3002` on your machine → Port 3001 inside `kinky-backend` container
- Other containers can access backend on `backend:3001` (internal network)

```bash
# Check port mappings
docker-compose ps
docker port kinky-backend

# Test from host
curl http://localhost:80
curl http://localhost:3002/health

# Test from inside another container
docker-compose exec web curl http://backend:3001/health
```

### When Do I Need to Rebuild vs Restart?
docker-compose ps
| Change Type | Action Required | Command |
|-------------|----------------|---------|
| HTML/PHP/CSS/JS files | **Nothing!** | Files are mounted, changes are instant |
| Environment variables in `.env` | **Restart** | `docker-compose restart SERVICE` |
| `docker-compose.yml` changes | **Recreate** | `docker-compose up -d` |
| Dockerfile changes | **Rebuild** | `docker-compose build SERVICE` |
| Added npm packages | **Rebuild** | `docker-compose build backend` |
| Changed volume mappings | **Recreate** | `docker-compose up -d --force-recreate` |

```bash
# Just changed index.html? Nothing needed!
# It's already live.

# Changed .env file? Restart the service
docker-compose restart backend

# Changed Dockerfile? Rebuild
docker-compose build backend
docker-compose up -d backend

# Changed docker-compose.yml? Recreate
docker-compose up -d
```

### Container Networking Explained

Containers in the same network can talk to each other by **service name**:

```yaml
services:
  backend:  # Service name = hostname
    networks:
      - kinky-network

  db:      # Service name = hostname
    networks:
      - kinky-network
```

**From backend container, you can access:**
- Database: `db:3306` or `db` (not localhost!)
- Web server: `web:80`
- RTMP: `rtmp:1935`

**From your host machine:**
- Backend: `localhost:3002`
- Web: `localhost:80`
- Database: NOT accessible (not exposed)

```bash
# Test connectivity between containers
docker-compose exec backend ping db
docker-compose exec backend nc -zv db 3306
docker-compose exec web curl http://backend:3001/health

# Check which network containers are on
docker network inspect kinky-thots_kinky-network
```

### Environment Variables Flow

```bash
# 1. Defined in .env file
MARIADB_PASSWORD=REDACTED_DB_PASSWORD

# 2. Referenced in docker-compose.yml
environment:
  - MARIADB_PASSWORD=${MARIADB_PASSWORD}

# 3. Available inside container
docker-compose exec backend env | grep MARIADB_PASSWORD
```

**Check environment variables:**
```bash
# View all env vars in service
docker-compose exec backend env

# Check specific variable
docker-compose exec backend sh -c 'echo $MARIADB_PASSWORD'

# View what docker-compose will use
docker-compose config | grep -A 5 "environment:"
```

### File Permissions & Ownership

```bash
# Files created by containers may have different ownership
ls -la /var/www/kinky-thots/uploads/

# Check what user container runs as
docker-compose exec web whoami
docker-compose exec backend whoami

# Fix permissions if needed (run on host)
sudo chown -R www-data:www-data /var/www/kinky-thots/uploads
sudo chown -R gkeylow:www-data /var/www/kinky-thots/uploads
```

### Quick Diagnostics

```bash
# Is my container using the host files?
docker-compose exec web cat /var/www/html/index.html | head -5
cat /var/www/kinky-thots/index.html | head -5
# Should be identical!

# What port is actually listening?
docker-compose ps
ss -tlnp | grep 80

# Can containers reach each other?
docker-compose exec backend ping -c 2 db
docker-compose exec web curl -I http://backend:3001/health

# Where are volumes stored?
docker volume ls
docker volume inspect kinky-thots_db_data

# How much space are containers using?
docker system dfdocker-compose exec backend ping -c 2 db
docker-compose exec web df -h
```

### Common Gotchas

1. **"File not found" but file exists on host**
   - Check volume mapping in docker-compose.yml
   - Container path ≠ host path

2. **Changes not reflected**
   - Mounted volumes: Changes are instant
   - Copied files (in Dockerfile): Need rebuild

3. **Can't connect to database**
   - From container: Use service name `db:3306`
   - From host: Use `localhost:3306` (only if port exposed)

4. **Permission denied on uploads**
   - Container user ≠ host user
   - Fix with `chown` on host

5. **Port already in use**
   - Another service using the same port
   - Check with `ss -tlnp | grep PORT`

---

## Basic Commands

### Start Services

```bash
# Start all services in detached mode (background)
docker-compose up -d

# Start specific service
docker-compose up -d backend

# Start with build (rebuild images)
docker-compose up -d --build

# Start and view logs in foreground
docker-compose up
```

### Stop Services

```bash
# Stop all services (keeps containers)
docker-compose stop

# Stop specific service
docker-compose stop backend

# Stop and remove containers, networks
docker-compose down

# Stop and remove containers, networks, volumes
docker-compose down -v

# Stop and remove containers, networks, images
docker-compose down --rmi all
```

### Restart Services

```bash
# Restart all services
docker-compose restart

# Restart specific service
docker-compose restart backend

# Recreate containers (preserves volumes)
docker-compose up -d --force-recreate

# Recreate specific service
docker-compose up -d --force-recreate backend
```

---

## Viewing Information

### Container Status

```bash
# List all containers
docker-compose ps

# List with formatting
docker-compose ps --format "table {{.Name}}\t{{.Status}}\t{{.Ports}}"

# Show all containers (including stopped)
docker-compose ps -a
```

### Logs

```bash
# View logs from all services
docker-compose logs

# Follow logs in real-time
docker-compose logs -f

# View logs from specific service
docker-compose logs backend

# Follow logs from specific service
docker-compose logs -f backend

# Show last N lines
docker-compose logs --tail=50 backend

# Show logs with timestamps
docker-compose logs -t backend
```

### Service Details

```bash
# Show running processes
docker-compose top

# Show running processes for specific service
docker-compose top backend

# Show configuration
docker-compose config

# Validate docker-compose.yml
docker-compose config --quiet
```

---

## Building & Images

### Build Images

```bash
# Build all images
docker-compose build

# Build specific service
docker-compose build backend

# Build without cache
docker-compose build --no-cache

# Build with progress output
docker-compose build --progress=plain

# Pull latest base images before building
docker-compose build --pull
```

### Image Management

```bash
# Pull images defined in compose file
docker-compose pull

# Pull specific service image
docker-compose pull backend

# List images used by services
docker-compose images
```

---

## Executing Commands

### Run Commands in Containers

```bash
# Execute command in running container
docker-compose exec backend bash

# Execute command as root
docker-compose exec -u root backend bash

# Execute one-off command
docker-compose exec backend npm --version

# Run command without starting service
docker-compose run backend bash

# Run without dependencies
docker-compose run --no-deps backend bash

# Run and remove container after
docker-compose run --rm backend bash
```

### Common Exec Examples

```bash
# Open bash shell in backend
docker-compose exec backend bash

# Check backend logs
docker-compose exec backend cat /var/log/app.log

# Check database connection
docker-compose exec backend node -e "console.log('DB test')"

# Access MariaDB
docker-compose exec db mysql -u root -p

# Check web server config
docker-compose exec web apache2ctl -t
```

---

## Scaling & Resource Management

### Scale Services

```bash
# Scale specific service to N instances
docker-compose up -d --scale backend=3

# Scale multiple services
docker-compose up -d --scale backend=2 --scale worker=4
```

### Resource Usage

```bash
# Show resource usage stats
docker stats $(docker-compose ps -q)

# Show stats for specific service
docker stats $(docker-compose ps -q backend)
```

---

## Volumes & Data

### Volume Management

```bash
# List volumes
docker volume ls

# Inspect volume
docker volume inspect kinky-thots_db_data

# Remove unused volumes
docker volume prune

# Remove specific volume
docker volume rm kinky-thots_db_data
```

### Backup & Restore

```bash
# Backup database volume
docker run --rm \
  -v kinky-thots_db_data:/data \
  -v $(pwd):/backup \
  alpine tar czf /backup/db-backup-$(date +%Y%m%d).tar.gz /data

# Restore database volume
docker run --rm \
  -v kinky-thots_db_data:/data \
  -v $(pwd):/backup \
  alpine tar xzf /backup/db-backup.tar.gz -C /
```

---

## Network Management

### Network Commands

```bash
# List networks
docker network ls

# Inspect network
docker network inspect kinky-thots_kinky-network

# Remove unused networks
docker network prune
```

---

## Database Operations

### MariaDB Container

```bash
# Access MySQL CLI
docker-compose exec db mysql -u root -p

# Execute SQL file
docker-compose exec -T db mysql -u root -p kinky_thots < backup.sql

# Dump database
docker-compose exec db mysqldump -u root -p kinky_thots > backup.sql

# Create database backup
docker-compose exec db sh -c 'mysqldump -u root -p"$MYSQL_ROOT_PASSWORD" kinky_thots' > backup-$(date +%Y%m%d).sql

# Show databases
docker-compose exec db mysql -u root -p -e "SHOW DATABASES;"

# Check database size
docker-compose exec db mysql -u root -p -e "SELECT table_schema AS 'Database', ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)' FROM information_schema.tables GROUP BY table_schema;"
```

---

## Troubleshooting

### Debug Commands

```bash
# View container details
docker-compose ps -a
docker inspect $(docker-compose ps -q backend)

# Check container health
docker-compose ps --filter "health=unhealthy"

# View environment variables
docker-compose exec backend env

# Check disk usage
docker system df

# View last container exit code
docker-compose ps backend

# Follow logs with grep filter
docker-compose logs -f backend | grep ERROR
```

### Cleanup Commands

```bash
# Stop all containers and remove everything
docker-compose down -v --rmi all

# Remove stopped containers
docker-compose rm

# Remove stopped containers (force)
docker-compose rm -f

# Clean up Docker system
docker system prune

# Clean up everything (dangerous!)
docker system prune -a --volumes
```

### Health Checks

```bash
# Check service health status
docker-compose ps

# Inspect health check details
docker inspect --format='{{json .State.Health}}' kinky-db

# View health check logs
docker inspect --format='{{range .State.Health.Log}}{{.Output}}{{end}}' kinky-db
```

---

## File Operations

### Copy Files

```bash
# Copy file from host to container
docker-compose cp ./local-file.txt backend:/app/file.txt

# Copy file from container to host
docker-compose cp backend:/app/file.txt ./local-file.txt

# Copy directory
docker-compose cp ./local-dir backend:/app/
```

---

## Environment & Configuration

### Environment Variables

```bash
# View environment variables in service
docker-compose exec backend env

# Check specific env var
docker-compose exec backend sh -c 'echo $NODE_ENV'

# Run with different env file
docker-compose --env-file .env.production up -d
```

### Configuration

```bash
# Validate configuration
docker-compose config

# View resolved configuration
docker-compose config --services

# View volumes configuration
docker-compose config --volumes

# Check configuration with specific file
docker-compose -f docker-compose.prod.yml config
```

---

## Project-Specific Commands

### Kinky-Thots Stack

```bash
# Start full stack
docker-compose up -d

# Restart backend after code changes
docker-compose restart backend

# View backend logs
docker-compose logs -f backend

# Access backend shell
docker-compose exec backend bash

# Access database
docker-compose exec db mysql -u root -pREDACTED_DB_PASSWORD kinky_thots

# Check all services health
docker-compose ps

# Rebuild and restart backend
docker-compose up -d --build --force-recreate backend

# View RTMP streaming logs
docker-compose logs -f rtmp

# Tail all logs
docker-compose logs -f
```

### Emergency Recovery

```bash
# Full restart (preserves data)
docker-compose down && docker-compose up -d

# Nuclear option (destroys data!)
docker-compose down -v
docker volume prune -f
docker-compose up -d --build
```

---

## Useful Aliases (Optional)

Add to your `~/.bashrc` or `~/.zshrc`:

```bash
# Docker Compose shortcuts
alias dc='docker-compose'
alias dcu='docker-compose up -d'
alias dcd='docker-compose down'
alias dcr='docker-compose restart'
alias dcl='docker-compose logs -f'
alias dcp='docker-compose ps'
alias dce='docker-compose exec'
alias dcb='docker-compose build'

# Project-specific
alias kt-up='cd /var/www/kinky-thots && docker-compose up -d'
alias kt-down='cd /var/www/kinky-thots && docker-compose down'
alias kt-logs='cd /var/www/kinky-thots && docker-compose logs -f'
alias kt-db='cd /var/www/kinky-thots && docker-compose exec db mysql -u root -pREDACTED_DB_PASSWORD kinky_thots'
alias kt-backend='cd /var/www/kinky-thots && docker-compose exec backend bash'
```

---

## Quick Reference Table

> **💡 First time using Docker Compose?** Start with [Understanding the Basics](#understanding-the-basics-important) section above!

| Command | Description |
|---------|-------------|
| `docker-compose up -d` | Start all services |
| `docker-compose down` | Stop and remove containers |
| `docker-compose ps` | List containers |
| `docker-compose logs -f` | Follow logs |
| `docker-compose exec SERVICE bash` | Open shell in service |
| `docker-compose restart SERVICE` | Restart service |
| `docker-compose build` | Build images |
| `docker-compose pull` | Pull latest images |
| `docker-compose config` | Validate config |
| `docker-compose up -d --build` | Rebuild and start |
| `docker-compose exec web ls /var/www/html` | Check mounted files |
| `docker-compose exec backend ping db` | Test container networking |

---

## Common Workflows

### Update and Restart Service

```bash
# 1. Pull latest changes
git pull

# 2. Rebuild image
docker-compose build backend

# 3. Restart service
docker-compose up -d --force-recreate backend

# 4. Check logs
docker-compose logs -f backend
```

### Database Migration

```bash
# 1. Backup database first
docker-compose exec db mysqldump -u root -pREDACTED_DB_PASSWORD kinky_thots > backup.sql

# 2. Run migration
docker-compose exec backend npm run migrate

# 3. Check logs
docker-compose logs backend
```

### Debug Issues

```bash
# 1. Check service status
docker-compose ps

# 2. View logs
docker-compose logs -f backend

# 3. Access container
docker-compose exec backend bash

# 4. Check environment
docker-compose exec backend env

# 5. Test connectivity
docker-compose exec backend curl http://db:3306
```

---

## Tips & Best Practices

1. **Always use `-d` flag** for production to run in background
2. **Check logs** after starting services: `docker-compose logs -f`
3. **Use `--build`** when you've made changes to Dockerfiles
4. **Backup volumes** before running `docker-compose down -v`
5. **Use `.env` files** for environment variables
6. **Name your containers** explicitly in docker-compose.yml
7. **Set restart policies** to `unless-stopped` for production
8. **Monitor resource usage** with `docker stats`
9. **Regular cleanup** with `docker system prune`
10. **Test configuration** with `docker-compose config` before deploying

---

## Additional Resources

- [Official Docker Compose Documentation](https://docs.docker.com/compose/)
- [Docker Compose CLI Reference](https://docs.docker.com/compose/reference/)
- Project docs: `/var/www/kinky-thots/.docs/`

---

**Last Updated**: January 8, 2026
**Project**: Kinky-Thots
**Stack**: Apache/PHP, Node.js, MariaDB, nginx-rtmp
