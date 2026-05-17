<?php

namespace App\Observers;

use App\Http\Middleware\SeoRedirectMiddleware;
use App\Models\SeoRedirect;
use Illuminate\Support\Facades\Cache;

class SeoRedirectObserver
{
    public function saved(SeoRedirect $redirect): void
    {
        Cache::forget(SeoRedirectMiddleware::CACHE_KEY);
    }

    public function deleted(SeoRedirect $redirect): void
    {
        Cache::forget(SeoRedirectMiddleware::CACHE_KEY);
    }
}
