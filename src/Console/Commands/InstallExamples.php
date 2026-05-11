<?php

namespace BristolDigital\QwikBlog\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

/**
 * blog:examples
 *
 * Installs a built-in example post set. Wraps `blog:import` so users don't
 * have to remember the path to the manifest — they just type
 * `php artisan blog:examples flamenco`.
 *
 * Looks for the manifest in the host app's `resources/seeds/` first; falls
 * back to the package's own `resources/seeds/` (the seeds the package ships
 * with) if the host hasn't published them. That means a fresh install can
 * run `php artisan blog:examples flamenco` directly after `composer require`,
 * without needing `vendor:publish --tag=qwikblog-seeds` first — publishing
 * is only necessary if the host wants to edit the manifest.
 *
 * To add a new set, drop a `<name>-posts.php` file into resources/seeds/.
 * It'll show up automatically in the "available sets" list.
 */
class InstallExamples extends Command
{
    protected $signature = 'blog:examples
                            {set=flamenco : Which example set to install}
                            {--overwrite : Replace any existing posts with matching slugs}
                            {--skip-images : Skip image downloads (useful for fast re-runs)}
                            {--dry-run : Preview without writing}';

    protected $description = 'Install a bundled example post set (flamenco, etc.)';

    public function handle(): int
    {
        $set = $this->argument('set');
        $manifestPath = $this->locateManifest($set);

        if ($manifestPath === null) {
            $this->error("Example set not found: {$set}");
            $this->newLine();
            $this->line("Available sets:");

            $found = false;
            foreach ($this->availableSets() as $name => $location) {
                $this->line("  • {$name}  <fg=gray>({$location})</>");
                $found = true;
            }
            if (!$found) {
                $this->warn("  (none — drop a manifest into resources/seeds/{name}-posts.php)");
            }
            return self::FAILURE;
        }

        $this->info("Installing example set: {$set}");
        $this->line("Manifest: {$manifestPath}");
        $this->newLine();

        return Artisan::call('blog:import', [
            'file' => $manifestPath,
            '--overwrite' => $this->option('overwrite'),
            '--skip-images' => $this->option('skip-images'),
            '--dry-run' => $this->option('dry-run'),
        ], $this->getOutput());
    }

    /**
     * Look for `{set}-posts.php` in the host app first, then fall back to
     * the package's own seeds directory.
     */
    private function locateManifest(string $set): ?string
    {
        $candidates = [
            resource_path("seeds/{$set}-posts.php"),
            __DIR__ . "/../../../resources/seeds/{$set}-posts.php",
        ];

        foreach ($candidates as $path) {
            if (File::exists($path)) {
                return realpath($path) ?: $path;
            }
        }

        return null;
    }

    /**
     * List every available set, indicating where each one lives.
     *
     * @return array<string,string>  name => location label
     */
    private function availableSets(): array
    {
        $sets = [];

        // Host-app seeds win on display order — they're what the user is most likely editing
        foreach (File::glob(resource_path('seeds/*-posts.php')) as $file) {
            $name = preg_replace('/-posts\.php$/', '', basename($file));
            $sets[$name] = 'host app';
        }

        // Bundled package seeds
        $bundledPath = __DIR__ . '/../../../resources/seeds';
        if (File::isDirectory($bundledPath)) {
            foreach (File::glob($bundledPath . '/*-posts.php') as $file) {
                $name = preg_replace('/-posts\.php$/', '', basename($file));
                if (!isset($sets[$name])) {
                    $sets[$name] = 'bundled';
                }
            }
        }

        return $sets;
    }
}
