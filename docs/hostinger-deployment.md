# Hostinger Deployment Guide

This guide explains how to deploy the IGNA Studio Laravel platform to the main domain:

```text
https://ignesstudio.com
```

The project repository is:

```text
git@github.com:jesusDavidCas/igna.git
```

Google Drive storage is intentionally disabled for now:

```env
GOOGLE_DRIVE_ENABLED=false
```

## Big Picture

The production system has three important places:

- GitHub stores the application code.
- Hostinger stores and runs the Laravel application.
- MySQL stores the platform data: users, services, tickets, stages, blog posts, settings, and file metadata.

Composer does not create the database. You already created the MySQL database, database user, and database password in Hostinger. Laravel will connect to that database through the `.env` file, then create the required tables when you run migrations.

## Before You Start

Have these values ready from Hostinger:

- Server SSH host, username, and port.
- MySQL database name.
- MySQL database username.
- MySQL database password.
- Main domain: `ignesstudio.com`.

Also make sure your server can access the GitHub repository. Since the repository is private, the server SSH key must be added to GitHub as a deploy key or to your GitHub account.

## Step 1: Push The Latest Code From Your Computer

Run this on your local computer, inside the project folder:

```bash
cd "/Users/jesus/Library/CloudStorage/GoogleDrive-administrador.web@iejuandecabrera.edu.co/My Drive/Trabajo/Trabajos Actuales/Igna company/IgnaIT/studio-platform"
git push origin main
```

This sends the latest local commits to GitHub so the server can pull the same code.

## Step 2: Connect To Your Server By SSH

Open your terminal and connect to Hostinger.

The command usually looks like this:

```bash
ssh your_server_user@your_server_ip
```

If Hostinger gave you a custom SSH port, use:

```bash
ssh -p your_port your_server_user@your_server_ip
```

After you run this command, you are no longer working on your computer. You are inside your Hostinger server.

## Step 3: Go To The Folder Where Websites Live

On a VPS, this is commonly:

```bash
cd /var/www
```

If Hostinger gave you another folder, use that folder instead.

You can check where you are with:

```bash
pwd
```

## Step 4: Download The Project From GitHub

Run this on the server:

```bash
git clone git@github.com:jesusDavidCas/igna.git igna-studio
cd igna-studio
```

This creates a folder named:

```text
igna-studio
```

That folder is the Laravel project root.

## Step 5: Create The Production `.env` File

Run this on the server:

```bash
cp .env.example .env
nano .env
```

Inside the `.env` file, set the production values.

Use your real Hostinger database values:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://ignesstudio.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_hostinger_database_name
DB_USERNAME=your_hostinger_database_user
DB_PASSWORD=your_hostinger_database_password

SUPER_ADMIN_EMAIL=your_admin_email@example.com
SUPER_ADMIN_PASSWORD=choose-a-strong-password-here

