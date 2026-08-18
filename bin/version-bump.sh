#!/usr/bin/env bash

set -euo pipefail

required_commands=(awk dirname grep mktemp mv node npm perl)
missing_commands=()

for required_command in "${required_commands[@]}"; do
	if ! command -v "$required_command" >/dev/null 2>&1; then
		missing_commands+=("$required_command")
	fi
done

if (( ${#missing_commands[@]} > 0 )); then
	echo "Missing required command(s): ${missing_commands[*]}" >&2
	echo "Please install the missing command(s) and try again." >&2
	exit 1
fi

if [[ $# -ne 1 ]]; then
	echo "Usage: $0 <major|minor|patch>" >&2
	exit 1
fi

bump_type="$1"

case "$bump_type" in
	major|minor|patch)
		;;
	*)
		echo "Invalid bump type: $bump_type" >&2
		echo "Expected one of: major, minor, patch" >&2
		exit 1
		;;
esac

script_dir="$(cd "$(dirname "$0")" && pwd)"
repo_dir="$(cd "$script_dir/.." && pwd)"

cd "$repo_dir"

for required_file in readme.txt distributor.php package.json package-lock.json CHANGELOG.md; do
	if [[ ! -f "$required_file" ]]; then
		echo "Missing required file: $required_file" >&2
		exit 1
	fi
done

current_version="$(node -p "require('./package.json').version")"

if [[ -z "$current_version" ]]; then
	echo "Unable to determine current version from package.json" >&2
	exit 1
fi

next_version="$(node -e "const [major, minor, patch] = require('./package.json').version.split('.').map(Number); const bump = process.argv[1]; const next = { major: [major + 1, 0, 0], minor: [major, minor + 1, 0], patch: [major, minor, patch + 1] }[bump]; if (!next || next.some(Number.isNaN)) { process.exit(1); } process.stdout.write(next.join('.'));" "$bump_type")"

if [[ -z "$next_version" ]]; then
	echo "Unable to calculate next version" >&2
	exit 1
fi

if grep -Eq "^## \[$next_version\]" CHANGELOG.md; then
	echo "Changelog already contains version $next_version" >&2
	exit 1
fi

if grep -Eq "^\[$next_version\]: https://github.com/10up/distributor/compare/" CHANGELOG.md; then
	echo "Changelog footer already contains version $next_version" >&2
	exit 1
fi

if grep -Eq "^= $next_version( - .*)? =$" readme.txt; then
	echo "readme.txt changelog already contains version $next_version" >&2
	exit 1
fi

if ! grep -Fq "[View historical changelog details here]" readme.txt; then
	echo "Unable to find historical changelog link in readme.txt" >&2
	exit 1
fi

npm version "$bump_type" --no-git-tag-version >/dev/null

new_version="$(node -p "require('./package.json').version")"

if [[ -z "$new_version" ]]; then
	echo "Unable to determine updated version from package.json" >&2
	exit 1
fi

perl -0pi -e "s/^(Stable tag:\s+).*
/\${1}$new_version\n/m" readme.txt

perl -0pi -e "s/^(== Changelog ==\r?\n\r?\n)/\${1}= $new_version - TBD =\n\n/m" readme.txt

readme_tmp="$(mktemp)"
awk '
	BEGIN {
		in_changelog = 0
		release_count = 0
		skipping_old = 0
	}
	/^== Changelog ==\r?$/ {
		in_changelog = 1
		print
		next
	}
	in_changelog && /^\[View historical changelog details here\]/ {
		skipping_old = 0
		in_changelog = 0
		print
		next
	}
	in_changelog {
		if ( $0 ~ /^= [0-9]+\.[0-9]+\.[0-9]+( - .*)? =\r?$/ ) {
			release_count++
			if ( release_count > 4 ) {
				skipping_old = 1
				next
			}
			skipping_old = 0
		}
		if ( skipping_old ) {
			next
		}
	}
	{
		print
	}
' readme.txt > "$readme_tmp"
mv "$readme_tmp" readme.txt

perl -0pi -e "s/^(\s*\* Version:\s+).*
/\${1}$new_version\n/m" distributor.php

perl -0pi -e "s/^(define\( 'DT_VERSION', ')([^']+)('\s*\);)
/\${1}$new_version\${3}\n/m" distributor.php

perl -0pi -e "s/^(define\( 'DT_VERSION', ')([^']+)('\s*\);)
/\${1}$new_version\${3}\n/m" tests/php/bootstrap.php

perl -0pi -e "s/^(## \[Unreleased\].*\n\n)/\${1}## [$new_version] - TBD\n\n/m" CHANGELOG.md

perl -0pi -e "s#^(\[Unreleased\]: .*\n)#\${1}[$new_version]: https://github.com/10up/distributor/compare/$current_version...$new_version\n#m" CHANGELOG.md

echo "Bumped version to $new_version"
