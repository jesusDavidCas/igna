# Hostinger Deployment

This project is ready to be deployed from GitHub and then updated from the server with `git pull`.

## Recommended Path

Use a private GitHub repository and deploy to a Hostinger VPS or a Hostinger plan that supports Laravel, SSH, Composer, PHP 8.3+, MySQL, and a public web root pointing to Laravel's `public` directory.

Official Hostinger references:

- Git deployment in hPanel: https://www.hostinger.com/support/1583302-how-to-deploy-a-git-repository-in-hostinger/
- Adding Hostinger SSH keys to GitHub: https://www.hostinger.com/support/1583773-how-to-add-your-ssh-key-to-github-bitbucket-in-hostinger/
- Laravel deployment on Hostinger VPS: https://www.hostinger.com/tutorials/how-to-deploy-laravel

## First Server Setup

```bash
cd /var/www
git clone git@github.com:YOUR_USER/YOUR_REPOSITORY.git igna-studio
cd igna-studio

cp .env.example .env
nano .env

composer install --no-dev --optimize-autoloader
npm ci
npm run build

php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Required Production `.env` Values

Set these before running `php artisan db:seed --force` in production:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password

SUPER_ADMIN_EMAIL=your-admin-email@example.com
SUPER_ADMIN_PASSWORD=use-a-strong-password-here

GOOGLE_DRIVE_ENABLED=false
```

## Updating Later

After the first deploy, future updates can be pulled with:

```bash
cd /var/www/igna-studio
bash scripts/deploy-hostinger.sh
```

## Important Notes

- Do not commit `.env`, service account JSON files, database dumps, or storage files.
- If Hostinger deploys into `public_html`, make sure `public_html` serves Laravel's `public` directory contents, not the project root.
- Do not run `migrate:fresh` in production because it deletes database data.
- Google Drive is intentionally disabled for now. The code remains available for a future activation.
