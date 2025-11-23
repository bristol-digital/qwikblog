<?php

namespace BristolDigital\QwikBlog\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallExamplePosts extends Command
{
    protected $signature = 'qwikblog:install-examples {--force : Overwrite existing files}';

    protected $description = 'Install example blog posts and images';

    public function handle(): int
    {
        $this->info('📝 Installing QwikBlog example data...');
        $this->newLine();

        // Check if posts directory exists
        $postsPath = resource_path('posts');
        if (!File::exists($postsPath)) {
            File::makeDirectory($postsPath, 0755, true);
            $this->info('✓ Created posts directory');
        }

        // Check if images directory exists
        $imagesPath = public_path('images/blog');
        if (!File::exists($imagesPath)) {
            File::makeDirectory($imagesPath, 0755, true);
            $this->info('✓ Created images directory');
        }

        // Check for existing posts
        $existingPosts = File::glob($postsPath . '/*.md');
        if (count($existingPosts) > 0 && !$this->option('force')) {
            if (!$this->confirm('Posts directory is not empty. Continue anyway?')) {
                $this->warn('Installation cancelled.');
                return self::FAILURE;
            }
        }

        // Copy example posts
        $stubsPath = __DIR__ . '/../../stubs/posts';
        $posts = File::files($stubsPath);

        foreach ($posts as $post) {
            $destination = $postsPath . '/' . $post->getFilename();

            if (File::exists($destination) && !$this->option('force')) {
                $this->warn("⚠ Skipped {$post->getFilename()} (already exists)");
                continue;
            }

            File::copy($post->getPathname(), $destination);
            $this->info("✓ Copied {$post->getFilename()}");
        }

        // Copy example images
        $stubsImagesPath = __DIR__ . '/../../stubs/images';
        $images = File::files($stubsImagesPath);

        foreach ($images as $image) {
            $destination = $imagesPath . '/' . $image->getFilename();

            if (File::exists($destination) && !$this->option('force')) {
                $this->warn("⚠ Skipped {$image->getFilename()} (already exists)");
                continue;
            }

            File::copy($image->getPathname(), $destination);
            $this->info("✓ Copied {$image->getFilename()}");
        }

        $this->newLine();
        $this->info('🎉 Example data installed successfully!');
        $this->newLine();
        $this->info('Visit your blog at: ' . url('/blog'));
        $this->info('Posts location: resources/posts/');
        $this->info('Images location: public/images/blog/');

        return self::SUCCESS;
    }
}
