#!/bin/bash
# Try to find PHP executable
PHP_CMD=""
if command -v php7.2 >/dev/null 2>&1; then
	PHP_CMD="php7.2"
elif command -v php >/dev/null 2>&1; then
	PHP_CMD="php"
else
	echo "PHP not found. Skipping syntax check."
	exit 0
fi

for file in "$@"; do
	$PHP_CMD -l "$file" || exit 1
done
