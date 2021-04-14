<?php

namespace Avant\ZohoClient\Books\RequestHandlers;

use Avant\ZohoClient\Books\ZohoBooksClient;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class GetRequestHandler extends RequestHandler
{
    public function handle(string $resource, array $arguments)
    {
        return $this->client
            ->getRecords($resource, ...$arguments)
            ->object()
            ->{Str::singular(Arr::get(ZohoBooksClient::RESOURCE_MAP, $resource, $resource))};
    }
}
