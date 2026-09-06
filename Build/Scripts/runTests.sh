#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/../.."
suite=ci
php_bin="${PHP_BIN:-php}"
while getopts 's:p:' option; do
    case "$option" in
        s) suite="$OPTARG" ;;
        p) php_bin="php$OPTARG" ;;
        *) exit 2 ;;
    esac
done
case "$suite" in
    unit|functional) "$php_bin" vendor/bin/phpunit --testsuite "$suite" ;;
    phpstan) "$php_bin" vendor/bin/phpstan analyse --no-progress ;;
    audit) "$php_bin" scripts/audit-content-elements.php ;;
    ci)
        "$php_bin" vendor/bin/phpunit
        "$php_bin" vendor/bin/phpstan analyse --no-progress
        "$php_bin" scripts/audit-content-elements.php
        ;;
    *) echo "Unknown suite: $suite" >&2; exit 2 ;;
esac
