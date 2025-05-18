<?php

namespace Avant\ZohoBooks;

use Illuminate\Support\ServiceProvider;

class ZohoBooksServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/services.zoho_books.php', 'services.zoho_books');

        $this->app->singleton(Client::class, fn () => new Client(
            organizationId: config('services.zoho_books.organization_id'),
        ));
    }
}