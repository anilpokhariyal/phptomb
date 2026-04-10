#!/usr/bin/env sh
set -eu
cd "$(dirname "$0")/.."
docker compose build app
docker compose run --rm app sh -c "composer install --no-interaction && ./vendor/bin/phpunit -c phpunit.e2e.xml"
