<?php

namespace App\Services\Razorpay;

use App\Models\User;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class RazorpayClient
{
    private PendingRequest $client;

    private string $baseUrl = 'https://api.razorpay.com/v1';

    public function __construct(
        private User $user
    ) {
        if (! $this->user->hasRazorpayConfigured()) {
            throw new \RuntimeException('Razorpay credentials not configured.');
        }

        $this->client = Http::withBasicAuth(
            $this->user->razorpay_key_id,
            $this->user->razorpay_key_secret
        )->baseUrl($this->baseUrl)
            ->acceptJson()
            ->asJson();
    }

    public function createPaymentLink(array $data): Response
    {
        return $this->client->post('/payment_links', $data);
    }

    public function getPaymentLink(string $id): Response
    {
        return $this->client->get("/payment_links/{$id}");
    }

    public function cancelPaymentLink(string $id): Response
    {
        return $this->client->post("/payment_links/{$id}/cancel");
    }

    public function listPaymentLinks(array $params = []): Response
    {
        return $this->client->get('/payment_links', $params);
    }

    public function createOrder(array $data): Response
    {
        return $this->client->post('/orders', $data);
    }

    public function getOrder(string $id): Response
    {
        return $this->client->get("/orders/{$id}");
    }

    public function listOrders(array $params = []): Response
    {
        return $this->client->get('/orders', $params);
    }

    public function getOrderPayments(string $orderId): Response
    {
        return $this->client->get("/orders/{$orderId}/payments");
    }
}
