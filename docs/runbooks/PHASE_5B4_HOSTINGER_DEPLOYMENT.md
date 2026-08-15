# Phase 5B.4 Hostinger Deployment Runbook

Scope: Blog publishing article typography, route-backed Blog header image delivery, and CommonMark security lockfile update.

No production commands were executed during local release preparation.

## Pre-Deployment Checks

Run from the Laravel root on the production target:

```bash
git branch --show-current
git rev-parse HEAD
php artisan route:list --except-vendor | grep 'blog.header-image'
php artisan route:list --except-vendor | grep 'team.photo'
test -d storage/app/public/blog/headers || mkdir -p storage/app/public/blog/headers
test -w storage/app/public/blog/headers && echo "BLOG_HEADER_STORAGE_WRITABLE=PASS"
```

## Deployment Behavior

Existing Blog post records remain valid when `header_image_path` is under:

```text
blog/headers/
```

After deployment, public Blog article header images should use:

```text
/blog/{blog-post-slug}/header-image
```

They should not use:

```text
/storage/blog/headers/...
```

## Public Asset Deployment

Deploy only the verified public artifact contents to the configured public bridge:

- `public/build/`
- favicon assets present in `public/`
- web app manifest if present

Do not deploy `.env`, source code, `vendor/`, `node_modules/`, storage uploads, QA output, screenshots, Graphify output, or local databases through the public artifact.

## Post-Deployment Laravel Commands

Run the standard production cache rebuild:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Smoke Test

Use a published non-sensitive Blog article with a stored header image:

```bash
curl -I https://ignastudio.com/igna-app/blog/{blog-post-slug}
curl -I https://ignastudio.com/igna-app/blog/{blog-post-slug}/header-image
```

Expected article response:

```text
HTTP/2 200
```

Expected header image response:

```text
HTTP/2 200
content-type: image/jpeg
x-content-type-options: nosniff
cache-control: public, max-age=604800
```

The content type may be `image/png` or `image/webp` when the stored header image uses that format.

## Admin Check

In the Blog administration form, open a post with a header image and confirm the preview thumbnail loads through the route-backed image URL.

Do not paste secrets, cookies, private paths, signed URLs, or credential contents into deployment notes.
