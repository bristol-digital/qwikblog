<?php

namespace App\Console\Commands;

use App\Services\BlogService;
use Illuminate\Console\Command;

class RefreshBlog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'blog:refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh the blog file content as driven by cached static files';

    /**
     * Execute the console command.
     */
    public function handle(BlogService $blogService)
    {
        $blogService->clearCache();
        $this->info('Blog cache refreshed!');
    }
}
