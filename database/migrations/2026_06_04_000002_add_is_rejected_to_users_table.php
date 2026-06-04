<?php

// Миграция добавляет пользователям признак отклонения администратором.
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
            // true - админ отклонил регистрацию, false - не отклонял.
            $table->boolean('is_rejected')->default(false)->after('is_approved');
        });
    }

    // Выполняется при откате миграции.
    public function down(): void
    {
        // Удаляем поле is_rejected.
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_rejected');
        });
    }
};
