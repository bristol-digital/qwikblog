<?php

use Illuminate\Support\Facades\File;

/**
 * Tests for the blog:examples artisan command, focused on v1.1.5's vendor
 * seed fallback.
 *
 * In v1.1.4, `php artisan blog:examples flamenco` would fail on a fresh
 * install unless the user first ran `vendor:publish --tag=qwikblog-seeds`.
 * v1.1.5 falls back to the bundled manifest in the package's own
 * resources/seeds/ directory if the host hasn't published one.
 *
 * These tests assume an Orchestra Testbench setup. The host-app
 * resource_path('seeds') is the testbench skeleton's resources/seeds/,
 * which is empty by default — that's exactly the "fresh install" condition
 * we want to test.
 */

beforeEach(function () {
    // Clean any host-app seeds from a previous test
    $hostSeedsDir = resource_path('seeds');
    if (File::isDirectory($hostSeedsDir)) {
        File::cleanDirectory($hostSeedsDir);
    }
});

it('falls back to bundled seeds when the host app has none', function () {
    // Host's resources/seeds/ is empty (cleaned in beforeEach).
    // The package ships flamenco-posts.php internally, so this should succeed.

    // We pass --dry-run so we don't actually create posts on disk
    $this->artisan('blog:examples', ['set' => 'flamenco', '--dry-run' => true])
        ->expectsOutputToContain('Installing example set: flamenco')
        ->assertSuccessful();
});

it('uses the host-published manifest when one exists', function () {
    $hostSeedsDir = resource_path('seeds');
    File::ensureDirectoryExists($hostSeedsDir);

    // Write a minimal host manifest with a distinctive marker title
    file_put_contents(
        $hostSeedsDir . '/flamenco-posts.php',
        '<?php return [["title" => "HOST_VERSION", "body" => "Test."]];'
    );

    $this->artisan('blog:examples', ['set' => 'flamenco', '--dry-run' => true])
        ->expectsOutputToContain(resource_path('seeds/flamenco-posts.php'))
        ->assertSuccessful();
});

it('errors helpfully when neither host nor bundled set exists', function () {
    $this->artisan('blog:examples', ['set' => 'nonexistent-set-xyz'])
        ->expectsOutputToContain('Example set not found: nonexistent-set-xyz')
        ->expectsOutputToContain('Available sets:')
        ->assertFailed();
});

it('lists bundled sets in the not-found error', function () {
    $this->artisan('blog:examples', ['set' => 'nonexistent-set-xyz'])
        ->expectsOutputToContain('flamenco')
        ->expectsOutputToContain('bundled')
        ->assertFailed();
});

it('labels host-app sets distinctly from bundled sets in the listing', function () {
    $hostSeedsDir = resource_path('seeds');
    File::ensureDirectoryExists($hostSeedsDir);
    file_put_contents(
        $hostSeedsDir . '/custom-posts.php',
        '<?php return [];'
    );

    $this->artisan('blog:examples', ['set' => 'nonexistent-set-xyz'])
        ->expectsOutputToContain('custom')
        ->expectsOutputToContain('host app')
        ->expectsOutputToContain('flamenco')
        ->expectsOutputToContain('bundled')
        ->assertFailed();
});
