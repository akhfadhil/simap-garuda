<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
            ['name' => 'Administrator', 'username' => 'admin', 'role' => 'admin', 'password' => Hash::make('admin123')],
            ['name' => 'Komisioner', 'username' => 'komisioner', 'role' => 'komisioner', 'password' => Hash::make('komisioner123')],
        ];

        foreach ($users as $u) {
            User::updateOrCreate(['username' => $u['username']], $u);
        }
    }
}
