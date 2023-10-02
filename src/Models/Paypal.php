<?php

namespace Lancodev\LunarPaypal\Models;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Lunar\Models\Cart;
use Lunar\Models\Transaction;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

class Paypal
{
    public PayPalClient $client;

    public ?string $clientId;

    public function __construct()
    {
        $this->client = new PayPalClient();
        $this->client->setApiCredentials(config('paypal'));

        if (array_keys($this->cacheAccessToken()['access_token'])) {
            $this->client->setAccessToken($this->cacheAccessToken());
        }

        $mode = config('paypal.mode');
        $this->clientId = config("paypal.{$mode}.client_id");
    }

    public function cacheAccessToken()
    {
        return Cache::remember('paypal.access_token', 60 * 60 * 4, function () {
            return $this->client->getAccessToken();
        });
    }

    public function getClientToken()
    {
        return Cache::remember('paypal.client_token', 60 * 60 * 4, function () {
            return $this->client->getClientToken()['client_token'] ?? null;
        });
    }

    public function authorize(Cart $cart, $order = null)
    {
        $cart->calculate();

        $payPalOrder = $this->client->createOrder([
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'amount' => [
                        'currency_code' => 'USD',
                        'value' => $cart->total / 100,
                    ],
                ],
            ],
        ]);

        if (! $order) {
            if (! $order = $cart->order) {
                $order = $cart->createOrder();
            }
        }

        if ($order->placed_at) {
            // Something's gone wrong!
            return false;
        }

        $order->meta->put('paypal', [
            'order_id' => $payPalOrder['id'],
            'intent' => $payPalOrder['intent'],
            'status' => $payPalOrder['status'],
        ]);

        return $payPalOrder;
    }

    public function capture(Transaction $transaction)
    {
        try {
            $response = $this->client->capturePaymentOrder($transaction->reference);
        } catch (\Exception $e) {
            return false;
        }

        $paymentType = key($response['payment_source']);
        $cardType = 'paypal';
        $lastFour = null;

        if ($paymentType === 'card') {
            $paymentMethod = $response['payment_source']['card'];
            $cardType = Str::lower($paymentMethod['brand']);
            $lastFour = $paymentMethod['last_digits'];
        }

        $charge = $response['purchase_units'][0]['payments']['captures'][0];

        $transactions = [];

        $transaction->update([
            'parent_transaction_id' => $transaction->id,
            'success' => $charge['status'] === 'COMPLETED',
            'type' => 'capture',
            'driver' => 'paypal',
            'card_type' => $cardType,
            'last_four' => $lastFour,
            'amount' => $charge['amount']['value'] * 100,
            'reference' => $charge['id'],
            'status' => $charge['status'],
            'captured_at' => now(),
        ]);

        $transaction->order->transactions()->createMany($transactions);

        $transaction->order->update([
            'status' => 'paid',
            'placed_at' => now(),
        ]);

        return $response;
    }

    public function refund(Transaction $transaction, $amount = 0, $notes = null)
    {
        if (empty($notes)) {
            $notes = 'Your refund has been processed.';
        }
        $randomId = uniqid('', true);
        try {
            $refund = $this->client->refundCapturedPayment($transaction->reference, $randomId, $amount / 100, $notes);
        } catch (\Exception $e) {
            return false;
        }

        $transaction->order->transactions()->create([
            'success' => $refund['status'] === 'COMPLETED',
            'type' => 'refund',
            'driver' => 'paypal',
            'amount' => $amount,
            'reference' => $refund['id'],
            'status' => $refund['status'],
            'notes' => $notes,
            'card_type' => 'paypal',
        ]);

        return true;
    }
}
