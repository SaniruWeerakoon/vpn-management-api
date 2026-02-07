# VPN Management Platform (Laravel Backend)

This repository contains the **backend system for managing OpenVPN clients**, including provisioning, revocation, and operational safety controls.

The system is designed to:
- Safely provision and revoke VPN clients
- Prevent double-provisioning and race conditions
- Run long-running operations asynchronously
- Provide auditability for all sensitive actions

The repository reflects a stable, reviewed snapshot of the system, not the complete iteration history.
---

## Overview

The VPN Management Platform provides a controlled API layer over OpenVPN operations.

Key characteristics:
- Human-initiated actions only
- No automatic client creation or rotation
- Explicit provisioning and revocation flows
- Infrastructure-aware backend design

---

## Architecture

- **Framework:** Laravel
- **VPN Technology:** OpenVPN
- **Queue & Cache:** Redis
- **Database:** Relational (MySQL / PostgreSQL)
- **Execution Model:** Async jobs with locking
- **Deployment:** Docker (local), AWS (partial)

---

## Core Domain Concepts

### VPN Client

Represents a logical VPN user managed by the system.

Key fields:
- id
- name
- status (pending, active, revoked)
- created_at
- revoked_at

---

### Provisioning Job

Handles the creation of VPN credentials.

Characteristics:
- Executed asynchronously
- Protected by Redis locks
- Idempotent by design
- Retries safely on transient failure

---

### Revocation Job

Handles the secure removal of VPN access.

Characteristics:
- Executed asynchronously
- Protected by Redis locks
- Audited and irreversible

---

## Redis Usage (Critical)

Redis is used for **correctness**, not convenience.

### Redis Locks
- Prevent concurrent provisioning of the same client
- Enforce single-flight execution
- Handle crash recovery via TTL

### Redis Queues
- Offload slow OpenVPN operations
- Keep APIs responsive
- Allow retries without duplication

---

## OpenVPN Integration

- OpenVPN client management is performed via hardened shell scripts
- Scripts handle:
  - client creation
  - certificate generation
  - revocation
- Scripts are executed via secure SSH from Laravel
- Output is captured and logged for auditing

---

## API Capabilities

- Create VPN client records
- Revoke VPN clients
- Trigger async provisioning jobs
- Query client status
- Health checks for Redis and queue system

---

## Audit Logging

All sensitive actions are logged:
- Client creation
- Provisioning attempts
- Revocation events
- Failures and retries

Audit logs are immutable and append-only.

---

## Environment & Setup

Local development:
- Docker
- Docker Compose (app + redis)

Commands:
- composer install
- php artisan migrate
- php artisan queue:work

---

## Current Status

- Core backend complete
- Redis locking and async jobs working
- OpenVPN scripts integrated and tested
- Dockerized local environment functional
- AWS deployment incomplete

---

## Deferred Work

- Finalize AWS deployment
- CI/CD pipeline
- Admin UI

---

## Project Philosophy

- Safety over speed
- Explicit actions over automation
- Locks before caching

This project is intentionally designed to demonstrate **real-world backend and infrastructure thinking**, not just CRUD APIs.

