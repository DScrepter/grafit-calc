#!/bin/bash
# Скрипт для определения пути на reg.ru хостинге
# Использование: bash scripts/check-regru-path.sh user@your-server.com

if [ -z "$1" ]; then
	echo "Использование: $0 user@your-server.com"
	exit 1
fi

SERVER="$1"

echo "Проверка структуры директорий на reg.ru хостинге..."
echo ""

ssh "$SERVER" << 'SSH_COMMANDS'
	echo "=== Текущая директория ==="
	pwd
	echo ""
	
	echo "=== Домашняя директория ==="
	echo $HOME
	echo ""
	
	echo "=== Содержимое домашней директории ==="
	ls -la ~ | head -20
	echo ""
	
	echo "=== Поиск директорий domains ==="
	find ~ -maxdepth 2 -type d -name "domains" 2>/dev/null | head -5
	echo ""
	
	echo "=== Поиск public_html ==="
	find ~ -maxdepth 4 -type d -name "public_html" 2>/dev/null | head -5
	echo ""
	
	echo "=== Рекомендуемые пути для reg.ru ==="
	echo "Обычно на reg.ru структура такая:"
	echo "  /home/uXXXXXX/domains/domain.com/public_html"
	echo "  /home/uXXXXXX/domains/subdomain.domain.com/public_html"
	echo ""
	echo "Проверьте панель управления reg.ru для точного пути"
SSH_COMMANDS
