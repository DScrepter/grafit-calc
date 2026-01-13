# Быстрый старт для OSPanel

Краткая инструкция для тех, кто хочет быстро запустить приложение.

## За 5 минут:

### 1. Скопируйте папку
```
C:\OSPanel\domains\cost-calc\  (весь содержимое web-app)
```

### 2. Создайте базу данных
- Откройте phpMyAdmin (OSPanel → Дополнительно → phpMyAdmin)
- Создайте БД: `cost_calculator_web`
- Импортируйте: `database/schema.sql`

### 3. Настройте конфиг
```bash
# Скопируйте
config/config.example.php → config/config.php
```

Откройте `config/config.php` и укажите:
```php
'username' => 'root',
'password' => '',  // Обычно пустой для OSPanel
```

### 4. (Опционально) Миграция данных
```cmd
cd C:\OSPanel\domains\cost-calc\database
C:\OSPanel\modules\php\PHP-7.4\php.exe migrate.php
```

### 5. Откройте в браузере
```
http://localhost/cost-calc/frontend/login.html
```

### 6. Зарегистрируйтесь и войдите!

---

**Подробная инструкция:** см. `INSTALL_OSPANEL.md`
