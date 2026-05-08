# QwikBlog

A simple, file-based blog for Laravel — headless front-end + lightweight admin
with a Livewire-powered image gallery and a WYSIWYG body editor. No database
required. Posts live in `resources/posts/` as Markdown files with YAML front
matter.

## Installation

```bash
composer require bristol-digital/qwikblog
```

The package's service provider auto-registers via Laravel's package discovery.
Then publish the config and the admin's JS entry point:

```bash
php artisan vendor:publish --tag=qwikblog-config
php artisan vendor:publish --tag=qwikblog-admin-js
```

The admin JS publish puts `resources/js/qwikblog-admin.js` in your host app —
a tiny Vite entry that bundles Toast UI Editor for the post form's body field.

Set admin credentials in `.env` (see [Configuration](#configuration)):

```dotenv
ADMIN_USERNAME=your-username
ADMIN_PASSWORD=your-password
```

Wire the admin's JS into your Vite build. In `vite.config.js`:

```js
input: [
    'resources/css/app.css',
    'resources/js/app.js',
    'resources/js/qwikblog-admin.js',  // <-- add this
],
```

Install the editor's npm dependency:

```bash
npm install @toast-ui/editor
```

Make sure your host app's Tailwind setup includes the package's blade files
in its content scanning (so admin styling Just Works) and has the typography
plugin and Alpine for the public views. In `resources/css/app.css` (or your
Tailwind config):

```css
@import "tailwindcss";
@plugin "@tailwindcss/typography";
@source "../../vendor/bristol-digital/qwikblog/resources/views/**/*.blade.php";
```

In `resources/js/app.js`:

```js
import Alpine from 'alpinejs';
window.Alpine = Alpine;
Alpine.start();
```

In your host app's `app.blade.php`, make sure you have `@stack('head')`
inside `<head>` so the package can inject SEO meta. To enable RSS
autodiscovery, also add:

```html
<link rel="alternate" type="application/rss+xml" title="{{ config('app.name') }}" href="{{ url('/blog/feed.xml') }}">
```

Finally:

```bash
npm run dev
php artisan serve
```

To populate with the bundled flamenco demo content (12 posts across 4
categories with images):

```bash
php artisan vendor:publish --tag=qwikblog-seeds
php artisan blog:examples flamenco
```

Visit `/` to see the blog. Visit `/admin` to log in.

## Routes

| Route | Description |
|-------|-------------|
| `/` and `/blog` | Post index — both serve the same content |
| `/blog/{slug}` | Single post **or** category filter **or** tag filter (resolves in that order) |
| `/blog/search?q=...` | Full-text search results |
| `/blog/author/{slug}` | All posts by an author |
| `/blog/{year}` | All posts from a year |
| `/blog/{year}/{month}` | All posts from a month (e.g. `/blog/2024/11`) |
| `/blog/category/{slug}` | Category filter — explicit prefix, always works |
| `/blog/tag/{slug}` | Tag filter — explicit prefix, always works |
| `/blog/feed.xml` | RSS 2.0 feed |
| `/sitemap.xml` | XML sitemap |

Public views live at `resources/views/blog/index.blade.php` and
`show.blade.php`. Both extend the host site's `app.blade.php` layout. The
package ships a starter `app.blade.php`; host sites typically have their
own and only need `@stack('head')` somewhere in `<head>` for SEO meta to
inject correctly.

## Post fields

Posts are stored as `resources/posts/YYYY-MM-DD-slug.md`. Front matter:

```yaml
---
title: My First Post
subtitle: An optional subtitle
summary: Short excerpt — used on listings.
categories: Announcements, News
tags: launch, hello-world
hero_image: /images/blog/my-first-post/1.jpg
author: Jane Editor
date: 2024-11-15 10:00:00
---

Markdown body goes here.
```

| Field | Required | Notes |
|-------|----------|-------|
| `title` | yes | Determines the URL slug |
| `subtitle` | no | Shown on cards and post header |
| `summary` | yes | Short excerpt for listings, OG description |
| `categories` | no | Comma-separated; multi-valued |
| `tags` | no | Comma-separated; multi-valued |
| `hero_image` | no | Path under `public/`; falls back to first gallery image |
| `author` | yes | Byline; links to `/blog/author/{slug}` |
| `date` | yes | `YYYY-MM-DD` or `YYYY-MM-DD HH:MM:SS`; future = scheduled |

The admin writes this format automatically. The legacy singular `category:`
field is still read for back-compat with older posts.

## Categories and tags

Posts can have any number of each. Both render in the right-rail sidebar
on the index page (categories as a vertical list, tags inline as
`#hashtag` chips), each with a post count. Active filter is highlighted.

| URL | Result |
|-----|--------|
| `/` or `/blog` | All published posts |
| `/blog/palos` | Posts in **Palos** (flat URL style, the default) |
| `/blog/category/palos` | Same — explicit prefix; always works regardless of style |
| `/blog/gitano` | Posts tagged **gitano** |
| `/blog/tag/gitano` | Same — explicit prefix |

One filter at a time — clicking a different chip replaces the active
filter rather than stacking with AND. Each post's own categories and tags
also appear in the show-page sidebar with counts.

If no published post has any categories, the categories section hides
entirely. Same for tags. The view checks `count(...) > 0` before
rendering.

### URL collision

Posts win on slug collision. A post titled "Palos" would shadow the Palos
category filter at `/blog/palos`. If you need strict separation, set
`QWIKBLOG_TAXONOMY_URL_STYLE=prefixed` — chip links then go to
`/blog/category/palos` etc. and never collide with post slugs.

## Scheduling

Set `date` to a future timestamp. The post will:

- Show in the admin index with a yellow **Scheduled** badge and a relative
  countdown ("in 3 weeks") that updates on the 30-second `wire:poll`
- Be hidden from the public `/blog` until the date passes
- Return 404 at its slug to public visitors but render normally for
  logged-in admins (preview)
- Auto-publish silently when the date arrives — no cron job required

## Search

`/blog/search?q=...` runs an AND match across title, subtitle, summary,
author, categories, tags, and rendered body content. Multi-term queries
are supported (`jerez bulerías` returns posts containing both terms).
Pure-PHP `str_contains` — fine up to a few thousand posts; past that, swap
in a real index.

Search results pages are `noindex` by default — every unique query is a
unique URL and not worth indexing.

## Author pages

`/blog/author/{slug}` lists every published post by an author. The author
byline on each show page links here automatically.

## Archive

`/blog/{year}` and `/blog/{year}/{month}` return posts from that period.
The sidebar archive nav appears when at least 2 distinct months are
represented; below that it stays hidden (a single-month archive is just
the index).

## Related posts

Each post page shows up to 3 related posts at the bottom, scored by
taxonomy overlap:

```
score = (shared_tags × 2) + (shared_categories × 1)
```

Posts scoring 0 are excluded entirely — better to hide the section than
show weak recommendations. Weights and limit are configurable.

## RSS, sitemap, SEO

| Feature | Where |
|---------|-------|
| RSS 2.0 feed | `/blog/feed.xml` (most recent 50 posts) |
| Sitemap | `/sitemap.xml` (every post + filter URL + author + archive) |
| Autodiscovery `<link>` | In every page's `<head>` |
| Open Graph + Twitter Card | On every post page |
| Canonical URL | Every page |
| Reading time | "X min read" on post pages, computed at 200 wpm by default |
| Robots noindex | Search results, scheduled-post admin previews |

Reference the sitemap from `public/robots.txt`:

```
Sitemap: https://your-site.test/sitemap.xml
```

## Image gallery

Each post has its own folder at `public/images/blog/{slug}/`. Images are
numbered `1.jpg`, `2.jpg`, … and ordering is set by the numeric filename.

The Livewire gallery (`/admin/posts/{slug}/images`) lets you:

- Drag-and-drop upload (multi-select, JPG/PNG/GIF, max 20 MB each)
- Resize and re-orient automatically (max 1600×1200, 85% quality JPG; GIFs
  pass through untouched so animations are preserved)
- Reorder images by drag
- Delete individual images
- Copy a path to the clipboard for pasting into the post's `hero_image` field

If `hero_image` is left blank in the front matter, the first numerically-
named image is used automatically. Set it explicitly to override.

When a post is renamed, its image folder moves with it. When a post is
deleted, its image folder is deleted too.

On the public show page, multiple images render as a crossfading carousel
with prev/next, dot navigation, a counter, and a thumbnail strip.

## Body editor (WYSIWYG)

The admin's body field uses [Toast UI Editor](https://ui.toast.com/tui-editor),
defaulting to WYSIWYG mode with a Markdown toggle in the toolbar. The
editor is bundled via Vite from `resources/js/qwikblog-admin.js` (published
into your host app on install — see [Installation](#installation)) — no
CDN dependency at runtime. Posts are stored as plain CommonMark, so files
remain hand-editable.

If you don't run the editor and just want plain markdown editing, delete
`resources/js/qwikblog-admin.js` and remove `@vite(['resources/js/qwikblog-admin.js'])`
from the admin layout (publish the views via `vendor:publish --tag=qwikblog-views`
to override). The textarea behind the editor stays usable as a markdown
fallback in any case.

## Admin

A small admin lives at `/admin` (or whatever path you set via `QWIKBLOG_ADMIN_PATH`).

### Auth — three options

The package's admin protection is configurable via `QWIKBLOG_ADMIN_MIDDLEWARE`. By default it uses the package's own self-contained auth (single shared username/password from `.env`). For sites with their own auth system, you can wire the blog admin into that instead so users only log in once.

**Option 1 — Self-contained auth (default)**

Set credentials in `.env`:

```dotenv
ADMIN_USERNAME=your-username
ADMIN_PASSWORD=your-password
```

If either is empty, login is refused — there is no default password. Visit `/admin/login` to log in. This is the right choice for static sites, no-database deployments, or any host that doesn't already have a login system.

**Option 2 — Integrate with Laravel's auth**

If your host app already has Laravel auth set up (Breeze, Jetstream, Fortify, Sanctum, or a starter kit's bundled login), tell the package to use that instead:

```dotenv
QWIKBLOG_ADMIN_MIDDLEWARE=auth
QWIKBLOG_ADMIN_LOGOUT_ROUTE=logout
```

Now any user logged in to your host app can access the blog admin at `/admin` (or `QWIKBLOG_ADMIN_PATH`). The logout button in the admin chrome calls your host's logout endpoint. You can ignore `ADMIN_USERNAME` / `ADMIN_PASSWORD` entirely.

To restrict the blog admin to specific users (not just any authenticated user), combine with a Laravel gate:

```dotenv
QWIKBLOG_ADMIN_MIDDLEWARE=auth,can:manage-blog
```

Then define the gate in your host app's `AppServiceProvider`:

```php
Gate::define('manage-blog', fn(User $user) => $user->isAdmin());
```

**Option 3 — Custom middleware**

Any middleware alias your host registers will work. Examples:

```dotenv
# Filament panel auth
QWIKBLOG_ADMIN_MIDDLEWARE=panel.auth

# Multiple middlewares stacked
QWIKBLOG_ADMIN_MIDDLEWARE=auth,verified,role:editor

# Fully custom
QWIKBLOG_ADMIN_MIDDLEWARE=my-blog-gate
```

### Routes

| Route | Description |
|-------|-------------|
| `/admin/login` | Login form (used only with default `admin` middleware) |
| `/admin` → `/admin/posts` | Posts index (Livewire — search, filters, pagination, polls every 30s) |
| `/admin/posts/create` | New post |
| `/admin/posts/{slug}/edit` | Edit post |
| `/admin/posts/{slug}/images` | Manage images (Livewire) |

The package's `AdminAuth` middleware (alias `admin`) uses session auth, file-based by default (`SESSION_DRIVER=file`), so no database is required.

### Filtering

The admin posts index has four filters that combine with AND:

| Filter | What it matches |
|--------|-----------------|
| **Search** | Title, subtitle, summary, author. Debounced 300ms |
| **Category** | Single category — drop-down of every category across all posts |
| **Tag** | Single tag |
| **Status** | All / Published / Scheduled |

Filter state is preserved in the URL — `/admin/posts?status=scheduled&category=Palos`
is bookmarkable and reload-safe. The header shows totals
(`12 published · 3 scheduled · 15 total`); the **Status** dropdown shows
the same counts inline so editors can find scheduled posts without first
applying a filter.

Page paginates at 30 by default — useful when scheduling content months
ahead. Override via `QWIKBLOG_ADMIN_PER_PAGE`.

Scheduled rows show their relative publish time underneath the date
("in 3 weeks"), updating on the 30-second `wire:poll`.

## Bulk import

For migrating content or seeding test data:

```bash
php artisan blog:import path/to/manifest.php
```

The manifest is a `.php` file returning an array (recommended for long
bodies — heredoc is friendlier than JSON-escaping) or `.json`.

```php
<?php
return [
    [
        'title' => 'My Imported Post',
        'date' => '2025-01-15',
        'subtitle' => '...',
        'summary' => '...',
        'categories' => ['News'],
        'tags' => ['launch'],
        'author' => 'Jane Editor',
        'hero_image_url' => 'https://example.com/photo.jpg',
        'gallery_image_urls' => [
            'https://example.com/photo-2.jpg',
        ],
        'body' => <<<'MD'
# Markdown body here
MD,
    ],
];
```

Image URLs (when present) are downloaded into `public/images/blog/{slug}/`
at import time. Failures are non-fatal — posts are still created.

| Flag | Effect |
|------|--------|
| `--dry-run` | Preview without writing |
| `--skip-images` | Create posts, skip downloads |
| `--overwrite` | Replace any existing post with a matching slug |

## Example post sets

Bundled example sets live in `resources/seeds/{name}-posts.php`:

```bash
php artisan blog:examples flamenco
```

| Set | Description |
|-----|-------------|
| `flamenco` | 12 educational posts about flamenco — palos, history, compás, maestros |

Same flags as `blog:import` (`--overwrite`, `--skip-images`, `--dry-run`).

To list available sets: `php artisan blog:examples nonexistent` shows
what's in `resources/seeds/`. To add your own, drop `<name>-posts.php`
into that directory and it's runnable as `php artisan blog:examples <name>`.

## Customising the look

The public-facing views (`resources/views/blog/index.blade.php`,
`show.blade.php`) use Vite-built Tailwind. Article body styling uses the
[Tailwind Typography plugin](https://github.com/tailwindlabs/tailwindcss-typography)
— the `prose` class on `<article>` is the source of truth.

The index uses a 5-column grid on desktop (4 columns of posts + a thinner
sidebar column with categories, archive, tags, search). Sidebar widgets
sit in `bg-gray-100` cards. Stacks to a single column on mobile.

The admin uses CDN Tailwind to stay self-contained, plus a Vite-bundled
JS file (`resources/js/qwikblog-admin.js` in your host app, published from
the package on install) that provides Toast UI Editor.

## Configuration

All package configuration lives in `config/qwikblog.php` and reads from
`.env` overrides. Every value is optional except admin credentials.

| Env var | Default | What it does |
|---------|---------|--------------|
| `ADMIN_USERNAME` | — | **Required.** Admin login |
| `ADMIN_PASSWORD` | — | **Required.** Admin login |
| `QWIKBLOG_POSTS_PATH` | `resources/posts` | Where the .md post files live |
| `QWIKBLOG_CACHE_DURATION` | `3600` | Post cache TTL in seconds |
| `QWIKBLOG_LANGUAGE` | app locale | RSS feed `<language>` tag |
| `QWIKBLOG_READING_WPM` | `200` | Words per minute for "X min read" |
| `QWIKBLOG_PER_PAGE` | `12` | Posts per page on public blog |
| `QWIKBLOG_ADMIN_PER_PAGE` | `30` | Posts per page in admin table |
| `QWIKBLOG_FEED_LIMIT` | `50` | Max items in RSS feed |
| `QWIKBLOG_ADMIN_PATH` | `admin` | Admin URL prefix |
| `QWIKBLOG_ADMIN_MIDDLEWARE` | `admin` | Middleware that gates the admin (set to `auth` to use Laravel's auth) |
| `QWIKBLOG_ADMIN_LOGOUT_ROUTE` | `admin.logout` | Route name the admin's logout button posts to |
| `QWIKBLOG_LAYOUT` | `app` | Layout name the public blog views extend |
| `QWIKBLOG_TAXONOMY_URL_STYLE` | `flat` | `flat` (`/blog/{slug}`) or `prefixed` (`/blog/category/{slug}`) |
| `QWIKBLOG_RELATED_TAG_WEIGHT` | `2` | Related-post tag weight |
| `QWIKBLOG_RELATED_CATEGORY_WEIGHT` | `1` | Related-post category weight |
| `QWIKBLOG_RELATED_LIMIT` | `3` | Number of related posts shown |

After editing `.env`:

```bash
php artisan optimize:clear
```

Clears config, route, view and compiled caches in one go. Required if
you've previously run `php artisan config:cache` or are deployed in a way
that caches config.

To verify a value is being read:

```bash
php artisan tinker --execute="dump(config('qwikblog.per_page'));"
```

If the value comes back as the default when you've set the env var,
either `config/qwikblog.php` isn't at the project root, the env var is
mistyped (case-sensitive), or config is still cached.

## Cache

Posts are cached for an hour. To clear manually:

```bash
php artisan blog:refresh
```

The admin clears this cache automatically on every create/update/delete,
and `blog:import` clears it after a bulk import.

## Common commands

```bash
# Dev
npm run dev
php artisan serve

# After editing post files directly
php artisan blog:refresh

# After dropping in new components, commands or config
php artisan view:clear
php artisan optimize:clear

# Production build
npm run build
```

## Troubleshooting

| Symptom | Fix |
|---------|-----|
| Stale post data after editing files directly | `php artisan blog:refresh` |
| 500 with "undefined property" or "undefined method" on `BlogPost` | Cached `BlogPost` doesn't match the current class shape — `php artisan blog:refresh` |
| Admin pages 500 with "Unable to locate component" | Stale view cache. `php artisan view:clear` |
| WYSIWYG editor doesn't load | Browser console says "Toast UI Editor not found on window" — run `npm install @toast-ui/editor && npm run dev`. Verify `resources/js/qwikblog-admin.js` exists (publish it via `vendor:publish --tag=qwikblog-admin-js` if not) and is in your `vite.config.js` inputs |
| `/admin` shows "Admin credentials are not configured" | Set both `ADMIN_USERNAME` and `ADMIN_PASSWORD`, then `php artisan optimize:clear` |
| `QWIKBLOG_*` env var seems ignored | `php artisan optimize:clear`. Verify with `php artisan tinker --execute="dump(config('qwikblog.per_page'));"` |
| Image upload "browse and nothing happens" | Duplicate Alpine load. Make sure no `<script src=".../alpine...">` is in the admin layout — Livewire bundles its own |

## Architecture

| Path | Role |
|------|------|
| `app/ValueObjects/BlogPost.php` | Front-matter parsing, YAML serialisation, post object |
| `app/Services/BlogService.php` | Post CRUD, caching, taxonomy/archive/search/related helpers |
| `app/Services/BlogImageService.php` | Per-post image directory management |
| `app/Support/BlogUrls.php` | Taxonomy URL helper (flat/prefixed style) |
| `app/Http/Controllers/BlogController.php` | All public routes |
| `app/Http/Controllers/AdminController.php` | Admin auth + post CRUD |
| `app/Http/Middleware/AdminAuth.php` | Session-based admin guard |
| `app/Livewire/Admin/PostsIndex.php` | Live-polling admin posts table with filters |
| `app/Livewire/Admin/BlogImages.php` | Image gallery component |
| `app/Console/Commands/ImportPosts.php` | `blog:import` |
| `app/Console/Commands/InstallExamples.php` | `blog:examples` |
| `app/Console/Commands/RefreshBlog.php` | `blog:refresh` |
| `config/qwikblog.php` | Package configuration |
| `resources/posts/` | Markdown post files |
| `resources/seeds/` | Bundled example post manifests |
| `resources/views/blog/` | Public-facing views |
| `resources/views/admin/` | Admin views |

## Dependencies

PHP / Composer:

- `laravel/framework` ^12
- `livewire/livewire` ^3.7

Node / npm:

- `@toast-ui/editor` — admin body editor (Vite-bundled)
- `@tailwindcss/typography` — `prose` styling on the show page
- `alpinejs` — public-facing carousel
- `tailwindcss` ^4 with `@tailwindcss/vite`

Node / npm (in your host app):

- `@toast-ui/editor` — admin body editor (Vite-bundled via the published `qwikblog-admin.js`)
- `@tailwindcss/typography` — `prose` styling on the show page
- `alpinejs` — public-facing carousel
- `tailwindcss` ^4 with `@tailwindcss/vite`

CDN-loaded by the admin chrome only:

- Tailwind (Play CDN)
- Sortable.js (gallery reordering)

Alpine in the admin is provided by Livewire's bundled copy — do not load it separately.
