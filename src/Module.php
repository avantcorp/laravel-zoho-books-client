<?php

namespace Avant\ZohoBooks;

use Avant\ZohoBooks\RequestHandlers\GetRequestHandler;
use Avant\ZohoBooks\RequestHandlers\ListRequestHandler;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\LazyCollection;

readonly class Module
{
    public function __construct(
        private Client $client,
        private string $resource,
        private string $property
    ) { }

    public function create($data)
    {
        return $this->client->create($this->resource, $data);
    }

    public function update(string $id, $data)
    {
        return $this->client->update($this->resource, $id, $data);
    }

    public function list(array $query = []): LazyCollection
    {
        return (new ListRequestHandler($this->client, $this->resource, $this->property))
            ->handle($query);
    }

    public function get(string $id)
    {
        return (new GetRequestHandler($this->client, $this->resource, $this->property))
            ->handle([$id]);
    }

    public function delete(string $id)
    {
        return $this->client->delete($this->resource, $id);
    }

    public function uploadImage(string $id, string $path)
    {
        return $this->client->uploadImage($this->resource, $id, $path);
    }
}