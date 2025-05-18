<?php

namespace Avant\ZohoBooks\RequestHandlers;

use Avant\ZohoBooks\Client;

class GetRequestHandler extends RequestHandler
{
    public function handle(string $resource, array $arguments)
    {
        $response = $this->client->getRecords($resource, ...$arguments)->object();
        $mappedResource = str(data_get(Client::RESOURCE_MAP, str($resource)->plural()->toString(), $resource))->singular();

        return data_get($response, $mappedResource);
    }
}
