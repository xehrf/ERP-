<?php

// Пространство имен моделей.
namespace App\Models;

// Fillable разрешает массовое заполнение полей.
use Illuminate\Database\Eloquent\Attributes\Fillable;
// HasFactory нужен для тестов.
use Illuminate\Database\Eloquent\Factories\HasFactory;
// Model - базовая модель Eloquent.
use Illuminate\Database\Eloquent\Model;

// Эти поля можно записывать через create().
#[Fillable(['document_request_id', 'user_id', 'parent_id', 'body'])]
// Модель комментария к заявке.
class RequestComment extends Model
{
    // Подключаем фабрики Laravel.
    use HasFactory;

    // Связь: комментарий принадлежит одной заявке.
    public function documentRequest()
    {
        // Laravel использует поле document_request_id.
        return $this->belongsTo(DocumentRequest::class);
    }

    // Связь: комментарий написал один пользователь.
    public function user()
    {
        // Laravel использует поле user_id.
        return $this->belongsTo(User::class);
    }

    // Связь: если это ответ, у него есть родительский комментарий.
    public function parent()
    {
        // parent_id указывает на комментарий, которому отвечают.
        return $this->belongsTo(RequestComment::class, 'parent_id');
    }

    // Связь: у комментария могут быть ответы.
    public function replies()
    {
        // Ищем комментарии, у которых parent_id равен ID текущего комментария.
        return $this->hasMany(RequestComment::class, 'parent_id')->oldest();
    }
}
