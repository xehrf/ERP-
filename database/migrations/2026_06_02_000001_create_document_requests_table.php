<?php

// Подключаем базовый класс миграции.
use Illuminate\Database\Migrations\Migration;
// Blueprint нужен для описания колонок.
use Illuminate\Database\Schema\Blueprint;
// Schema управляет таблицами.
use Illuminate\Support\Facades\Schema;

// Миграция таблицы заявок на отпуск и больничный.
return new class extends Migration
{
    // Выполняется при migrate.
    public function up(): void
    {
        // Создаем таблицу document_requests.
        Schema::create('document_requests', function (Blueprint $table) {
            // ID заявки.
            $table->id();
            // Пользователь, который создал заявку.
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Тип заявки: vacation или sick_leave.
            $table->string('type');
            // Дата начала.
            $table->date('start_date');
            // Дата конца.
            $table->date('end_date');
            // Количество календарных дней.
            $table->unsignedInteger('calendar_days');
            // Количество рабочих дней.
            $table->unsignedInteger('working_days');
            // Статус заявки.
            $table->string('status')->default('draft');
            // Кто согласовал как кадровик.
            $table->foreignId('hr_approved_by')->nullable()->constrained('users')->nullOnDelete();
            // Когда кадровик согласовал.
            $table->timestamp('hr_approved_at')->nullable();
            // Кто утвердил как директор.
            $table->foreignId('director_approved_by')->nullable()->constrained('users')->nullOnDelete();
            // Когда директор утвердил.
            $table->timestamp('director_approved_at')->nullable();
            // Старое поле комментария, оставлено для совместимости.
            $table->text('comment')->nullable();
            // created_at и updated_at.
            $table->timestamps();
        });
    }

    // Выполняется при откате миграции.
    public function down(): void
    {
        // Удаляем таблицу заявок.
        Schema::dropIfExists('document_requests');
    }
};
