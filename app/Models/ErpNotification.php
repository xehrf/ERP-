<?php

// Пространство имен моделей Laravel.
namespace App\Models;

// Fillable разрешает массово заполнять перечисленные поля через create().
use Illuminate\Database\Eloquent\Attributes\Fillable;
// HasFactory нужен для тестов и фабрик Laravel.
use Illuminate\Database\Eloquent\Factories\HasFactory;
// Model - базовый класс обычной таблицы базы данных.
use Illuminate\Database\Eloquent\Model;

// Эти поля можно записывать при создании уведомления.
#[Fillable(['user_id', 'document_request_id', 'title', 'body', 'read_at'])]
// Модель ErpNotification связана с таблицей erp_notifications.
// Она хранит сообщения: "новая заявка", "ожидает директора", "заявка утверждена" и так далее.
class ErpNotification extends Model
{
    // Подключаем фабрики Laravel.
    use HasFactory;

    // Laravel будет автоматически преобразовывать некоторые поля в нужные типы.
    protected function casts(): array
    {
        // Возвращаем список преобразований.
        return [
            // read_at будет объектом даты и времени, если уведомление когда-нибудь помечается прочитанным.
            'read_at' => 'datetime',
        ];
    }

    // Связь: уведомление принадлежит одному пользователю.
    public function user()
    {
        // Laravel связывает erp_notifications.user_id с users.id.
        return $this->belongsTo(User::class);
    }

    // Связь: уведомление может относиться к одной заявке.
    public function documentRequest()
    {
        // Laravel связывает erp_notifications.document_request_id с document_requests.id.
        return $this->belongsTo(DocumentRequest::class);
    }
}
