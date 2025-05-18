<?php

namespace Avant\ZohoBooks;

use Avant\Zoho\Client as ZohoClient;
use GuzzleHttp\Middleware;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\LazyCollection;
use Psr\Http\Message\RequestInterface;
use Spatie\GuzzleRateLimiterMiddleware\RateLimiterMiddleware;
use Throwable;

/**
 * @method LazyCollection listItems($query = null)
 * @method LazyCollection listBills($query = null)
 * @method LazyCollection listInvoices($query = null)
 * @method LazyCollection listInventoryAdjustments($query = null)
 * @method LazyCollection listCreditNotes($query = null)
 * @method object getItem(string $id)
 * @method object getBill(string $id)
 * @method object getInvoice(string $id)
 * @method object getInventoryAdjustment(string $id)
 * @method object getCreditNote(string $id)
 */
class Client extends ZohoClient
{
    public const RESOURCE_MAP = [
        'inventoryadjustments' => 'inventory_adjustments',
        'vendorcredits'        => 'vendor_credits',
    ];
    protected string $baseUrl = 'https://www.zohoapis.com/books/v3';

    public function __construct(
        protected readonly string $organizationId
    ) {}

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

    protected function request(): PendingRequest
    {
        return parent::request()
            ->withMiddleware(RateLimiterMiddleware::perMinute(100, new RedisStore()))
            ->withMiddleware(Middleware::mapRequest(function (RequestInterface $request) {
                parse_str($request->getUri()->getQuery(), $uriQuery);

                return $request->withUri($request
                    ->getUri()
                    ->withQuery(http_build_query($uriQuery + ['organization_id' => $this->organizationId]))
                );
            }));
    }

    public function createRecords(string $resource, $data)
    {
        return $this->retryOnConnectionFailure(
            fn () => $this->request()
                ->post($resource, $data)
                ->throw()
        );
    }

    public function updateRecords(string $resource, string $id, $data)
    {
        return $this->retryOnConnectionFailure(
            fn () => $this->request()
                ->put($resource.'/'.$id, $data)
                ->throw()
        );
    }

    public function listRecords(string $resource, $query = null)
    {
        return $this->retryOnConnectionFailure(
            fn () => $this->request()
                ->get($resource, $query)
                ->throw()
        );
    }

    public function getRecords(string $resource, string $id)
    {
        return $this->retryOnConnectionFailure(
            fn () => $this->request()
                ->get($resource.'/'.$id)
                ->throw()
        );
    }

    public function deleteRecords(string $resource, string $id)
    {
        return $this->retryOnConnectionFailure(
            fn () => $this->request()
                ->delete($resource.'/'.$id)
                ->throw()
        );
    }

    public function uploadImage(string $resource, string $id, string $path)
    {
        return $this->retryOnConnectionFailure(
            fn () => $this->request()
                ->attach(
                    'image',
                    fopen($path, 'r'),
                    pathinfo($path, PATHINFO_BASENAME),
                    ['Content-Type' => mime_content_type($path)]
                )
                ->post("{$resource}/{$id}/images")
                ->object()
        );
    }

    protected function retryOnConnectionFailure(callable $callback)
    {
        return retry(3, $callback, 1000, fn (Throwable $exception) => $exception instanceof ConnectionException);
    }
}
