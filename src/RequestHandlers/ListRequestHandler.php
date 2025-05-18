<?php

namespace Avant\ZohoBooks\RequestHandlers;

use Avant\ZohoBooks\Client;
use Illuminate\Support\LazyCollection;

class ListRequestHandler extends RequestHandler
{
    public function handle(array $arguments): LazyCollection
    {
        return LazyCollection::make(function () use ($arguments) {
            $hasMorePage = true;
            while ($hasMorePage) {
                $response = $this->client->list($this->resource, ...$arguments)->object();

                foreach (data_get($response, $this->property) as $record) {
                    yield $record;
                }

                $hasMorePage = $response->page_context->has_more_page ?? false;
                $arguments[0]['page'] = $response->page_context->page + 1;
            }
        });
    }
}