GOOGLE_DRIVE_ENABLED=false
```

Replace:

- `your_hostinger_database_name` with the database name you created.
- `your_hostinger_database_user` with the database user you created.
- `your_hostinger_database_password` with the database password you created.
- `your_admin_email@example.com` with the email you want to use for the first super admin.
- `choose-a-strong-password-here` with a strong password.

To save in `nano`:

```text
Control + O
Enter
Control + X
```

## Step 6: Install Laravel Dependencies

Run this on the server, inside the `igna-studio` folder:

```bash
composer install --no-dev --optimize-autoloader
```

This installs the PHP packages Laravel needs in production.

## Step 7: Install And Build Frontend Assets

Run this on the server:

```bash
npm ci
npm run build
```

This generates the production CSS and JavaScript files.

If your Hostinger plan does not support Node/npm on the server, build locally with `npm run build` and upload/build through another workflow later. On a VPS, running these commands on the server is usually fine.

## Step 8: Generate Laravel App Key

Run this on the server:

```bash
php artisan key:generate
```

This creates the encryption key Laravel needs for sessions, cookies, and encrypted data.

Only run this once during first setup. Do not regenerate it later unless you understand the consequences.

## Step 9: Create The Database Tables

Run this on the server:

```bash
php artisan migrate --force
```

This creates the tables inside your existing Hostinger MySQL database.

It will create tables such as:

- `users`
- `services`
- `service_stages`
- `tickets`
- `ticket_stage_events`
- `ticket_files`
- `blog_posts`
- `settings`

Do not run `php artisan migrate:fresh` in production because it deletes all database data.

## Step 10: Create The Initial Data

Run this on the server:

```bash
php artisan db:seed --force
```

This creates:

- The first super admin user from `SUPER_ADMIN_EMAIL` and `SUPER_ADMIN_PASSWORD`.
- Initial services.
- Initial workflow stages.
- Initial settings.
- Demo data for testing the admin interface.

After the first successful login, you can later remove demo content from the admin area or database if you do not want it in production.

## Step 11: Create The Storage Link

Run this on the server:

```bash
php artisan storage:link
```

This allows public assets stored by Laravel, such as branding uploads, to be available from the browser.

Uploaded project files remain private and are downloaded through protected Laravel routes.

## Step 12: Optimize Laravel For Production

Run this on the server:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

This makes Laravel faster in production.

If you later change `.env`, run:

```bash
php artisan config:clear
php artisan config:cache
```

## Step 13: Point The Domain To Laravel's `public` Folder

This is the most important deployment rule.

Your domain `ignesstudio.com` must point to Laravel's `public` directory:

```text
/var/www/igna-studio/public
```

Do not point the domain to:

```text
/var/www/igna-studio
```

The project root contains private files like `.env`, `storage`, `vendor`, and application source code. Those files must never be publicly exposed.

### If You Are Using A VPS

Configure Nginx or Apache so the document root is:

```text
/var/www/igna-studio/public
```

### If You Are Using Hostinger hPanel / Shared Hosting

Use Hostinger's domain or website settings so the website serves only Laravel's `public` directory.

If Hostinger forces the website to use `public_html`, the safe structure is:

- Keep the Laravel project outside `public_html` if possible.
- Make `public_html` expose only the contents of Laravel's `public` directory.
- Never expose `.env`, `storage`, `vendor`, `app`, `database`, or `config` directly.

## Step 14: Test The Website

Open:

```text
https://ignesstudio.com
```

Then test:

- Public landing page loads.
- Language switch works.
- Login page opens.
- Admin login works.
- `/admin` opens after login.
- Public request form creates a ticket.
- Ticket tracking works with ticket code and email.
- File upload works for a small test file.

## Future Updates

After the first deployment, you do not need to clone again.

On your local computer:

```bash
git add .
git commit -m "Describe your change"
git push origin main
```

Then connect to the server by SSH:

```bash
ssh your_server_user@your_server_ip
```

Go to the project folder:

```bash
cd /var/www/igna-studio
```

Run the deployment script:

```bash
bash scripts/deploy-hostinger.sh
```

That script pulls the latest code, installs dependencies, builds assets, runs migrations, and refreshes Laravel caches.

## Common Problems

### The Home Page Shows A Laravel Error

Run:

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

Then check `.env` carefully.

### The Database Connection Fails

Check these values in `.env`:

```env
DB_HOST=
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=
```

The database must already exist in Hostinger before running migrations.

### The Domain Shows Files Instead Of The Website

The domain is probably pointing to the wrong folder.

It must point to:

```text
igna-studio/public
```

Not:

```text
igna-studio
```

### CSS Or JavaScript Looks Missing

Run:

```bash
npm ci
npm run build
php artisan view:clear
```

### Uploaded Files Do Not Work

Run:

```bash
php artisan storage:link
```

Also make sure the server has write permission for:

```text
storage/
bootstrap/cache/
```

## Security Reminders

- Do not upload `.env` to GitHub.
- Do not upload SSH private keys to GitHub.
- Keep `APP_DEBUG=false` in production.
- Use a strong `SUPER_ADMIN_PASSWORD`.
- Keep `GOOGLE_DRIVE_ENABLED=false` until the Drive feature is intentionally configured.
- Do not run destructive database commands in production.

## Useful Hostinger References

- Git deployment in hPanel: https://www.hostinger.com/support/1583302-how-to-deploy-a-git-repository-in-hostinger/
- Adding SSH keys to GitHub from Hostinger: https://www.hostinger.com/support/1583773-how-to-add-your-ssh-key-to-github-bitbucket-in-hostinger/
- Laravel deployment on Hostinger VPS: https://www.hostinger.com/tutorials/how-to-deploy-laravel
