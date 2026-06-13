# IGNA Studio SEO Notes

## Current foundation

- Canonical domain: `https://ignastudio.com`.
- `www.ignastudio.com` redirects permanently to the canonical non-www host through both Laravel middleware and the public `.htaccess` bridge.
- Public pages render canonical URLs, meta descriptions, Open Graph tags, Twitter Card tags, and JSON-LD.
- Internal/admin/client/auth/tracking surfaces are intentionally marked `noindex`.
- `sitemap.xml` is generated from public, indexable content only.
- `robots.txt` points crawlers to `https://ignastudio.com/sitemap.xml`.
- `/llms.txt` and `/markdown/*.md` provide AI-readable content mirrors without exposing private platform surfaces.
- Dummy/placeholder blog slugs are excluded through `config/igna.php`.

## Public indexed surfaces

- `/`
- `/blog`
- `/blog/{slug}` for published, non-excluded posts
- `/team/{slug}` for active team members

## Non-indexed surfaces

- `/login`
- `/forgot-password`
- `/reset-password/{token}`
- `/tracking`
- `/markdown/*`
- admin routes
- client portal routes
- proposal public review links

## Multilingual strategy

The current language switch is session-based, so Spanish and English do not yet have separate crawlable URLs. For that reason, the platform does not emit `hreflang` tags yet.

Recommended future migration:

1. Add stable localized routes such as `/es` and `/en`.
2. Give every public page an equivalent Spanish and English URL.
3. Emit reciprocal `hreflang` tags only after both URLs exist and render independently.
4. Add both language URL sets to the sitemap.

## Social image

The current default social image is `/social-card.svg`. For stronger compatibility with social crawlers, replace it later with a rendered PNG or JPG at 1200 x 630 px and update `IGNA_CANONICAL_URL` / `config/igna.php` only if the production domain changes.

## Deployment checks

After deployment, run:

```bash
cd /home/u935649387/apps/igna-studio
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Then verify:

```bash
curl -I https://www.ignastudio.com/
curl https://ignastudio.com/sitemap.xml
curl https://ignastudio.com/robots.txt
curl https://ignastudio.com/llms.txt
```
