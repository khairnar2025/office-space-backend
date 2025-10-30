<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DeliveryPincodeSeeder extends Seeder
{
    public function run(): void
    {
        $pincodes = [
            ['pincode' => '110001', 'shipping_cost' => 50, 'is_serviceable' => true,  'delivery_days_min' => 2, 'delivery_days_max' => 4],
            ['pincode' => '110002', 'shipping_cost' => 60, 'is_serviceable' => true,  'delivery_days_min' => 3, 'delivery_days_max' => 5],
            ['pincode' => '110003', 'shipping_cost' => 70, 'is_serviceable' => false, 'delivery_days_min' => 0, 'delivery_days_max' => 0],
            ['pincode' => '110004', 'shipping_cost' => 40, 'is_serviceable' => true,  'delivery_days_min' => 1, 'delivery_days_max' => 3],
            ['pincode' => '110005', 'shipping_cost' => 55, 'is_serviceable' => true,  'delivery_days_min' => 2, 'delivery_days_max' => 5],
        ];

        foreach ($pincodes as $pincode) {
            DB::table('delivery_pincodes')->insert(array_merge($pincode, [
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]));
        }
    }
}
