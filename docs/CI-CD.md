# CI/CD — T.N. Memorial Public School Digital Platform

## Architecture Overview

```
Developer
    │
    ├── git push (feature branch) ──→ CI runs
    │                                   ├── Repo safety
    │                                   ├── PHP lint + composer
    │                                   ├── Website build
    │                                   ├── ERP build
    │                                   └── Docker validation
    │
    ├── Pull Request to main ──→ CI runs (same gates)
    │
    └── Merge to main ──→ CD runs
                            ├── CI gate (full CI re-run)
                            ├── SSH to VPS
                            ├── Pull exact commit
                            ├── Docker Compose build
                            ├── Health checks (6/6 services)
                            ├── HTTP smoke tests
                            ├── Security verification
                            └── PASS or rollback
```

## Production Domain

| Environment | Target | Access |
|-------------|--------|--------|
| Development / Demo | Local PC + Docker Compose | Cloudflare Tunnel (temporary) |
| Production (24×7) | VPS + Docker Compose | `tnmemorialschool.in` via Cloudflare |

## Workflow Files

| File | Purpose | Trigger |
|------|---------|---------|
| `.github/workflows/ci.yml` | Continuous Integration | Push to `main`, `dev`, `feat/**`, `sprint/**`, `feature/**`; Pull requests to `main`, `dev` |
| `.github/workflows/deploy-production.yml` | Production Deployment | Push to `main` only |

---

## CI Pipeline

### Jobs

| Job | What it does |
|-----|-------------|
| **repo-safety** | Verifies no `.env`, `.next`, `node_modules`, `vendor`, private keys, or backup files are committed |
| **backend** | PHP 8.3 syntax lint across all `.php` files, composer validate, autoloader check |
| **website-build** | `npm ci` + `npm run build` for Website, verifies `assetPrefix: '/website'`, validates `.next` output |
| **erp-build** | `npm ci` + `npm run build` for ERP, verifies `assetPrefix: '/erp'`, validates `.next` output |
| **docker-validation** | Validates `docker-compose.yml` config, verifies all Dockerfiles and nginx.conf exist |

### Dependency Graph

```
repo-safety
    ├── backend
    ├── website-build
    ├── erp-build
    └── docker-validation
```

`repo-safety` runs first. All other jobs run in parallel after it passes.

---

## CD Pipeline

### Trigger

Production deployment only triggers on:

```yaml
push:
  branches:
    - main
```

### Concurrency

```yaml
concurrency:
  group: production-deployment
  cancel-in-progress: false
```

Only one production deployment can run at a time. New pushes queue rather than cancel in-progress deployments.

### Deployment Steps

1. **CI Gate** — Full CI pipeline re-runs as prerequisite
2. **Secret Validation** — Verifies VPS_HOST, VPS_USER, VPS_SSH_PRIVATE_KEY are configured
3. **SSH Setup** — Configures SSH with deploy key
4. **VPS Deployment:**
   - Records current (previous) commit SHA for rollback
   - Fetches and checks out `origin/main`
   - Validates production `.env` exists with required variables
   - Validates Docker Compose configuration
   - Runs `docker compose up -d --build --remove-orphans` (preserves volumes)
   - Waits up to 120 seconds for all 6 services to become healthy
   - Runs HTTP smoke tests (7 endpoints)
   - Verifies security paths blocked (2 paths)
   - Verifies security headers (4 headers)
5. **On Failure** — Automatic rollback to previous commit

---

## GitHub Environment Setup

### Create the Environment

1. Go to: `Settings → Environments → New environment`
2. Name: `production`
3. (Optional) Add required reviewers for manual approval
4. (Optional) Restrict to `main` branch

### Required Secrets

| Secret | Description | Example |
|--------|-------------|---------|
| `VPS_HOST` | VPS IP address or hostname | `203.0.113.10` |
| `VPS_USER` | SSH username on VPS | `deploy` |
| `VPS_SSH_PRIVATE_KEY` | SSH private key (Ed25519 recommended) | Contents of `~/.ssh/id_ed25519` |
| `VPS_PORT` | SSH port (optional, default: 22) | `22` |

### Required Variables

