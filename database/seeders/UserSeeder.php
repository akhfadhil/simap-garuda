<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            ['name' => config('party.roles.admin_partai') . ' ' . config('party.short_name'), 'username' => 'admin', 'role' => 'admin_partai', 'password' => Hash::make('admin123'), 'phone' => '081234567890'],
        ];

        foreach ($users as $u) {
            User::updateOrCreate(['username' => $u['username']], $u);
        }
    }
}
