<?php

use BristolDigital\QwikBlog\ValueObjects\BlogPost;

/**
 * Tests for YAML front matter parsing.
 *
 * parseFrontMatter is private, so we exercise it through fromFile() —
 * the public entry point used by BlogService when reading posts off disk.
 * Each test writes a temp .md file with the YAML under test, parses it,
 * and asserts on the resulting BlogPost's properties.
 *
 * v1.1.5 added multi-line YAML list support. v1.1.4 and earlier only
 * understood inline (comma-separated) lists.
 */

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir() . '/qwikblog-test-' . uniqid();
    mkdir($this->tempDir);
});

afterEach(function () {
    array_map('unlink', glob($this->tempDir . '/*'));
    @rmdir($this->tempDir);
});

/**
 * Write a markdown post with the given front matter and parse it.
 * Returns the resulting BlogPost.
 */
function writeAndParse(string $frontMatter, string $body = 'Test body content.'): BlogPost
{
    $filename = '2026-05-11-test-post.md';
    $path = test()->tempDir . '/' . $filename;
    $content = "---\n{$frontMatter}\n---\n{$body}\n";
    file_put_contents($path, $content);
    return BlogPost::fromFile($path);
}

// ─── Inline form (regression — v1.1.4 behaviour must keep working) ───

describe('inline (comma-separated) form', function () {

    it('parses a single category', function () {
        $post = writeAndParse("title: Hello\ncategories: News");
        expect($post->categories)->toBe(['News']);
    });

    it('parses multiple comma-separated categories', function () {
        $post = writeAndParse("title: Hello\ncategories: News, Updates, Announcements");
        expect($post->categories)->toBe(['News', 'Updates', 'Announcements']);
    });

    it('parses comma-separated tags', function () {
        $post = writeAndParse("title: Hello\ntags: launch, hello-world, v1");
        expect($post->tags)->toBe(['launch', 'hello-world', 'v1']);
    });

    it('handles extra whitespace around commas', function () {
        $post = writeAndParse("title: Hello\ncategories:   News  ,  Updates  ,Announcements");
        expect($post->categories)->toBe(['News', 'Updates', 'Announcements']);
    });

    it('returns empty array when categories field is empty', function () {
        $post = writeAndParse("title: Hello\ncategories: ");
        expect($post->categories)->toBe([]);
    });

    it('returns empty array when categories field is absent', function () {
        $post = writeAndParse("title: Hello");
        expect($post->categories)->toBe([]);
        expect($post->tags)->toBe([]);
    });
});

// ─── Multi-line YAML list form (new in v1.1.5) ───

describe('multi-line YAML list form', function () {

    it('parses a multi-line categories list', function () {
        $post = writeAndParse(<<<'YAML'
title: Hello
categories:
  - News
  - Updates
  - Announcements
YAML);
        expect($post->categories)->toBe(['News', 'Updates', 'Announcements']);
    });

    it('parses a multi-line tags list', function () {
        $post = writeAndParse(<<<'YAML'
title: Hello
tags:
  - launch
  - hello-world
YAML);
        expect($post->tags)->toBe(['launch', 'hello-world']);
    });

    it('parses both categories and tags as multi-line lists', function () {
        $post = writeAndParse(<<<'YAML'
title: Hello
categories:
  - News
  - Updates
tags:
  - launch
  - hello-world
YAML);
        expect($post->categories)->toBe(['News', 'Updates']);
        expect($post->tags)->toBe(['launch', 'hello-world']);
    });

    it('handles a single-item list', function () {
        $post = writeAndParse(<<<'YAML'
title: Hello
categories:
  - News
YAML);
        expect($post->categories)->toBe(['News']);
    });

    it('handles a list at the end of front matter', function () {
        // The "flushList at EOF" path — no terminating non-list line follows
        $post = writeAndParse(<<<'YAML'
title: Hello
tags:
  - alpha
  - beta
YAML);
        expect($post->tags)->toBe(['alpha', 'beta']);
    });

    it('handles ampersand in list values without YAML anchor confusion', function () {
        // This is the exact bug that originally bit us: "Anxiety & Overwhelm"
        // would silently parse as nothing under a strict YAML parser because
        // & is the anchor character. The package's permissive parser must
        // accept it as a literal.
        $post = writeAndParse(<<<'YAML'
title: Hello
categories:
  - Anxiety & Overwhelm
  - Self-Understanding
YAML);
        expect($post->categories)->toBe(['Anxiety & Overwhelm', 'Self-Understanding']);
    });

    it('handles other YAML-special characters in list values', function () {
        $post = writeAndParse(<<<'YAML'
title: Hello
tags:
  - tag-with-hyphen
  - 12-beat
  - siglo-xix
YAML);
        expect($post->tags)->toBe(['tag-with-hyphen', '12-beat', 'siglo-xix']);
    });
});

