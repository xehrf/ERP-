<?php

// Пространство имен сидеров.
namespace Database\Seeders;

// Модель пользователя.
use App\Models\User;
// Отключает события моделей во время заполнения базы.
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
// Базовый класс сидера.
use Illuminate\Database\Seeder;

// Сидер заполняет базу начальными пользователями.
class DatabaseSeeder extends Seeder
{
    // Во время сидов Laravel не будет запускать события моделей.
    use WithoutModelEvents;

    // Метод run запускается командой php artisan db:seed или migrate:fresh --seed.
    public function run(): void
    {
        // Массив стартовых пользователей.
        $users = [
            [
                // Имя работника.
                'name' => 'Алия Работник',
                // Email для входа.
                'email' => 'employee@example.com',
                // Роль работника.
                'role' => 'employee',
                // Работник может создавать заявки и комментировать.
                'permissions' => ['create_requests', 'comment'],
                // Демо-пользователь уже одобрен.
                'is_approved' => true,
                // Демо-пользователь не отклонен.
                'is_rejected' => false,
            ],
            [
                // Имя кадровика.
                'name' => 'Марат Кадровик',
                // Email кадровика.
                'email' => 'hr@example.com',
                // Роль кадровика.
                'role' => 'hr',
                // Кадровик видит все заявки, согласовывает и комментирует.
                'permissions' => ['view_all_requests', 'approve_hr', 'comment'],
                // Демо-пользователь уже одобрен.
                'is_approved' => true,
                // Демо-пользователь не отклонен.
                'is_rejected' => false,
            ],
            [
                // Имя директора.
                'name' => 'Дана Директор',
                // Email директора.
                'email' => 'director@example.com',
                // Роль директора.
                'role' => 'director',
                // Директор видит все заявки, утверждает и комментирует.
                'permissions' => ['view_all_requests', 'approve_director', 'comment'],
                // Демо-пользователь уже одобрен.
                'is_approved' => true,
                // Демо-пользователь не отклонен.
                'is_rejected' => false,
            ],
            [
                // Имя администратора.
                'name' => 'Админ ERP',
                // Email администратора.
                'email' => 'admin@example.com',
                // Роль администратора.
                'role' => 'admin',
                // Админ получает все основные права.
                'permissions' => ['create_requests', 'view_all_requests', 'approve_hr', 'approve_director', 'manage_users', 'comment'],
                // Админ уже одобрен.
                'is_approved' => true,
                // Админ не отклонен.
                'is_rejected' => false,
            ],
        ];

        // Проходим по каждому пользователю из массива.
        foreach ($users as $user) {
            // updateOrCreate не создает дубль, если email уже есть.
            User::updateOrCreate(
                // Ищем пользователя по email.
                ['email' => $user['email']],
                // Если найден - обновляем, если нет - создаем; пароль у всех password.
                $user + ['password' => 'password']
            );
        }
    }
}
