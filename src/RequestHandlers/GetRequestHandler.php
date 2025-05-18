<?php

namespace Avant\ZohoBooks\RequestHandlers;

use Avant\ZohoBooks\Client;

class GetRequestHandler extends RequestHandler
{
    public function __construct(Client $client, string $resource, string $property)
    {
        parent::__construct($client, $resource, str($property)->singular()->toString());
    }

    public function handle(array $arguments)
    {
        $response = $this->client->get($this->resource, ...$arguments)->object();

        return data_get($response, $this->property);
    }
}
