<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'paulinopjc@gmail.com'],
            [
                'name' => 'Paulino Awino',
                'role' => User::ROLE_ADMIN,
                'is_active' => true,
            ]
        );
    }
}