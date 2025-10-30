<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        $clients = [
            [
                'title' => 'Acme Corp',
                'logo' => 'acme_logo.png',
                'website' => 'https://www.acme.com',
                'status' => true,
            ],
            [
                'title' => 'Globex Inc',
                'logo' => 'globex_logo.png',
                'website' => 'https://www.globex.com',
                'status' => true,
            ],
            [
                'title' => 'Initech',
                'logo' => 'initech_logo.png',
                'website' => 'https://www.initech.com',
                'status' => true,
            ],
            [
                'title' => 'Umbrella Corp',
                'logo' => 'umbrella_logo.png',
                'website' => 'https://www.umbrella.com',
                'status' => false,
            ],
        ];

        foreach ($clients as $client) {
            DB::table('clients')->insert(array_merge($client, [
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]));
        }
    }
}
