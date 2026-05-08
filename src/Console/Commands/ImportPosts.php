<?php

namespace BristolDigital\QwikBlog\Console\Commands;

use BristolDigital\QwikBlog\Services\BlogService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * blog:import
 *
 * Bulk-imports posts from a manifest file (JSON or PHP array). For each entry
 * it creates a post via BlogService, then optionally downloads referenced
 * images into /public/images/blog/{slug}/ and points hero_image at the first.
 *
 * Manifest entry shape (all fields optional except title and body):
 * [
 *   'date'              => 'YYYY-MM-DD' or 'YYYY-MM-DD HH:MM:SS',
 *   'title'             => string,
 *   'subtitle'          => string,
 *   'summary'           => string,
 *   'categories'        => array<string>,
 *   'tags'              => array<string>,
 *   'author'            => string,
 *   'body'              => string (markdown),
 *   'hero_image_url'    => string (URL),
 *   'gallery_image_urls'=> array<string> (URLs),
 * ]
 */
class ImportPosts extends Command
{
    protected $signature = 'blog:import
                            {file : Path to manifest (.json or .php returning an array)}
                            {--dry-run : Preview what would happen without writing anything}
                            {--skip-images : Skip image downloads (posts created without imagery)}
                            {--overwrite : Replace existing posts with matching slug (default: skip)}';

    protected $description = 'Bulk-import blog posts from a manifest file';

    public function handle(BlogService $blogService): int
    {
        $file = $this->argument('file');
        $isDryRun = (bool) $this->option('dry-run');
        $skipImages = (bool) $this->option('skip-images');
        $overwrite = (bool) $this->option('overwrite');

        if (!File::exists($file)) {
            $this->error("Manifest not found: $file");
            return self::FAILURE;
        }

        $manifest = $this->loadManifest($file);
        if (!is_array($manifest)) {
            $this->error("Manifest must be a JSON array or a PHP file returning an array.");
            return self::FAILURE;
        }

        $count = count($manifest);
        $this->info("Loaded $count entries from $file");
        if ($isDryRun) {
            $this->warn("DRY RUN — no changes will be made");
        }
        $this->newLine();

        $created = 0;
        $skipped = 0;
        $imageHits = 0;
        $imageMisses = 0;

        foreach ($manifest as $i => $entry) {
            $title = $entry['title'] ?? null;
            if (!$title) {
                $this->warn("Entry [$i] has no title — skipping");
                $skipped++;
                continue;
            }

            $this->line("<comment>{$title}</comment>");

            $intendedSlug = Str::slug($title);
            $existing = $blogService->getPostBySlug($intendedSlug);

            if ($existing && !$overwrite) {
                $this->warn("  Slug '$intendedSlug' already exists — skipping. Use --overwrite to replace.");
                $skipped++;
                continue;
            }

            if ($isDryRun) {
                $this->line("  [dry-run] would create slug=$intendedSlug");
                continue;
            }

            // For overwrite, drop the existing post first (also cleans its image dir).
            if ($existing && $overwrite) {
                $blogService->delete($intendedSlug);
                $this->line("  Replaced existing post.");
            }

            $payload = $this->buildPayload($entry);
            $slug = $blogService->create($payload);

            if (!$slug) {
                $this->error("  Failed to create post.");
                $skipped++;
                continue;
            }
            $this->line("  Created: slug=$slug");

            // Download images, if any. The post is already saved at this point —
            // image failures don't roll back the post, just leave it without imagery.
            if (!$skipImages) {
                [$hero, $hits, $misses] = $this->downloadImages($slug, $entry);
                $imageHits += $hits;
                $imageMisses += $misses;

                if ($hero !== null) {
                    // Re-save the post with the hero_image set to the locally-saved file.
                    $payload['hero_image'] = $hero;
                    $blogService->update($slug, $payload);
                }
            }

            $created++;
        }

        $this->newLine();
        $this->info("Done. Posts: $created created, $skipped skipped. Images: $imageHits succeeded, $imageMisses failed.");

        if (!$isDryRun) {
            Artisan::call('blog:refresh');
            $this->line("Cache refreshed.");
        }

        return self::SUCCESS;
    }

