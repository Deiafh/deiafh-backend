<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class VisaSettingController extends Controller
{
    private function settingFields(Setting $setting): array
    {
        return [
            'is_visa_available'              => $setting->is_visa_available,
            'visa_sandbox_mode'              => $setting->visa_sandbox_mode,
            'visa_provider'                  => $setting->visa_provider,
            'visa_fixed_fees'                => $setting->visa_fixed_fees,
            'visa_percentage_fees'           => $setting->visa_percentage_fees,
            // QNB live
            'qnb_merchant_id'                => $setting->qnb_merchant_id,
            'qnb_api_password'               => $setting->qnb_api_password,
            // QNB sandbox
            'qnb_sandbox_merchant_id'        => $setting->qnb_sandbox_merchant_id,
            'qnb_sandbox_api_password'       => $setting->qnb_sandbox_api_password,
            // Paymob live
            'paymob_secret_key'              => $setting->paymob_secret_key,
            'paymob_public_key'              => $setting->paymob_public_key,
            'paymob_hmac'                    => $setting->paymob_hmac,
            'paymob_integration_id'          => $setting->paymob_integration_id,
            // Paymob sandbox
            'paymob_sandbox_secret_key'      => $setting->paymob_sandbox_secret_key,
            'paymob_sandbox_public_key'      => $setting->paymob_sandbox_public_key,
            'paymob_sandbox_hmac'            => $setting->paymob_sandbox_hmac,
            'paymob_sandbox_integration_id'  => $setting->paymob_sandbox_integration_id,
        ];
    }

    public function show()
    {
        $setting = Setting::first();

        return response()->json($this->settingFields($setting));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'is_visa_available'     => 'boolean',
            'visa_sandbox_mode'     => 'boolean',
            'visa_provider'         => 'nullable|in:qnb,paymob',
            'visa_fixed_fees'       => 'numeric|min:0',
            'visa_percentage_fees'  => 'numeric|min:0',
            'qnb_merchant_id'               => 'nullable|string|max:255',
            'qnb_api_password'              => 'nullable|string|max:255',
            'qnb_sandbox_merchant_id'       => 'nullable|string|max:255',
            'qnb_sandbox_api_password'      => 'nullable|string|max:255',
            'paymob_secret_key'             => 'nullable|string|max:1000',
            'paymob_public_key'             => 'nullable|string|max:1000',
            'paymob_hmac'                   => 'nullable|string|max:255',
            'paymob_integration_id'         => 'nullable|string|max:255',
            'paymob_sandbox_secret_key'     => 'nullable|string|max:1000',
            'paymob_sandbox_public_key'     => 'nullable|string|max:1000',
            'paymob_sandbox_hmac'           => 'nullable|string|max:255',
            'paymob_sandbox_integration_id' => 'nullable|string|max:255',
        ]);

        $setting = Setting::first();
        $setting->update($data);

        return response()->json($this->settingFields($setting));
    }
}
