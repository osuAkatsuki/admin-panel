#!/bin/bash
if command -v phpcs >/dev/null 2>&1 || [ -f vendor/bin/phpcs ]; then
	if [ -f vendor/bin/phpcs ]; then
		OUTPUT=$(vendor/bin/phpcs --standard=.phpcs.xml --extensions=php --warning-severity=0 "$@" 2>&1)
		EXIT_CODE=$?
		echo "$OUTPUT"
		# Only fail if there are actual ERROR messages (not just warnings)
		if echo "$OUTPUT" | grep -q "FOUND.*ERROR"; then
			exit 1
		fi
		exit 0
	else
		OUTPUT=$(phpcs --standard=.phpcs.xml --extensions=php --warning-severity=0 "$@" 2>&1)
		EXIT_CODE=$?
		echo "$OUTPUT"
		# Only fail if there are actual ERROR messages (not just warnings)
		if echo "$OUTPUT" | grep -q "FOUND.*ERROR"; then
			exit 1
		fi
		exit 0
	fi
else
	echo "PHP_CodeSniffer not found. Install with: composer require --dev squizlabs/php_codesniffer"
	exit 0
fi
