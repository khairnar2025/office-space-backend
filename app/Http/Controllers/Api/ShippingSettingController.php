<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use App\Models\ShippingSetting;

class ShippingSettingController extends BaseController
{
    // GET /shipping-settings
    public function index()
    {
        $setting = ShippingSetting::first(); // single row
        return $this->sendResponse($setting, 'Shipping settings fetched successfully.');
    }

    // PUT /shipping-settings
    public function update(Request $request)
    {
        $request->validate([
            'minimum_free_shipping_amount' => 'required|numeric|min:0',
            'shipping_cost' => 'required|numeric|min:0',
        ]);

        // Create or update the single shipping setting record
        $setting = ShippingSetting::updateOrCreate(
            ['id' => 1], // Only one record, id=1
            $request->only(['minimum_free_shipping_amount', 'shipping_cost'])
        );

        return $this->sendResponse($setting, 'Shipping settings updated successfully.');
    }
}
