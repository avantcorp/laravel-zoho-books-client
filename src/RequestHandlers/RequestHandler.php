<?php

namespace Avant\ZohoBooks\RequestHandlers;

use Avant\ZohoBooks\Client;

abstract class RequestHandler
{
    public function __construct(
        protected Client $client,
        protected string $resource,
        protected string $property,
    ) {}
}
