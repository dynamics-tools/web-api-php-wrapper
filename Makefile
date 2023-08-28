test:
	composer install -o
	vendor/bin/phpunit --bootstrap vendor/autoload.php tests