<?php

namespace Avant\ZohoClient\Books;

use Avant\ZohoClient\ZohoClient;
use GuzzleHttp\RequestOptions;
use Spatie\GuzzleRateLimiterMiddleware\RateLimiterMiddleware;

/**
 * @method \Illuminate\Support\LazyCollection listItems($query = null)
 *
 * @method \Illuminate\Support\LazyCollection listBills($query = null)
 * @method object getBills(string $id)
 *
 * @method \Illuminate\Support\LazyCollection listInvoices($query = null)
 * @method object getInvoices(string $id)
 *
 * @method \Illuminate\Support\LazyCollection listInventoryAdjustments($query = null)
 * @method object getInventoryAdjustments(string $id)
 *
 * @method \Illuminate\Support\LazyCollection listCreditNotes($query = null)
 * @method object getCreditNotes(string $id)
 */
class ZohoBooksClient extends ZohoClient
{
    public const RESOURCE_MAP = [
        'inventoryadjustments' => 'inventory_adjustments',
        'vendorcredits'        => 'vendor_credits',
    ];

    protected string $baseUrl = 'https://www.zohoapis.com/books/v3';

    public function __construct($user, protected readonly string $organizationId)
    {
        parent::__construct($user);
    }

    public function __call($name, $arguments)
    {
        preg_match('/^(create|update|list|get|delete)(.*)/', $name, $matches);
        if (count($matches) < 2) {
            throw new \Exception('Invalid method action specified.');
        }
        $action = $matches[1];
        $resource = strtolower($matches[2]);
        $responseHandlerClass = '\\Avant\\ZohoClient\\Books\\RequestHandlers\\'.ucfirst($action).'RequestHandler';
        if (!class_exists($responseHandlerClass)) {
            return $this->{$action.'Records'}($resource, ...$arguments)->object();
        }
        return (new $responseHandlerClass($this))
            ->handle($resource, $arguments);
    }

    protected function request()
    {
        return parent::request()
            ->withMiddleware(RateLimiterMiddleware::perMinute(100, new RedisStore()))
            ->withOptions([RequestOptions::QUERY => ['organization_id' => $this->organizationId]]);
    }

    protected function mergeQuery($query): array
    {
        return array_merge_recursive((array)$query, [
            'organization_id' => $this->organizationId,
        ]);
    }

    public function createRecords(string $resource, $data)
    {
        return $this->request()
            ->post($resource, $data)
            ->throw();
    }

    public function updateRecords(string $resource, string $id, $data)
    {
        return $this->request()
            ->put($resource.'/'.$id, $data)
            ->throw();
    }

    public function listRecords(string $resource, $query = null)
    {
        return $this->request()
            ->get($resource, $this->mergeQuery($query))
            ->throw();
    }

    public function getRecords(string $resource, string $id)
    {
        return $this->request()
            ->get($resource.'/'.$id)
            ->throw();
    }

    public function deleteRecords(string $resource, string $id)
    {
        return $this->request()
            ->delete($resource.'/'.$id)
            ->throw();
    }
}
