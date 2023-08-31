Version 2.0.0 of Distributor includes a number of breaking changes that will require updates to custom code you may have written for distributor.

## External connections require a minimum of version 2.0.0

It is recommended that both ends of an external connection run the same version of Distributor.

Version 2.0.0 of Distributor will prevent the pulling of posts from sites running Version 1.9.x or lower of Distributor.

### Remove canonical links for both Internal and External Connections

The code snippet required to prevent sites from displaying the source post as canonical URLs for distributed posts has changed.

If you have implemented this using the code snippet from our tutorial file, please update your code to the following:

```php
/**
 * Stop Distributor from changing the canonical links.
 *
 * This removes Distributor's canonical functionality from both Internal and
 * External Connections.
 *
 * This accounts for sites using either WordPress or Yoast SEO to generate the
 * canonical URL.
 */
add_action( 'plugins_loaded', function() {
	remove_filter( 'get_canonical_url', '\\Distributor\\Hooks\\get_canonical_url', 10, 2 );
	remove_filter( 'wpseo_canonical', '\\Distributor\\Hooks\\wpseo_canonical', 10, 2 );
} );
```

### REST API Changes

The distributor REST API endpoint at `/wp/v2/distributor/list-pull-content` has been modified substantially and will now reject connections from 1.x versions of Distributor.

The fields returned by the endpoint have been modified to match the names used by `wp_insert_post` and `wp_update_post`.

#### Additional parameters

* `include` (Array|Int): Ensure result set includes specific Post IDs. Default empty.
* `order` (`asc`|`desc`): Specify order of returned data. Default `desc`.
* `orderby` (`author`|`date`|`id`|`include`|`modified`|`parent`|`relevance`|`slug`|`title`): Field to order results by. Default `date`, `relevance` for search queries.

#### Modified parameters

* `post_type` (String|String[]): Modified to accept multiple post types. Post types are limited to posts the connected account can edit, are public post types and visible in the WordPress REST API's standard endpoints. Default `post`.
* `post_status` (String|String[]): Modified to accept multiple post statuses. Statuses are limited to public statuses only. Default `publish`.

### `dt_push_post` action deprecated

The <a href="./dt_push_post.html">`dt_push_post`</a> action has been deprecated in favor of two actions: <a href="./dt_push_external_post.html">`dt_push_external_post`</a> and <a href="./dt_push_network_post.html">`dt_push_network_post`</a>.

Extenders using the old action are advised to switch to the new actions as a matter of priority. The deprecated action had conflicting arguments so can not be relied upon to pass data consistently.

### Internationalization improvements

The generation of translation files has been updated to include strings included in JavaScript files.

Version 2.0.0 of Distributor has also improved pluralization of strings and combined similar strings to reduce the burden on translators.
