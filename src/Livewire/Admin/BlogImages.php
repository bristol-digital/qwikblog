<?php

namespace BristolDigital\QwikBlog\Livewire\Admin;

use BristolDigital\QwikBlog\Services\BlogImageService;
use BristolDigital\QwikBlog\Services\BlogService;
use Livewire\Component;
use Livewire\WithFileUploads;

class BlogImages extends Component
{
    use WithFileUploads;

    public string $slug;
    public array $post;
    public array $images = [];
    public $uploads = [];
    public bool $uploading = false;

    public function mount(string $slug, BlogService $blogService)
    {
        $post = $blogService->getPostBySlug($slug);

        if (!$post) {
            abort(404);
        }

        $this->slug = $slug;
        $this->post = [
            'title' => $post->title,
            'date' => $post->date->format('jS M Y'),
        ];

        $this->loadImages();
    }

    public function loadImages(): void
    {
        $this->images = BlogImageService::getImagesForPost($this->slug);
    }

    /**
     * Livewire hook: fired automatically when `uploads` is updated by the
     * file input. Validates and processes the batch in one go.
     */
    public function updatedUploads(): void
    {
        $this->validate([
            'uploads.*' => 'image|mimes:jpg,jpeg,png,gif|max:20480',
        ]);

        $this->uploading = true;

        foreach ($this->uploads as $upload) {
            BlogImageService::upload($upload, $this->slug);
        }

        $this->uploads = [];
        $this->uploading = false;
        $this->loadImages();

        $this->dispatch('images-updated');
    }

    public function deleteImage(string $filename): void
    {
        BlogImageService::delete($this->slug, $filename);
        $this->loadImages();

        $this->dispatch('images-updated');
    }

    /**
     * @param  array<int,string>  $order  Filenames in the new order.
     */
    public function reorderImages(array $order): void
    {
        BlogImageService::reorder($this->slug, $order);
        $this->loadImages();
    }

    public function render()
    {
        return view('qwikblog::livewire.admin.blog-images')
            ->layout('qwikblog::components.admin.layout', [
                'title' => 'Manage Images: ' . $this->post['title'],
            ]);
    }
}
