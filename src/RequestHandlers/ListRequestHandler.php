<?php

namespace Avant\ZohoClient\Books\RequestHandlers;

use Illuminate\Support\Arr;
use Illuminate\Support\LazyCollection;

class ListRequestHandler extends RequestHandler
{
    public function handle(string $resource, array $arguments)
    {
        return LazyCollection::make(function () use ($resource, $arguments) {
            $hasMorePage = true;
            while ($hasMorePage) {
                $result = $this->client
                    ->listRecords($resource, ...$arguments)
                    ->object();
                foreach (data_get($result, Arr::get(get_class($this->client)::RESOURCE_MAP, $resource, $resource)) as $record) {
                    yield $record;
                }
                $hasMorePage = $result->page_context->has_more_page ?? false;
                $arguments[0]['page'] = $result->page_context->page + 1;
            }
        });
    }
}
