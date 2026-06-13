# IGNA Studio Platform Deployment Guide

This guide details the SSH deployment, public bridge configuration, and rollback strategies for the production environment on Hostinger.

---

## 1. Environment Topology

* **Laravel Application Root (Private):**
  `/home/u935649387/apps/igna-studio`
* **Exposed Web Folder (Public Bridge):**
  `/home/u935649387/domains/ignastudio.com/public_html/igna-app`

The split-folder configuration keeps code, environments (`.env`), credentials, storage directories, and logs private, exposing only the compiled public assets and the single entry point.

---

## 2. Public Bridge Entry Point Setup

The `index.php` file inside the exposed public directory (`/home/u935649387/domains/ignastudio.com/public_html/igna-app/index.php`) must point absolutely to the private application root:

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = '/home/u935649387/apps/igna-studio/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require '/home/u935649387/apps/igna-studio/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once '/home/u935649387/apps/igna-studio/bootstrap/app.php';

$app->handleRequest(Request::capture());
```

---

## 3. SSH Deployment Process

Execute these commands when deploying a new release:

### Step 1: Connect to Hostinger
```bash
ssh -p 65002 u935649387@212.85.28.79
```

### Step 2: Update Code and PHP Dependencies
Navigate to the private root folder:
```bash
cd /home/u935649387/apps/igna-studio
git checkout main
git pull origin main
composer install --no-dev --optimize-autoloader
```

### Step 3: Run Safe Database Migrations
```bash
php artisan migrate --force
```

### Step 4: Rebuild Caches
```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 4. Frontend Assets Build (No-NPM Server Workflow)

Since the server may not have node/npm installed, compile assets locally on your computer and upload them using `scp`:

### Step 1: Build Locally
On your local computer, inside the project folder:
```bash
npm run build
```

### Step 2: Upload Assets to both Required Locations
Upload to the private Laravel public folder:
```bash
scp -P 65002 -r public/build u935649387@212.85.28.79:/home/u935649387/apps/igna-studio/public/
```
Upload to the exposed domains folder:
```bash
scp -P 65002 -r public/build u935649387@212.85.28.79:/home/u935649387/domains/ignastudio.com/public_html/igna-app/
```

### Step 3: Clear View Cache on the Server
Connect by SSH and clear view cache to load the updated Vite manifest:
```bash
cd /home/u935649387/apps/igna-studio
php artisan view:clear
php artisan view:cache
```

---

## 5. Rollback Strategy

If a deployment fails, run the following commands to restore the last stable commit:

### Step 1: Git Checkout Previous Commit
```bash
cd /home/u935649387/apps/igna-studio
git log --oneline -5
git checkout PREVIOUS_GOOD_COMMIT
```

### Step 2: Re-cache Configuration
```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

> [!WARNING]
> If the failed deployment contained migrations that altered the database structure, checking out a previous commit may cause errors. In this scenario, you must restore the Hostinger database to a backup snapshot generated prior to the deployment.
