<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\User;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@nepse.com'],
            [
                'name'     => 'Admin',
                'password' => bcrypt('admin@123'),
                'role_id'  => 1,
                'email_verified_at' => now(),
            ]
        );
    }
}
