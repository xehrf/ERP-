<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $users = [
            ['name' => 'Тимур Работник', 'email' => 'employee@example.com', 'role' => 'employee'],
            ['name' => 'Айдар Кадровик', 'email' => 'hr@example.com', 'role' => 'hr'],
            ['name' => 'Дана Директор', 'email' => 'director@example.com', 'role' => 'director'],
            ['name' => 'Админ ERP', 'email' => 'admin@example.com', 'role' => 'admin'],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                $user + ['password' => 'password']
            );
        }
    }
}
