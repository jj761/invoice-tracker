<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@webtreeonline.com'],
            [
                'name' => 'Admin',
                'password' => 'password',
            ]
        );

        $this->call(ClientInvoiceSeeder::class);
    }
}
