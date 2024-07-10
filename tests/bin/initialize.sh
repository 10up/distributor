#!/bin/bash
set -e

npx wp-env run tests-wordpress chmod -c ugo+w /var/www/html
npx wp-env run tests-cli wp rewrite structure '/%postname%/' --hard

status=0
npx wp-env run tests-cli wp site list || status=$?

if [ $status -eq 0 ]
then
	echo "Multisite already initialized"
else
	echo "Converting to multisite"
	npx wp-env run tests-cli wp core multisite-convert --title='Distributor Multisite'
	npx wp-env run tests-cli wp user create second 'second@admin.local' --user_pass=password --role=administrator
	npx wp-env run tests-cli wp site create --slug=second --title='Second Site' --email='second@admin.local'
	npx wp-env run tests-cli wp theme enable twentytwentyone --activate
	npx wp-env run tests-cli wp theme enable twentytwentyone --url=localhost/second --activate
	npx wp-env run tests-cli cp wp-content/plugins/distributor/tests/cypress/.htaccess .htaccess
fi
