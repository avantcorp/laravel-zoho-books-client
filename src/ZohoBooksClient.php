<?php

namespace Avant\ZohoClient\Books;

use Avant\ZohoClient\ZohoClient;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
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
        return $this->retryableRequest(
            fn () => $this->request()
                ->post($resource, $data)
                ->throw()
        );
    }

    public function updateRecords(string $resource, string $id, $data)
    {
        return $this->retryableRequest(
            fn () => $this->request()
                ->put($resource.'/'.$id, $data)
                ->throw()
        );
    }

    public function listRecords(string $resource, $query = null)
    {
        return $this->retryableRequest(
            fn () => $this->request()
                ->get($resource, $query)
                ->throw()
        );
    }

    public function getRecords(string $resource, string $id)
    {
        return $this->retryableRequest(
            fn () => $this->request()
                ->get($resource.'/'.$id)
                ->throw()
        );
    }

    public function deleteRecords(string $resource, string $id)
    {
        return $this->retryableRequest(
            fn () => $this->request()
                ->delete($resource.'/'.$id)
                ->throw()
        );
    }

    public function uploadImage(string $resource, string $id, string $path)
    {
        return $this->retryableRequest(
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

    protected function retryableRequest(callable $callback)
    {
        return retry(3, $callback, 1000, [$this, 'canRetry']);
    }

    protected function shouldRetry(\Throwable $exception): bool
    {
        return $exception instanceof RequestException &&
            str_contains($exception->getMessage(), 'cURL error 28: SSL connection timeout');
    }
}
