<?php

// Пространство имен контроллеров.
namespace App\Http\Controllers;

// Модель пользователя.
use App\Models\User;
// Ответ-перенаправление.
use Illuminate\Http\RedirectResponse;
// Данные текущего запроса.
use Illuminate\Http\Request;
use Illuminate\Support\Str;
// Rule нужен для проверки, что роль/разрешение входят в разрешенный список.
use Illuminate\Validation\Rule;
// Blade-страница.
use Illuminate\View\View;

// Контроллер админки для управления пользователями.
class AdminUserController extends Controller
{
    // Показывает страницу "Пользователи и права".
    public function index(Request $request): View
    {
        // Доступ только у пользователя с правом manage_users.
        abort_unless($request->user()->canManageUsers(), 403);

        // Текст из поля поиска. По нему ищем сотрудника в админке.
        $search = trim((string) $request->query('q', ''));

        // Базовый запрос к таблице users.
        $usersQuery = User::query()->orderBy('name');

        // Если админ ввел текст, фильтруем сотрудников по имени, email и роли.
        if ($search !== '') {
            // Нормализуем текст поиска, чтобы регистр букв не мешал результатам.
            $normalizedSearch = Str::lower($search);

            // Дополнительно ищем роль по русскому названию: например "директор" найдет role = director.
            $matchingRoleKeys = collect(User::availableRoles())
                ->filter(fn (string $label, string $key) => Str::contains(Str::lower($label.' '.$key), $normalizedSearch))
                ->keys()
                ->all();

            $usersQuery->where(function ($query) use ($normalizedSearch, $matchingRoleKeys) {
                $query
                    ->whereRaw('LOWER(name) LIKE ?', ['%'.$normalizedSearch.'%'])
                    ->orWhereRaw('LOWER(email) LIKE ?', ['%'.$normalizedSearch.'%'])
                    ->orWhereRaw('LOWER(role) LIKE ?', ['%'.$normalizedSearch.'%']);

                if ($matchingRoleKeys !== []) {
                    $query->orWhereIn('role', $matchingRoleKeys);
                }
            });
        }

        // Возвращаем Blade-страницу admin/users.blade.php.
        return view('admin.users', [
            // Текущий пользователь.
            'currentUser' => $request->user(),
            // Все пользователи из базы.
            'users' => $usersQuery->get(),
            'search' => $search,
            // Список ролей для select.
            'roles' => User::availableRoles(),
            // Список разрешений для чекбоксов.
            'permissions' => User::availablePermissions(),
        ]);
    }

    // Создает нового пользователя.
    public function store(Request $request): RedirectResponse
    {
        // Создавать пользователей может только админ с правом manage_users.
        abort_unless($request->user()->canManageUsers(), 403);

        // Проверяем данные формы создания пользователя.
        $data = $request->validate([
            // Имя обязательно.
            'name' => ['required', 'string', 'max:255'],
            // Email обязателен, должен быть уникальным.
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            // Пароль обязателен, минимум 6 символов.
            'password' => ['required', 'string', 'min:6'],
            // Роль должна быть одной из доступных ролей.
            'role' => ['required', Rule::in(array_keys(User::availableRoles()))],
            // Разрешения необязательны и приходят массивом.
            'permissions' => ['nullable', 'array'],
            // Каждое разрешение должно быть из разрешенного списка.
            'permissions.*' => [Rule::in(array_keys(User::availablePermissions()))],
        ]);

        // Создаем пользователя в таблице users.
        User::create([
            // Имя пользователя.
            'name' => $data['name'],
            // Email для входа.
            'email' => $data['email'],
            // Пароль автоматически захешируется в модели User.
            'password' => $data['password'],
            // Роль пользователя.
            'role' => $data['role'],
            // Права пользователя сохраняются JSON-массивом.
            'permissions' => array_values($data['permissions'] ?? []),
            // Пользователь, созданный админом, сразу одобрен.
            'is_approved' => true,
        ]);

        // Возвращаемся назад с сообщением об успехе.
        return back()->with('success', 'Пользователь создан.');
    }

    // Обновляет роль и права существующего пользователя.
    public function update(Request $request, User $user): RedirectResponse
    {
        // Менять права может только пользователь с manage_users.
        abort_unless($request->user()->canManageUsers(), 403);

        // Проверяем данные формы обновления.
        $data = $request->validate([
            // Роль должна быть из списка доступных ролей.
            'role' => ['required', Rule::in(array_keys(User::availableRoles()))],
            // Разрешения необязательны.
            'permissions' => ['nullable', 'array'],
            // Каждое разрешение должно быть из списка доступных разрешений.
            'permissions.*' => [Rule::in(array_keys(User::availablePermissions()))],
        ]);

        // Обновляем пользователя.
        $user->update([
            // Новая роль.
            'role' => $data['role'],
            // Новый список разрешений.
            'permissions' => array_values($data['permissions'] ?? []),
        ]);

        // Возвращаемся назад с сообщением.
        return back()->with('success', 'Права пользователя обновлены.');
    }

    // Одобряет пользователя, который зарегистрировался сам.
    public function approve(Request $request, User $user): RedirectResponse
    {
        // Одобрять пользователей может только админ с правом manage_users.
        abort_unless($request->user()->canManageUsers(), 403);

        // Меняем статус пользователя на "одобрен".
        $user->update([
            'is_approved' => true,
            'is_rejected' => false,
        ]);

        // Возвращаемся назад с сообщением.
        return back()->with('success', 'Пользователь одобрен.');
    }

    // Отклоняет пользователя, который зарегистрировался сам.
    public function reject(Request $request, User $user): RedirectResponse
    {
        // Отклонять пользователей может только админ с правом manage_users.
        abort_unless($request->user()->canManageUsers(), 403);

        // Меняем статус пользователя на "отклонен".
        $user->update([
            'is_approved' => false,
            'is_rejected' => true,
        ]);

        // Возвращаемся назад с сообщением.
        return back()->with('success', 'Пользователь отклонен.');
    }
}