// ─── Mixed forms ───

describe('mixed forms', function () {

    it('handles categories inline and tags multi-line', function () {
        $post = writeAndParse(<<<'YAML'
title: Hello
categories: News, Updates
tags:
  - launch
  - hello-world
YAML);
        expect($post->categories)->toBe(['News', 'Updates']);
        expect($post->tags)->toBe(['launch', 'hello-world']);
    });

    it('handles categories multi-line and tags inline', function () {
        $post = writeAndParse(<<<'YAML'
title: Hello
categories:
  - News
  - Updates
tags: launch, hello-world
YAML);
        expect($post->categories)->toBe(['News', 'Updates']);
        expect($post->tags)->toBe(['launch', 'hello-world']);
    });

    it('handles list followed by a non-list scalar field', function () {
        // The list must be flushed when a new key appears
        $post = writeAndParse(<<<'YAML'
title: Hello
categories:
  - News
  - Updates
author: Jane Editor
summary: A short summary.
YAML);
        expect($post->categories)->toBe(['News', 'Updates']);
        expect($post->author)->toBe('Jane Editor');
        expect($post->summary)->toBe('A short summary.');
    });
});

// ─── Other front-matter fields keep working alongside the list changes ───

describe('other fields unaffected by list-parsing changes', function () {

    it('parses scalar fields normally with multi-line lists present', function () {
        $post = writeAndParse(<<<'YAML'
title: My Test Post
subtitle: An Optional Subtitle
summary: Short excerpt for listings.
author: Jane Editor
hero_image: /images/blog/test/1.jpg
categories:
  - News
tags:
  - launch
YAML);
        expect($post->title)->toBe('My Test Post');
        expect($post->subtitle)->toBe('An Optional Subtitle');
        expect($post->summary)->toBe('Short excerpt for listings.');
        expect($post->author)->toBe('Jane Editor');
        expect($post->heroImage)->toBe('/images/blog/test/1.jpg');
    });

    it('still reads the legacy singular "category" field', function () {
        // Back-compat path in fromFile: $categoriesRaw = $frontMatter['categories'] ?? $frontMatter['category']
        $post = writeAndParse("title: Hello\ncategory: News");
        expect($post->categories)->toBe(['News']);
    });
});

// ─── Round-trip via toMarkdown ───

describe('round-trip serialisation', function () {

    it('preserves data when written inline and read back', function () {
        $original = [
            'title' => 'Test',
            'categories' => ['News', 'Updates'],
            'tags' => ['launch', 'hello'],
            'author' => 'Jane',
            'date' => '2026-05-11',
        ];
        $markdown = BlogPost::toMarkdown($original, 'Body content.');

        $path = $this->tempDir . '/2026-05-11-roundtrip.md';
        file_put_contents($path, $markdown);

        $post = BlogPost::fromFile($path);
        expect($post->categories)->toBe(['News', 'Updates']);
        expect($post->tags)->toBe(['launch', 'hello']);
        expect($post->author)->toBe('Jane');
    });

    it('reads a multi-line list and writes it back as inline (canonical form)', function () {
        $post = writeAndParse(<<<'YAML'
title: Test
categories:
  - News
  - Updates
tags:
  - alpha
  - beta
date: 2026-05-11
YAML);

        $rewritten = BlogPost::toMarkdown([
            'title' => $post->title,
            'categories' => $post->categories,
            'tags' => $post->tags,
            'date' => $post->date->format('Y-m-d'),
        ], 'Body.');

        // The canonical writer always emits inline form
        expect($rewritten)->toContain('categories: News, Updates');
        expect($rewritten)->toContain('tags: alpha, beta');

        // And re-parsing the rewritten form gives the same arrays
        $path = $this->tempDir . '/2026-05-11-rewritten.md';
        file_put_contents($path, $rewritten);
        $reparsed = BlogPost::fromFile($path);

        expect($reparsed->categories)->toBe($post->categories);
        expect($reparsed->tags)->toBe($post->tags);
    });
});
