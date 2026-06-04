<?php

// Пространство имен моделей.
namespace App\Models;

// Fillable разрешает массовое заполнение полей.
use Illuminate\Database\Eloquent\Attributes\Fillable;
// HasFactory нужен для тестов.
use Illuminate\Database\Eloquent\Factories\HasFactory;
// Model - базовая модель Laravel Eloquent.
use Illuminate\Database\Eloquent\Model;

// Эти поля можно заполнять через DocumentRequest::create() и update().
#[Fillable([
    'user_id',
    'type',
    'start_date',
    'end_date',
    'calendar_days',
    'working_days',
    'status',
    'hr_approved_by',
    'hr_approved_at',
    'director_approved_by',
    'director_approved_at',
    'comment',
])]
// Модель DocumentRequest связана с таблицей document_requests.
class DocumentRequest extends Model
{
    // Подключаем фабрики Laravel.
    use HasFactory;

    // Laravel будет автоматически преобразовывать эти поля в даты.
    protected function casts(): array
    {
        // Возвращаем список преобразований.
        return [
            // Дата начала будет объектом даты.
            'start_date' => 'date',
            // Дата конца будет объектом даты.
            'end_date' => 'date',
            // Время согласования кадровиком будет датой и временем.
            'hr_approved_at' => 'datetime',
            // Время утверждения директором будет датой и временем.
            'director_approved_at' => 'datetime',
        ];
    }

    // Связь: заявка принадлежит одному пользователю.
    public function user()
    {
        // Laravel использует поле user_id.
        return $this->belongsTo(User::class);
    }

    // Связь: пользователь, который согласовал как кадровик.
    public function hrApprover()
    {
        // Здесь используется поле hr_approved_by.
        return $this->belongsTo(User::class, 'hr_approved_by');
    }

    // Связь: пользователь, который утвердил как директор.
    public function directorApprover()
    {
        // Здесь используется поле director_approved_by.
        return $this->belongsTo(User::class, 'director_approved_by');
    }

    // Связь: комментарии заявки.
    public function comments()
    {
        // Берем только основные комментарии, а ответы подгружаем отдельно.
        return $this->hasMany(RequestComment::class)->whereNull('parent_id')->with(['user', 'replies.user'])->oldest();
    }

    // Связь: история действий по заявке.
    public function histories()
    {
        // События показываем от старых к новым.
        return $this->hasMany(RequestHistory::class)->with('user')->oldest();
    }

    // Переводит технический тип заявки в русский текст.
    public function typeLabel(): string
    {
        // vacation и sick_leave хранятся в базе, русский текст показывается на сайте.
        return [
            'vacation' => 'Отпуск',
            'sick_leave' => 'Больничный',
        // Если тип неизвестен, показываем его как есть.
        ][$this->type] ?? $this->type;
    }

    // Переводит технический статус заявки в русский текст.
    public function statusLabel(): string
    {
        // Эти статусы показываются в таблице и карточке заявки.
        return [
            'draft' => 'Черновик',
            'pending_hr' => 'На проверке кадровика',
            'pending_director' => 'На подписи директора',
            'approved' => 'Утверждено',
            'rejected' => 'Отклонено',
        // Если статус неизвестен, показываем его как есть.
        ][$this->status] ?? $this->status;
    }
}
