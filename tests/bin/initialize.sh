#!/bin/bash
set -e

npm run env run tests-wordpress "chmod -c ugo+w /var/www/html"
npm run env run tests-cli "wp rewrite structure '/%postname%/' --hard"

status=0
npm run env run tests-cli "wp site list" || status=$?

if [ $status -eq 0 ]
then
	echo "Multisite already initialized"
else
	echo "Converting to multisite"
	npm run env run tests-cli "wp core multisite-convert --title='Distributor Multisite'"
	npm run env run tests-cli "wp site create --slug=second --title='Second Site' --email='second@admin.local'"
fi
