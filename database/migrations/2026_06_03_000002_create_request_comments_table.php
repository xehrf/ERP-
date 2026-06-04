<?php

// Базовый класс миграции.
use Illuminate\Database\Migrations\Migration;
// Описание колонок таблицы.
use Illuminate\Database\Schema\Blueprint;
// Управление таблицами базы.
use Illuminate\Support\Facades\Schema;

// Миграция создает таблицу комментариев к заявкам.
return new class extends Migration
{
    // Выполняется при migrate.
    public function up(): void
    {
        // Создаем таблицу request_comments.
        Schema::create('request_comments', function (Blueprint $table) {
            // ID комментария.
            $table->id();
            // ID заявки, к которой относится комментарий.
            $table->foreignId('document_request_id')->constrained()->cascadeOnDelete();
            // ID пользователя, который написал комментарий.
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // ID родительского комментария, если это ответ.
            $table->foreignId('parent_id')->nullable()->constrained('request_comments')->cascadeOnDelete();
            // Текст комментария.
            $table->text('body');
            // created_at и updated_at.
            $table->timestamps();
        });
    }

    // Выполняется при откате миграции.
    public function down(): void
    {
        // Удаляем таблицу комментариев.
        Schema::dropIfExists('request_comments');
    }
};
