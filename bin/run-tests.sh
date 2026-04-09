#!/usr/bin/env bash
#
# Wait for MySQL, install the WordPress test suite, and run PHPUnit.
#
# Environment variables (with defaults from docker-compose.test.yml):
#   DB_NAME       - Test database name (default: wordpress_test)
#   DB_USER       - Database user (default: root)
#   DB_PASS       - Database password (default: rootpassword)
#   DB_HOST       - Database host (default: db)
#   WP_VERSION    - WordPress version to test against (default: latest)
#   WP_TESTS_DIR  - Path to WP test lib (default: /tmp/wordpress-tests-lib)

set -euo pipefail

DB_NAME="${DB_NAME:-wordpress_test}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-rootpassword}"
DB_HOST="${DB_HOST:-db}"
WP_VERSION="${WP_VERSION:-latest}"

export WP_TESTS_DIR="${WP_TESTS_DIR:-/tmp/wordpress-tests-lib}"

echo "Waiting for MySQL at ${DB_HOST}..."
until mysqladmin ping -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" --silent 2>/dev/null; do
    sleep 1
done
echo "MySQL is ready."

# Create the test database if it doesn't exist.
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\`;" 2>/dev/null

# Install the WordPress test suite.
echo "Installing WordPress test suite..."
bash bin/install-wp-tests.sh "$DB_NAME" "$DB_USER" "$DB_PASS" "$DB_HOST" "$WP_VERSION" true

echo "Running PHPUnit integration tests..."
vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap.php "$@"
