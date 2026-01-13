#!/bin/bash
# Скрипт для экспорта базы данных из OSPanel
# Использование: ./scripts/export-db.sh [output_file.sql]

set -e

# Параметры подключения к БД (из config/config.php или переменные окружения)
DB_HOST="${DB_HOST:-MySQL-8.2}"
DB_PORT="${DB_PORT:-3306}"
DB_NAME="${DB_NAME:-cost_calculator_web}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-}"

# Путь к MySQL (для OSPanel)
MYSQL_BIN="${MYSQL_BIN:-}"
MYSQLDUMP_BIN="${MYSQLDUMP_BIN:-}"

# Определяем путь к MySQL в OSPanel (Windows)
if [ -z "$MYSQL_BIN" ]; then
	if [ -d "C:/OSPanel/modules/database/MySQL-8.0/bin" ]; then
		MYSQL_BIN="C:/OSPanel/modules/database/MySQL-8.0/bin/mysql.exe"
		MYSQLDUMP_BIN="C:/OSPanel/modules/database/MySQL-8.0/bin/mysqldump.exe"
	elif [ -d "C:/OSPanel/modules/database/MySQL-8.2/bin" ]; then
		MYSQL_BIN="C:/OSPanel/modules/database/MySQL-8.2/bin/mysql.exe"
		MYSQLDUMP_BIN="C:/OSPanel/modules/database/MySQL-8.2/bin/mysqldump.exe"
	else
		# Пытаемся найти в PATH
		MYSQL_BIN="mysql"
		MYSQLDUMP_BIN="mysqldump"
	fi
fi

# Имя выходного файла
OUTPUT_FILE="${1:-database/dump_$(date +%Y%m%d_%H%M%S).sql}"

# Создаем директорию если не существует
mkdir -p "$(dirname "$OUTPUT_FILE")"

echo "Экспорт базы данных..."
echo "База данных: $DB_NAME"
echo "Хост: $DB_HOST"
echo "Выходной файл: $OUTPUT_FILE"
echo ""

# Формируем команду mysqldump
if [ -n "$DB_PASS" ]; then
	"$MYSQLDUMP_BIN" -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" \
		--single-transaction \
		--routines \
		--triggers \
		--add-drop-table \
		--default-character-set=utf8mb4 \
		"$DB_NAME" > "$OUTPUT_FILE"
else
	"$MYSQLDUMP_BIN" -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" \
		--single-transaction \
		--routines \
		--triggers \
		--add-drop-table \
		--default-character-set=utf8mb4 \
		"$DB_NAME" > "$OUTPUT_FILE"
fi

if [ $? -eq 0 ]; then
	echo "✓ База данных успешно экспортирована в $OUTPUT_FILE"
	echo "Размер файла: $(du -h "$OUTPUT_FILE" | cut -f1)"
else
	echo "✗ Ошибка при экспорте базы данных"
	exit 1
fi
