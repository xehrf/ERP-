<?php

// Пространство имен моделей.
namespace App\Models;

// Фабрика нужна для автоматического создания пользователей в тестах.
use Database\Factories\UserFactory;
// Fillable указывает, какие поля можно массово заполнять.
use Illuminate\Database\Eloquent\Attributes\Fillable;
// Hidden скрывает чувствительные поля при выводе.
use Illuminate\Database\Eloquent\Attributes\Hidden;
// HasFactory подключает фабрики Laravel.
use Illuminate\Database\Eloquent\Factories\HasFactory;
// Authenticatable делает модель настоящим пользователем Laravel для входа в систему.
use Illuminate\Foundation\Auth\User as Authenticatable;
// Notifiable нужен для уведомлений Laravel.
use Illuminate\Notifications\Notifiable;

// Эти поля разрешено заполнять через User::create() и $user->update().
#[Fillable(['name', 'email', 'password', 'role', 'permissions', 'is_approved', 'is_rejected'])]
// Эти поля не будут показываться при преобразовании пользователя в массив или JSON.
#[Hidden(['password', 'remember_token'])]
// Модель User связана с таблицей users.
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    // Подключаем фабрики и уведомления.
    use HasFactory, Notifiable;

    // Laravel будет автоматически преобразовывать эти поля.
    protected function casts(): array
    {
        // Возвращаем список преобразований.
        return [
            // Дата подтверждения email хранится как дата и время.
            'email_verified_at' => 'datetime',
            // Пароль автоматически хешируется перед сохранением.
            'password' => 'hashed',
            // permissions хранится в базе как JSON, а в PHP работает как массив.
            'permissions' => 'array',
            // is_approved превращается в true/false.
            'is_approved' => 'boolean',
            // is_rejected превращается в true/false.
            'is_rejected' => 'boolean',
        ];
    }

    // Связь: один пользователь может создать много заявок.
    public function documentRequests()
    {
        // Laravel свяжет users.id с document_requests.user_id.
        return $this->hasMany(DocumentRequest::class);
    }

    // Связь: один пользователь может написать много комментариев.
    public function comments()
    {
        // Laravel свяжет users.id с request_comments.user_id.
        return $this->hasMany(RequestComment::class);
    }

    // Связь: один пользователь может получать много уведомлений.
    public function notifications()
    {
        // Laravel свяжет users.id с erp_notifications.user_id.
        return $this->hasMany(ErpNotification::class);
    }

    // Возвращает красивое название роли для интерфейса.
    public function roleLabel(): string
    {
        // Техническая роль переводится в русский текст.
        return [
            'candidate' => 'Кандидат',
            'employee' => 'Работник',
            'hr' => 'Кадровик',
            'director' => 'Директор',
            'admin' => 'Админ',
        // Если роль неизвестна, показываем ее как есть.
        ][$this->role] ?? $this->role;
    }

    // Возвращает список разрешений пользователя красивым русским текстом.
    public function permissionLabels(): array
    {
        // Берем permissions, заменяем технические ключи на названия и возвращаем массив.
        return collect($this->permissions ?? [])
            ->map(fn (string $permission) => self::availablePermissions()[$permission] ?? $permission)
            ->values()
            ->all();
    }

    // Проверяет, есть ли у пользователя конкретное разрешение.
    public function hasPermission(string $permission): bool
    {
        // Админ получает все права автоматически, даже если чекбокс не отмечен.
        return $this->role === 'admin' || in_array($permission, $this->permissions ?? [], true);
    }

    // Может ли пользователь управлять другими пользователями.
    public function canManageUsers(): bool
    {
        // Проверяем разрешение manage_users.
        return $this->hasPermission('manage_users');
    }

    // Может ли пользователь видеть все заявки.
    public function canViewAllRequests(): bool
    {
        // Проверяем разрешение view_all_requests.
        return $this->hasPermission('view_all_requests');
    }

    // Может ли пользователь создавать заявки.
    public function canCreateRequests(): bool
    {
        // Создавать заявки можно только при наличии отдельного разрешения.
        return $this->hasPermission('create_requests');
    }

    // Может ли пользователь согласовывать как кадровик.
    public function canApproveHr(): bool
    {
        // Проверяем разрешение approve_hr.
        return $this->hasPermission('approve_hr');
    }

    // Может ли пользователь утверждать как директор.
    public function canApproveDirector(): bool
    {
        // Проверяем разрешение approve_director.
        return $this->hasPermission('approve_director');
    }

    // Может ли пользователь читать и писать комментарии.
    public function canComment(): bool
    {
        // Проверяем разрешение comment.
        return $this->hasPermission('comment');
    }

    // Может ли пользователь открыть конкретную заявку.
    public function canViewRequest(DocumentRequest $request): bool
    {
        // Можно смотреть все заявки или только свою заявку.
        return $this->canViewAllRequests() || $request->user_id === $this->id;
    }

    // Список всех ролей системы.
    public static function availableRoles(): array
    {
        // Ключи сохраняются в базе, значения показываются на сайте.
        return [
            'candidate' => 'Кандидат',
            'employee' => 'Работник',
            'hr' => 'Кадровик',
            'director' => 'Директор',
            'admin' => 'Админ',
        ];
    }

    // Список всех разрешений системы.
    public static function availablePermissions(): array
    {
        // Ключи сохраняются в JSON permissions, значения показываются админу.
        return [
            'create_requests' => 'Создавать заявки',
            'view_all_requests' => 'Видеть все заявки',
            'approve_hr' => 'Согласовывать как кадровик',
            'approve_director' => 'Утверждать как директор',
            'manage_users' => 'Управлять пользователями',
            'comment' => 'Читать и писать комментарии',
        ];
    }
}
