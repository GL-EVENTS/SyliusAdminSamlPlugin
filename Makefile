DC = docker compose
BIN = tests/Application/bin/console

phpunit:
	vendor/bin/phpunit

phpspec:
	vendor/bin/phpspec run --ansi --no-interaction -f dot

phpstan:
	vendor/bin/phpstan analyse

behat-js:
	APP_ENV=test vendor/bin/behat --colors --strict --no-interaction -vvv -f progress

install:
	composer install --no-interaction --no-scripts

backend:
	tests/Application/bin/console sylius:install --no-interaction
	tests/Application/bin/console sylius:fixtures:load default --no-interaction

frontend:
	(cd tests/Application && yarn install --pure-lockfile)
	(cd tests/Application && GULP_ENV=prod yarn build)

behat:
	APP_ENV=test vendor/bin/behat --colors --strict --no-interaction -vvv -f progress

init: install backend frontend

ci: init phpstan phpunit phpspec behat

integration: init phpunit behat

static: install phpspec phpstan

fix-cs:
	vendor/bin/ecs check --fix

## Start the project
start:
	@$(DC) up -d --remove-orphans

## Stop the project
stop:
	@$(DC) down

php:
	@$(DC) exec app sh

db-fixtures:
	$(DC) exec app $(BIN) sylius:fixtures:load default --no-interaction

db-database:
	$(DC) exec app $(BIN) d:d:c
	$(DC) exec app $(BIN) d:s:u -f
	$(DC) exec app $(BIN) sylius:fixtures:load default --no-interaction
