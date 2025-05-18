<?php

namespace Avant\ZohoBooks\RequestHandlers;

use Illuminate\Support\LazyCollection;

class ListRequestHandler extends RequestHandler
{
    public function handle(string $resource, array $arguments): LazyCollection
    {
        return LazyCollection::make(function () use ($resource, $arguments) {
            $hasMorePage = true;
            while ($hasMorePage) {
                $response = $this->client->listRecords($resource, ...$arguments)->object();
                $mappedResource = data_get($this->client::RESOURCE_MAP, $resource, $resource);

                foreach (data_get($response, $mappedResource) as $record) {
                    yield $record;
                }

                $hasMorePage = $response->page_context->has_more_page ?? false;
                $arguments[0]['page'] = $response->page_context->page + 1;
            }
        });
    }
}
