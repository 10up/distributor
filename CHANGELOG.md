# Changelog

All notable changes to this project will be documented in this file, per [the Keep a Changelog standard](http://keepachangelog.com/).

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
