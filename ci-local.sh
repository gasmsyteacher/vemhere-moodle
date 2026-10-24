#!/usr/bin/env bash
# The checks the moodle.org plugins directory reviews, run locally (GitHub Actions
# are off on this account). Needs the dev rig in .moodle-dev/ (Moodle 4.5 in Docker,
# PHPUnit initialised) and moodle-cs in .moodle-dev/tools.
set -euo pipefail
cd "$(dirname "$0")"
echo "== php -l"
docker run --rm -v "$PWD":/p -w /p php:8.2-cli sh -c 'for f in $(find local mod -name "*.php"); do php -l "$f" >/dev/null || exit 1; done'
echo "== moodle-cs"
docker run --rm -u "$(id -u):$(id -g)" -v "$PWD":/p -w /p composer:2 \
  .moodle-dev/tools/vendor/bin/phpcs --standard=moodle --extensions=php -q local/vemhere mod/vemhere
echo "== PHPUnit"
for suite in local_vemhere_testsuite mod_vemhere_testsuite; do
  docker exec -u "$(id -u):$(id -g)" -e HOME=/tmp -w /var/www/html vemhere-moodle-dev-web-1 vendor/bin/phpunit --testsuite "$suite"
done
echo "== all green"
