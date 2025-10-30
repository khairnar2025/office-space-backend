<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CategoriesSeeder extends Seeder
{
    public function run()
    {
        $categories = [
            ['name' => 'Chairs'],
            ['name' => 'Tables'],
            ['name' => 'Sofas'],
            ['name' => 'Beds'],
            ['name' => 'Cabinets'],
            ['name' => 'Office Furniture'],
            ['name' => 'Outdoor Furniture'],
        ];

        foreach ($categories as &$category) {
            $category['created_at'] = Carbon::now();
            $category['updated_at'] = Carbon::now();
        }

        DB::table('categories')->insert($categories);
    }
}
