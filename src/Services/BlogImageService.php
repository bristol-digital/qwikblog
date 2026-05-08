<?php

namespace BristolDigital\QwikBlog\Services;

use Illuminate\Support\Facades\File;

/**
 * Per-post image management.
 *
 * Each blog post has its own folder at public/images/blog/{slug}/. Images
 * are numbered (1.jpg, 2.jpg, …) and ordering is determined by the numeric
 * filename. The first image is the "main" image of the gallery — note that
 * this is independent of the post's `hero_image` front-matter field, which
 * the admin sets manually (and may or may not point at one of these images).
 */
class BlogImageService
{
    protected static function basePath(string $slug): string
    {
        return public_path('images/blog/' . $slug);
    }

    protected static function publicPath(string $slug): string
    {
        return '/images/blog/' . $slug;
    }

    public static function getImagesForPost(string $slug): array
    {
        $directory = self::basePath($slug);

        if (!File::isDirectory($directory)) {
            return [];
        }

        $files = collect(File::files($directory))
            ->filter(fn($file) => in_array(
                strtolower($file->getExtension()),
                ['jpg', 'jpeg', 'png', 'gif']
            ))
            ->sortBy(fn($file) => self::sortKey($file->getFilename()))
            ->values();

        return $files->map(fn($file) => [
            'filename' => $file->getFilename(),
            'path' => self::publicPath($slug) . '/' . $file->getFilename(),
            'full_path' => $file->getPathname(),
            'size' => round($file->getSize() / 1024), // KB
        ])->all();
    }

    public static function upload($file, string $slug): ?string
    {
        $directory = self::basePath($slug);

        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $nextNumber = self::getNextImageNumber($slug);

        $originalExt = strtolower($file->getClientOriginalExtension());
        // Keep GIFs as GIFs (preserves animation), convert everything else to JPG
        $extension = ($originalExt === 'gif') ? 'gif' : 'jpg';
        $filename = "{$nextNumber}.{$extension}";
        $destination = "{$directory}/{$filename}";

        if ($originalExt === 'gif') {
            File::copy($file->getRealPath(), $destination);
            return $filename;
        }

        if (self::processImage($file->getPathname(), $destination)) {
            return $filename;
        }

        return null;
    }

    public static function delete(string $slug, string $filename): bool
    {
        // Defensive: refuse paths that try to escape the post's folder
        if (str_contains($filename, '/') || str_contains($filename, '\\') || str_contains($filename, '..')) {
            return false;
        }

        $path = self::basePath($slug) . '/' . $filename;

        if (File::exists($path)) {
            return File::delete($path);
        }

        return false;
    }

    /**
     * Reorder a post's images to match the supplied filename order.
     *
     * Renames everything in two passes (via temporary names) to avoid
     * collisions when the new order overlaps with existing numeric names.
     */
    public static function reorder(string $slug, array $filenames): bool
    {
        $directory = self::basePath($slug);
        if (!File::isDirectory($directory)) {
            return false;
        }

        $tempPrefix = 'temp_' . uniqid() . '_';

        // First pass: move to temp names, preserving extension
        $extensions = [];
        foreach ($filenames as $index => $filename) {
            // Same defensive check as delete()
            if (str_contains($filename, '/') || str_contains($filename, '\\') || str_contains($filename, '..')) {
                continue;
            }
            $oldPath = "{$directory}/{$filename}";
            if (!File::exists($oldPath)) {
                continue;
            }
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION)) ?: 'jpg';
            $extensions[$index] = $ext;
            $tempPath = "{$directory}/{$tempPrefix}{$index}.{$ext}";
            File::move($oldPath, $tempPath);
        }

        // Second pass: rename to final 1-based numeric names
        foreach ($extensions as $index => $ext) {
            $tempPath = "{$directory}/{$tempPrefix}{$index}.{$ext}";
            $newPath = "{$directory}/" . ($index + 1) . ".{$ext}";
            if (File::exists($tempPath)) {
                File::move($tempPath, $newPath);
            }
        }

        return true;
    }

    /**
     * Sort by leading number, falling back to the full filename.
     * That way "1.jpg" < "2.jpg" < "10.jpg" rather than lexical "1, 10, 2".
     */
    protected static function sortKey(string $filename): int
    {
        if (preg_match('/^(\d+)\./', $filename, $m)) {
            return (int) $m[1];
        }
        return PHP_INT_MAX;
    }

    protected static function getNextImageNumber(string $slug): int
    {
        $images = self::getImagesForPost($slug);

        if (empty($images)) {
            return 1;
        }

        $numbers = collect($images)
            ->map(function ($image) {
                if (preg_match('/^(\d+)\./', $image['filename'], $matches)) {
                    return (int) $matches[1];
                }
                return 0;
            })
            ->filter();

        return ($numbers->isEmpty() ? 0 : $numbers->max()) + 1;
    }

    protected static function processImage(
        string $source,
        string $destination,
        int $maxWidth = 1600,
        int $maxHeight = 1200,
        int $quality = 85
    ): bool {
        // Without GD we can still upload — just a straight copy without resize.
        if (!extension_loaded('gd')) {
            return copy($source, $destination);
        }

        $info = @getimagesize($source);
        if (!$info) {
            return false;
        }

        [$width, $height, $type] = $info;

        $image = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($source),
            IMAGETYPE_PNG => @imagecreatefrompng($source),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($source) : null,
            IMAGETYPE_GIF => @imagecreatefromgif($source),
            default => null,
        };

        if (!$image) {
            return false;
        }

        // Fix orientation from EXIF (mobile photos)
        $image = self::fixOrientation($source, $image, $type);

        $width = imagesx($image);
        $height = imagesy($image);

        $ratio = min($maxWidth / $width, $maxHeight / $height, 1);
        $newWidth = (int) round($width * $ratio);
        $newHeight = (int) round($height * $ratio);

        if ($ratio < 1) {
            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagefill($resized, 0, 0, imagecolorallocate($resized, 255, 255, 255));

            imagecopyresampled(
                $resized, $image,
                0, 0, 0, 0,
                $newWidth, $newHeight,
                $width, $height
            );

            imagedestroy($image);
            $image = $resized;
        }

        $result = imagejpeg($image, $destination, $quality);
        imagedestroy($image);

        return $result;
    }

    protected static function fixOrientation(string $source, $image, int $type)
    {
        if ($type !== IMAGETYPE_JPEG || !function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($source);

        if (empty($exif['Orientation'])) {
            return $image;
        }

        return match ($exif['Orientation']) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };
    }
}
