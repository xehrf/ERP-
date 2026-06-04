<?php

// Пространство имен feature-тестов.
namespace Tests\Feature;

// Модель заявки.
use App\Models\DocumentRequest;
// Модель пользователя.
use App\Models\User;
// RefreshDatabase пересоздает базу перед тестами.
use Illuminate\Foundation\Testing\RefreshDatabase;
// Базовый класс тестов Laravel.
use Tests\TestCase;

// Feature-тесты проверяют работу сайта как пользователь.
class ExampleTest extends TestCase
{
    // Каждый тест запускается на чистой базе.
    use RefreshDatabase;

    // Проверяем, что гость без входа не может открыть журнал заявок.
    public function test_guest_is_redirected_to_login_from_requests(): void
    {
        // Открываем журнал заявок и ожидаем редирект на login.
        $this->get(route('requests.index'))->assertRedirect(route('login'));
    }

    // Проверяем настоящий вход по email и паролю.
    public function test_user_can_login_with_email_and_password(): void
    {
        // Создаем пользователя для теста.
        User::factory()->create([
            'email' => 'employee@example.com',
            'password' => 'password',
            'role' => 'employee',
            'permissions' => ['create_requests', 'comment'],
        ]);

        // Отправляем форму входа.
        $this->post(route('login.store'), [
            'email' => 'employee@example.com',
            'password' => 'password',
        // После успешного входа должен быть редирект в журнал.
        ])->assertRedirect(route('requests.index'));

        // Проверяем, что Laravel считает пользователя авторизованным.
        $this->assertAuthenticated();
    }

