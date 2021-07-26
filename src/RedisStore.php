<?php

namespace Avant\ZohoClient\Books;

use Illuminate\Support\Facades\Cache;
use Spatie\GuzzleRateLimiterMiddleware\Store;

class RedisStore implements Store
{
    public function get(): array
    {
        return Cache::get('zoho-books-rate-limiter', []);
    }

    public function push(int $timestamp, int $limit)
    {
        Cache::put('zoho-books-rate-limiter', array_merge($this->get(), [$timestamp]));
    }
}
