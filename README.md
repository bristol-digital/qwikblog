# QwikBlog

A simple, file-based blog system for Laravel with Tailwind CSS. No database required.

## Features

- 📝 **Markdown-based** - Write posts in simple markdown files
- 🎨 **Tailwind CSS** - Beautiful, responsive design out of the box
- ⚡ **Fast** - File-based with caching
- 🚀 **Zero config** - Drop in and start writing
- 🎯 **Customizable** - Publish views and config to customize

## Requirements

- PHP 8.1+
- Laravel 10.x or 11.x
- Tailwind CSS

## Installation
```bash
composer require bristol-digital/qwikblog
```

## Quick Start

1. **Publish the config:**
```bash
php artisan vendor:publish --tag=qwikblog-config
```

2. **Create posts directory:**
```bash
mkdir resources/posts
```

3. **Configure Tailwind** to include package views in `tailwind.config.js`:
```js
content: [
    './resources/**/*.blade.php',
    './vendor/bristol-digital/qwikblog/src/resources/**/*.blade.php',
],
plugins: [
    require('@tailwindcss/typography'),
],
```

4. **Install Tailwind Typography:**
```bash
npm install @tailwindcss/typography
```

5. **Create your first post** `resources/posts/2024-11-20-hello-world.md`:
```markdown
---
title: Hello World
subtitle: My first blog post
category: General
hero_image: /images/hero.jpg
---

This is my first blog post using QwikBlog!

## It supports markdown

- Easy to write
- Easy to read
- Easy to maintain
```

6. **Visit your blog:**
```
http://yourapp.test/blog
```

## Usage

### Creating Posts

Create markdown files in `resources/posts/` with the naming format:
```
YYYY-MM-DD-slug.md
```

Example: `2024-11-20-my-awesome-post.md`

### Post Format
```markdown
---
title: Post Title
subtitle: Post subtitle
category: Category Name
hero_image: /path/to/image.jpg
---

Your markdown content here...
```

### Configuration

Edit `config/qwikblog.php` to customize:
```php
return [
    'posts_path' => resource_path('posts'),
    'layout' => 'layouts.app',  // Your app layout
    'route' => [
        'prefix' => 'blog',  // Change to 'articles', 'news', etc.
        'middleware' => ['web'],
        'name_prefix' => 'blog.',
    ],
    'per_page' => 12,
    'cache_duration' => 3600,
];
```

### Customizing Views

Publish views to customize the design:
```bash
php artisan vendor:publish --tag=qwikblog-views
```

Views will be published to `resources/views/vendor/qwikblog/`

### Clearing Cache

After adding new posts:
```bash
php artisan cache:clear
```

## Routes

The package automatically registers these routes:

- `GET /blog` - Blog index with pagination
- `GET /blog/{slug}` - Individual blog post

## License

MIT
