<?php

// Миграция добавляет пользователям признак одобрения админом.
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Выполняется при migrate.
    public function up(): void
    {
        // Изменяем таблицу users.
        Schema::table('users', function (Blueprint $table) {
            // true - пользователь может войти, false - ждет одобрения админа.
            $table->boolean('is_approved')->default(true)->after('permissions');
        });
    }

    // Выполняется при откате миграции.
    public function down(): void
    {
        // Удаляем поле is_approved.
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_approved');
        });
    }
};
