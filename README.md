# ERP HR

Laravel ERP-сайт для кадровых документов: заявки на отпуск и больничный.

## Что есть

- 2 приложения: `Отпуск` и `Больничный`
- поля заявки: дата начала, дата конца, календарные дни, рабочие дни
- роли: `Работник`, `Кадровик`, `Директор`, `Админ`
- PostgreSQL в `.env`
- Blade-интерфейс, CSS, JavaScript
- демо-вход через выбор пользователя
- workflow: работник создает заявку, кадровик проверяет, директор утверждает

## Демо-пользователи

Сиды создают пользователей:

- `employee@example.com` - Работник
- `hr@example.com` - Кадровик
- `director@example.com` - Директор
- `admin@example.com` - Админ

В демо-интерфейсе пароль не нужен: пользователь выбирается на странице входа.

## PostgreSQL

Создайте базу:

```sql
CREATE DATABASE erp_hr;
```

Параметры в `.env`:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=erp_hr
DB_USERNAME=postgres
DB_PASSWORD=
```

Если у PostgreSQL другой пароль или пользователь, измените `.env`.

## Запуск

```bash
composer install
npm install
php artisan key:generate
php artisan migrate:fresh --seed
npm run build
php artisan serve
```

Откройте:

```text
http://127.0.0.1:8000
```

## Проверка

```bash
php artisan test
```
