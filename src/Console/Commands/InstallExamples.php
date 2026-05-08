<?php

namespace BristolDigital\QwikBlog\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

/**
 * blog:examples
 *
 * Installs a built-in example post set. Wraps `blog:import` so users don't
 * have to remember the path to resources/seeds/{set}-posts.php — they just
 * type `php artisan blog:examples flamenco`.
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
        $manifestPath = resource_path("seeds/{$set}-posts.php");

        if (!File::exists($manifestPath)) {
            $this->error("Example set not found: {$set}");
            $this->newLine();
            $this->line("Available sets in resources/seeds/:");

            $found = false;
            foreach (File::glob(resource_path('seeds/*-posts.php')) as $file) {
                $name = preg_replace('/-posts\.php$/', '', basename($file));
                $this->line("  • {$name}");
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

        // Forward all our options to the underlying generic importer.
        return Artisan::call('blog:import', [
            'file' => $manifestPath,
            '--overwrite' => $this->option('overwrite'),
            '--skip-images' => $this->option('skip-images'),
            '--dry-run' => $this->option('dry-run'),
        ], $this->getOutput());
    }
}
