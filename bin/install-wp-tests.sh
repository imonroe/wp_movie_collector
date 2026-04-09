#!/usr/bin/env bash
#
# Install WordPress test suite for integration testing.
#
# Usage: bin/install-wp-tests.sh <db-name> <db-user> <db-pass> [db-host] [wp-version] [skip-db-creation]
#
# Example: bin/install-wp-tests.sh wordpress_test root '' localhost latest

if [ $# -lt 3 ]; then
	echo "Usage: $0 <db-name> <db-user> <db-pass> [db-host] [wp-version] [skip-db-creation]"
	exit 1
fi

DB_NAME=$1
DB_USER=$2
DB_PASS=$3
DB_HOST=${4-localhost}
WP_VERSION=${5-latest}
SKIP_DB_CREATE=${6-false}

TMPDIR=${TMPDIR-/tmp}
TMPDIR=$(echo "$TMPDIR" | sed -e "s/\/$//")
WP_TESTS_DIR=${WP_TESTS_DIR-$TMPDIR/wordpress-tests-lib}
WP_CORE_DIR=${WP_CORE_DIR-$TMPDIR/wordpress}

download() {
	if [ "$(which curl)" ]; then
		curl -s "$1" > "$2";
	elif [ "$(which wget)" ]; then
		wget -nv -O "$2" "$1"
	fi
}

if [ "$WP_VERSION" = "latest" ]; then
	local_wp_version="latest"
	WP_TESTS_TAG="trunk"
elif [[ "$WP_VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
	local_wp_version=$WP_VERSION
	WP_TESTS_TAG="tags/$WP_VERSION"
elif [[ "$WP_VERSION" =~ ^[0-9]+\.[0-9]+$ ]]; then
	# When given a minor version (e.g. 6.4), get the latest patch.
	local_wp_version=$WP_VERSION
	WP_TESTS_TAG="branches/$WP_VERSION"
else
	local_wp_version=$WP_VERSION
	WP_TESTS_TAG=$WP_VERSION
fi

set -ex

install_wp() {
	if [ -d "$WP_CORE_DIR" ]; then
		return
	fi

	mkdir -p "$WP_CORE_DIR"

	if [ "$WP_VERSION" = "latest" ]; then
		local ARCHIVE_URL='https://wordpress.org/latest.tar.gz'
	else
		local ARCHIVE_URL="https://wordpress.org/wordpress-$WP_VERSION.tar.gz"
	fi

	download "$ARCHIVE_URL" /tmp/wordpress.tar.gz
	tar --strip-components=1 -zxmf /tmp/wordpress.tar.gz -C "$WP_CORE_DIR"

	download https://raw.githubusercontent.com/marber/wp-content/refs/heads/master/db.php "$WP_CORE_DIR/wp-content/db.php"
}

install_test_suite() {
	# Set up testing suite if it doesn't yet exist.
	if [ -d "$WP_TESTS_DIR" ]; then
		return
	fi

	mkdir -p "$WP_TESTS_DIR"

	local TESTS_LIB_URL
	if [ "$WP_TESTS_TAG" = "trunk" ]; then
		TESTS_LIB_URL="https://develop.svn.wordpress.org/trunk/tests/phpunit"
	else
		TESTS_LIB_URL="https://develop.svn.wordpress.org/$WP_TESTS_TAG/tests/phpunit"
	fi

	svn export --quiet --force "$TESTS_LIB_URL/includes/" "$WP_TESTS_DIR/includes/"
	svn export --quiet --force "$TESTS_LIB_URL/data/" "$WP_TESTS_DIR/data/"

	if [ ! -f wp-tests-config.php ]; then
		download "https://develop.svn.wordpress.org/$WP_TESTS_TAG/wp-tests-config-sample.php" "$WP_TESTS_DIR/wp-tests-config.php"
		# Point the config to the WP install.
		sed -i "s:dirname( __FILE__ ) . '/src/':'$WP_CORE_DIR/':" "$WP_TESTS_DIR/wp-tests-config.php"
		sed -i "s/youremptytestdbnamehere/$DB_NAME/" "$WP_TESTS_DIR/wp-tests-config.php"
		sed -i "s/yourusernamehere/$DB_USER/" "$WP_TESTS_DIR/wp-tests-config.php"
		sed -i "s/yourpasswordhere/$DB_PASS/" "$WP_TESTS_DIR/wp-tests-config.php"
		sed -i "s|localhost|${DB_HOST}|" "$WP_TESTS_DIR/wp-tests-config.php"
	fi
}

install_db() {
	if [ "$SKIP_DB_CREATE" = "true" ]; then
		return 0
	fi

	local EXTRA=""
	if [ -n "$DB_PASS" ]; then
		EXTRA=" -p$DB_PASS"
	fi

	# Create database if it doesn't exist.
	mysqladmin create "$DB_NAME" --user="$DB_USER"$EXTRA --host="$DB_HOST" --force 2>/dev/null || true
}

install_wp
install_test_suite
install_db
