<?php

namespace Avant\ZohoClient\Books\RequestHandlers;

use Avant\ZohoClient\Books\ZohoBooksClient;

abstract class RequestHandler
{
    protected ZohoBooksClient $client;

    public function __construct(ZohoBooksClient $client)
    {
        $this->client = $client;
    }

    abstract public function handle(string $resource, array $arguments);
}
