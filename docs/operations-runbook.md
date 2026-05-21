# Operations Runbook

Last updated: 2026-05-21

This runbook explains how to run, deploy, verify, troubleshoot, and maintain the IGNA Studio platform.

## 1. Local Development

From the project folder:

```bash
cd "/Users/jesus/Library/CloudStorage/GoogleDrive-administrador.web@iejuandecabrera.edu.co/My Drive/Trabajo/Trabajos Actuales/Igna company/IgnaIT/studio-platform"
```

Install dependencies:

```bash
composer install
npm install
```

Prepare environment:

```bash
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
```

Build assets:

```bash
npm run build
```

Run development server:

```bash
composer run dev
```

If you need a manual PHP server:

```bash
php -d upload_max_filesize=25M -d post_max_size=25M -S 127.0.0.1:8000 -t public public/dev-server.php
```

## 2. Local Login

Default local super admin if no `SUPER_ADMIN_PASSWORD` is configured:

```text
Email:    admin@ignastudio.com
Password: Igna12345!
```

In production, always configure `SUPER_ADMIN_PASSWORD` before running seeders.

## 3. Quality Checks Before Push

Run:

```bash
composer validate --strict
composer audit --locked
php -d memory_limit=512M vendor/bin/pint --test
php -d memory_limit=512M vendor/bin/phpunit
npm run build
npm audit --omit=dev
```

If Pint fails and the issue is formatting only:

```bash
php -d memory_limit=512M vendor/bin/pint
```

## 4. Push From Local Machine

```bash
git status
git add .
git commit -m "Describe the change"
git push origin main
```

Before committing, check that `.env`, SSH keys, private files, and local caches are not staged.

## 5. Pull On Hostinger

Connect:

```bash
ssh -p 65002 u935649387@212.85.28.79
```

Go to the Laravel app:

```bash
cd /home/u935649387/apps/igna-studio
```

Pull code and install PHP dependencies:

```bash
git pull origin main
composer install --no-dev --optimize-autoloader
```

Run migrations:

```bash
php artisan migrate --force
```

Clear and rebuild Laravel caches:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 6. Sync Public Assets On Hostinger

Because the Laravel app and public web folder are separated, built assets must be copied to the exposed folder.

```bash
rm -rf /home/u935649387/domains/ignastudio.com/public_html/igna-app/build
cp -R /home/u935649387/apps/igna-studio/public/build /home/u935649387/domains/ignastudio.com/public_html/igna-app/build
```

If CSS changes appear locally but not on production, this is usually the first thing to check.

Also confirm there is no `public/hot` file in production.

## 7. Production Paths

Laravel application:

```text
/home/u935649387/apps/igna-studio
```

Public domain folder:

```text
/home/u935649387/domains/ignastudio.com/public_html/igna-app
```

Public entrypoint:

```text
/home/u935649387/domains/ignastudio.com/public_html/igna-app/index.php
```

The public entrypoint should require:

```text
/home/u935649387/apps/igna-studio/vendor/autoload.php
/home/u935649387/apps/igna-studio/bootstrap/app.php
```

## 8. Email Configuration

Production `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_USERNAME=support@ignastudio.com
MAIL_PASSWORD=...
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=support@ignastudio.com
MAIL_FROM_NAME="IGNA Studio"
MAIL_REPLY_TO_ADDRESS=support@ignastudio.com
MAIL_REPLY_TO_NAME="IGNA Studio"
MAIL_EHLO_DOMAIN=ignastudio.com
```

After changing mail configuration:

```bash
php artisan optimize:clear
php artisan config:cache
```

Quick SMTP test:

```bash
php -r 'require "vendor/autoload.php"; $app=require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); Illuminate\Support\Facades\Mail::raw("IGNA Studio SMTP test.", function ($m) { $m->to("your-email@example.com")->subject("IGNA Studio SMTP test"); }); echo "sent\n";'
```

This raw test does not use the branded email template. To test the branded template, trigger a real ticket update or use the mailable class from Tinker/CLI.

## 9. Common Production Problems

### Styling does not match local

Most likely cause:

- `public/build` was not synced to `public_html/igna-app/build`.
- Browser cache still uses old asset.
- `public/hot` exists and points to a local dev server.

Fix:

```bash
cd /home/u935649387/apps/igna-studio
npm run build # only if npm is available on server; otherwise build locally and upload
rm -rf /home/u935649387/domains/ignastudio.com/public_html/igna-app/build
cp -R public/build /home/u935649387/domains/ignastudio.com/public_html/igna-app/build
php artisan optimize:clear
php artisan view:cache
```

### 500 Server Error

Check logs:

```bash
cd /home/u935649387/apps/igna-studio
tail -n 120 storage/logs/laravel.log
```

Common causes:

- Missing migration.
- Missing column after code update.
- Wrong `.env`.
- Cached config from old values.
- Missing `vendor` dependencies after composer.lock changed.

Fix sequence:

```bash
composer install --no-dev --optimize-autoloader
php artisan optimize:clear
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Could not open input file: artisan

You are in the wrong folder.

Correct folder:

```bash
cd /home/u935649387/apps/igna-studio
```

The public folder `/home/u935649387/domains/ignastudio.com/public_html/igna-app` does not contain `artisan`.

### Upload rejected even under 20 MB

Check PHP limits:

```bash
php -i | grep -E "upload_max_filesize|post_max_size"
```

Laravel validation allows admin files up to 20 MB, but PHP must allow the request before Laravel receives it.

### Email authentication fails

Check:

- Correct mailbox password.
- Correct SMTP host and port.
- SSL on port `465`.
- Username is the full email address.
- Config cache has been cleared.

Run:

```bash
php artisan optimize:clear
php artisan config:cache
```

## 10. Backups

At minimum, back up:

- MySQL database.
- `storage/app/private`
- `storage/app/public`
- `.env` stored securely outside Git.

Manual database export:

```bash
mysqldump -u DB_USER -p DB_NAME > igna-backup-$(date +%Y-%m-%d).sql
```

Private file archive:

```bash
tar -czf igna-private-storage-$(date +%Y-%m-%d).tar.gz storage/app/private storage/app/public
```

Suggested storage:

- Download backups from Hostinger.
- Store copies in Google Drive or another external storage.
- Do not leave many large backup files on the hosting account because storage is limited.

## 11. Manual QA Checklist

After deployment:

- Visit `https://ignastudio.com`.
- Test desktop navbar.
- Test mobile hamburger menu.
- Switch ES/EN.
- Submit a public request.
- Confirm admin receives email.
- Confirm client receives request confirmation.
- Log in as admin.
- Open the new ticket.
- Move stage forward.
- Complete a stage.
- Upload a file.
- Toggle file visible to client.
- Track the ticket publicly by code and email.
- Open blog index/detail.
- Open team profile and credential viewer.
- Create or open proposal.
- Generate proposal PDF.
- Generate WhatsApp link.

## 12. Maintenance Rhythm

Weekly:

- Check Laravel logs.
- Confirm backups exist.
- Review new tickets/files.

Monthly:

- Run `composer audit --locked`.
- Run `npm audit --omit=dev`.
- Remove old temporary exports/backups from hosting.
- Confirm public assets are not stale.

Before every deployment:

- Run tests locally.
- Build assets locally.
- Commit and push.
- Pull on Hostinger.
- Sync `public/build`.
- Clear/cache Laravel.
