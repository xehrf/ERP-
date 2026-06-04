<?php

// В этом файле описаны все URL-адреса сайта.
// Laravel смотрит сюда, чтобы понять: какую страницу показать и какой контроллер запустить.

// Контроллер админки: создание пользователей и выдача ролей/прав.
use App\Http\Controllers\AdminUserController;
// Контроллер входа и выхода из системы.
use App\Http\Controllers\AuthController;
// Контроллер заявок: список, создание, просмотр, согласование, отклонение.
use App\Http\Controllers\DocumentRequestController;
// Контроллер комментариев к заявкам.
use App\Http\Controllers\RequestCommentController;
// Фасад Route нужен для объявления маршрутов.
use Illuminate\Support\Facades\Route;

// Главная страница сайта.
Route::get('/', function () {
    // При открытии главной страницы сразу перенаправляем пользователя в журнал заявок.
    return redirect()->route('requests.index');
});

// Страница входа доступна без авторизации.
Route::get('/login', [AuthController::class, 'login'])->name('login');
// Обработка формы входа: email + пароль.
Route::post('/login', [AuthController::class, 'store'])->name('login.store');
// Страница регистрации доступна без авторизации.
Route::get('/register', [AuthController::class, 'register'])->name('register');
// Обработка формы регистрации нового работника.
Route::post('/register', [AuthController::class, 'registerStore'])->name('register.store');

// Все маршруты внутри этой группы доступны только авторизованным пользователям.
Route::middleware('auth')->group(function () {
    // Выход из системы.
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Журнал заявок.
    Route::get('/requests', [DocumentRequestController::class, 'index'])->name('requests.index');
    // Форма создания заявки.
    Route::get('/requests/create', [DocumentRequestController::class, 'create'])->name('requests.create');
    // Сохранение новой заявки.
    Route::post('/requests', [DocumentRequestController::class, 'store'])->name('requests.store');
    // Детальная страница одной заявки.
    Route::get('/requests/{documentRequest}', [DocumentRequestController::class, 'show'])->name('requests.show');
    // Повтор отклоненной заявки с теми же данными.
    Route::get('/requests/{documentRequest}/repeat', [DocumentRequestController::class, 'repeat'])->name('requests.repeat');
    // Согласование заявки кадровиком.
    Route::patch('/requests/{documentRequest}/hr-approve', [DocumentRequestController::class, 'approveHr'])->name('requests.hr-approve');
    // Утверждение заявки директором.
    Route::patch('/requests/{documentRequest}/director-approve', [DocumentRequestController::class, 'approveDirector'])->name('requests.director-approve');
    // Отклонение заявки.
    Route::patch('/requests/{documentRequest}/reject', [DocumentRequestController::class, 'reject'])->name('requests.reject');
    // Добавление комментария или ответа к заявке.
    Route::post('/requests/{documentRequest}/comments', [RequestCommentController::class, 'store'])->name('requests.comments.store');

    // Страница админа со списком пользователей и правами.
    Route::get('/admin/users', [AdminUserController::class, 'index'])->name('admin.users.index');
    // Создание нового пользователя админом.
    Route::post('/admin/users', [AdminUserController::class, 'store'])->name('admin.users.store');
    // Обновление роли и разрешений пользователя.
    Route::patch('/admin/users/{user}', [AdminUserController::class, 'update'])->name('admin.users.update');
    // Одобрение нового пользователя после самостоятельной регистрации.
    Route::patch('/admin/users/{user}/approve', [AdminUserController::class, 'approve'])->name('admin.users.approve');
    // Отклонение нового пользователя после самостоятельной регистрации.
    Route::patch('/admin/users/{user}/reject', [AdminUserController::class, 'reject'])->name('admin.users.reject');
});
