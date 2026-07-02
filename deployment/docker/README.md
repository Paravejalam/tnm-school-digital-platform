# Docker Infrastructure — T.N. Memorial Public School Digital Platform

> **Sprint 1 — Milestone 1: Docker Infrastructure**
> Authority: `.github/AGENT.md` Section 4.1 — Docker Governance
> Version: 1.0.0 | Created: 2026-07-02

This directory contains the complete Docker infrastructure for local development of the T.N. Memorial Public School Digital Platform.

---

## Container Overview

| Container | Image | Port | Role |
|-----------|-------|------|------|
| `tnm_php` | PHP 8.3 FPM (custom) | 9000 (internal) | PHP backend runtime |
| `tnm_nginx` | Nginx stable (custom) | 80 | Reverse proxy |
| `tnm_website` | Node 22 Alpine (custom) | 3000 | Public website frontend |
| `tnm_erp` | Node 22 Alpine (custom) | 3001 | ERP dashboard frontend |
| `tnm_mysql` | MySQL 8.0 (official) | 3306 | Relational database |
| `tnm_phpmyadmin` | phpMyAdmin latest | 8080 | Database admin UI |

---

## Port Reference

| Service | Local Port | Container Port |
|---------|-----------|----------------|
| Nginx (main entry) | 80 | 80 |
| Public Website | 3000 | 3000 |
| ERP Dashboard | 3001 | 3001 |
| MySQL | 3306 | 3306 |
| phpMyAdmin | 8080 | 80 |

---

## Volume Reference

| Volume | Purpose |
|--------|---------|
| `mysql-data` | Persistent MySQL database storage |
| `node_modules_website` | Website npm packages (isolated from host) |
| `node_modules_erp` | ERP npm packages (isolated from host) |
| `vendor` | PHP Composer packages (isolated from host) |

---

## Network

All containers communicate on a single bridge network: `tnm-network`.

---

## File Structure

```
deployment/docker/
├── docker-compose.yml      — Service orchestration
├── Dockerfile.php          — PHP 8.3 FPM image
├── Dockerfile.node         — Node 22 image (website + erp)
├── Dockerfile.nginx        — Nginx stable image
├── .env.example            — Environment variable template
├── README.md               — This file
├── nginx/
│   └── nginx.conf          — Nginx reverse proxy configuration
├── php/
│   └── php.ini             — PHP 8.3 runtime configuration
└── mysql/
    └── my.cnf              — MySQL 8 server configuration
```

---

## Prerequisites

- Docker Desktop (Windows/macOS) or Docker Engine + Docker Compose plugin (Linux)
- Docker Compose v2.0+
- At least 4 GB RAM allocated to Docker

---

## Setup

### Step 1 — Create your environment file

```bash
cp deployment/docker/.env.example deployment/docker/.env
```

Edit `deployment/docker/.env` and set secure values for:
- `DB_PASSWORD`
- `MYSQL_ROOT_PASSWORD`
- `JWT_SECRET`

> Never commit `.env` to the repository. It is listed in `.gitignore`.

---

## How to Build

Build all images from scratch:

```bash
docker compose -f deployment/docker/docker-compose.yml build
```

Build a single service:

```bash
docker compose -f deployment/docker/docker-compose.yml build php
docker compose -f deployment/docker/docker-compose.yml build website
docker compose -f deployment/docker/docker-compose.yml build erp
docker compose -f deployment/docker/docker-compose.yml build nginx
```

---

## How to Run

Start all containers (foreground):

```bash
docker compose -f deployment/docker/docker-compose.yml up
```

Start all containers (background / detached):

```bash
docker compose -f deployment/docker/docker-compose.yml up -d
```

---

## How to Stop

Stop all running containers:

```bash
docker compose -f deployment/docker/docker-compose.yml down
```

Stop and remove volumes (WARNING — deletes database data):

```bash
docker compose -f deployment/docker/docker-compose.yml down -v
```

---

## How to Rebuild

Rebuild images and restart all containers:

```bash
docker compose -f deployment/docker/docker-compose.yml down
docker compose -f deployment/docker/docker-compose.yml build --no-cache
docker compose -f deployment/docker/docker-compose.yml up -d
```

Rebuild a single service without stopping others:

```bash
docker compose -f deployment/docker/docker-compose.yml up -d --build php
```

---

## How to View Logs

All services:

```bash
docker compose -f deployment/docker/docker-compose.yml logs -f
```

Single service:

```bash
docker compose -f deployment/docker/docker-compose.yml logs -f php
docker compose -f deployment/docker/docker-compose.yml logs -f nginx
docker compose -f deployment/docker/docker-compose.yml logs -f mysql
```

---

## How to Access Containers

```bash
# PHP container shell
docker exec -it tnm_php bash

# MySQL prompt
docker exec -it tnm_mysql mysql -u tnm_user -p tnm_school_platform

# Nginx shell
docker exec -it tnm_nginx sh
```

---

## Service URLs

| Service | URL |
|---------|-----|
| Public Website | http://localhost:3000 |
| ERP Dashboard | http://localhost:3001 |
| PHP API (via Nginx) | http://localhost/api |
| phpMyAdmin | http://localhost:8080 |

---

## Health Checks

All containers have configured health checks. Check container health:

```bash
docker compose -f deployment/docker/docker-compose.yml ps
```

---

## Docker Governance Rules

Per `.github/AGENT.md` Section 4.1:

- No Docker configuration file may be modified without explicit written approval from the project architect.
- `docker-compose.override.yml` must be used for local personal overrides and must NOT be committed.
- `.env` files must NOT be committed to the repository.
- All four core services (Apache/Nginx, PHP, MySQL) must remain compatible at all times.

---

## Revision History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0.0 | 2026-07-02 | Project Team | Sprint 1 Milestone 1 — Initial Docker infrastructure |

---

© T.N. Memorial School Digital Platform