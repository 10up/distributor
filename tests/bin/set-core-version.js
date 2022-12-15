#!/usr/bin/env node

const fs = require("fs");
const { exit } = require("process");

const path = `${process.cwd()}/.wp-env.override.json`;

let config = fs.existsSync(path) ? require(path) : {};

const args = process.argv.slice(2);

if (args.length == 0) exit(0);

if (args[0] == "latest") {
	config.core = null;
} else {
	let coreVersion = args[ 0 ];
	if ( ! coreVersion.match( /^WordPress\/WordPress\#/i ) ) {
		coreVersion = 'WordPress/WordPress#' + coreVersion;
	}
	config.core = coreVersion;
}

if ( !! args[ 1 ] ) {
	config.phpVersion = args[ 1 ];
}

try {
  fs.writeFileSync(path, JSON.stringify(config));
} catch (err) {
  console.error(err);
}
