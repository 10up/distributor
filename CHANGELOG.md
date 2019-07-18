# Changelog

All notable changes to this project will be documented in this file, per [the Keep a Changelog standard](http://keepachangelog.com/).

## [1.5.0] - 2019-07-18
### Added
- Provide more context to the `dt_create_missing_terms` hook (props [@mmcachran](https://github.com/mmcachran) and [@helen](https://github.com/helen) via [#378](https://github.com/10up/distributor/pull/378))
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
- Ensure media meta is passed through `prepare_meta()` to apply the blacklist. This completes the generated image size info fix from 1.3.3.
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
- Blacklist the `_wp_old_slug` and `_wp_old_date` meta
- Disable pull UI while switching between pull connections

### Fixed
- Issue where content pulled or skipped from an internal connection (in the Pull interface) would show up as "pulled" across all internal sites / connections. **Backwards compatibility break**: internal posts that were previously skipped or pulled will show as available for pull again in all internal sites.

## [1.2.1] - 2018-07-06
### Fixed
- Gutenberg bugs; parent post bug.

## [1.2.0] - 2018-05-27
### Added
- Gutenberg support, public release.

## [1.1.0] - 2018-05-07
### Added
- WordPress.com Oauth2 authentication.

## [1.0.0]
- Initial closed release.

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
[1.2.2]: https://github.com/10up/distributor/releases/tag/1.2.2
[1.1.0]: https://github.com/10up/distributor/releases/tag/archive%2Ffeature%2Fenable-oath2