    /**
     * Loads a manifest file. Supports .json (json_decode) and .php (require returning array).
     */
    private function loadManifest(string $file): mixed
    {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        if ($ext === 'json') {
            $raw = File::get($file);
            $decoded = json_decode($raw, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error("JSON parse error: " . json_last_error_msg());
                return null;
            }
            return $decoded;
        }

        if ($ext === 'php') {
            return require $file;
        }

        $this->error("Unsupported manifest extension: .$ext (must be .json or .php)");
        return null;
    }

    private function buildPayload(array $entry): array
    {
        return [
            'title' => $entry['title'],
            'subtitle' => $entry['subtitle'] ?? '',
            'summary' => $entry['summary'] ?? '',
            'categories' => is_array($entry['categories'] ?? null) ? $entry['categories'] : [],
            'tags' => is_array($entry['tags'] ?? null) ? $entry['tags'] : [],
            'author' => $entry['author'] ?? '',
            'date' => $entry['date'] ?? now()->format('Y-m-d'),
            'hero_image' => '',  // filled after image download succeeds
            'body' => $entry['body'] ?? '',
        ];
    }

    /**
     * Downloads hero + gallery images into /public/images/blog/{slug}/.
     * Returns [heroPath, hitCount, missCount].
     */
    private function downloadImages(string $slug, array $entry): array
    {
        $urls = [];
        if (!empty($entry['hero_image_url'])) {
            $urls[] = $entry['hero_image_url'];
        }
        if (!empty($entry['gallery_image_urls']) && is_array($entry['gallery_image_urls'])) {
            $urls = array_merge($urls, $entry['gallery_image_urls']);
        }

        if (count($urls) === 0) {
            return [null, 0, 0];
        }

        $imageDir = public_path("images/blog/$slug");
        File::ensureDirectoryExists($imageDir);

        $firstSavedPath = null;
        $hits = 0;
        $misses = 0;
        $fileIndex = 1;

        foreach ($urls as $url) {
            $shortUrl = Str::limit($url, 70);
            $this->line("  Fetching $shortUrl");

            try {
                // 30s timeout — some image hosts are slow on first hit.
                // Follow redirects so URLs that 302 to a CDN still work.
                $response = Http::timeout(30)
                    ->withOptions(['allow_redirects' => true])
                    ->get($url);

                if (!$response->successful()) {
                    $this->warn("    HTTP " . $response->status() . " — skipped");
                    $misses++;
                    continue;
                }

                $body = $response->body();
                if (strlen($body) < 100) {
                    $this->warn("    Response too small (" . strlen($body) . " bytes) — likely an error page, skipped");
                    $misses++;
                    continue;
                }

                $ext = $this->guessExtension($response->header('Content-Type'), $url);
                $filename = "$fileIndex.$ext";
                File::put("$imageDir/$filename", $body);

                $publicPath = "/images/blog/$slug/$filename";
                $this->line("    Saved → $publicPath (" . round(strlen($body) / 1024) . " KB)");

                if ($firstSavedPath === null) {
                    $firstSavedPath = $publicPath;
                }
                $fileIndex++;
                $hits++;
            } catch (\Throwable $e) {
                $this->warn("    Error: " . $e->getMessage());
                $misses++;
            }
        }

        return [$firstSavedPath, $hits, $misses];
    }

    /**
     * Best-effort extension detection. Prefers Content-Type header; falls back
     * to URL extension; defaults to jpg. Picsum sometimes serves jpeg with no
     * obvious URL extension, hence the fallback chain.
     */
    private function guessExtension(?string $contentType, string $url): string
    {
        $ct = strtolower($contentType ?? '');
        if (str_contains($ct, 'png')) return 'png';
        if (str_contains($ct, 'webp')) return 'webp';
        if (str_contains($ct, 'gif')) return 'gif';
        if (str_contains($ct, 'svg')) return 'svg';
        if (str_contains($ct, 'jpeg') || str_contains($ct, 'jpg')) return 'jpg';

        $urlExt = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION));
        if (in_array($urlExt, ['png', 'webp', 'gif', 'svg', 'jpg', 'jpeg'], true)) {
            return $urlExt === 'jpeg' ? 'jpg' : $urlExt;
        }

        return 'jpg';
    }
}
