# Changelog

All notable changes to this project will be documented in this file, per [the Keep a Changelog standard](http://keepachangelog.com/).

## [ 1.3.0 ] - 2018-09-20
### Added
* Support pulling multiple post types for external connections. This adds a post type selector when viewing the Pull Content list for both external and internal connections, which is both easier to use and more performant.
* Add a media processing option to only distribute the featured image instead of the featured image and all attached media (children attachments). This is now the default option, as in-content media URLs are not rewritten, making local copies of most media are unnecessary in default setups.
* Distributed posts that are later permanently deleted are now marked as `skipped`, making them available for pull again while not appearing as new content.
* Add `dt_original_post_parent` to post meta, allowing for easier re-parenting of posts.

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