    // Проверяем, что новый пользователь может зарегистрироваться, но ждет одобрения админа.
    public function test_user_can_register_and_waits_for_admin_approval(): void
    {
        // Отправляем форму регистрации.
        $this->post(route('register.store'), [
            'name' => 'Самостоятельный работник',
            'email' => 'self.employee@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertRedirect(route('login'));

        // Проверяем, что пользователь появился в базе как кандидат, но еще не одобрен.
        $this->assertDatabaseHas('users', [
            'name' => 'Самостоятельный работник',
            'email' => 'self.employee@example.com',
            'role' => 'candidate',
            'is_approved' => false,
            'is_rejected' => false,
        ]);

        // После регистрации пользователь еще не авторизован.
        $this->assertGuest();
    }

    // Проверяем, что пользователь без одобрения не может войти.
    public function test_unapproved_user_cannot_login(): void
    {
        // Создаем не одобренного пользователя.
        User::factory()->create([
            'email' => 'waiting@example.com',
            'password' => 'password',
            'role' => 'employee',
            'permissions' => ['create_requests', 'comment'],
            'is_approved' => false,
            'is_rejected' => false,
        ]);

        // Пытаемся войти.
        $this->post(route('login.store'), [
            'email' => 'waiting@example.com',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        // Пользователь остается гостем.
        $this->assertGuest();
    }

    // Проверяем, что работник может создать отпуск и комментарий.
    public function test_employee_can_create_vacation_request_with_comment(): void
    {
        // Создаем работника с правами.
        $user = User::factory()->create([
            'role' => 'employee',
            'permissions' => ['create_requests', 'comment'],
        ]);

        // Авторизуемся как этот пользователь и отправляем форму заявки.
        $response = $this
            ->actingAs($user)
            ->post(route('requests.store'), [
                'type' => 'vacation',
                'start_date' => '2026-06-01',
                'end_date' => '2026-06-03',
                'comment' => 'Ежегодный отпуск',
            ]);

        // Берем первую созданную заявку.
        $request = DocumentRequest::first();

        // После создания пользователь попадает на страницу заявки.
        $response->assertRedirect(route('requests.show', $request));

        // Проверяем, что заявка появилась в базе с правильными днями и статусом.
        $this->assertDatabaseHas(DocumentRequest::class, [
            'user_id' => $user->id,
            'type' => 'vacation',
            'calendar_days' => 3,
            'working_days' => 3,
            'status' => 'pending_hr',
        ]);

        // Проверяем, что комментарий тоже сохранился.
        $this->assertDatabaseHas('request_comments', [
            'document_request_id' => $request->id,
            'user_id' => $user->id,
            'body' => 'Ежегодный отпуск',
        ]);

        // Проверяем, что история заявки записалась.
        $this->assertDatabaseHas('request_histories', [
            'document_request_id' => $request->id,
            'action' => 'created',
            'title' => 'Заявка создана',
        ]);
    }

    // Проверяем, что сотрудник не может создать отпуск/больничный на уже занятые даты.
    public function test_employee_cannot_create_overlapping_request(): void
    {
        // Создаем работника с правом создавать заявки.
        $user = User::factory()->create([
            'role' => 'employee',
            'permissions' => ['create_requests', 'comment'],
        ]);

        // Уже существующий отпуск сотрудника.
        DocumentRequest::create([
            'user_id' => $user->id,
            'type' => 'vacation',
            'start_date' => '2026-06-04',
            'end_date' => '2026-06-20',
            'calendar_days' => 17,
            'working_days' => 13,
            'status' => 'pending_hr',
        ]);

        // Пытаемся создать больничный внутри уже занятого периода.
        $this
            ->actingAs($user)
            ->from(route('requests.create'))
            ->post(route('requests.store'), [
                'type' => 'sick_leave',
                'start_date' => '2026-06-10',
                'end_date' => '2026-06-12',
                'comment' => 'Больничный в период отпуска',
            ])
            ->assertRedirect(route('requests.create'))
            ->assertSessionHasErrors('start_date');

        // В базе должна остаться только первая заявка.
        $this->assertSame(1, DocumentRequest::count());
    }

    // Проверяем, что отклоненную заявку можно повторить.
    public function test_employee_can_repeat_rejected_request(): void
    {
        // Создаем работника.
        $user = User::factory()->create([
            'role' => 'employee',
            'permissions' => ['create_requests', 'comment'],
        ]);

        // Создаем отклоненную заявку.
        $request = DocumentRequest::create([
            'user_id' => $user->id,
            'type' => 'vacation',
            'start_date' => '2026-06-04',
            'end_date' => '2026-06-20',
            'calendar_days' => 17,
            'working_days' => 13,
            'status' => 'rejected',
        ]);

        // Открываем повтор заявки.
        $this
            ->actingAs($user)
            ->get(route('requests.repeat', $request))
            ->assertRedirect(route('requests.create', [
                'type' => 'vacation',
                'start_date' => '2026-06-04',
                'end_date' => '2026-06-20',
                'comment' => 'Повтор заявки #'.$request->id,
            ]));
    }

    // Проверяем, что кадровик получает уведомление о новой заявке.
    public function test_hr_gets_notification_about_new_request(): void
    {
        // Создаем работника.
        $employee = User::factory()->create([
            'role' => 'employee',
            'permissions' => ['create_requests', 'comment'],
        ]);

        // Создаем кадровика с правом согласования.
        $hr = User::factory()->create([
            'role' => 'hr',
            'permissions' => ['view_all_requests', 'approve_hr', 'comment'],
            'is_approved' => true,
            'is_rejected' => false,
        ]);

        // Работник создает заявку.
        $this
            ->actingAs($employee)
            ->post(route('requests.store'), [
                'type' => 'vacation',
                'start_date' => '2026-07-01',
                'end_date' => '2026-07-03',
            ]);

        // Проверяем уведомление у кадровика.
        $this->assertDatabaseHas('erp_notifications', [
            'user_id' => $hr->id,
            'title' => 'Новая заявка ожидает кадровика',
        ]);
    }

    // Проверяем, что админ может менять роли и права.
    public function test_admin_can_update_user_permissions(): void
    {
        // Создаем администратора.
        $admin = User::factory()->create([
            'role' => 'admin',
            'permissions' => ['manage_users'],
        ]);
        // Создаем обычного работника.
        $employee = User::factory()->create([
            'role' => 'employee',
            'permissions' => ['create_requests'],
        ]);

        // Админ отправляет форму изменения роли и прав.
        $this
            ->actingAs($admin)
            ->patch(route('admin.users.update', $employee), [
                'role' => 'hr',
                'permissions' => ['view_all_requests', 'approve_hr', 'comment'],
            ])
            ->assertRedirect();

        // Обновляем модель из базы.
        $employee->refresh();

        // Проверяем новую роль и права.
        $this->assertSame('hr', $employee->role);
        $this->assertTrue($employee->canApproveHr());
        $this->assertTrue($employee->canComment());
    }

    // Проверяем, что админ может создать нового пользователя.
    public function test_admin_can_create_user(): void
    {
        // Создаем администратора.
        $admin = User::factory()->create([
            'role' => 'admin',
            'permissions' => ['manage_users'],
        ]);

        // Админ отправляет форму создания пользователя.
        $this
            ->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Новый сотрудник',
                'email' => 'new.employee@example.com',
                'password' => 'secret123',
                'role' => 'employee',
                'permissions' => ['create_requests', 'comment'],
            ])
            ->assertRedirect();

        // Проверяем, что пользователь появился в базе.
        $this->assertDatabaseHas('users', [
            'name' => 'Новый сотрудник',
            'email' => 'new.employee@example.com',
            'role' => 'employee',
            'is_approved' => true,
        ]);
    }

    // Проверяем, что админ может одобрить пользователя после регистрации без выдачи прав.
    public function test_admin_can_approve_registered_user_without_permissions(): void
    {
        // Создаем администратора.
        $admin = User::factory()->create([
            'role' => 'admin',
            'permissions' => ['manage_users'],
        ]);

        // Создаем пользователя, который ожидает одобрения.
        $employee = User::factory()->create([
            'role' => 'employee',
            'permissions' => [],
            'is_approved' => false,
            'is_rejected' => false,
        ]);

        // Админ одобряет пользователя.
        $this
            ->actingAs($admin)
            ->patch(route('admin.users.approve', $employee))
            ->assertRedirect();

        // Проверяем, что пользователь стал одобренным.
        $employee->refresh();

        $this->assertTrue($employee->is_approved);
        $this->assertFalse($employee->is_rejected);
        $this->assertFalse($employee->canCreateRequests());
        $this->assertFalse($employee->canComment());
    }

    // Проверяем, что админ может отклонить пользователя после регистрации.
    public function test_admin_can_reject_registered_user(): void
    {
        // Создаем администратора.
        $admin = User::factory()->create([
            'role' => 'admin',
            'permissions' => ['manage_users'],
        ]);

        // Создаем пользователя, который ожидает решения.
        $employee = User::factory()->create([
            'role' => 'employee',
            'permissions' => [],
            'is_approved' => false,
            'is_rejected' => false,
        ]);

        // Админ отклоняет пользователя.
        $this
            ->actingAs($admin)
            ->patch(route('admin.users.reject', $employee))
            ->assertRedirect();

        // Проверяем, что пользователь отклонен.
        $employee->refresh();

        $this->assertFalse($employee->is_approved);
        $this->assertTrue($employee->is_rejected);
    }

    // Проверяем, что отклоненный пользователь не может войти.
    public function test_rejected_user_cannot_login(): void
    {
        // Создаем отклоненного пользователя.
        User::factory()->create([
            'email' => 'rejected@example.com',
            'password' => 'password',
            'role' => 'employee',
            'permissions' => [],
            'is_approved' => false,
            'is_rejected' => true,
        ]);

        // Пытаемся войти.
        $this->post(route('login.store'), [
            'email' => 'rejected@example.com',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        // Пользователь остается гостем.
        $this->assertGuest();
    }
}
