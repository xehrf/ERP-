<?php

// Класс миграции.
use Illuminate\Database\Migrations\Migration;
// Описание колонок таблицы.
use Illuminate\Database\Schema\Blueprint;
// Управление схемой базы данных.
use Illuminate\Support\Facades\Schema;

// Миграция добавляет пользователям JSON-права.
return new class extends Migration
{
    // Выполняется при migrate.
    public function up(): void
    {
        // Изменяем существующую таблицу users.
        Schema::table('users', function (Blueprint $table) {
            // permissions хранит массив разрешений пользователя в JSON.
            $table->json('permissions')->nullable()->after('role');
        });
    }

    // Выполняется при откате миграции.
    public function down(): void
    {
        // Изменяем таблицу users обратно.
        Schema::table('users', function (Blueprint $table) {
            // Удаляем колонку permissions.
            $table->dropColumn('permissions');
        });
    }
};
