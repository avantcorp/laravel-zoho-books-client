<?php

namespace Avant\ZohoClient\Books;

use Avant\ZohoClient\ZohoClient;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Collection;
use Spatie\GuzzleRateLimiterMiddleware\RateLimiterMiddleware;

/**
 * @method Collection listItems($query = null)
 *
 * @method Collection listBills($query = null)
 * @method getBills(string $id)
 *
 * @method Collection listInvoices($query = null)
 * @method getInvoices(string $id)
 */
class ZohoBooksClient extends ZohoClient
{
    protected string $baseUrl = 'https://books.zoho.com/api/v3/';

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
            ->withMiddleware(RateLimiterMiddleware::perMinute(100))
            ->withOptions([RequestOptions::QUERY => ['organization_id' => config('services.zoho_client.books.organization_id')]]);
    }


    public function createRecords(string $resource, $data)
    {
        return $this->request()
            ->post($resource, $data);
    }

    public function updateRecords(string $resource, string $id, $data)
    {
        return $this->request()
            ->put($resource.'/'.$id, $data);
    }

    public function listRecords(string $resource, $query = null)
    {
        return $this->request()
            ->get($resource, $query);
    }

    public function getRecords(string $resource, string $id)
    {
        return $this->request()
            ->get($resource.'/'.$id);
    }

    public function deleteRecords(string $resource, string $id)
    {
        return $this->request()
            ->delete($resource.'/'.$id);
    }
}
