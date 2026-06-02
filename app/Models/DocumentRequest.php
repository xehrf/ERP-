<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
class DocumentRequest extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'hr_approved_at' => 'datetime',
            'director_approved_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function hrApprover()
    {
        return $this->belongsTo(User::class, 'hr_approved_by');
    }

    public function directorApprover()
    {
        return $this->belongsTo(User::class, 'director_approved_by');
    }

    public function typeLabel(): string
    {
        return [
            'vacation' => 'Отпуск',
            'sick_leave' => 'Больничный',
        ][$this->type] ?? $this->type;
    }

    public function statusLabel(): string
    {
        return [
            'draft' => 'Черновик',
            'pending_hr' => 'На проверке кадровика',
            'pending_director' => 'На подписи директора',
            'approved' => 'Утверждено',
            'rejected' => 'Отклонено',
        ][$this->status] ?? $this->status;
    }
}
