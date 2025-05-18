<?php

namespace Avant\ZohoBooks;

use Avant\Zoho\Client as ZohoClient;
use GuzzleHttp\Middleware;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Psr\Http\Message\RequestInterface;
use Spatie\GuzzleRateLimiterMiddleware\RateLimiterMiddleware;
use Throwable;

/**
 * @method Module bills()
 * @method Module creditNotes()
 * @method Module customerPayments()
 * @method Module customers()
 * @method Module estimates()
 * @method Module expenses()
 * @method Module inventoryAdjustments()
 * @method Module invoices()
 * @method Module items()
 * @method Module purchaseOrders()
 * @method Module recurringBills()
 * @method Module recurringExpenses()
 * @method Module recurringInvoices()
 * @method Module salesOrders()
 * @method Module vendorCredits()
 * @method Module vendorPayments()
 * @method Module vendors()
 */
class Client extends ZohoClient
{
    private const RESOURCE_MAP = [
        'inventoryadjustments' => 'inventory_adjustments',
        'vendorcredits'        => 'vendor_credits',
    ];
    protected string $baseUrl = 'https://www.zohoapis.com/books/v3';

    public function __construct(
        protected readonly string $organizationId
    ) {}

    public function __call($name, $arguments)
    {
        $module = strtolower($name);

        return new Module($this, $module, data_get(static::RESOURCE_MAP, $module, $module));
    }

    public function request(): PendingRequest
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

    public function chartOfAccounts(): Collection
    {
        $response = $this->retryOnConnectionFailure(
            fn () => $this->request()
                ->get('chartofaccounts')
                ->throw()
        );

        return collect($response->chartofaccounts);
    }

    public function create(string $resource, $data)
    {
        return $this->retryOnConnectionFailure(
            fn () => $this->request()
                ->post($resource, $data)
                ->throw()
        );
    }

    public function update(string $resource, string $id, $data)
    {
        return $this->retryOnConnectionFailure(
            fn () => $this->request()
                ->put("{$resource}/{$id}", $data)
                ->throw()
        );
    }

    public function list(string $resource, $query = null)
    {
        return $this->retryOnConnectionFailure(
            fn () => $this->request()
                ->get($resource, $query)
                ->throw()
        );
    }

    public function get(string $resource, string $id)
    {
        return $this->retryOnConnectionFailure(
            fn () => $this->request()
                ->get("{$resource}/{$id}")
                ->throw()
        );
    }

    public function delete(string $resource, string $id)
    {
        return $this->retryOnConnectionFailure(
            fn () => $this->request()
                ->delete("{$resource}/{$id}")
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

    public function retryOnConnectionFailure(callable $callback)
    {
        return retry(3, $callback, 1000, fn (Throwable $exception) => $exception instanceof ConnectionException);
    }
}
