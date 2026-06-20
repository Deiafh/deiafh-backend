<?php

namespace App\Services\Payment;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;

class PaymobPaymentService
{
    public function initiate(
        string $orderReference,
        float $amount,
        string $returnUrl,
        string $webhookUrl,
        string $clientPhone,
        Setting $settings
    ): array {
        $sandbox       = (bool) $settings->visa_sandbox_mode;
        $secretKey     = $sandbox ? $settings->paymob_sandbox_secret_key     : $settings->paymob_secret_key;
        $publicKey     = $sandbox ? $settings->paymob_sandbox_public_key     : $settings->paymob_public_key;
        $integrationId = $sandbox ? $settings->paymob_sandbox_integration_id : $settings->paymob_integration_id;

        $payload = [
            'amount'          => (int) round($amount * 100),
            'currency'        => 'EGP',
            'payment_methods' => [(int) $integrationId],
            'billing_data'    => [
                'first_name'   => 'Customer',
                'last_name'    => 'Order',
                'phone_number' => $clientPhone ?: '01000000000',
                'email'        => 'noemail@noemail.com',
            ],
            'expiration'         => 3600,
            'notification_url'   => $webhookUrl,
            'redirection_url'    => $returnUrl,
            'special_reference'  => $orderReference,
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Token ' . $secretKey,
        ])->post('https://accept.paymob.com/v1/intention/', $payload)->json();

        $operationId  = $response['intention_order_id'] ?? null;
        $clientSecret = $response['client_secret'] ?? null;

        return [
            'payment_url'  => "https://accept.paymob.com/unifiedcheckout/?publicKey={$publicKey}&clientSecret={$clientSecret}",
            'operation_id' => (string) $operationId,
        ];
    }

    public function verifyRedirect(array $params, Setting $settings): bool
    {
        // Paymob redirect sends FLAT params:
        //   - 'order' holds the order ID  (NOT 'order.id')
        //   - 'source_data.pan', 'source_data.sub_type', 'source_data.type' are literal dot-key strings
        // Map from HMAC field name → actual param key received from Paymob
        $keysMap = [
            'amount_cents'           => 'amount_cents',
            'created_at'             => 'created_at',
            'currency'               => 'currency',
            'error_occured'          => 'error_occured',
            'has_parent_transaction' => 'has_parent_transaction',
            'id'                     => 'id',
            'integration_id'         => 'integration_id',
            'is_3d_secure'           => 'is_3d_secure',
            'is_auth'                => 'is_auth',
            'is_capture'             => 'is_capture',
            'is_refunded'            => 'is_refunded',
            'is_standalone_payment'  => 'is_standalone_payment',
            'is_voided'              => 'is_voided',
            'order.id'               => 'order',              // flat param key
            'owner'                  => 'owner',
            'pending'                => 'pending',
            'source_data.pan'        => 'source_data.pan',     // literal dot key
            'source_data.sub_type'   => 'source_data.sub_type',
            'source_data.type'       => 'source_data.type',
            'success'                => 'success',
        ];

        $concatenated = '';
        foreach ($keysMap as $paramKey) {
            $value = $params[$paramKey] ?? '';
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }
            $concatenated .= $value;
        }

        $hmacSecret = $settings->visa_sandbox_mode ? $settings->paymob_sandbox_hmac : $settings->paymob_hmac;
        $computed   = hash_hmac('sha512', $concatenated, $hmacSecret);
        return $computed === ($params['hmac'] ?? '');
    }

    public function verifyWebhook(array $data, string $receivedHmac, Setting $settings): bool
    {
        $obj  = $data['obj'] ?? [];
        $keys = [
            'amount_cents', 'created_at', 'currency', 'error_occured', 'has_parent_transaction',
            'id', 'integration_id', 'is_3d_secure', 'is_auth', 'is_capture', 'is_refunded',
            'is_standalone_payment', 'is_voided', 'order.id', 'owner', 'pending',
            'source_data.pan', 'source_data.sub_type', 'source_data.type', 'success',
        ];

        $str = '';
        foreach ($keys as $key) {
            $parts = explode('.', $key);
            $value = $obj;
            foreach ($parts as $part) {
                $value = is_array($value) ? ($value[$part] ?? '') : '';
            }
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }
            $str .= $value;
        }

        $hmacSecret = $settings->visa_sandbox_mode ? $settings->paymob_sandbox_hmac : $settings->paymob_hmac;
        return hash_hmac('sha512', $str, $hmacSecret) === $receivedHmac;
    }
}
