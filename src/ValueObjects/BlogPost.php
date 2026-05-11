<?php

namespace BristolDigital\QwikBlog\ValueObjects;

use BristolDigital\QwikBlog\Services\BlogImageService;
use Illuminate\Support\Str;
use Carbon\Carbon;

#[\AllowDynamicProperties]
class BlogPost
{
    /**
     * @param  array<int,string>  $categories
     * @param  array<int,string>  $tags
     */
    public function __construct(
        public string $slug,
        public string $title,
        public string $subtitle,
        public string $summary,
        public array $categories,
        public array $tags,
        public string $heroImage,
        public string $author,
        public Carbon $date,
        public string $content,
        public string $filepath,
    ) {}

    public function isPublished(): bool
    {
        return $this->date->lte(Carbon::now());
    }

    public function isScheduled(): bool
    {
        return !$this->isPublished();
    }

    public function effectiveHeroImage(): string
    {
        if ($this->heroImage !== '') {
            return $this->heroImage;
        }
        $images = BlogImageService::getImagesForPost($this->slug);
        return $images[0]['path'] ?? '';
    }

    public function galleryImages(): array
    {
        return BlogImageService::getImagesForPost($this->slug);
    }

    public function displayImages(): array
    {
        $gallery = $this->galleryImages();
        if (count($gallery) > 0) {
            return $gallery;
        }
        if ($this->heroImage !== '') {
            return [['path' => $this->heroImage]];
        }
        return [];
    }

    public function wordCount(): int
    {
        return str_word_count(strip_tags($this->content));
    }

    /**
     * Estimated reading time. WPM defaults to qwikblog.reading_wpm config
     * (200 by default — standard for prose). Pass an explicit $wpm to
     * override per-call.
     */
    public function readingTime(?int $wpm = null): int
    {
        $wpm ??= (int) config('qwikblog.reading_wpm', 200);
        if ($wpm <= 0) {
            $wpm = 200;
        }
        return max(1, (int) ceil($this->wordCount() / $wpm));
    }

    public static function fromFile(string $filepath): self
    {
        $filename = basename($filepath, '.md');
        $raw = file_get_contents($filepath);

        preg_match('/^---\s*\n(.*?)\n---\s*\n?(.*)$/s', $raw, $matches);

        if (!$matches) {
            throw new \Exception("Invalid post format: {$filepath}");
        }

        $frontMatter = self::parseFrontMatter($matches[1]);
        $markdown = $matches[2] ?? '';

        if (!preg_match('/^(\d{4}-\d{2}-\d{2})-(.+)$/', $filename, $dateMatches)) {
            throw new \Exception("Invalid filename format. Expected: YYYY-MM-DD-slug.md");
        }

        $date = isset($frontMatter['date']) && $frontMatter['date'] !== ''
            ? Carbon::parse($frontMatter['date'])
            : Carbon::parse($dateMatches[1]);

        $slug = $dateMatches[2];

        $categoriesRaw = $frontMatter['categories'] ?? $frontMatter['category'] ?? '';
        $categories = self::parseList($categoriesRaw);

        $tags = self::parseList($frontMatter['tags'] ?? '');

        return new self(
            slug: $slug,
            title: $frontMatter['title'] ?? 'Untitled',
            subtitle: $frontMatter['subtitle'] ?? '',
            summary: $frontMatter['summary'] ?? '',
            categories: $categories,
            tags: $tags,
            heroImage: $frontMatter['hero_image'] ?? '',
            author: $frontMatter['author'] ?? '',
            date: $date,
            content: Str::markdown($markdown),
            filepath: $filepath,
        );
    }

    public static function buildFilename(Carbon $date, string $slug): string
    {
        return $date->format('Y-m-d') . '-' . Str::slug($slug) . '.md';
    }

    public static function toMarkdown(array $data, string $body): string
    {
        $lines = ['---'];

        $fields = ['title', 'subtitle', 'summary', 'categories', 'tags', 'hero_image', 'author', 'date'];
        foreach ($fields as $key) {
            $value = $data[$key] ?? null;
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            $value = (string) ($value ?? '');
            if ($value !== '') {
                $lines[] = $key . ': ' . self::yamlEscape($value);
            }
        }

        $lines[] = '---';

        return implode("\n", $lines) . "\n" . trim($body) . "\n";
    }

    public function rawBody(): string
    {
        $raw = file_get_contents($this->filepath);
        if (preg_match('/^---\s*\n(.*?)\n---\s*\n?(.*)$/s', $raw, $matches)) {
            return $matches[2] ?? '';
        }
        return $raw;
    }

    /**
     * @return array<int,string>
     */
    public static function parseList(string $value): array
    {
        $value = trim($value);
        if ($value === '') {
            return [];
        }
        $value = trim($value, "[]");
        $parts = array_map('trim', explode(',', $value));
        return array_values(array_filter($parts, fn($t) => $t !== ''));
    }

    /**
     * Hand-rolled YAML parser for post front matter.
     *
     * Supports two forms for list values, both producing the same internal
     * representation (a comma-separated string that downstream `parseList`
     * splits):
     *
     *   Inline:
     *     categories: News, Updates
     *
     *   Multi-line YAML list:
     *     categories:
     *       - News
     *       - Updates
     *
     * The package's own writer (toMarkdown) always emits the inline form,
     * so a freshly admin-created post round-trips identically. The multi-
     * line form is supported because it's what people instinctively reach
     * for when authoring posts by hand or pasting from snippets.
     *
     * @return array<string,string>
     */
    private static function parseFrontMatter(string $yaml): array
    {
        $lines = explode("\n", trim($yaml));
        $data = [];

        $listKey = null;
        $list = [];

        $flushList = function () use (&$listKey, &$list, &$data): void {
            if ($listKey !== null) {
                $data[$listKey] = implode(', ', $list);
                $listKey = null;
                $list = [];
            }
        };

        foreach ($lines as $line) {
            // Multi-line YAML list continuation: "  - item"
            if ($listKey !== null && preg_match('/^\s+-\s*(.*)$/', $line, $m)) {
                $list[] = self::yamlUnquote($m[1]);
                continue;
            }

            // Anything else terminates the current list (if any)
            $flushList();

            if (strpos($line, ':') !== false) {
                [$key, $value] = explode(':', $line, 2);
                $key = trim($key);
                $value = trim(self::yamlUnquote($value));

                if ($value === '') {
                    // Empty value — this might be a list opener.
                    // Mark the key as pending; subsequent "- item" lines will
                    // populate it. If no list follows, $data[$key] stays ''.
                    $listKey = $key;
                    $list = [];
                    $data[$key] = '';
                } else {
                    $data[$key] = $value;
                }
            }
        }

        // Flush any list that ran to the end of the front matter
        $flushList();

        return $data;
    }

    private static function yamlEscape(string $value): string
    {
        if (preg_match('/[:#\n"\'\\\\]/', $value) || trim($value) !== $value) {
            return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $value) . '"';
        }
        return $value;
    }

    private static function yamlUnquote(string $value): string
    {
        $value = trim($value);
        if (strlen($value) >= 2) {
            $first = $value[0];
            $last = $value[strlen($value) - 1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $inner = substr($value, 1, -1);
                if ($first === '"') {
                    $inner = str_replace(['\\"', '\\\\'], ['"', '\\'], $inner);
                }
                return $inner;
            }
        }
        return $value;
    }
}
