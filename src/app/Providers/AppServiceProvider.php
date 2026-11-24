<?php

namespace App\Providers;

use App\Contracts\DocumentParserInterface;
use App\Services\Document\LocalDocumentParserService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(DocumentParserInterface::class, LocalDocumentParserService::class);
    }

    public function boot(): void
    {
        //
    }
}
