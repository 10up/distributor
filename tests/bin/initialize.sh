#!/bin/bash
set -e

wp-env run tests-wordpress chmod -c ugo+w /var/www/html
wp-env run tests-cli wp rewrite structure '/%postname%/' --hard
wp-env run tests-cli wp plugin deactivate classic-editor

status=0
wp-env run tests-cli wp site list || status=$?

if [ $status -eq 0 ]
then
	echo "Multisite already initialized"
else
	echo "Converting to multisite"
	wp-env run tests-cli wp core multisite-convert --title='Distributor Multisite'
	wp-env run tests-cli wp user create second 'second@admin.local' --user_pass=password --role=administrator
	wp-env run tests-cli wp site create --slug=second --title='Second Site' --email='second@admin.local'
	wp-env run tests-cli wp theme enable twentytwentyone --activate
	wp-env run tests-cli wp theme enable twentytwentyone --url=localhost/second --activate
	wp-env run tests-cli cp wp-content/plugins/distributor/tests/cypress/.htaccess .htaccess
fi

# Create some posts to bump the post IDs so they differ between the two sites.
wp-env run tests-cli wp post generate --count=10 --post_type=post --post_author=1
wp-env run tests-cli wp post generate --count=20 --post_type=post --post_author=1 --url=localhost/second

# Now delete the posts created by the test suite
wp-env run tests-cli wp post delete $(wp-env run tests-cli wp post list --post_type=post --format=ids) --force
wp-env run tests-cli wp post delete $(wp-env run tests-cli wp post list --post_type=post --format=ids --url=localhost/second) --url=localhost/second --force

# Create some terms to bump the term IDs so they differ between the two sites.
wp-env run tests-cli wp term generate category --count=10
wp-env run tests-cli wp term generate category --count=20 --url=localhost/second

# Now delete the terms created by the test suite
wp-env run tests-cli wp term delete category $(wp-env run tests-cli wp term list category --format=ids)
wp-env run tests-cli wp term delete category $(wp-env run tests-cli wp term list category --format=ids --url=localhost/second) --url=localhost/second
