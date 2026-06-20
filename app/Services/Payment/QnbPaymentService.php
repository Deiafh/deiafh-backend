<?php

namespace App\Services\Payment;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;

class QnbPaymentService
{
    public function initiate(string $orderReference, float $amount, string $returnUrl, Setting $settings): array
    {
        $sandbox     = (bool) $settings->visa_sandbox_mode;
        $merchantId  = $sandbox ? $settings->qnb_sandbox_merchant_id  : $settings->qnb_merchant_id;
        $apiPassword = $sandbox ? $settings->qnb_sandbox_api_password  : $settings->qnb_api_password;

        $host = $sandbox
            ? 'qnbalahli.test.gateway.mastercard.com'
            : 'qnbalahli.gateway.mastercard.com';

        $url = "https://{$host}/api/rest/version/67/merchant/{$merchantId}/session";

        $payload = [
            'apiOperation' => 'INITIATE_CHECKOUT',
            'interaction'  => [
                'operation'      => 'PURCHASE',
                'displayControl' => [
                    'billingAddress' => 'HIDE',
                    'customerEmail'  => 'HIDE',
                ],
                'merchant' => [
                    'name' => $settings->title,
                ],
                'returnUrl' => $returnUrl,
            ],
            'order' => [
                'currency'    => 'EGP',
                'amount'      => number_format((float) $amount, 2, '.', ''),
                'id'          => $orderReference,
                'description' => "Order #{$orderReference}",
            ],
        ];

        $response = Http::withBasicAuth("merchant.{$merchantId}", $apiPassword)
            ->post($url, $payload)
            ->json();

        return [
            'session_id'   => $response['session']['id'] ?? null,
            'operation_id' => $response['successIndicator'] ?? null,
            'merchant_id'  => $merchantId,
        ];
    }
}
