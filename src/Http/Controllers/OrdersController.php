<?php

namespace Lancodev\LunarPaypal\Http\Controllers;

use Illuminate\Http\Request;
use Lancodev\LunarPaypal\Models\Paypal;
use Lunar\Models\Cart;
use Lunar\Models\Order;
use Lunar\Models\Transaction;
use Spatie\Ray\Ray;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

class OrdersController
{
    public function create(Request $request)
    {
        $cart = Cart::find($request->cart_id)->calculate();

        $order = Order::where('cart_id', $cart->id)->first() ?? $cart->createOrder();

        if (! $order->customer()->exists() && $cart->user()->exists()) {
            $customer = $cart->user->customers()->first();
            $newCustomer = $customer->orders()->count() === 0;

            $order->update([
                'customer_id' => $customer->id,
                'new_customer' => $newCustomer,
            ]);

            $order->save();
        }

        $paypal = new PayPalClient();
        $paypal->getAccessToken();
        $paypal->getClientToken();

        $purchaseUnits = [];

        $purchaseUnits[] = [
            'amount' => [
                'currency_code' => $cart->total->currency->code,
                'value' => $cart->total->value / 100,
            ],
        ];

        $data = [
            'intent' => 'CAPTURE',
            'purchase_units' => $purchaseUnits,
        ];

        $paypalOrder = $paypal->createOrder($data);

        $transaction = Transaction::create([
            'order_id' => $order->id,
            'reference' => $paypalOrder['id'],
            'amount' => $cart->total->value,
            'success' => true,
            'driver' => 'paypal',
            'status' => 'pending',
            'card_type' => 'paypal',
            'type' => 'intent',
        ]);

        return $paypalOrder;
    }

    public function capture($orderId)
    {
        $transaction = Transaction::where('reference', $orderId)->first();

        $paypal = new Paypal();

        return $paypal->capture($transaction);
    }
}
