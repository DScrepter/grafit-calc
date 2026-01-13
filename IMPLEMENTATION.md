# Описание реализации веб-приложения

## Что было реализовано

### 1. База данных MySQL

- Создана полная схема базы данных (`database/schema.sql`)
- Таблицы: users, units, materials, operations, product_types, product_type_parameters, coefficients, calculations
- Все внешние ключи и индексы настроены

### 2. PHP Бэкенд

#### Классы:
- `Database` - синглтон для работы с MySQL через PDO
- `Auth` - авторизация через PHP сессии, регистрация, вход/выход
- `MaterialManager` - CRUD операции для материалов
- `OperationManager` - CRUD операции для операций
- `ProductTypeManager` - работа с типами изделий и вычисление формул
- `Calculator` - основная логика расчета себестоимости

#### API Endpoints:
- `/backend/api/auth.php` - авторизация, регистрация, проверка сессии
- `/backend/api/materials.php` - CRUD материалы
- `/backend/api/operations.php` - CRUD операции
- `/backend/api/units.php` - CRUD единицы измерения
- `/backend/api/product_types.php` - получение типов изделий
- `/backend/api/coefficients.php` - CRUD коэффициенты
- `/backend/api/calculate.php` - расчет себестоимости

### 3. Фронтенд

#### HTML:
- `login.html` - страница входа и регистрации
- `index.html` - главная страница приложения с навигацией

#### CSS:
- `style.css` - полный набор стилей, повторяющий внешний вид десктопного приложения
- Адаптивный дизайн
- Модальные окна
- Таблицы с сортировкой
- Формы и кнопки

#### JavaScript:
- `api.js` - клиент для работы с API
- `app.js` - главный файл приложения, управление страницами
- `login.js` - логика страницы входа
- `navigation.js` - навигация по приложению
- `calculator.js` - калькулятор себестоимости с диалогом выбора операций
- `materials.js` - управление материалами
- `operations.js` - управление операциями
- `units.js` - управление единицами измерения
- `product_types.js` - просмотр типов изделий
- `coefficients.js` - управление коэффициентами

### 4. Миграция данных

- Скрипт `database/migrate.php` для переноса данных из SQLite в MySQL
- Сохранение всех ID и связей
- Поддержка транзакций для целостности данных

### 5. Безопасность

- Prepared statements (PDO) для всех SQL запросов
- Хеширование паролей через `password_hash()`
- Авторизация через PHP сессии
- Проверка авторизации на всех API endpoints (кроме auth.php)
- Валидация входных данных

### 6. Функциональность

#### Калькулятор:
- Выбор материала из справочника
- Выбор типа изделия
- Динамическая загрузка параметров в зависимости от типа изделия
- Добавление операций с коэффициентами сложности
- Расчет объемов (изделие, заготовка, отходы)
- Расчет масс
- Расчет стоимости материала
- Расчет стоимости операций (зарплата)
- Расчет коэффициентов (проценты от зарплаты)
- Итоговая себестоимость

#### Справочники:
- Материалы - полный CRUD
- Операции - полный CRUD
- Единицы измерения - полный CRUD
- Типы изделий - просмотр
- Коэффициенты - полный CRUD

## Структура файлов

```
web-app/
├── backend/
│   ├── api/                    # API endpoints
│   │   ├── auth.php
│   │   ├── materials.php
│   │   ├── operations.php
│   │   ├── units.php
│   │   ├── product_types.php
│   │   ├── coefficients.php
│   │   └── calculate.php
│   ├── classes/                # PHP классы
│   │   ├── Database.php
│   │   ├── Auth.php
│   │   ├── MaterialManager.php
│   │   ├── OperationManager.php
│   │   ├── ProductTypeManager.php
│   │   └── Calculator.php
│   └── config/                 # Конфигурация
│       ├── config.php
│       └── database.php
├── frontend/
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   ├── api.js
│   │   ├── app.js
│   │   ├── login.js
│   │   ├── navigation.js
│   │   ├── calculator.js
│   │   ├── materials.js
│   │   ├── operations.js
│   │   ├── units.js
│   │   ├── product_types.js
│   │   └── coefficients.js
│   ├── index.html
│   └── login.html
├── database/
│   ├── schema.sql
│   └── migrate.php
├── config/
│   └── config.example.php
├── .htaccess
├── .gitignore
├── README.md
├── INSTALL.md
└── IMPLEMENTATION.md
```

## Особенности реализации

1. **Чистый PHP** - без фреймворков для простоты и скорости
2. **Vanilla JavaScript** - без сборщиков, работает сразу
3. **PDO** - безопасная работа с БД
4. **REST API** - стандартные HTTP методы (GET, POST, PUT, DELETE)
5. **Сессии PHP** - простая авторизация
6. **Адаптивный дизайн** - работает на разных экранах

## Соответствие десктопной версии

Веб-версия полностью повторяет функциональность десктопного приложения:
- ✅ Все справочники
- ✅ Калькулятор с операциями
- ✅ Расчет себестоимости
- ✅ Внешний вид (адаптирован для веба)
- ✅ Логика расчетов идентична

## Следующие шаги (опционально)

- Добавить экспорт расчетов в Excel/PDF
- Добавить сохранение расчетов в БД
- Добавить историю расчетов
- Улучшить UI/UX
- Добавить темную тему
- Добавить мультиязычность
