#!/bin/bash

# This script generates a POT file for a WordPress plugin or theme using WP-CLI.
# We are using wp-scripts to generate plugin zip which bundle files defined in 'package.json' in files param.
# https://developer.wordpress.org/block-editor/packages/packages-scripts/#files
# This script will run with npm run makepot command.
#
# @since x.x.x

if ! command -v wp &>/dev/null; then
	echo "Error: wp cli could not be found. Please install wp cli and try again."
	exit
fi

# Get the list of files and directories which bundle in final zip file.
FILES=$(node -p "require('./package.json').files.join(',')")

# Run the WP-CLI command.
# This command will generate a POT file in the root directory of the plugin and store it in land directory.
wp i18n make-pot . --include="$FILES"

echo '.pot file updated'
