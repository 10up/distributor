#!/bin/bash
set -e

wp-env run tests-wordpress chmod -c ugo+w /var/www/html
wp-env run tests-cli wp rewrite structure '/%postname%/' --hard

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
