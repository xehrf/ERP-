<?php

// Пространство имен моделей Laravel.
namespace App\Models;

// Fillable разрешает массово заполнять перечисленные поля через create().
use Illuminate\Database\Eloquent\Attributes\Fillable;
// HasFactory нужен для тестов и фабрик Laravel.
use Illuminate\Database\Eloquent\Factories\HasFactory;
// Model - базовый класс обычной таблицы базы данных.
use Illuminate\Database\Eloquent\Model;

// Эти поля можно записывать в историю заявки через histories()->create().
#[Fillable(['document_request_id', 'user_id', 'action', 'title', 'body'])]
// Модель RequestHistory связана с таблицей request_histories.
// Она хранит хронологию: создано, согласовано кадровиком, утверждено директором, отклонено.
class RequestHistory extends Model
{
    // Подключаем фабрики Laravel.
    use HasFactory;

    // Связь: одна запись истории относится к одной заявке.
    public function documentRequest()
    {
        // Laravel связывает request_histories.document_request_id с document_requests.id.
        return $this->belongsTo(DocumentRequest::class);
    }

    // Связь: действие в истории мог выполнить один пользователь.
    public function user()
    {
        // Laravel связывает request_histories.user_id с users.id.
        return $this->belongsTo(User::class);
    }
}
