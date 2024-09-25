# Changelog

All notable changes to this project will be documented in this file, per [the Keep a Changelog standard](http://keepachangelog.com/).

## [Unreleased] - TBD

## [2.0.6] - 2024-09-26
### Changed
- Improved the management of pulled content both from the admin post list view and from the admin single post view, making it easier to see what content has been pulled and what content is linked or not linked, as well as making linking and unlinking clearer (props [@roshniahuja](https://github.com/roshniahuja), [@faisal-alvi](https://github.com/faisal-alvi), [@ravinderk](https://github.com/ravinderk), [@iamdharmesh](https://github.com/iamdharmesh), [@Sidsector9](https://github.com/Sidsector9), [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul), [@oszkarnagy](https://github.com/oszkarnagy) via [#1036](https://github.com/10up/distributor/pull/1036)).

### Fixed
- Address creation of dynamic property warning in PHP 8.2+ (props [@kirtangajjar](https://github.com/kirtangajjar), [@Sidsector9](https://github.com/Sidsector9), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#1230](https://github.com/10up/distributor/pull/1230)).

### Security
- Bump `axios` from 1.6.4 to 1.7.4 (props [@dependabot](https://github.com/apps/dependabot), [@iamdharmesh](https://github.com/iamdharmesh) via [#1260](https://github.com/10up/distributor/pull/1260)).
- Bump `webpack` from 5.89.0 to 5.94.0 (props [@dependabot](https://github.com/apps/dependabot), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#1263](https://github.com/10up/distributor/pull/1263)).

### Developer
- Bump `@wordpress/env` from 10.1.0 to 10.5.0 (props [@peterwilsoncc](https://github.com/peterwilsoncc), [@faisal-alvi](https://github.com/faisal-alvi) via [#1236](https://github.com/10up/distributor/pull/1236)).
- Update the `README.md` file with proper repo badges, the plugin banner image and some minor text updates (props [@jeffpaul](https://github.com/jeffpaul), [@dkotter](https://github.com/dkotter) via [#1265](https://github.com/10up/distributor/pull/1265)).

## [2.0.5] - 2024-08-15
**Note that Distributor now requires WordPress 6.4 or later.**

### Added
- Introduce "View all" post type view when pulling content from external connections (props [@mehul0810](https://github.com/mehul0810), [@ravinderk](https://github.com/ravinderk), [@cadic](https://github.com/cadic), [@peterwilsoncc](https://github.com/peterwilsoncc), [@ankitrox](https://github.com/ankitrox), [@Sidsector9](https://github.com/Sidsector9), [@jeffpaul](https://github.com/jeffpaul) via [#1002](https://github.com/10up/distributor/pull/1002)).

### Changed
- Account for FSE post types in rest endpoints (props [@mehul0810](https://github.com/mehul0810), [@peterwilsoncc](https://github.com/peterwilsoncc), [@faisal-alvi](https://github.com/faisal-alvi) via [#1246](https://github.com/10up/distributor/pull/1246)).
- Bump WordPress "tested up to" version 6.6 (props [@jeffpaul](https://github.com/jeffpaul), [@qasumitbagthariya](https://github.com/qasumitbagthariya), [@dkotter](https://github.com/dkotter) via [#1214](https://github.com/10up/distributor/pull/1214), [#1243](https://github.com/10up/distributor/pull/1243)).
- Bump WordPress minimum from 5.7 to 6.4 (props [@jeffpaul](https://github.com/jeffpaul), [@dkotter](https://github.com/dkotter) via [#1214](https://github.com/10up/distributor/pull/1214), [#1261](https://github.com/10up/distributor/pull/1261)).

### Fixed
- Ensure you can publish distributed posts in WordPress 6.6 (props [@claudiulodro](https://github.com/claudiulodro), [@phpbits](https://github.com/phpbits), [@faisal-alvi](https://github.com/faisal-alvi) via [#1252](https://github.com/10up/distributor/pull/1252)).
- Address an issue where the incorrect featured image can sometimes be pushed with a post (props [@phpbits](https://github.com/phpbits), [@matthew-upslope](https://github.com/matthew-upslope), [@dkotter](https://github.com/dkotter), [@faisal-alvi](https://github.com/faisal-alvi) via [#1254](https://github.com/10up/distributor/pull/1254)).
- Add a check to ensure a connection is internal and exit early if not (props [@kirtangajjar](https://github.com/kirtangajjar), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#1225](https://github.com/10up/distributor/pull/1225)).
- Ensure the proper admin CSS file loads (props [@psorensen](https://github.com/psorensen), [@Sidsector9](https://github.com/Sidsector9) via [#1208](https://github.com/10up/distributor/pull/1208)).
- Address a PHP deprecation notice (props [@kirtangajjar](https://github.com/kirtangajjar), [@peterwilsoncc](https://github.com/peterwilsoncc), [@faisal-alvi](https://github.com/faisal-alvi) via [#1227](https://github.com/10up/distributor/pull/1227)).
- Ensure no errors occur in the synced patterns editor (props [@kirtangajjar](https://github.com/kirtangajjar), [@peterwilsoncc](https://github.com/peterwilsoncc), [@Sidsector9](https://github.com/Sidsector9) via [#1228](https://github.com/10up/distributor/pull/1228)).
- Undefined array key "id" warning on the Pull Content page (props [@Sidsector9](https://github.com/Sidsector9), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#1247](https://github.com/10up/distributor/pull/1247)).
- PHP warning about invalid argument supplied for foreach in `\Distributor\Utils\prepare_meta()` (props [@dhanendran](https://github.com/dhanendran), [@peterwilsoncc](https://github.com/peterwilsoncc), [@faisal-alvi](https://github.com/faisal-alvi), [@kirtangajjar](https://github.com/kirtangajjar) via [#1167](https://github.com/10up/distributor/pull/1167)).
- Deprecation notice in `PullListTable` when distributed posts are not allowed to be edited (props [@kirtangajjar](https://github.com/kirtangajjar), [@faisal-alvi](https://github.com/faisal-alvi) via [#1245](https://github.com/10up/distributor/pull/1245)).
- Undefined "id" notices showing in the error log (props [@phpbits](https://github.com/phpbits), [@dkotter](https://github.com/dkotter) via [#1253](https://github.com/10up/distributor/pull/1253)).

### Security
- Bump `follow-redirects` from 1.15.4 to 1.15.6 (props [@dependabot](https://github.com/apps/dependabot), [@faisal-alvi](https://github.com/faisal-alvi) via [#1209](https://github.com/10up/distributor/pull/1209)).
- Bump `ip` from 1.1.8 to 1.1.9 (props [@dependabot](https://github.com/apps/dependabot), [@faisal-alvi](https://github.com/faisal-alvi) via [#1209](https://github.com/10up/distributor/pull/1209)).
- Bump `jsdoc` from 3.6.11 to 4.0.2 (props [@dependabot](https://github.com/apps/dependabot), [@faisal-alvi](https://github.com/faisal-alvi) via [#1209](https://github.com/10up/distributor/pull/1209)).
- Bump `webpack-dev-middleware` from 5.3.3 to 5.3.4 (props [@dependabot](https://github.com/apps/dependabot), [@faisal-alvi](https://github.com/faisal-alvi) via [#1209](https://github.com/10up/distributor/pull/1209)).
- Bump `express` from 4.18.2 to 4.19.2 (props [@dependabot[bot]](https://github.com/apps/dependabot), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#1213](https://github.com/10up/distributor/pull/1213)).
- Bump `braces` from 3.0.2 to 3.03, `pac-resolver` from 7.0.0 to 7.0.1, `socks` from 2.7.1 to 2.8.3, `ws` from 7.5.9 to 7.5.10 and remove `ip` (props [@dependabot[bot]](https://github.com/apps/dependabot), [@Sidsector9](https://github.com/Sidsector9) via [#1234](https://github.com/10up/distributor/pull/1234)).

### Developer
- "Testing" section in the `CONTRIBUTING.md` file (props [@kmgalanakis](https://github.com/kmgalanakis), [@iamdharmesh](https://github.com/iamdharmesh), [@jeffpaul](https://github.com/jeffpaul) via [#1219](https://github.com/10up/distributor/pull/1219)).
- Bumped `actions/upload-artifact` from v3 to v4 (props [@iamdharmesh](https://github.com/iamdharmesh), [@jeffpaul](https://github.com/jeffpaul) via [#1217](https://github.com/10up/distributor/pull/1217)).
- Replaced `lee-dohm/no-response` with `actions/stale` to help with closing no-response/stale issues (props [@jeffpaul](https://github.com/jeffpaul), [@dkotter](https://github.com/dkotter) via [#1206](https://github.com/10up/distributor/pull/1206)).
- Use `GITHUB_OUTPUT` envvar instead of `set-output` command (props [@arunsathiya](https://github.com/arunsathiya), [@iamdharmesh](https://github.com/iamdharmesh) via [#1178](https://github.com/10up/distributor/pull/1178)).
- Fix some failing E2E tests (props [@kirtangajjar](https://github.com/kirtangajjar), [@iamdharmesh](https://github.com/iamdharmesh) via [#1242](https://github.com/10up/distributor/pull/1242)).
- Add `taffydb` as devDependecy (props [@dependabot](https://github.com/apps/dependabot), [@faisal-alvi](https://github.com/faisal-alvi) via [#1209](https://github.com/10up/distributor/pull/1209)).

## [2.0.4] - 2024-02-29
### Added
- Repo Automator GitHub Action to automate repo operations (props [@iamdharmesh](https://github.com/iamdharmesh), [@jeffpaul](https://github.com/jeffpaul) via [#1191](https://github.com/10up/distributor/pull/1191)).

### Changed
- Address typos across the codebase (props [@szepeviktor](https://github.com/szepeviktor), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#1184](https://github.com/10up/distributor/pull/1184)).

### Fixed
- Address an issue with data being cached incorrectly (props [@leogermani](https://github.com/leogermani), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#1185](https://github.com/10up/distributor/pull/1185)).

## [2.0.3] - 2024-01-18
### Added
- New filter, `dt_post_to_pull`, that allows modifying the post to be pulled (props [@leogermani](https://github.com/leogermani), [@peterwilsoncc](https://github.com/peterwilsoncc), [@dkotter](https://github.com/dkotter) via [#1181](https://github.com/10up/distributor/pull/1181)).

### Fixed
- Ensure the code snippet for keeping the original post date is correct (props [@leogermani](https://github.com/leogermani), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#1160](https://github.com/10up/distributor/pull/1160)).

### Security
- Bump `tj-actions/changed-files` from 36 to 41 (props [@dependabot[bot]](https://github.com/apps/dependabot), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#1172](https://github.com/10up/distributor/pull/1172)).
- Bump `@wordpress/scripts` from 26.6.0 to 26.19.0 (props [@dependabot[bot]](https://github.com/apps/dependabot), [@iamdharmesh](https://github.com/iamdharmesh) via [#1174](https://github.com/10up/distributor/pull/1174)).

## [2.0.2] - 2023-11-29
### Added
- New snippet detailing how to disable automatic content updates (props [@dkotter](https://github.com/dkotter), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#1145](https://github.com/10up/distributor/pull/1145)).

### Changed
- Show proper external connection status error messages when the status is checked (props [@theskinnyghost](https://github.com/theskinnyghost), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#1140](https://github.com/10up/distributor/pull/1140)).
- Updated our docs around how to connect two pieces of existing content (props [@dkotter](https://github.com/dkotter), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#1145](https://github.com/10up/distributor/pull/1145)).
- Bump WordPress "tested up to" version 6.4 (props [@severine-pozzo](https://github.com/severine-pozzo), [@QAharshalkadu](https://github.com/QAharshalkadu), [@jeffpaul](https://github.com/jeffpaul), [@dkotter](https://github.com/dkotter) via [#1151](https://github.com/10up/distributor/pull/1151)).

### Fixed
- Copy the htaccess file when initializing the E2E test environment (props [@peterwilsoncc](https://github.com/peterwilsoncc), [@theskinnyghost](https://github.com/theskinnyghost), [@dkotter](https://github.com/dkotter) via [#1143](https://github.com/10up/distributor/pull/1143)).
- Prevent early publishing of scheduled posts when pulled internally (props [@peterwilsoncc](https://github.com/peterwilsoncc), [@jeffpaul](https://github.com/jeffpaul), [@dkotter](https://github.com/dkotter), [@maxledoux](https://github.com/maxledoux), [@pcrumm](https://github.com/pcrumm) via [#1156](https://github.com/10up/distributor/pull/1156)).
- Avoid a PHP fatal error when pulling content that has previously been pulled into a different network site (props [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#1159](https://github.com/10up/distributor/pull/1159)).

### Security
- Bump `postcss` from 8.4.24 to 8.4.31 (props [@dependabot[bot]](https://github.com/apps/dependabot), [@ravinderk](https://github.com/ravinderk) via [#1141](https://github.com/10up/distributor/pull/1141)).
- Bump `@babel/traverse` from 7.22.5 to 7.23.2 (props [@dependabot[bot]](https://github.com/apps/dependabot), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#1147](https://github.com/10up/distributor/pull/1147)).

## [2.0.1] - 2023-09-18
### Changed
- Update from Cypress v10 to v13 (props [@dkotter](https://github.com/dkotter), [@iamdharmesh](https://github.com/iamdharmesh), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#1128](https://github.com/10up/distributor/pull/1128)).
- Documentation updates (props [@jeffpaul](https://github.com/jeffpaul), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#1131](https://github.com/10up/distributor/pull/1131)).

### Removed
- GitHub Actions for version 1.x releases (props [@peterwilsoncc](https://github.com/peterwilsoncc), [@jeffpaul](https://github.com/jeffpaul) via [#1135](https://github.com/10up/distributor/pull/1135)).

### Fixed
- Invalid author IDs being set when pushing and pulling posts (props [@peterwilsoncc](https://github.com/peterwilsoncc), [@faisal-alvi](https://github.com/faisal-alvi), [@joemcgill](https://github.com/joemcgill), [@margaretschneider](https://github.com/margaretschneider) via [#1133](https://github.com/10up/distributor/pull/1133)).
- Featured image delete push issue (props [@ravichdev](https://github.com/ravichdev), [@helen](https://github.com/helen), [@dkotter](https://github.com/dkotter), [@adamsilverstein](https://github.com/adamsilverstein), [@jeffpaul](https://github.com/jeffpaul), [@arsendovlatyan](https://github.com/arsendovlatyan), [@ravinderk](https://github.com/ravinderk), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#310](https://github.com/10up/distributor/pull/310)).
- Allow clicking on the `Save to draft` confirmation modal buttons (props [@av3nger](https://github.com/av3nger), [@peterwilsoncc](https://github.com/peterwilsoncc), [@ravinderk](https://github.com/ravinderk), [@pdewouters](https://github.com/pdewouters) via [#1042](https://github.com/10up/distributor/pull/1042)).
- Admin icon has been updated so it has the same width and height (props [@zach-adams](https://github.com/zach-adams), [@ravinderk](https://github.com/ravinderk), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#1132](https://github.com/10up/distributor/pull/1132)).
- Ensure our E2E tests pass on WordPress 6.3 (props [@dkotter](https://github.com/dkotter), [@iamdharmesh](https://github.com/iamdharmesh), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#1128](https://github.com/10up/distributor/pull/1128)).

## [2.0.0] - 2023-09-05
**Note that Distributor now requires PHP 7.4 or later and WordPress 5.7 or later.**

Please see the [migration guide](https://10up.github.io/distributor/tutorial-migration-guide-version-1-to-version-2.html) for important changes between version 1 and version 2 that may require your action.

### Added
- Migration guide for version 1 to version 2 (props [@peterwilsoncc](https://github.com/peterwilsoncc), [@faisal-alvi](https://github.com/faisal-alvi), [@jeffpaul](https://github.com/jeffpaul) via [#996](https://github.com/10up/distributor/pull/996)).
- Show count of selected connections in Push menu (props [@roshniahuja](https://github.com/roshniahuja), [@peterwilsoncc](https://github.com/peterwilsoncc), [@jeffpaul](https://github.com/jeffpaul) via [#1020](https://github.com/10up/distributor/pull/1020)).
- Reduce duplication when pulling posts (props [@peterwilsoncc](https://github.com/peterwilsoncc), [@jeffpaul](https://github.com/jeffpaul), [@dkotter](https://github.com/dkotter), [@ravinderk](https://github.com/ravinderk) via [#1017](https://github.com/10up/distributor/pull/1017)).
- Post abstraction class (props [@peterwilsoncc](https://github.com/peterwilsoncc), [@dkotter](https://github.com/dkotter) via [#964](https://github.com/10up/distributor/pull/964)).
-  `dt_push_external_post` and `dt_push_network_post` action hooks (props [@dkotter](https://github.com/dkotter), [@peterwilsoncc](https://github.com/peterwilsoncc), [@dhanendran](https://github.com/dhanendran), [@ravinderk](https://github.com/ravinderk), [@jeffpaul](https://github.com/jeffpaul) via [#1024](https://github.com/10up/distributor/pull/1024)).
- Register scripts with `EnqueueScript` class (props [@ravinderk](https://github.com/ravinderk), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#1014](https://github.com/10up/distributor/pull/1014)).
- Snippets for setting meta data automatically (props [@peterwilsoncc](https://github.com/peterwilsoncc), [@iamdharmesh](https://github.com/iamdharmesh) via [#1063](https://github.com/10up/distributor/pull/1063)).
- Documentation updates (props [@adamsilverstein](https://github.com/adamsilverstein), [@jeffpaul](https://github.com/jeffpaul), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#1066](https://github.com/10up/distributor/pull/1066), [#1072](https://github.com/10up/distributor/pull/1072), [#1112](https://github.com/10up/distributor/pull/1112)).
- End-to-end tests for minimum requirements (props [@peterwilsoncc](https://github.com/peterwilsoncc), [@jeffpaul](https://github.com/jeffpaul), [@faisal-alvi](https://github.com/faisal-alvi) via [#936](https://github.com/10up/distributor/pull/936)).
- Migrate WPAcceptance end-to-end tests to Cypress (props [@cadic](https://github.com/cadic), [@Sidsector9](https://github.com/Sidsector9), [@iamdharmesh](https://github.com/iamdharmesh), [@jeffpaul](https://github.com/jeffpaul), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#941](https://github.com/10up/distributor/pull/941)).
- Generate POT file with WP-CLI (props [@ravinderk](https://github.com/ravinderk), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#1014](https://github.com/10up/distributor/pull/1014)).
- `dt_before_set_meta`, `dt_prepared_meta` and `dt_prepared_taxonomy_terms` filter hooks (props [@sethrubenstein](https://github.com/sethrubenstein), [@jeffpaul](https://github.com/jeffpaul), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#1095](https://github.com/10up/distributor/pull/1095)).

### Changed
- Now requires PHP 7.4 or later and WordPress 5.7 or later (props [@peterwilsoncc](https://github.com/peterwilsoncc), [@vikrampm1](https://github.com/vikrampm1), [@iamdharmesh](https://github.com/iamdharmesh), [@jeffpaul](https://github.com/jeffpaul), [@dkotter](https://github.com/dkotter) via [#917](https://github.com/10up/distributor/pull/917)).
- Descriptive warning message copy on remote, distributed posts (props [@jeffpaul](https://github.com/jeffpaul), [@peterwilsoncc](https://github.com/peterwilsoncc), [@cadic](https://github.com/cadic) via [#1006](https://github.com/10up/distributor/pull/1006)).
- Indicate if the origin post has been deleted in Distributor push interface (props [@peterwilsoncc](https://github.com/peterwilsoncc), [@cadic](https://github.com/cadic), [@jeffpaul](https://github.com/jeffpaul), [@faisal-alvi](https://github.com/faisal-alvi) via [#1067](https://github.com/10up/distributor/pull/1067)).
- Toggles the Distributor admin bar element in Gutenberg based on the post status (props [@ggutenberg](https://github.com/ggutenberg), [@jeffpaul](https://github.com/jeffpaul), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#1022](https://github.com/10up/distributor/pull/1022), [#1114](https://github.com/10up/distributor/pull/1114)).
- Reduced duplicate code pushing posts to connections (props [@peterwilsoncc](https://github.com/peterwilsoncc), [@cadic](https://github.com/cadic), [@jeffpaul](https://github.com/jeffpaul), [@faisal-alvi](https://github.com/faisal-alvi), [@aaronware](https://github.com/aaronware) via [#999](https://github.com/10up/distributor/pull/999)).
- Unified translator comments (props [@peterwilsoncc](https://github.com/peterwilsoncc), [@cadic](https://github.com/cadic) via [#949](https://github.com/10up/distributor/pull/949)).
- WordPress "tested up to" version 6.3 (props [@jayedul](https://github.com/jayedul), [@faisal-alvi](https://github.com/faisal-alvi), [@jeffpaul](https://github.com/jeffpaul), [@QAharshalkadu](https://github.com/QAharshalkadu), [@dkotter](https://github.com/dkotter) via [#1047](https://github.com/10up/distributor/pull/1047), [#1118](https://github.com/10up/distributor/issues/1118), [#1123](https://github.com/10up/distributor/pull/1123)).
- Browserlist database updated to version 1.0.30001489 (props [@peterwilsoncc](https://github.com/peterwilsoncc), [@dkotter](https://github.com/dkotter) via [#1059](https://github.com/10up/distributor/pull/1059)).
- Build process now uses `@wordpress/scripts` in place of Gulp (props [@peterwilsoncc](https://github.com/peterwilsoncc), [@dinhtungdu](https://github.com/dinhtungdu), [@dkotter](https://github.com/dkotter) via [#916](https://github.com/10up/distributor/pull/916)).
- Migrated E2E tests to Cypress (props [@peterwilsoncc](https://github.com/peterwilsoncc), [@jeffpaul](https://github.com/jeffpaul), [@iamdharmesh](https://github.com/iamdharmesh) via [#1070](https://github.com/10up/distributor/pull/1070)).
- Update `dependency-review.yml` (props [@jeffpaul](https://github.com/jeffpaul), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#1054](https://github.com/10up/distributor/pull/1054)).
- Order of operations setting meta, media and terms in the network push and pull functions (props [@sethrubenstein](https://github.com/sethrubenstein), [@jeffpaul](https://github.com/jeffpaul), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#1095](https://github.com/10up/distributor/pull/1095)).
- Upgrade the Plugin Update Checker library from 4.13 to 5.1 (props [@dkotter](https://github.com/dkotter) via [#1124](https://github.com/10up/distributor/pull/1124)).

### Deprecated
- `dt_push_post` action hook (props [@dkotter](https://github.com/dkotter), [@peterwilsoncc](https://github.com/peterwilsoncc), [@dhanendran](https://github.com/dhanendran), [@ravinderk](https://github.com/ravinderk), [@jeffpaul](https://github.com/jeffpaul) via [#1024](https://github.com/10up/distributor/pull/1024)).

### Removed
- Duplicate code modifying post/author meta data (props [@peterwilsoncc](https://github.com/peterwilsoncc), [@dkotter](https://github.com/dkotter) via [#988](https://github.com/10up/distributor/pull/988)).

### Fixed
- Author override fails in the block theme author block (props [@ggutenberg](https://github.com/ggutenberg), [@jeffpaul](https://github.com/jeffpaul), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#1035](https://github.com/10up/distributor/pull/1035)).
- Distribute empty taxonomies to external sites (props [@peterwilsoncc](https://github.com/peterwilsoncc), [@manolobevia](https://github.com/manolobevia), [@jeffpaul](https://github.com/jeffpaul), [@cadic](https://github.com/cadic) via [#951](https://github.com/10up/distributor/pull/951)).
- Ensure post meta and terms have saved prior to distribution (props [@peterwilsoncc](https://github.com/peterwilsoncc), [@dinhtungdu](https://github.com/dinhtungdu), [@dkotter](https://github.com/dkotter), [@timstl](https://github.com/timstl), [@faisal-alvi](https://github.com/faisal-alvi), [@jeffpaul](https://github.com/jeffpaul) via [#938](https://github.com/10up/distributor/pull/938)).
- Fatal error in WordPress versions 6.0 and earlier (props [@peterwilsoncc](https://github.com/peterwilsoncc), [@ravinderk](https://github.com/ravinderk) via [#1056](https://github.com/10up/distributor/pull/1056)).
- Handle a PHP deprecation notice around converting `false` to `array` (props [@dkotter](https://github.com/dkotter), [@peterwilsoncc](https://github.com/peterwilsoncc), [@jeffpaul](https://github.com/jeffpaul) via [#1040](https://github.com/10up/distributor/pull/1040)).
- Improved accuracy of determining authentication endpoint (props [@peterwilsoncc](https://github.com/peterwilsoncc), [@TimothyBJacobs](https://github.com/TimothyBJacobs), [@cadic](https://github.com/cadic), [@jeffpaul](https://github.com/jeffpaul) via [#934](https://github.com/10up/distributor/pull/934)).
- Inconsistent data returned from post abstraction after site switch on multisite (props [@peterwilsoncc](https://github.com/peterwilsoncc), [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul) via [#1010](https://github.com/10up/distributor/pull/1010)).
- Prevent the modification of terms on Distributed content (props [@peterwilsoncc](https://github.com/peterwilsoncc), [@turtlepod](https://github.com/turtlepod), [@jeffpaul](https://github.com/jeffpaul), [@ravinderk](https://github.com/ravinderk), [@kmgalanakis-sage](https://github.com/kmgalanakis-sage), [@dkotter](https://github.com/dkotter) via [#1069](https://github.com/10up/distributor/pull/1069)).
- Retain published time when editing distributed posts (props [@madriverweb](https://github.com/madriverweb), [@codiller](https://github.com/codiller), [@peterwilsoncc](https://github.com/peterwilsoncc), [@Sidsector9](https://github.com/Sidsector9) via [#956](https://github.com/10up/distributor/pull/956)).
- Undefined variable notice (props [@peterwilsoncc](https://github.com/peterwilsoncc), [@Sidsector9](https://github.com/Sidsector9), [@jeffpaul](https://github.com/jeffpaul) via [#945](https://github.com/10up/distributor/pull/945)).
- Updated numerous strings to improve their translatability (props [@helen](https://github.com/helen), [@peterwilsoncc](https://github.com/peterwilsoncc), [@Sidsector9](https://github.com/Sidsector9) via [#935](https://github.com/10up/distributor/pull/935)).
- Account for relative scheme URLs when processing media (props [@peterwilsoncc](https://github.com/peterwilsoncc), [@ravinderk](https://github.com/ravinderk), [@jeffpaul](https://github.com/jeffpaul) via [#1110](https://github.com/10up/distributor/pull/1110)).

### Security
- Bump `loader-utils` from 2.0.2 to 2.0.4 (props [@dependabot[bot]](https://github.com/apps/dependabot), [@jeffpaul](https://github.com/jeffpaul), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#963](https://github.com/10up/distributor/pull/963), [#970](https://github.com/10up/distributor/pull/970)).
- Bump `simple-git` from 3.11.0 to 3.15.1 (props [@dependabot[bot]](https://github.com/apps/dependabot), [@jeffpaul](https://github.com/jeffpaul), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#983](https://github.com/10up/distributor/pull/983)).
- Bump `webpack` from 5.74.0 to 5.76.2 (props [@dependabot[bot]](https://github.com/apps/dependabot), [@jeffpaul](https://github.com/jeffpaul), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#1029](https://github.com/10up/distributor/pull/1029), [#1032](https://github.com/10up/distributor/pull/1032)).
- Bump `word-wrap` from 1.2.3 to 1.2.4 (props [@dependabot[bot]](https://github.com/apps/dependabot), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#1116](https://github.com/10up/distributor/pull/1116)).

## [1.9.1] - 2023-03-15
### Security
- Removed potential for data exposure via the REST API (props [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul), [@peterwilsoncc](https://github.com/peterwilsoncc) via [GHSA-q43c-v867-4cfp](https://github.com/10up/distributor/security/advisories/GHSA-q43c-v867-4cfp)).

## [1.9.0] - 2022-12-15
### Changed
- Change uses of `blacklist` and use `exclude` instead (props [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#961](https://github.com/10up/distributor/pull/961))
- Indicate WordPress 6.1 support (props [@peterwilsoncc](https://github.com/peterwilsoncc), [@faisal-alvi](https://github.com/faisal-alvi) via [#967](https://github.com/10up/distributor/pull/967)).

### Deprecated
- Deprecate the `Distributor\Utils\blacklisted_meta` function and the `dt_blacklisted_meta` filter (props [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#961](https://github.com/10up/distributor/pull/961)).

### Fixed
- Prevent distribution of classic editor plugin related meta data. (props [@peterwilsoncc](https://github.com/peterwilsoncc), [@faisal-alvi](https://github.com/faisal-alvi), [@jeffpaul](https://github.com/jeffpaul) via [#977](https://github.com/10up/distributor/pull/977)).
- Return distributed author name when using the get_the_author_meta('display_name') function. (props [@cadic](https://github.com/cadic), [@ravinderk](https://github.com/ravinderk), [@iamdharmesh](https://github.com/iamdharmesh), [@mapamond](https://github.com/mapamond), [@peterwilsoncc](https://github.com/peterwilsoncc), [@jeffpaul](https://github.com/jeffpaul), via [#984](https://github.com/10up/distributor/pull/984),  [#952](https://github.com/10up/distributor/pull/952)).

### Other
- PHPCS workflow failures. (props [@peterwilsoncc](https://github.com/peterwilsoncc), [@jeffpaul](https://github.com/jeffpaul), [@gsarig](https://github.com/gsarig), [@faisal-alvi](https://github.com/faisal-alvi) via [#969](https://github.com/10up/distributor/pull/969)).

## [1.8.0] - 2022-09-29
### Changed
- Upgrade the Plugin Update Checker library, `yahnis-elsts/plugin-update-checker`, from 4.9 to 4.13 (props [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#937](https://github.com/10up/distributor/pull/937)).

### Other
- Automated creation of release pull requests (props [@dinhtungdu](https://github.com/dinhtungdu), [@peterwilsoncc](https://github.com/peterwilsoncc), [@faisal-alvi](https://github.com/faisal-alvi), [@jeffpaul](https://github.com/jeffpaul) via [#940](https://github.com/10up/distributor/pull/940)).
- Use config files to determine version of node used in GitHub actions (props [@peterwilsoncc](https://github.com/peterwilsoncc), [@Sidsector9](https://github.com/Sidsector9), [@jeffpaul](https://github.com/jeffpaul) via [#933](https://github.com/10up/distributor/pull/933)).

## [1.7.1] - 2022-08-04

### Added
- Cypress E2E tests (props [@dkotter](https://github.com/dkotter), [@faisal-alvi](https://github.com/faisal-alvi), [@dinhtungdu](https://github.com/dinhtungdu), [@iamdharmesh](https://github.com/iamdharmesh), [@Sidsector9](https://github.com/Sidsector9) via [#900](https://github.com/10up/distributor/pull/900)).

### Fixed
- Ensure we don't lose the post_type value when pushing or pulling content (props [@dkotter](https://github.com/dkotter), [@pdewouters](https://github.com/pdewouters), [@andygagnon](https://github.com/andygagnon), [@jmstew3](https://github.com/jmstew3) via [#922](https://github.com/10up/distributor/pull/922)).

## [1.7.0] - 2022-07-26

### Added
- Ability to set user roles to pull content (props [@faisal-alvi](https://github.com/faisal-alvi), [@dkotter](https://github.com/dkotter), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#877](https://github.com/10up/distributor/pull/877)).
- More robust PHP testing (props [@dkotter](https://github.com/dkotter), [@peterwilsoncc](https://github.com/peterwilsoncc), [@jeffpaul](https://github.com/jeffpaul) via [#853](https://github.com/10up/distributor/pull/853)).
- Support for plugin auto-updates for registered sites (props [@dhanendran](https://github.com/dhanendran), [@jeffpaul](https://github.com/jeffpaul), [@dkotter](https://github.com/dkotter), [@sksaju](https://github.com/sksaju) via [#726](https://github.com/10up/distributor/pull/726)).
- Distributable post types made consistent (props [@faisal-alvi](https://github.com/faisal-alvi), [@dkotter](https://github.com/dkotter), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#907](https://github.com/10up/distributor/pull/907)).

### Changed
- Bump WordPress "tested up to" version 6.0 (props [@jeffpaul](https://github.com/jeffpaul), [@lukaspawlik](https://github.com/lukaspawlik), [@vikrampm1](https://github.com/vikrampm1), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#902](https://github.com/10up/distributor/pull/902)).
- Removed system post types for External Connections. (props [@dkotter](https://github.com/dkotter), [@faisal-alvi](https://github.com/faisal-alvi), [@peterwilsoncc](https://github.com/peterwilsoncc), [@dinhtungdu](https://github.com/dinhtungdu), [@jeffpaul](https://github.com/jeffpaul) via [#898](https://github.com/10up/distributor/pull/898)).
- The `Distributor > Pull Content` menu is now be visible for all user roles. (props [@faisal-alvi](https://github.com/faisal-alvi), [@dkotter](https://github.com/dkotter), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#877](https://github.com/10up/distributor/pull/877)).
- Update how we check if someone is running a development version of Distributor (props [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul), [@dinhtungdu](https://github.com/dinhtungdu) via [#882](https://github.com/10up/distributor/pull/882)).
- GH Action used for deploy to GH Pages (props [@iamdharmesh](https://github.com/iamdharmesh), [@jeffpaul](https://github.com/jeffpaul) via [#886](https://github.com/10up/distributor/pull/886)).

### Fixed
- Unicode characters not escaped correctly (props [@amalajith](https://github.com/amalajith), [@dkotter](https://github.com/dkotter), [@cadic](https://github.com/cadic), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#890](https://github.com/10up/distributor/pull/890)).
- Manually entering a page number doesn't work on the Pull screen (props [@faisal-alvi](https://github.com/faisal-alvi), [@dkotter](https://github.com/dkotter) via [#878](https://github.com/10up/distributor/pull/878)).
- Account for plugin changes in test to determine editor type (classic or block). (props [@peterwilsoncc](https://github.com/peterwilsoncc), [@faisal-alvi](https://github.com/faisal-alvi), [@dinhtungdu](https://github.com/dinhtungdu), [@jeffpaul](https://github.com/jeffpaul) via [#894](https://github.com/10up/distributor/pull/894)).
- Prevent conflict with `pre_post_link` filter. (props [@jeremyfelt](https://github.com/jeremyfelt), [@peterwilsoncc](https://github.com/peterwilsoncc), [@jeffpaul](https://github.com/jeffpaul), [@dinhtungdu](https://github.com/dinhtungdu) via [#895](https://github.com/10up/distributor/pull/895)).

### Removed
- The `dt_capabilities` & `dt_pull_capabilities` filters are removed while displaying the menus. (props [@faisal-alvi](https://github.com/faisal-alvi), [@dkotter](https://github.com/dkotter), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#877](https://github.com/10up/distributor/pull/877)).
- Known Issue listing for full screen mode (issue fixed in 1.6.5). (props [@faisal-alvi](https://github.com/faisal-alvi), [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul) via [#897](https://github.com/10up/distributor/pull/897)).

### Security
- build(deps): bump guzzlehttp/guzzle from 6.5.3 to 7.4.4 (props [@dependabot[bot]](https://github.com/apps/dependabot), [@jeffpaul](https://github.com/jeffpaul), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#885](https://github.com/10up/distributor/pull/885), [#891](https://github.com/10up/distributor/pull/891)).
- build(deps): bump terser from 4.7.0 to 4.8.1 (props [@dependabot[bot]](https://github.com/apps/dependabot), [@jeffpaul](https://github.com/jeffpaul), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#911](https://github.com/10up/distributor/pull/911)).

## [1.6.9] - 2022-04-18
### Added
- Dependency security scanning (props [@jeffpaul](https://github.com/jeffpaul), [@dkotter](https://github.com/dkotter) via [#869](https://github.com/10up/distributor/pull/869)).
- Added new code snippet to [Snippets](https://10up.github.io/distributor/tutorial-snippets.html) page detailing how to remove canonical links (props [@dkotter](https://github.com/dkotter) via [#855](https://github.com/10up/distributor/pull/855)).

### Changed
- Update the version of the bundled Application Passwords plugin to 0.1.3 (props [@claytoncollie](https://github.com/claytoncollie), [@Sidsector9](https://github.com/Sidsector9) via [#824](https://github.com/10up/distributor/pull/824)).
- Clarified the instructions for setting up External Connections (props [@skorasaurus](https://github.com/skorasaurus), [@jeffpaul](https://github.com/jeffpaul) via [#838](https://github.com/10up/distributor/pull/838)).
- Minor changes to the `remote_post` method (props [@dkotter](https://github.com/dkotter), [@cadic](https://github.com/cadic) via [#841](https://github.com/10up/distributor/pull/841)).
- Bump WordPress "tested up to" version to 5.9 (props [@mohitwp](https://github.com/mohitwp), [@jeffpaul](https://github.com/jeffpaul), [@iamdharmesh](https://github.com/iamdharmesh) via [#854](https://github.com/10up/distributor/pull/854)).

### Fixed
- Ensure content updates work for distributed items that use the block editor in WordPress 5.9+ (props [@dkotter](https://github.com/dkotter), [@cadic](https://github.com/cadic) via [#845](https://github.com/10up/distributor/pull/845)).
- Tidied up the position and style of the help icon that shows on the Distributor settings page (props [@willhowat](https://github.com/willhowat), [@dkotter](https://github.com/dkotter) via [#871](https://github.com/10up/distributor/pull/871)).

### Security
- Bump `tar` from 4.4.8 to 4.4.19 (props [@dependabot](https://github.com/apps/dependabot) via [#843](https://github.com/10up/distributor/pull/843)).
- Bump `ajv` from 6.12.2 to 6.12.6 (props [@dependabot](https://github.com/apps/dependabot) via [#849](https://github.com/10up/distributor/pull/849)).
- Bump `lodash.template` from 4.4.0 to 4.5.0 (props [@dependabot](https://github.com/apps/dependabot) via [#850](https://github.com/10up/distributor/pull/850)).
- Bump `copy-props` from 2.0.4 to 2.0.5 (props [@dependabot](https://github.com/apps/dependabot) via [#851](https://github.com/10up/distributor/pull/851)).
- Bump `guzzlehttp/psr7` from 1.6.1 to 1.8.5 (props [@dependabot](https://github.com/apps/dependabot) via [#866](https://github.com/10up/distributor/pull/866)).

## [1.6.8] - 2022-02-02
### Added
- New hook `dt_get_pull_content_rest_query_args` to filter `WP_Query` args for the `list-pull-content` REST endpoint (props [@theskinnyghost](https://github.com/theskinnyghost), [@dkotter](https://github.com/dkotter) via [#839](https://github.com/10up/distributor/pull/839)).

### Changed
- Clear out a user's authorized site list instead of rebuilding it on site changes (props [@dkotter](https://github.com/dkotter) , [@cadic](https://github.com/cadic) via [#829](https://github.com/10up/distributor/pull/829)).

### Fixed
- Ensure the connection information we have is valid prior to using that for deletion (props [@dkotter](https://github.com/dkotter), [@LucyTurtle](https://github.com/LucyTurtle) via [#830](https://github.com/10up/distributor/pull/830)).
- Ensure users can enter a per page limit of greater than 100 and have that properly used on the Pull Content screen for External Connections (props [@dkotter](https://github.com/dkotter), [@iamdharmesh](https://github.com/iamdharmesh), [@jmstew3](https://github.com/jmstew3) via [#831](https://github.com/10up/distributor/pull/831)).
- Ensure the [Snippets tutorials](https://10up.github.io/distributor/tutorial-snippets.html) have a proper height (props [@dkotter](https://github.com/dkotter), [@pcrumm](https://github.com/pcrumm) via [#836](https://github.com/10up/distributor/pull/836)).

### Security
- Bump `actions/checkout` in GitHub Action workflow files from v1/v2 to v2.4.0 (props [@faisal-alvi](https://github.com/faisal-alvi) via [#828](https://github.com/10up/distributor/pull/828)).

## [1.6.7] - 2021-11-09
### Added
- Added `Snippets` page to [Distributor's documentation site](https://10up.github.io/distributor/) with helpful filters and callbacks (props [@claytoncollie](https://github.com/claytoncollie) via [#817](https://github.com/10up/distributor/pull/817)).

### Fixed
- Change how the `New` tab on the Pull Content screen is populated for External Connections (props [@dkotter](https://github.com/dkotter), [@dinhtungdu](https://github.com/dinhtungdu), [@cadic](https://github.com/cadic), [@helen](https://github.com/helen), [@jjgrainger](https://github.com/jjgrainger), [@jakemgold](https://github.com/jakemgold), [Lily Bonney](https://www.linkedin.com/in/lilybonney/), [Mollie Pugh](https://www.linkedin.com/in/molliepugh/), [Martina Haines](https://www.linkedin.com/in/martinahaines/) via [#811](https://github.com/10up/distributor/pull/811)).

## [1.6.6] - 2021-09-28
### Added
- Add filters to control terms and meta distribution for internal connections: `dt_push_post_meta` and `dt_push_post_terms` (props [@dinhtungdu](https://github.com/dinhtungdu), [@dkotter](https://github.com/dkotter) via [#800](https://github.com/10up/distributor/pull/800)).

### Fixed
- Ensure error messages are shown properly if an error happens during a push (props [@dkotter](https://github.com/dkotter), [@Drmzindec](https://github.com/Drmzindec) via [#803](https://github.com/10up/distributor/pull/803)).

## [1.6.5] - 2021-09-01
### Added
- Better support for the Block Editor's fullscreen mode via a new Distributor panel with a toggle option (props [@dkotter](https://github.com/dkotter), [@dinhtungdu](https://github.com/dinhtungdu), [@helen](https://github.com/helen), [@jeffpaul](https://github.com/jeffpaul) via [#750](https://github.com/10up/distributor/pull/750), [#790](https://github.com/10up/distributor/pull/790)).
- `Update URI` header to ensure only legitimate Distributor updates are applied to this install (props [@jeffpaul](https://github.com/jeffpaul) via [#778](https://github.com/10up/distributor/pull/778)).
- Issue management automation via GitHub Actions (props [@jeffpaul](https://github.com/jeffpaul) [#782](https://github.com/10up/distributor/pull/782)).

### Changed
- Update `subscriptions.php` hook priority so plugins hooked to `save_post` can process before syncing happens (props [@pascalknecht](https://github.com/pascalknecht), [@dkotter](https://github.com/dkotter), [@dinhtungdu](https://github.com/dinhtungdu) via [#590](https://github.com/10up/distributor/pull/590)).
- Documentation updates (props [@jeffpaul](https://github.com/jeffpaul) via [#770](https://github.com/10up/distributor/pull/770)).

### Fixed
- Ensure original site information is set properly on content Pulled from external connections (props [@dkotter](https://github.com/dkotter), [@justiny](https://github.com/justiny) via [#776](https://github.com/10up/distributor/pull/776)).
- Ensure we are on a multisite before using `switch_to_blog` (props [@dkotter](https://github.com/dkotter), [@Drmzindec](https://github.com/Drmzindec) via [#780](https://github.com/10up/distributor/pull/780)).

### Security
- Bump `y18n` from 3.2.1 to 3.2.2 (props [@dependabot](https://github.com/apps/dependabot) via [#747](https://github.com/10up/distributor/pull/747)).
- Bump `rmccue/requests` from 1.7.0 to 1.8.0 (props [@dependabot](https://github.com/apps/dependabot) via [#756](https://github.com/10up/distributor/pull/756)).
- Bump `ssri` from 6.0.1 to 6.0.2 (props [@dependabot](https://github.com/apps/dependabot) via [#757](https://github.com/10up/distributor/pull/757)).
- Bump `lodash` from 4.17.19 to 4.17.21 (props [@dependabot](https://github.com/apps/dependabot) via [#759](https://github.com/10up/distributor/pull/759)).
- Bump `hosted-git-info` from 2.8.8 to 2.8.9 (props [@dependabot](https://github.com/apps/dependabot) via [#760](https://github.com/10up/distributor/pull/760)).
- Bump `path-parse` from 1.0.6 to 1.0.7 (props [@dependabot](https://github.com/apps/dependabot) via [#785](https://github.com/10up/distributor/pull/785)).

## [1.6.4] - 2021-03-24
### Added
- Plugin banner and icon assets (props [@JackieKjome](https://github.com/JackieKjome) via [#736](https://github.com/10up/distributor/pull/736)).

### Changed
- Continuous Integration: Switch from Travis to GH Actions for linting and PHPUnit testing (props [@dinhtungdu](https://github.com/dinhtungdu) via [#663](https://github.com/10up/distributor/pull/663)).

### Fixed
- PHP fatal error with the `log_sync` function (props [@dkotter](https://github.com/dkotter), [@SieBer15](https://github.com/SieBer15) via [#742](https://github.com/10up/distributor/pull/742)).
- UI bug that displayed incorrect options when switching External Connection types (props [@dhanendran](https://github.com/dhanendran), [@dkotter](https://github.com/dkotter), [@helen](https://github.com/helen) via [#727](https://github.com/10up/distributor/pull/727)).

### Security
- Bump `elliptic` from 6.5.3 to 6.5.4 (props [@dependabot](https://github.com/apps/dependabot) via [#733](https://github.com/10up/distributor/pull/733)).
- Bump `yargs-parser` from 5.0.0 to 5.0.1 (props [@dependabot](https://github.com/apps/dependabot) via [#740](https://github.com/10up/distributor/pull/740)).

## [1.6.3] - 2021-03-09
### Added
- Ability to pull content in draft status, option to set `post_status` of pulled content (props [@dkotter](https://github.com/dkotter), [@dinhtungdu](https://github.com/dinhtungdu), [@francisco-domo](https://github.com/francisco-domo) via [#701](https://github.com/10up/distributor/pull/701)).
- Introduce `View all` post type filter on Pull Content screen (props [@elliott-stocks](https://github.com/elliott-stocks), [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul), [@oszkarnagy](https://github.com/oszkarnagy) via [#725](https://github.com/10up/distributor/pull/725)).
- Add ability to Unskip or Pull items from the Skipped tab on the Pull screen (props [@dkotter](https://github.com/dkotter), [@elliott-stocks](https://github.com/elliott-stocks), [@jeffpaul](https://github.com/jeffpaul), [@oszkarnagy](https://github.com/oszkarnagy), [@zacnboat](https://github.com/zacnboat) via [#728](https://github.com/10up/distributor/pull/728)).
- Support for plugins / themes to add additional columns to the Pull Content list table (props [@elliott-stocks](https://github.com/elliott-stocks) via [#721](https://github.com/10up/distributor/pull/721)).
- Test coverage to ensure meta denylist is applied to attachments (props [@dhanendran](https://github.com/dhanendran), [@helen](https://github.com/helen) via [#706](https://github.com/10up/distributor/pull/706)).

### Changed
- Notification text for added consistency (props [@cdwieber](https://github.com/cdwieber), [@jeffpaul](https://github.com/jeffpaul) via [#696](https://github.com/10up/distributor/pull/696)).
- Hide registration notice once Distributor has been successfully registered (props [@dhanendran](https://github.com/dhanendran), [@dkotter](https://github.com/dkotter), [@dinhtungdu](https://github.com/dinhtungdu), [@hearvox](https://github.com/hearvox) via [#702](https://github.com/10up/distributor/pull/702)).
- Documentation updates (props [@jeffpaul](https://github.com/jeffpaul), [@rosspbauer](https://github.com/rosspbauer)).

### Fixed
- Allow pulled posts to use block editor (props [@davidmpurdy](https://github.com/davidmpurdy), [@dkotter](https://github.com/dkotter) via [#581](https://github.com/10up/distributor/pull/581)).
- Ensure Distributor push menu in adminbar displays for appropriate post types (props [@dkotter](https://github.com/dkotter), [@avag-novembit](https://github.com/avag-novembit), [@jeffpaul](https://github.com/jeffpaul) via [#694](https://github.com/10up/distributor/pull/694)).
- Block editor check for posts that have no `post_content` set (props [@dkotter](https://github.com/dkotter), [@andrewortolano](https://github.com/andrewortolano), [@xyralothep](https://github.com/xyralothep), [@ggutenberg](https://github.com/ggutenberg), [@jmslbam](https://github.com/jmslbam) via [#710](https://github.com/10up/distributor/pull/710)).
- Pull Content UI errors that resulted in displaying incorrect post type content and PHP notices (props [@dkotter](https://github.com/dkotter), [@grappler](https://github.com/grappler) via [#703](https://github.com/10up/distributor/pull/703)).
- Reset the `last_changed_sites` option when a new site is created, also ensures initialization works on sites created with WP CLI (props [@dkotter](https://github.com/dkotter), [@sbrow](https://github.com/sbrow), [@helen](https://github.com/helen) via [#716](https://github.com/10up/distributor/pull/716)).
- Ensure Bulk Skip option in Pull Content screen correctly skips posts (props [@dhanendran](https://github.com/dhanendran), [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul), [@zacnboat](https://github.com/zacnboat) via [#717](https://github.com/10up/distributor/pull/717)).

## [1.6.2] - 2021-01-14
### Fixed
- Handles case where Application Passwords is available in WordPress core starting with 5.6 (props [@dinhtungdu](https://github.com/dinhtungdu), [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul), [@j0HnC0untry](https://github.com/j0HnC0untry), [@dfardon](https://github.com/dfardon), [@anilpainuly121](https://github.com/anilpainuly121) via [#676](https://github.com/10up/distributor/pull/676), [#681](https://github.com/10up/distributor/pull/681), [#682](https://github.com/10up/distributor/pull/682)).
- Update bundled version of Application Passwords to 1.1.2 (props [@dkotter](https://github.com/dkotter), [@vimalagarwalasentech](https://github.com/vimalagarwalasentech) via [#693](https://github.com/10up/distributor/pull/693)).
- Issue with HTML entity character encoding in a distributed post's title (props  [@dinhtungdu](https://github.com/dinhtungdu), [@dkotter](https://github.com/dkotter), [@rickalee](https://github.com/rickalee) via [#672](https://github.com/10up/distributor/pull/672)).
- Bumped WordPress tested-up-to version to 5.6 (props [@jeffpaul](https://github.com/jeffpaul) via [#683](https://github.com/10up/distributor/pull/683)).
- Moved readme screenshots to directory that won't be part of bundled release, helping to minimize the distributed ZIP file size (props [@jeffpaul](https://github.com/jeffpaul), [@helen](https://github.com/helen) via [#673](https://github.com/10up/distributor/pull/673)).

## Security
- Bump `ini` from 1.3.5 to 1.3.7 (props [@dependabot](https://github.com/apps/dependabot) via [#680](https://github.com/10up/distributor/pull/680)).

## [1.6.1] - 2020-11-19
### Added
- Support for the [official AMP plugin](https://github.com/ampproject/amp-wp) and front-end Push distribution via `amp-dev-mode` and new Mustache templates (props [@dkotter](https://github.com/dkotter), [@dinhtungdu](https://github.com/dinhtungdu), [@rickalee](https://github.com/rickalee) via [#665](https://github.com/10up/distributor/pull/665)).
- Better error reporting when creating External Connections and Pushing or Pulling content (props [@dinhtungdu](https://github.com/dinhtungdu), [@dkotter](https://github.com/dkotter) via [#630](https://github.com/10up/distributor/pull/630)).

### Fixed
- Empty admin body class causing compatibility issues with other plugins' styling (props [@dinhtungdu](https://github.com/dinhtungdu), [@PaddyWhacks](https://github.com/PaddyWhacks), [@robcain](https://github.com/robcain) via [#654](https://github.com/10up/distributor/pull/654)).
- `permission_callback` error on WordPress 5.5 (props [@dkotter](https://github.com/dkotter) via [#632](https://github.com/10up/distributor/pull/632)).

### Security
- Bump `lodash` from 4.17.15 to 4.17.19 (props [@dependabot](https://github.com/apps/dependabot) via [#614](https://github.com/10up/distributor/pull/614)).
- Bump `elliptic` from 6.5.2 to 6.5.3 (props [@dependabot](https://github.com/apps/dependabot) via [#621](https://github.com/10up/distributor/pull/621)).
- Bump `dot-prop` from 4.2.0 to 4.2.1 (props [@dependabot](https://github.com/apps/dependabot) via [#664](https://github.com/10up/distributor/pull/664)).

## [1.6.0] - 2020-07-02
### Added
- Authorization Setup Wizard for External Connections leveraging [Application Passwords](https://github.com/WordPress/application-passwords) (props [@adamsilverstein](https://github.com/adamsilverstein), [@dkotter](https://github.com/dkotter), [@dinhtungdu](https://github.com/dinhtungdu), [@helen](https://github.com/helen), [@jeffpaul](https://github.com/jeffpaul), [@hearvox](https://github.com/hearvox) via [#368](https://github.com/10up/distributor/pull/368), [#594](https://github.com/10up/distributor/pull/594), [#601](https://github.com/10up/distributor/pull/601)).
- "Select All" and "Clear" options in the Push menu (props [@biggiebangle](https://github.com/biggiebangle), [@dkotter](https://github.com/dkotter), [@oszkarnagy](https://github.com/oszkarnagy), [@helen](https://github.com/helen) via [#495](https://github.com/10up/distributor/pull/495), [#589](https://github.com/10up/distributor/pull/589)).
- "Pull" row action in the Pull menu (props [@lakrisgubben](https://github.com/lakrisgubben) via [#508](https://github.com/10up/distributor/pull/508)).
- "View" link for distribguted posts in Push menu for External Connections (props [@dinhtungdu](https://github.com/dinhtungdu), [@PaddyWhacks](https://github.com/PaddyWhacks) via [#571](https://github.com/10up/distributor/pull/571)).
- Accessibility improvements (props [@samikeijonen](https://github.com/samikeijonen), [@dinhtungdu](https://github.com/dinhtungdu), [@dkotter](https://github.com/dkotter) via [#558](https://github.com/10up/distributor/pull/558), [#559](https://github.com/10up/distributor/pull/559), [#560](https://github.com/10up/distributor/pull/560), [#562](https://github.com/10up/distributor/pull/562), [#565](https://github.com/10up/distributor/pull/565), [#566](https://github.com/10up/distributor/pull/566), [#569](https://github.com/10up/distributor/pull/569)).
- Site Health integration to display Distributor debug information (props [@dinhtungdu](https://github.com/dinhtungdu), [@jeffpaul](https://github.com/jeffpaul), [@johnwatkins0](https://github.com/johnwatkins0), [@dkotter](https://github.com/dkotter) via [#517](https://github.com/10up/distributor/pull/517)).
- `dt_syndicatable_capabilities` filter to Push menu (props [@pragmatic-tf](https://github.com/pragmatic-tf) via [#473](https://github.com/10up/distributor/pull/473)).
- `dt_subscription_post_timeout` filter to modify request timeout (props [@ahovhannissian](https://github.com/ahovhannissian), [@dinhtungdu](https://github.com/dinhtungdu) via [#529](https://github.com/10up/distributor/pull/529)).
- [Hook documentation GitHub Pages site](https://10up.github.io/distributor/) generated by GitHub Actions (props [@adamsilverstein](https://github.com/adamsilverstein), [@helen](https://github.com/helen), [@jeffpaul](https://github.com/jeffpaul) via [#448](https://github.com/10up/distributor/pull/448), [#467](https://github.com/10up/distributor/pull/467), [#474](https://github.com/10up/distributor/pull/474), [#475](https://github.com/10up/distributor/pull/475), [#476](https://github.com/10up/distributor/pull/476), [#477](https://github.com/10up/distributor/pull/477), [#478](https://github.com/10up/distributor/pull/478), [#479](https://github.com/10up/distributor/pull/479), [#482](https://github.com/10up/distributor/pull/482), [#485](https://github.com/10up/distributor/pull/485), [#545](https://github.com/10up/distributor/pull/545)).
- JSON PHP extension as a Composer requirement (props [@moebrowne](https://github.com/moebrowne), [@adamsilverstein](https://github.com/adamsilverstein), [@dinhtungdu](https://github.com/dinhtungdu) via [#460](https://github.com/10up/distributor/pull/460)).
- GitHub Actions to build and add release asset (props [@helen](https://github.com/helen), [@jeffpaul](https://github.com/jeffpaul) via [#608](https://github.com/10up/distributor/pull/608)).
- Documentation improvements (props [@jeffpaul](https://github.com/jeffpaul), [@dmchale](https://github.com/dmchale), [@kant](https://github.com/kant), [@petenelson](https://github.com/petenelson), [@dinhtungdu](https://github.com/dinhtungdu), [@jakemgold](https://github.com/jakemgold) via [#433](https://github.com/10up/distributor/pull/433), [#462](https://github.com/10up/distributor/pull/462), [#489](https://github.com/10up/distributor/pull/489), [#513](https://github.com/10up/distributor/pull/513), [#525](https://github.com/10up/distributor/pull/525), [#528](https://github.com/10up/distributor/pull/528), [#542](https://github.com/10up/distributor/pull/542), [#544](https://github.com/10up/distributor/pull/544), [#588](https://github.com/10up/distributor/pull/588), [#598](https://github.com/10up/distributor/pull/598), [#599](https://github.com/10up/distributor/pull/599)).

### Changed
- Internal distribution prepares posts the same for Push or Pull actions (props [@rmarscher](https://github.com/rmarscher), [@dinhtungdu](https://github.com/dinhtungdu) via [#169](https://github.com/10up/distributor/pull/169)).
- Use filesystem for copying media when doing a network pull/push instead of `download_url()` (props [@petenelson](https://github.com/petenelson), [@dkotter](https://github.com/dkotter), [@dmaslogh](https://github.com/dmaslogh), [@Kpudlo](https://github.com/Kpudlo) via [#567](https://github.com/10up/distributor/pull/567)).
- Redirect to pulled content tab after content is pulled (props [@joshuaabenazer](https://github.com/joshuaabenazer), [@dkotter](https://github.com/dkotter), [@hearvox](https://github.com/hearvox) via [#575](https://github.com/10up/distributor/pull/575)).
- Remove `hoverIntent` and add empty placeholder child item in Push menu to improve keyboard support (props [@samikeijonen](https://github.com/samikeijonen), [@dinhtungdu](https://github.com/dinhtungdu), [@dkotter](https://github.com/dkotter) via [#564](https://github.com/10up/distributor/pull/564), [#607](https://github.com/10up/distributor/pull/607)).
- Order of "View" and "Edit" links in the Pull menu to match WordPress standard order (props [@jspellman814](https://github.com/jspellman814), [@hearvox](https://github.com/hearvox) via [#532](https://github.com/10up/distributor/pull/532)).
- Show/hide credentials fields based on registration status on that Registration and Settings screen (props [@dinhtungdu](https://github.com/dinhtungdu), [@oszkarnagy](https://github.com/oszkarnagy) via [#543](https://github.com/10up/distributor/pull/543)).
- `date()` to `gmdate()` per PHPCS (props [@helen](https://github.com/helen) via [#602](https://github.com/10up/distributor/pull/602)).
- Bumped WordPress version support to 5.3 (props [@dkotter](https://github.com/dkotter) via [#499](https://github.com/10up/distributor/pull/499)).
- Update all packages and build process (props [@adamsilverstein](https://github.com/adamsilverstein), [@dkotter](https://github.com/dkotter) via [#450](https://github.com/10up/distributor/pull/450)).
- Run [WP Acceptance](https://github.com/10up/wpacceptance/) tests in parallel in Travis (props [@adamsilverstein](https://github.com/adamsilverstein) via [#439](https://github.com/10up/distributor/pull/439)).

### Fixed
- Block editor compatibility issues ([@dkotter](https://github.com/dkotter), [@dinhtungdu](https://github.com/dinhtungdu) via [#579](https://github.com/10up/distributor/pull/579)).
- Issue where push menu would disappear when push is in progress (props [@dinhtungdu](https://github.com/dinhtungdu), [@dkotter](https://github.com/dkotter), [@helen](https://github.com/helen) via [#538](https://github.com/10up/distributor/pull/538)).
- Undefined variable issue causing external pushes to not fully work (props [@dkotter](https://github.com/dkotter) via [#578](https://github.com/10up/distributor/pull/578)).
- Check for value of remote post id to verify push result (props [@dinhtungdu](https://github.com/dinhtungdu), [@eriktad](https://github.com/eriktad), [@arsendovlatyan](https://github.com/arsendovlatyan) via [#574](https://github.com/10up/distributor/pull/574)).
- Issue with wrong permission route that causes External Connections to fail on creation (props [@dinhtungdu](https://github.com/dinhtungdu), [@dkotter](https://github.com/dkotter) via [#570](https://github.com/10up/distributor/pull/570)).
- Issue where view links for external connections are incorrect if it is set up with a trailing slash (props [@dkotter](https://github.com/dkotter) via [#586](https://github.com/10up/distributor/pull/586)).
- Issue with timeouts on `ajax_verify_external_connection` (props [@madmax3365](https://github.com/madmax3365), [@dinhtungdu](https://github.com/dinhtungdu) via [#245](https://github.com/10up/distributor/pull/245)).
- Issue where searching for posts during a multisite pull does not work correctly (props [@petenelson](https://github.com/petenelson), [@dinhtungdu](https://github.com/dinhtungdu) via [#533](https://github.com/10up/distributor/pull/533)).
- Issue where block content pulled through External Connections get converted to classic block (props [@dinhtungdu](https://github.com/dinhtungdu), [@jshwlkr](https://github.com/jshwlkr) via [#534](https://github.com/10up/distributor/pull/534)).
- Issue where shortcodes were not rendered when pulling content within a multisite instance (props [@petenelson](https://github.com/petenelson), [@dkotter](https://github.com/dkotter) via [#498](https://github.com/10up/distributor/pull/498)).
- Issue where updating a post in a multisite environment was setting the distributed post's author to the current user rather than maintaining the original author (props [@petenelson](https://github.com/petenelson) via [#527](https://github.com/10up/distributor/pull/527)).
- Issue where REST API field was not getting populated correctly (props [@dinhtungdu](https://github.com/dinhtungdu), [@dkotter](https://github.com/dkotter), [@ivanlopez](https://github.com/ivanlopez) via [#519](https://github.com/10up/distributor/pull/519)).
- Issue where terms/meta/etc. gets distributed when updating a previously distributed post (props [@lakrisgubben](https://github.com/lakrisgubben), [@dkotter](https://github.com/dkotter) via [#518](https://github.com/10up/distributor/pull/518)).
- Replace `has_blocks` with `use_block_editor_for_post` in `is_using_gutenberg check` (props [@johnwatkins0](https://github.com/johnwatkins0), [@dkotter](https://github.com/dkotter) via [#514](https://github.com/10up/distributor/pull/514)).
- Issue where previously distributed posts appear as distributable for External Connections (props [@madmax3365](https://github.com/madmax3365), [@avag-novembit](https://github.com/avag-novembit), [@adamsilverstein](https://github.com/adamsilverstein) via [#444](https://github.com/10up/distributor/pull/444)).
- Enable multisite support for VIP Go sites (props [@dinhtungdu](https://github.com/dinhtungdu), [@WPprodigy](https://github.com/WPprodigy), [@jonny-bull](https://github.com/jonny-bull) via [#606](https://github.com/10up/distributor/pull/606)).
- External connections page markup enhancements (props [@joshuaabenazer](https://github.com/joshuaabenazer), [@samikeijonen](https://github.com/samikeijonen) via [#576](https://github.com/10up/distributor/pull/576)).
- Fixes and updates unit and acceptance tests, coding standards issues, and WordPress tested-up-to version (props [@dinhtungdu](https://github.com/dinhtungdu) via [#603](https://github.com/10up/distributor/pull/603)).

### Security
- Bump `websocket-extensions` from 0.1.3 to 0.1.4 (props [@dependabot](https://github.com/apps/dependabot) via [#587](https://github.com/10up/distributor/pull/587)).
- Bump `acorn` from 5.7.3 to 5.7.4 (props [@dependabot](https://github.com/apps/dependabot) via [#548](https://github.com/10up/distributor/pull/548)).
- Bump `extend` from 3.0.1 to 3.0.2 (props [@dependabot](https://github.com/apps/dependabot) via [#447](https://github.com/10up/distributor/pull/447)).

## [1.5.0] - 2019-07-18
### Added
- Provide more context to the `dt_create_missing_terms` hook (props [@mmcachran](https://github.com/mmcachran), [@helen](https://github.com/helen) via [#378](https://github.com/10up/distributor/pull/378))
- Test against multiple WP Snapshot variations and block tests (props [@adamsilverstein](https://github.com/adamsilverstein) via [#342](https://github.com/10up/distributor/pull/342) and [#367](https://github.com/10up/distributor/pull/367))
- Documentation improvements (props [@adamsilverstein](https://github.com/adamsilverstein), [@jeffpaul](https://github.com/jeffpaul), [@arsendovlatyan](https://github.com/arsendovlatyan) via [#352](https://github.com/10up/distributor/pull/352), [#363](https://github.com/10up/distributor/pull/363), [#403](https://github.com/10up/distributor/pull/403), [#414](https://github.com/10up/distributor/pull/414), [#415](https://github.com/10up/distributor/pull/415))

### Changed
- More efficient method of generating internal connection data on push and pull screens (props [@dkotter](https://github.com/dkotter) via [#355](https://github.com/10up/distributor/pull/355))
- Lazy load available push connections in toolbar dropdown to avoid blocking page render (props [@dkotter](https://github.com/dkotter) via [#365](https://github.com/10up/distributor/pull/365))
- More performant retrieval and consistent ordering on the pull content screen (props [@helen](https://github.com/helen) via [#431](https://github.com/10up/distributor/pull/431) and [#434](https://github.com/10up/distributor/pull/434))
- Unify args provided to the `dt_push_post_args` filter (props [@gthayer](https://github.com/gthayer) via [#371](https://github.com/10up/distributor/pull/371))
- Bumped WordPress version support to 5.2 (props [@adamsilverstein](https://github.com/adamsilverstein), [@jeffpaul](https://github.com/jeffpaul) via [#376](https://github.com/10up/distributor/pull/376))

### Fixed
- Avoid connection errors on the pull content screen for connections with a lot of pulled/skipped content (props [@helen](https://github.com/helen) via [#431](https://github.com/10up/distributor/pull/431))
- Pass slug when distributing terms to avoid duplicating terms with special characters or custom slugs (props [@arsendovlatyan](https://github.com/arsendovlatyan) and [@helen](https://github.com/helen) via [#262](https://github.com/10up/distributor/pull/262))
- Simplify and avoid a fatal error in `is_using_gutenberg()` (props [@helen](https://github.com/helen) via [#426](https://github.com/10up/distributor/pull/426))
- Avoid PHP notices (props [@grappler](https://github.com/grappler) via [#401](https://github.com/10up/distributor/pull/401) and [@mrazzari](https://github.com/mrazzari) via [#420](https://github.com/10up/distributor/pull/420))

## [1.4.1] - 2019-03-15
### Fixed
- Improve block editor detection, correcting an issue with post saving.

## [1.4.0] - 2019-03-07
### Added
- Clearer instructions and help text when adding an external connection.
- Log image sideloading failures when using `DISTRIBUTOR_DEBUG`.

### Fixed
- Allow attachments to be distributed from local environments.
- Ensure pagination is reset when switching views on the pull content screen.
- Remove extraneous checkboxes from pulled content screen.
- Suppress a PHP warning when no meta is being distributed for attachments.

## [1.3.9] - 2019-02-21
### Fixed
- Ensure posts distributed as draft can be published.

## [1.3.8] - 2019-01-30
### Added
- Add `dt_after_set_meta` action.
- Add `dt_process_subscription_attributes` action.

### Fixed
- Ensure post types without excerpt support can be distributed.

## [1.3.7] - 2019-01-16
### Added
- Distribute plaintext URLs instead of full markup for automatic embeds (oEmbeds). This was causing issues for non-privileged users where the markup was subject to sanitization/kses.
- Add `push`/`pull` context to `get_available_authorized_sites()`.
- Add `dt_allowed_media_extensions` and `dt_media_processing_filename` filters so that different media types or specific files can be detected and targeted.

### Fixed
- Ensure media meta is passed through `prepare_meta()` to apply the exclusion. This completes the generated image size info fix from 1.3.3.
- Avoid a PHP notice when only using the block editor on the receiving site.
- Avoid a jQuery Migrate notice.

## [1.3.6] - 2018-12-19
### Fixed (for WP 5.0 block editor)
- Properly detect block editor content.
- Show notices with actions.
- Ensure distributed posts can be published.
- Fully disable editing of classic blocks in distributed posts.
- Clean up distribution status display in side panel.
- Not block editor: Avoid notices on the pull content screen when no connections are set up yet.

## [1.3.5] - 2018-12-05
### Added
- Add a `dt_available_pull_post_types` filter to enable pulling of post types not registered on the destination site. NOTE: This requires custom handling to pull into an existing post type.

### Fixed
- Avoid duplicating empty meta values.
- Align with JS i18n coming in WordPress 5.0.

## [1.3.4] - 2018-11-20
### Added
- Provide `$taxonomy` to the `dt_update_term_hierarchy` filter.

### Fixed
- Enable distribution of multiple meta values stored using the same key.
- Retain comment status, pingback status, and post passwords on pull.

## [1.3.3] - 2018-10-19
### Fixed
- Do not interfere with non-subscription REST API requests.
- Retain generated image size info after media distribution.

## [1.3.2] - 2018-10-16
### Fixed
- Correctly encode search query in the pull list.
- Properly check the key for subscription updates.
- Ensure featured images are properly detected from environments that type juggle.
- Add plugin icon to plugin update UI.

## [1.3.1] - 2018-10-09
### Fixed
- Retain keys for associative array meta.
- Properly pass CPT slugs to external connections.
- Don't push updates to network sites that no longer exist.
- Escaping improvements.
- Stable build now only contains files necessary for production.

## [1.3.0] - 2018-09-20
### Added
- Add a media processing option to only distribute the featured image instead of the featured image and all attached media.

**Important note**: This is now the default option **for all sites**. Attached media is often loosely correlated with media use and in-content media URLs are not rewritten on distribution, making local copies of most attached media unnecessary in default setups, even as they add significant overhead to distribution. To retain the previous behavior of distributing all attached media (children attachments), change the setting on the **receiving** site to `Process the featured image and any attached images.`

- Support pulling multiple post types for external connections.

This adds a post type selector when viewing the Pull Content list for both external and internal connections, which is both easier to use and more performant.

- Distributed copies of posts that are later permanently deleted are now marked as `skipped` in the Pull Content list, making them available for pull again while not appearing as new content.
- Add `dt_original_post_parent` to post meta, allowing developers to better manage post parent handling.

### Fixed
- Restore support for storing arrays in meta
- Don't show pushed posts as available for pull on the receiving site
- Correctly save screen options on Distributor pages
- Removed a redundant argument
- Code formatting fixes

## [1.2.3] - 2018-08-16
### Fixed
- Issue that was hiding the "As Draft" checkbox on the push screen. We've introduced a new filter "dt_allow_as_draft_distribute" which can be set to false to disable the "as draft" checkbox.

## [1.2.2] - 2018-08-14
### Added
- Helper function to return post statuses that are allowed to be distributed
- Utilize the og:url from Yoast for external connections
- Add new filters for authorized sites for internal connections
- Documentation and formatting updates

### Changed
- Don’t set Distributor meta data on REST API post creation unless post was created by Distributor
- Exclude the `_wp_old_slug` and `_wp_old_date` meta
- Disable pull UI while switching between pull connections

### Fixed
- Issue where content pulled or skipped from an internal connection (in the Pull interface) would show up as "pulled" across all internal sites / connections. **Backwards compatibility break**: internal posts that were previously skipped or pulled will show as available for pull again in all internal sites.

## [1.2.1] - 2018-07-06
### Fixed
- Block editor bugs; parent post bug.

## [1.2.0] - 2018-05-27
### Added
- Block editor support, public release.

## [1.1.0] - 2018-01-19
### Added
- WordPress.com Oauth2 authentication.

## [1.0.0] - 2016-09-26
- Initial closed release.

[Unreleased]: https://github.com/10up/distributor/compare/trunk...develop
[2.0.6]: https://github.com/10up/distributor/compare/2.0.5...2.0.6
[2.0.5]: https://github.com/10up/distributor/compare/2.0.4...2.0.5
[2.0.4]: https://github.com/10up/distributor/compare/2.0.3...2.0.4
[2.0.3]: https://github.com/10up/distributor/compare/2.0.2...2.0.3
[2.0.2]: https://github.com/10up/distributor/compare/2.0.1...2.0.2
[2.0.1]: https://github.com/10up/distributor/compare/2.0.0...2.0.1
[2.0.0]: https://github.com/10up/distributor/compare/1.9.1...2.0.0
[1.9.1]: https://github.com/10up/distributor/compare/1.9.0...1.9.1
[1.9.0]: https://github.com/10up/distributor/compare/1.8.0...1.9.0
[1.8.0]: https://github.com/10up/distributor/compare/1.7.1...1.8.0
[1.7.1]: https://github.com/10up/distributor/compare/1.7.0...1.7.1
[1.7.0]: https://github.com/10up/distributor/compare/1.6.9...1.7.0
[1.6.9]: https://github.com/10up/distributor/compare/1.6.8...1.6.9
[1.6.8]: https://github.com/10up/distributor/compare/1.6.7...1.6.8
[1.6.7]: https://github.com/10up/distributor/compare/1.6.6...1.6.7
[1.6.6]: https://github.com/10up/distributor/compare/1.6.5...1.6.6
[1.6.5]: https://github.com/10up/distributor/compare/1.6.4...1.6.5
[1.6.4]: https://github.com/10up/distributor/compare/1.6.3...1.6.4
[1.6.3]: https://github.com/10up/distributor/compare/1.6.2...1.6.3
[1.6.2]: https://github.com/10up/distributor/compare/1.6.1...1.6.2
[1.6.1]: https://github.com/10up/distributor/compare/1.6.0...1.6.1
[1.6.0]: https://github.com/10up/distributor/compare/1.5.0...1.6.0
[1.5.0]: https://github.com/10up/distributor/compare/1.4.1...1.5.0
[1.4.1]: https://github.com/10up/distributor/compare/1.4.0...1.4.1
[1.4.0]: https://github.com/10up/distributor/compare/1.3.9...1.4.0
[1.3.9]: https://github.com/10up/distributor/compare/1.3.8...1.3.9
[1.3.8]: https://github.com/10up/distributor/compare/1.3.7...1.3.8
[1.3.7]: https://github.com/10up/distributor/compare/1.3.6...1.3.7
[1.3.6]: https://github.com/10up/distributor/compare/1.3.5...1.3.6
[1.3.5]: https://github.com/10up/distributor/compare/1.3.4...1.3.5
[1.3.4]: https://github.com/10up/distributor/compare/1.3.3...1.3.4
[1.3.3]: https://github.com/10up/distributor/compare/1.3.2...1.3.3
[1.3.2]: https://github.com/10up/distributor/compare/1.3.1...1.3.2
[1.3.1]: https://github.com/10up/distributor/compare/1.3.0...1.3.1
[1.3.0]: https://github.com/10up/distributor/compare/1.2.3...1.3.0
[1.2.3]: https://github.com/10up/distributor/compare/1.2.2...1.2.3
[1.2.2]: https://github.com/10up/distributor/compare/7f245b5...1.2.2
[1.2.1]: https://github.com/10up/distributor/compare/457b14...7f245b5
[1.2.0]: https://github.com/10up/distributor/compare/archive%2Ffeature%2Fenable-oath2...457b14
[1.1.0]: https://github.com/10up/distributor/compare/5f68677...archive%2Ffeature%2Fenable-oath2
[1.0.0]: https://github.com/10up/distributor/commit/5f68677da972336b6a8161c143faa456bfdbe4ef
