# Инструкция по установке

## Быстрая установка

### 1. Создание базы данных

Выполните SQL скрипт для создания базы данных:

```bash
mysql -u root -p < database/schema.sql
```

Или через phpMyAdmin:
- Создайте новую базу данных
- Импортируйте файл `database/schema.sql`

### 2. Настройка конфигурации

Скопируйте пример конфигурации:

```bash
cp config/config.example.php config/config.php
```

Отредактируйте `config/config.php`:

```php
<?php
return [
    'database' => [
        'host' => 'localhost',        // Хост MySQL
        'port' => 3306,                // Порт MySQL
        'dbname' => 'cost_calculator_web',  // Имя базы данных
        'username' => 'your_user',     // Имя пользователя MySQL
        'password' => 'your_password', // Пароль MySQL
        'charset' => 'utf8mb4'
    ],
    'app' => [
        'name' => 'Калькулятор себестоимости',
        'debug' => true,
        'timezone' => 'Europe/Moscow'
    ],
    'session' => [
        'lifetime' => 3600,
        'name' => 'cost_calc_session'
    ]
];
```

### 3. Миграция данных (опционально)

Если у вас есть данные в SQLite базе из десктопной версии:

```bash
php database/migrate.php
```

Скрипт перенесет:
- Единицы измерения
- Материалы
- Операции
- Типы изделий
- Параметры типов изделий
- Коэффициенты

### 4. Настройка веб-сервера

#### Apache

1. Убедитесь, что включен `mod_rewrite`
2. Скопируйте папку `web-app` в директорию веб-сервера
3. Настройте виртуальный хост (опционально)

Пример конфигурации Apache:

```apache
<VirtualHost *:80>
    ServerName cost-calc.local
    DocumentRoot /path/to/web-app
    
    <Directory /path/to/web-app>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

#### Nginx

Пример конфигурации:

```nginx
server {
    listen 80;
    server_name cost-calc.local;
    root /path/to/web-app;
    index index.html;

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

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

#### Встроенный PHP сервер (для разработки)

```bash
cd web-app
php -S localhost:8000
```

Откройте в браузере: `http://localhost:8000/frontend/login.html`

### 5. Права доступа

Убедитесь, что веб-сервер имеет права на чтение файлов:

```bash
chmod -R 755 web-app/
chmod 644 config/config.php
```

## Первый запуск

1. Откройте приложение в браузере
2. Нажмите "Регистрация"
3. Создайте первого пользователя
4. Войдите в систему
5. Начните работу!

## Проверка установки

После установки проверьте:

1. ✅ База данных создана и содержит таблицы
2. ✅ Конфигурация настроена правильно
3. ✅ Веб-сервер может читать файлы
4. ✅ PHP может подключаться к MySQL
5. ✅ Страница входа открывается
6. ✅ Регистрация работает
7. ✅ После входа открывается главная страница

## Решение проблем

### Ошибка подключения к базе данных

- Проверьте параметры в `config/config.php`
- Убедитесь, что MySQL запущен
- Проверьте права пользователя MySQL

### Ошибка 404 на API

- Проверьте настройки mod_rewrite (Apache)
- Проверьте конфигурацию Nginx
- Убедитесь, что пути к API правильные

### Сессии не работают

- Проверьте права на директорию сессий PHP
- Убедитесь, что cookies разрешены в браузере

## Обновление

При обновлении приложения:

1. Сделайте резервную копию базы данных
2. Обновите файлы
3. Выполните миграции БД (если есть)
4. Очистите кэш браузера
