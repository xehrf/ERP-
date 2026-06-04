<?php

// Пространство имен показывает Laravel, где находится этот класс.
namespace App\Http\Controllers;

// Модель пользователя нужна для создания аккаунта при регистрации.
use App\Models\User;
// RedirectResponse используется, когда метод возвращает перенаправление на другую страницу.
use Illuminate\Http\RedirectResponse;
// Request хранит данные формы, сессию и информацию о текущем запросе.
use Illuminate\Http\Request;
// Auth - встроенная система авторизации Laravel.
use Illuminate\Support\Facades\Auth;
// View используется, когда метод возвращает Blade-страницу.
use Illuminate\View\View;

// Этот контроллер отвечает за вход и выход пользователя.
class AuthController extends Controller
{
    // Показывает страницу входа.
    public function login(): View|RedirectResponse
    {
        // Если пользователь уже вошел, второй раз страницу входа не показываем.
        if (Auth::check()) {
            // Сразу отправляем его в журнал заявок.
            return redirect()->route('requests.index');
        }

        // Если пользователь не вошел, показываем файл resources/views/auth/login.blade.php.
        return view('auth.login');
    }

    // Показывает страницу регистрации.
    public function register(): View|RedirectResponse
    {
        // Если пользователь уже вошел, регистрацию ему не показываем.
        if (Auth::check()) {
            // Сразу отправляем его в журнал заявок.
            return redirect()->route('requests.index');
        }

        // Показываем файл resources/views/auth/register.blade.php.
        return view('auth.register');
    }

    // Обрабатывает отправку формы входа.
    public function store(Request $request): RedirectResponse
    {
        // Проверяем, что email и пароль пришли из формы и имеют правильный формат.
        $credentials = $request->validate([
            // Email обязателен и должен быть email-адресом.
            'email' => ['required', 'email'],
            // Пароль обязателен и должен быть строкой.
            'password' => ['required', 'string'],
        ]);

        // Пытаемся войти через стандартную Laravel-авторизацию.
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            // Если email или пароль неверные, возвращаем пользователя назад с ошибкой.
            return back()
                ->withErrors(['email' => 'Неверный email или пароль.'])
                ->onlyInput('email');
        }

        // Если аккаунт отклонен админом, вход запрещаем.
        if ($request->user()->is_rejected) {
            // Выходим обратно, чтобы пользователь не остался авторизованным.
            Auth::logout();

            // Обновляем сессию после отказа во входе.
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // Показываем понятное сообщение.
            return back()
                ->withErrors(['email' => 'Аккаунт отклонен администратором.'])
                ->onlyInput('email');
        }

        // Если аккаунт еще не одобрен админом, вход запрещаем.
        if (! $request->user()->is_approved) {
            // Выходим обратно, чтобы пользователь не остался авторизованным.
            Auth::logout();

            // Обновляем сессию после отказа во входе.
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // Показываем понятное сообщение.
            return back()
                ->withErrors(['email' => 'Аккаунт ожидает одобрения администратора.'])
                ->onlyInput('email');
        }

        // Обновляем ID сессии после входа, чтобы защититься от подмены сессии.
        $request->session()->regenerate();

        // Отправляем пользователя туда, куда он хотел попасть, или в журнал заявок.
        return redirect()->intended(route('requests.index'));
    }

    // Обрабатывает форму регистрации.
    public function registerStore(Request $request): RedirectResponse
    {
        // Проверяем данные нового пользователя.
        $data = $request->validate([
            // Имя обязательно, максимум 255 символов.
            'name' => ['required', 'string', 'max:255'],
            // Email обязателен, должен быть уникальным в таблице users.
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            // Пароль обязателен, минимум 6 символов, confirmation требует поле password_confirmation.
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        // Создаем нового пользователя как обычного работника.
        $user = User::create([
            // Имя из формы.
            'name' => $data['name'],
            // Email из формы.
            'email' => $data['email'],
            // Пароль автоматически захешируется в модели User.
            'password' => $data['password'],
            // Новые пользователи получают роль кандидата.
            'role' => 'candidate',
            // Новый пользователь пока не получает никаких прав.
            'permissions' => [],
            // Новый пользователь не может войти, пока админ его не одобрит.
            'is_approved' => false,
            // Новый пользователь еще не отклонен.
            'is_rejected' => false,
        ]);

        // После регистрации отправляем на вход и сообщаем, что нужно дождаться админа.
        return redirect()->route('login')->with('success', 'Регистрация отправлена. Дождитесь одобрения администратора.');
    }

    // Выход из системы.
    public function logout(Request $request): RedirectResponse
    {
        // Laravel забывает текущего авторизованного пользователя.
        Auth::logout();

        // Полностью очищаем текущую сессию.
        $request->session()->invalidate();
        // Создаем новый CSRF-токен для безопасности.
        $request->session()->regenerateToken();

        // После выхода отправляем пользователя на страницу входа.
        return redirect()->route('login');
    }
}
