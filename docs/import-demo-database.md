# Temporary Demo Database Import

This guide is only for temporarily reviewing the platform on Hostinger with local demo data.

Use this when you want the production or staging site to look like the local seeded platform while you test pages, tickets, services, blog posts, users, and file downloads.

## Generated Local Files

Two temporary files are generated locally and ignored by Git:

```text
database/exports/igna-demo-review-data.sql
database/exports/igna-demo-private-storage.tar.gz
```

The SQL file contains demo/review data for these application tables:

- `users`
- `services`
- `service_stages`
- `tickets`
- `ticket_stage_events`
- `ticket_files`
- `blog_posts`
- `settings`

The storage archive contains the private demo files referenced by `ticket_files`.

## Important Warning

The SQL file deletes existing rows in the listed application tables before inserting the demo data.

Use it only if the Hostinger database is temporary, empty, or safe to reset.

Do not import it into a database with real client data.

## Step 1: Make Sure The Server Database Tables Exist

On Hostinger, connect by SSH and go to the project folder:

```bash
ssh your_server_user@your_server_ip
cd /var/www/igna-studio
```

Run migrations:

```bash
php artisan migrate --force
```

This creates the database tables using your Hostinger MySQL database credentials from `.env`.

## Step 2: Import The SQL File Into Hostinger MySQL

You have two options.

### Option A: Import With phpMyAdmin

1. Open Hostinger hPanel.
2. Go to Databases.
3. Open phpMyAdmin for your database.
4. Select your database.
5. Open the Import tab.
6. Upload:

```text
database/exports/igna-demo-review-data.sql
```

7. Start the import.

### Option B: Import By SSH

Upload the SQL file to the server, then run:

```bash
mysql -u your_database_user -p your_database_name < igna-demo-review-data.sql
```

When prompted, paste your database password.

## Step 3: Upload The Demo Storage Files

The database import creates file metadata only. To make demo downloads work, upload the storage archive too:

```text
database/exports/igna-demo-private-storage.tar.gz
```

Place it inside the project folder on the server, then run:

```bash
cd /var/www/igna-studio
tar -xzf igna-demo-private-storage.tar.gz -C storage/app/private
```

After extraction, the server should contain:

```text
storage/app/private/stubs/tickets/...
```

## Step 4: Clear Laravel Cache

Run:

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

If this is production, rebuild caches:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Step 5: Test The Platform

Open:

```text
https://ignastudio.com
```

Then test:

- Login.
- Admin dashboard.
- Services.
- Tickets.
- Blog.
- My Services.
- Ticket tracking.
- Download one available demo file.

## Demo Access Notes

The SQL export includes the local seeded users. For convenience, the exported super admin email is:

```text
superadmin@ignastudio.com
```

It uses the local seeded password unless you reset it after import. If you need to change a password after import, use the super admin password reset form in:

```text
/admin/users/{user}/edit
```

or use Laravel Tinker on the server.

## Removing The Temporary Demo Data Later

The cleanest path is to create a fresh production database and run only the production migrations/seeders you actually want.

If you already have real data, do not use destructive commands. Export a backup first and remove demo rows carefully from the admin panel or with reviewed SQL.
