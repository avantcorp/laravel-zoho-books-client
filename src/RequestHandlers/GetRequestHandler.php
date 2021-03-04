<?php

namespace Avant\ZohoClient\Books\RequestHandlers;

use Illuminate\Support\Str;

class GetRequestHandler extends RequestHandler
{
    public function handle(string $resource, array $arguments)
    {
        return $this->client
            ->getRecords($resource, ...$arguments)
            ->object()
            ->{Str::singular($resource)};
    }
}
