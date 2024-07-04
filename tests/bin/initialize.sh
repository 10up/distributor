#!/bin/bash
set -e

npx wp-env run tests-wordpress chmod -c ugo+w /var/www/html
npm run env run tests-cli wp rewrite structure '/%postname%/' --hard

status=0
npm run env run tests-cli wp site list || status=$?

if [ $status -eq 0 ]
then
	echo "Multisite already initialized"
else
	echo "Converting to multisite"
	npm run env run tests-cli wp core multisite-convert --title='Distributor Multisite'
	npm run env run tests-cli wp user create second 'second@admin.local' --user_pass=password --role=administrator
	npm run env run tests-cli wp site create --slug=second --title='Second Site' --email='second@admin.local'
	npm run env run tests-cli wp theme enable twentytwentyone --activate
	npm run env run tests-cli wp theme enable twentytwentyone --url=localhost/second --activate
	npm run env run tests-cli cp wp-content/plugins/distributor/tests/cypress/.htaccess .htaccess
fi
