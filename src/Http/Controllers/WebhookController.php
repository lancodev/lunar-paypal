<?php

namespace Lancodev\LunarPaypal\Http\Controllers;

use Illuminate\Http\Request;
use Lancodev\LunarPaypal\Events\PaypalWebhookReceived;
use Srmklive\PayPal\Services\PayPal;

class WebhookController
{
    public function handle(Request $request)
    {
        $paypal = new PayPal;
        $paypal->setApiCredentials(config('paypal'));
        $paypal->getAccessToken();

        // Get paypal headers from requests
        $headers = [
            'auth_algo' => $request->header('PAYPAL-AUTH-ALGO', null),
            'cert_url' => $request->header('PAYPAL-CERT-URL', null),
            'transmission_id' => $request->header('PAYPAL-TRANSMISSION-ID', null),
            'transmission_sig' => $request->header('PAYPAL-TRANSMISSION-SIG', null),
            'transmission_time' => $request->header('PAYPAL-TRANSMISSION-TIME', null),
        ];

        // Get data from request body
        $data = $request->all();

        // Get paypal webhook id, get this from paypal developer site when you create webhook
        $paypal_webhook_id = env('PAYPAL_WEBHOOK_ID', null);

        // gather webhook data to verify it
        $verify_data = [
            'auth_algo' => $headers['auth_algo'],
            'cert_url' => $headers['cert_url'],
            'transmission_id' => $headers['transmission_id'],
            'transmission_sig' => $headers['transmission_sig'],
            'transmission_time' => $headers['transmission_time'],
            'webhook_id' => $paypal_webhook_id,
            'webhook_event' => $data,
        ];

        // Verify webhook
        $verified = $paypal->verifyWebHook($verify_data)['verification_status'] === 'SUCCESS';

        if ($verified || ! app()->environment('production')) {
            // Emit an event so subscribers can handle the webhook
            PaypalWebhookReceived::dispatch($data);
        }

        return response()->json(['success' => true]);
    }
}
