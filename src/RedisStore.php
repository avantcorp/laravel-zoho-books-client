<?php

namespace Avant\ZohoBooks;

use Illuminate\Support\Facades\Cache;
use Spatie\GuzzleRateLimiterMiddleware\Store;

class RedisStore implements Store
{
    public function get(): array
    {
        return Cache::get('zoho-books-rate-limiter', []);
    }

    public function push(int $timestamp, int $limit): void
    {
        Cache::put('zoho-books-rate-limiter', array_merge($this->get(), [$timestamp]));
    }
}
