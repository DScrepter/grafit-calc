#!/bin/bash
# Скрипт для импорта базы данных на тестовый сервер
# Использование: ./scripts/import-db.sh [input_file.sql] [server_user@server_host] [server_path]

set -e

# Параметры
INPUT_FILE="${1:-database/dump_latest.sql}"
SERVER="${2:-}"
SERVER_PATH="${3:-}"

if [ -z "$SERVER" ]; then
	echo "Использование: $0 [input_file.sql] [user@host] [remote_path]"
	echo ""
	echo "Примеры:"
	echo "  $0 database/dump.sql user@example.com /var/www/cost-calc"
	echo "  $0 database/dump.sql user@192.168.1.100 /home/user/cost-calc"
	exit 1
fi

if [ ! -f "$INPUT_FILE" ]; then
	echo "✗ Файл $INPUT_FILE не найден"
	exit 1
fi

echo "Импорт базы данных на сервер..."
echo "Файл: $INPUT_FILE"
echo "Сервер: $SERVER"
echo ""

# Копируем файл на сервер
REMOTE_FILE="/tmp/dump_$(date +%Y%m%d_%H%M%S).sql"
echo "Копирование файла на сервер..."
scp "$INPUT_FILE" "$SERVER:$REMOTE_FILE"

# Импортируем БД на сервере
echo "Импорт базы данных..."
ssh "$SERVER" "REMOTE_FILE=\"$REMOTE_FILE\"; \
	set -e; \
	DB_HOST=\"\${DB_HOST:-localhost}\"; \
	DB_NAME=\"\${DB_NAME:-cost_calculator_web}\"; \
	DB_USER=\"\${DB_USER:-root}\"; \
	DB_PASS=\"\${DB_PASS:-}\"; \
	if [ -n \"\$DB_PASS\" ]; then \
		mysql -h \"\$DB_HOST\" -u \"\$DB_USER\" -p\"\$DB_PASS\" \"\$DB_NAME\" < \"\$REMOTE_FILE\"; \
	else \
		mysql -h \"\$DB_HOST\" -u \"\$DB_USER\" \"\$DB_NAME\" < \"\$REMOTE_FILE\"; \
	fi; \
	rm -f \"\$REMOTE_FILE\"; \
	echo '✓ База данных успешно импортирована'"

if [ $? -eq 0 ]; then
	echo "✓ Импорт завершен успешно"
else
	echo "✗ Ошибка при импорте базы данных"
	exit 1
fi
