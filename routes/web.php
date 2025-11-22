<?php

use Illuminate\Support\Facades\Route;
use BristolDigital\QwikBlog\Http\Controllers\BlogController;

Route::prefix(config('qwikblog.route.prefix', 'blog'))
    ->middleware(config('qwikblog.route.middleware', ['web']))
    ->name(config('qwikblog.route.name_prefix', 'blog.'))
    ->group(function () {
        Route::get('/', [BlogController::class, 'index'])->name('index');
        Route::get('/{slug}', [BlogController::class, 'show'])->name('show');
    });
