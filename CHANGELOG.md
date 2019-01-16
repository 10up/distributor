# Changelog

All notable changes to this project will be documented in this file, per [the Keep a Changelog standard](http://keepachangelog.com/).

## [ 1.3.7 ] - 2019-01-16
### Added
* Distribute plaintext URLs instead of full markup for automatic embeds (oEmbeds). This was causing issues for non-privileged users where the markup was subject to sanitization/kses.
* Add `push`/`pull` context to `get_available_authorized_sites()`.
* Add `dt_allowed_media_extensions` and `dt_media_processing_filename` filters so that different media types or specific files can be detected and targeted.

### Fixed
* Ensure media meta is passed through `prepare_meta()` to apply the blacklist. This completes the generated image size info fix from 1.3.3.
* Avoid a PHP notice when only using the block editor on the receiving site.
* Avoid a jQuery Migrate notice.

## [ 1.3.6 ] - 2018-12-19
### Fixed (for WP 5.0 block editor)
* Properly detect block editor content.
* Show notices with actions.
* Ensure distributed posts can be published.
* Fully disable editing of classic blocks in distributed posts.
* Clean up distribution status display in side panel.
* Not block editor: Avoid notices on the pull content screen when no connections are set up yet.

## [ 1.3.5 ] - 2018-12-05
### Added
* Add a `dt_available_pull_post_types` filter to enable pulling of post types not registered on the destination site. NOTE: This requires custom handling to pull into an existing post type.

### Fixed
* Avoid duplicating empty meta values.
* Align with JS i18n coming in WordPress 5.0.

## [ 1.3.4 ] - 2018-11-20
### Added
* Provide `$taxonomy` to the `dt_update_term_hierarchy` filter.

### Fixed
* Enable distribution of multiple meta values stored using the same key.
* Retain comment status, pingback status, and post passwords on pull.

## [ 1.3.3 ] - 2018-10-19
### Fixed
* Do not interfere with non-subscription REST API requests.
* Retain generated image size info after media distribution.

## [ 1.3.2 ] - 2018-10-16
### Fixed
* Correctly encode search query in the pull list.
* Properly check the key for subscription updates.
* Ensure featured images are properly detected from environments that type juggle.
* Add plugin icon to plugin update UI.

## [ 1.3.1 ] - 2018-10-09
### Fixed
* Retain keys for associative array meta.
* Properly pass CPT slugs to external connections.
* Don't push updates to network sites that no longer exist.
* Escaping improvements.
* Stable build now only contains files necessary for production.


## [ 1.3.0 ] - 2018-09-20
### Added
* Add a media processing option to only distribute the featured image instead of the featured image and all attached media.

**Important note**: This is now the default option **for all sites**. Attached media is often loosely correlated with media use and in-content media URLs are not rewritten on distribution, making local copies of most attached media unnecessary in default setups, even as they add significant overhead to distribution. To retain the previous behavior of distributing all attached media (children attachments), change the setting on the **receiving** site to `Process the featured image and any attached images.`

* Support pulling multiple post types for external connections.

This adds a post type selector when viewing the Pull Content list for both external and internal connections, which is both easier to use and more performant.

* Distributed copies of posts that are later permanently deleted are now marked as `skipped` in the Pull Content list, making them available for pull again while not appearing as new content.
* Add `dt_original_post_parent` to post meta, allowing developers to better manage post parent handling.

### Fixed
* Restore support for storing arrays in meta
* Don't show pushed posts as available for pull on the receiving site
* Correctly save screen options on Distributor pages
* Removed a redundant argument
* Code formatting fixes

## [ 1.2.3 ] - 2018-08-16
* Fixed an issue that was hiding the "As Draft" checkbox on the push screen. We've introduced a new filter "dt_allow_as_draft_distribute" which can be set to false to disable the "as draft" checkbox.

## [ 1.2.2 ] - 2018-08-14
* Fixed an issue where content pulled or skipped from an internal connection (in the Pull interface) would show up as "pulled" across all internal sites / connections. **Backwards compatibility break**: internal posts that were previously skipped or pulled will show as available for pull again in all internal sites.
* Don’t set Distributor meta data on REST API post creation unless post was created by Distributor
* Add helper function to return post statuses that are allowed to be distributed
* Utilize the og:url from Yoast for external connections
* Blacklist the `_wp_old_slug` and `_wp_old_date` meta
* Disable pull UI while switching between pull connections
* Add new filters for authorized sites for internal connections
* Documentation and formatting updates

## [ 1.2.1 ] - 2018-07-06
* Gutenberg bug fixes; fix parent post bug.

## [ 1.2.0 ] - 2018-05-27
* Gutenberg support, public release.

## [ 1.1.0 ] - 2018-05-07
* Enable WordPress.com Oauth2 authentication.

## [ 1.0.0 ]
* Initial closed release.