| Variable | Description | Example |
|----------|-------------|---------|
| `DEPLOY_PATH` | Absolute path to repo on VPS | `/opt/tnm-school` |

---

## VPS Prerequisites

### System Requirements

- Ubuntu 22.04+ or Debian 12+
- Docker Engine 24+
- Docker Compose V2 (plugin)
- Git
- curl, wget

### Initial VPS Setup

```bash
# 1. Create deploy user
sudo useradd -m -s /bin/bash deploy
sudo usermod -aG docker deploy

# 2. Setup SSH key authentication
sudo mkdir -p /home/deploy/.ssh
# Add the public key corresponding to VPS_SSH_PRIVATE_KEY:
sudo tee /home/deploy/.ssh/authorized_keys <<< "ssh-ed25519 AAAA... deploy@tnm-school"
sudo chmod 700 /home/deploy/.ssh
sudo chmod 600 /home/deploy/.ssh/authorized_keys
sudo chown -R deploy:deploy /home/deploy/.ssh

# 3. Clone repository
sudo mkdir -p /opt/tnm-school
sudo chown deploy:deploy /opt/tnm-school
sudo -u deploy git clone https://github.com/Paravejalam/tnm-school-digital-platform.git /opt/tnm-school
cd /opt/tnm-school
sudo -u deploy git checkout main

# 4. Create production .env
sudo -u deploy cp deployment/docker/.env.example deployment/docker/.env
sudo -u deploy nano deployment/docker/.env
# Configure real production values:
#   APP_ENV=production
#   APP_DEBUG=false
#   DB_NAME=...
#   DB_USER=...
#   DB_PASSWORD=<strong random password>
#   MYSQL_ROOT_PASSWORD=<strong random password>
#   JWT_SECRET=<strong random secret>
#   JWT_ACCESS_TOKEN_TTL=3600
#   JWT_REFRESH_TOKEN_TTL=604800
#   NEXT_PUBLIC_API_URL=https://tnmemorialschool.in/api

# 5. Initial deployment
docker compose \
  --env-file deployment/docker/.env \
  -f deployment/docker/docker-compose.yml \
  up -d --build

# 6. Firewall — only expose HTTP/HTTPS
sudo ufw allow 22/tcp    # SSH
sudo ufw allow 80/tcp    # HTTP
sudo ufw allow 443/tcp   # HTTPS (via Cloudflare/reverse proxy)
sudo ufw enable
# Do NOT expose 3000, 3001, 3306, 8080, 9000 publicly
```

---

## Domain / Cloudflare Configuration

### DNS Architecture

```
tnmemorialschool.in
    → Cloudflare DNS (A record → VPS IP)
    → Cloudflare Proxy (orange cloud)
    → VPS port 80
    → Nginx
        ├── /website/ → Website Next.js (port 3000)
        ├── /erp/     → ERP Next.js (port 3001)
        ├── /api/     → PHP-FPM (port 9000)
        └── /         → PHP backend root
```

### Required DNS Records

| Type | Name | Content | Proxy |
|------|------|---------|-------|
| A | `@` | VPS public IP | ✅ Proxied |
| A | `www` | VPS public IP | ✅ Proxied |

### Cloudflare Settings

| Setting | Value |
|---------|-------|
| SSL/TLS Mode | Full (Strict) |
| Always Use HTTPS | On |
| Minimum TLS Version | 1.2 |
| Auto Minify | CSS, JS |
| Brotli | On |

### Local Development / Demo

For temporary public access from a local PC (development/demo only):

```bash
# Install cloudflared
# Then:
cloudflared tunnel --url http://localhost:80
```

This creates a temporary `*.trycloudflare.com` URL. **Not for 24×7 production.**

---

## Deployment Flow

### Normal Deployment

```
1. Developer pushes to feature branch
2. CI runs on push (5 parallel jobs)
3. Developer creates PR to main
4. CI runs on PR
5. PR reviewed and merged to main
6. CD triggers:
   a. CI gate passes
   b. SSH to VPS
   c. git fetch + checkout main
   d. docker compose up -d --build
   e. Health checks (6 services)
   f. Smoke tests (7 HTTP checks)
   g. Security verification
7. Deployment PASS
```

