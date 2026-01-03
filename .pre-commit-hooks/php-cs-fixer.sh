#!/bin/bash
export PHP_CS_FIXER_IGNORE_ENV=1
if command -v php-cs-fixer >/dev/null 2>&1 || [ -f vendor/bin/php-cs-fixer ]; then
	if [ -f vendor/bin/php-cs-fixer ]; then
		vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.php --allow-risky=yes "$@"
	else
		php-cs-fixer fix --config=.php-cs-fixer.php --allow-risky=yes "$@"
	fi
else
	echo "PHP-CS-Fixer not found. Install with: composer require --dev friendsofphp/php-cs-fixer"
	exit 0
fi
