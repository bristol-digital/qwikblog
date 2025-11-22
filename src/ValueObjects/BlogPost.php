<?php

namespace BristolDigital\QwikBlog\ValueObjects;  // ← Changed namespace

use Illuminate\Support\Str;
use Carbon\Carbon;

class BlogPost
{
    public function __construct(
        public string $slug,
        public string $title,
        public string $subtitle,
        public string $category,
        public string $heroImage,
        public string $content,
        public Carbon $date,
        public string $filepath,
    ) {}

    public static function fromFile(string $filepath): self
    {
        $filename = basename($filepath, '.md');
        $content = file_get_contents($filepath);

        preg_match('/^---\s*\n(.*?)\n---\s*\n(.*)$/s', $content, $matches);

        if (!$matches) {
            throw new \Exception("Invalid post format: {$filepath}");
        }

        $frontMatter = self::parseFrontMatter($matches[1]);
        $markdown = $matches[2];

        preg_match('/^(\d{4}-\d{2}-\d{2})-(.+)$/', $filename, $dateMatches);

        if (!$dateMatches) {
            throw new \Exception("Invalid filename format. Expected: YYYY-MM-DD-slug.md");
        }

        $date = Carbon::parse($dateMatches[1]);
        $slug = $dateMatches[2];

        return new self(
            slug: $slug,
            title: $frontMatter['title'] ?? 'Untitled',
            subtitle: $frontMatter['subtitle'] ?? '',
            category: $frontMatter['category'] ?? 'Uncategorized',
            heroImage: $frontMatter['hero_image'] ?? '',
            content: Str::markdown($markdown),
            date: $date,
            filepath: $filepath,
        );
    }

    private static function parseFrontMatter(string $yaml): array
    {
        $lines = explode("\n", trim($yaml));
        $data = [];

        foreach ($lines as $line) {
            if (strpos($line, ':') !== false) {
                [$key, $value] = explode(':', $line, 2);
                $data[trim($key)] = trim($value);
            }
        }

        return $data;
    }
}
