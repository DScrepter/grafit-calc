# Веб-приложение калькулятора себестоимости

Веб-версия калькулятора себестоимости изделий графитового производства.

## Требования

- PHP 7.4 или выше
- MySQL 5.7+ / MariaDB 10.3+
- Apache с mod_rewrite или Nginx
- Веб-браузер с поддержкой ES6+

## Установка

### 1. Настройка базы данных

Создайте базу данных MySQL:

```bash
mysql -u root -p < database/schema.sql
```

Или выполните SQL скрипт вручную через phpMyAdmin или другой клиент MySQL.

### 2. Настройка конфигурации

Скопируйте файл конфигурации и заполните данные:

```bash
cp config/config.example.php config/config.php
```

Отредактируйте `config/config.php` и укажите параметры подключения к базе данных:

```php
'database' => [
    'host' => 'localhost',
    'port' => 3306,
    'dbname' => 'cost_calculator_web',
    'username' => 'your_username',
    'password' => 'your_password',
    'charset' => 'utf8mb4'
],
```

### 3. Миграция данных из SQLite

Если у вас есть данные в SQLite базе (из десктопной версии), выполните миграцию:

```bash
php database/migrate.php
```

Скрипт перенесет все справочники из SQLite в MySQL.

### 4. Настройка веб-сервера

#### Apache

Убедитесь, что включен mod_rewrite. Файл `.htaccess` уже настроен.

#### Nginx

Добавьте в конфигурацию:

```nginx
location / {
    try_files $uri $uri/ /frontend/index.html;
}

location /backend/api {
    try_files $uri =404;
    fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
    fastcgi_index index.php;
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
}
```

### 5. Права доступа

Убедитесь, что веб-сервер имеет права на чтение файлов:

```bash
chmod -R 755 web-app/
```

## Первый запуск

1. Откройте приложение в браузере: `http://localhost/web-app/frontend/login.html`
2. Зарегистрируйте первого пользователя
3. Войдите в систему
4. Начните работу с калькулятором

## Структура проекта

```
web-app/
├── backend/              # PHP бэкенд
│   ├── api/             # API endpoints
│   ├── classes/         # Классы для работы с БД
│   └── config/          # Конфигурация
├── frontend/            # Фронтенд
│   ├── css/            # Стили
│   ├── js/             # JavaScript
│   ├── index.html      # Главная страница
│   └── login.html      # Страница входа
├── database/           # SQL скрипты
│   ├── schema.sql      # Схема БД
│   └── migrate.php     # Миграция из SQLite
└── config/             # Конфигурация проекта
```

## API Endpoints

Все API endpoints находятся в `/backend/api/`:

- `auth.php` - авторизация/регистрация
- `materials.php` - CRUD материалы
- `operations.php` - CRUD операции
- `units.php` - CRUD единицы измерения
- `product_types.php` - получение типов изделий
- `coefficients.php` - CRUD коэффициенты
- `calculate.php` - расчет себестоимости

## Безопасность

- Все запросы к БД используют prepared statements (PDO)
- Пароли хешируются с помощью `password_hash()`
- Авторизация через PHP сессии
- API endpoints требуют авторизации (кроме auth.php)

## Разработка

Для разработки рекомендуется использовать локальный сервер разработки PHP:

```bash
cd web-app
php -S localhost:8000
```

Затем откройте в браузере: `http://localhost:8000/frontend/login.html`

## Лицензия

© 2024 Graphite Production. Все права защищены.
