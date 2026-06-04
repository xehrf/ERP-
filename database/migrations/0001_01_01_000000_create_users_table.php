<?php

// Миграция - это инструкция Laravel для создания или изменения таблиц базы данных.
use Illuminate\Database\Migrations\Migration;
// Blueprint описывает колонки таблицы.
use Illuminate\Database\Schema\Blueprint;
// Schema запускает создание и удаление таблиц.
use Illuminate\Support\Facades\Schema;

// Возвращаем анонимный класс миграции.
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // Метод up выполняется, когда мы запускаем php artisan migrate.
    public function up(): void
    {
        // Создаем таблицу users для пользователей системы.
        Schema::create('users', function (Blueprint $table) {
            // Уникальный ID пользователя.
            $table->id();
            // Имя пользователя.
            $table->string('name');
            // Email пользователя, по нему выполняется вход.
            $table->string('email')->unique();
            // Роль пользователя: employee, hr, director или admin.
            $table->string('role')->default('employee');
            // Дата подтверждения email, сейчас в проекте почти не используется.
            $table->timestamp('email_verified_at')->nullable();
            // Захешированный пароль пользователя.
            $table->string('password');
            // Токен "запомнить меня" для долгой авторизации.
            $table->rememberToken();
            // created_at и updated_at.
            $table->timestamps();
        });

        // Таблица для восстановления пароля.
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            // Email пользователя.
            $table->string('email')->primary();
            // Токен восстановления.
            $table->string('token');
            // Когда токен создан.
            $table->timestamp('created_at')->nullable();
        });

        // Таблица сессий, потому что SESSION_DRIVER=database.
        Schema::create('sessions', function (Blueprint $table) {
            // ID сессии браузера.
            $table->string('id')->primary();
            // ID пользователя, если он вошел.
            $table->foreignId('user_id')->nullable()->index();
            // IP-адрес пользователя.
            $table->string('ip_address', 45)->nullable();
            // Информация о браузере.
            $table->text('user_agent')->nullable();
            // Данные сессии.
            $table->longText('payload');
            // Время последней активности.
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    // Метод down откатывает миграцию.
    public function down(): void
    {
        // Удаляем users.
        Schema::dropIfExists('users');
        // Удаляем password_reset_tokens.
        Schema::dropIfExists('password_reset_tokens');
        // Удаляем sessions.
        Schema::dropIfExists('sessions');
    }
};