### What the CD Does NOT Do

- Does **not** run `docker compose down` (avoids downtime)
- Does **not** delete database volumes (preserves data)
- Does **not** run `docker system prune` (avoids affecting other containers)
- Does **not** expose secrets in logs

---

## Rollback Flow

### Automatic Rollback (on deployment failure)

If smoke tests fail after deployment:

1. CD records the previous commit SHA before deploying
2. After smoke test failure, the previous SHA is checked out
3. Docker services are rebuilt from the previous code
4. Health check waits 30 seconds
5. `/api/health` is verified
6. The GitHub Actions job is marked **failed** regardless

### Manual Rollback

```bash
# SSH to VPS
ssh deploy@<VPS_HOST>
cd /opt/tnm-school

# Find the previous good commit
git log --oneline -10

# Checkout the known-good commit
git checkout main
git reset --hard <good-commit-sha>

# Rebuild
docker compose \
  --env-file deployment/docker/.env \
  -f deployment/docker/docker-compose.yml \
  up -d --build

# Verify
docker compose \
  --env-file deployment/docker/.env \
  -f deployment/docker/docker-compose.yml \
  ps

curl -s -o /dev/null -w "%{http_code}" http://localhost/api/health
# Should return: 200
```

---

## Health Checks

### Docker Service Health

| Service | Health Command | Expected |
|---------|---------------|----------|
| mysql | `mysqladmin ping` | healthy |
| php | `php-fpm -t` | healthy |
| nginx | `nginx -t` | healthy |
| website | `wget -qO- http://localhost:3000` | healthy |
| erp | `wget -qO- http://localhost:3001` | healthy |
| phpmyadmin | `curl -fsS http://localhost:80` | healthy |

### HTTP Smoke Tests

| Endpoint | Expected |
|----------|----------|
| `GET /` | 200 |
| `GET /api/health` | 200 |
| `GET /website/` | 200 |
| `GET /erp/` | 200 |
| `GET /api/does-not-exist` | 404 |
| `GET /api/students` | 401 |
| `GET /api/auth/profile` | 401 |

### Security Checks

| Path | Expected |
|------|----------|
| `GET /.env` | 404 |
| `GET /config/app.php` | 404 |

| Header | Required |
|--------|----------|
| X-Frame-Options | SAMEORIGIN |
| X-Content-Type-Options | nosniff |
| Referrer-Policy | strict-origin-when-cross-origin |
| Strict-Transport-Security | max-age=31536000 |

---

## Troubleshooting

### CI Failures

| Symptom | Cause | Fix |
|---------|-------|-----|
| `repo-safety` fails | Committed `.env`, `.next`, `node_modules`, etc. | Remove from tracking, update `.gitignore` |
| `backend` fails | PHP syntax error | Fix the PHP file indicated in the error |
| `website-build` fails | Missing `assetPrefix` or build error | Check `apps/website/next.config.js` and build output |
| `erp-build` fails | Missing `assetPrefix` or build error | Check `apps/erp/next.config.js` and build output |
| `docker-validation` fails | Invalid compose syntax | Run `docker compose config` locally |

### CD Failures

| Symptom | Cause | Fix |
|---------|-------|-----|
| "secrets are not configured" | Missing GitHub Environment | Configure secrets in Settings → Environments → production |
| SSH connection fails | Wrong key or firewall | Verify VPS_SSH_PRIVATE_KEY and port |
| ".env file is missing" | No production config on VPS | Create `deployment/docker/.env` on VPS |
| Health check timeout | Services failing to start | Check `docker compose logs` on VPS |
| Smoke test fails | Application error | Check container logs, rollback triggered automatically |

### Local Development

```bash
# Start all services
docker compose \
  --env-file deployment/docker/.env \
  -f deployment/docker/docker-compose.yml \
  up -d --build

# Check health
docker compose \
  --env-file deployment/docker/.env \
  -f deployment/docker/docker-compose.yml \
  ps

# View logs
docker compose \
  --env-file deployment/docker/.env \
  -f deployment/docker/docker-compose.yml \
  logs -f website erp php nginx
```
