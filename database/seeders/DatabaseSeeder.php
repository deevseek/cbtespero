<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(UserRoleSeeder::class);

        Setting::firstOrCreate([], ['nama_aplikasi' => 'Espero CBT']);
    }
}
