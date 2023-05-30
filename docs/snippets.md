## Snippets

### Limit to certain post types

```php
add_filter( 'distributable_post_types', 'client_prefix_filter_post_types' );
/**
 * Filter the post types we can distribute.
 *
 * @see https://10up.github.io/distributor/distributable_post_types.html
 *
 * @return array
 */
function client_prefix_filter_post_types() : array {
	return array( 'post', 'page' );
}
```

### Limit to certain user capabilities

```php
add_filter( 'dt_syndicatable_capabilities', 'client_prefix_filter_user_capabilities' );
/**
 * Filter the user capabilities that are allowed to distribute content.
 *
 * @see https://10up.github.io/distributor/dt_syndicatable_capabilities.html
 *
 * @return string
 */
function client_prefix_filter_user_capabilities() : string {
	return 'manage_options';
}
```

### Limit to certain sites on the network

```php
add_filter( 'dt_authorized_sites', 'client_prefix_filter_authorized_sites', 10, 2 );
/**
 * Filter certain sites from the authorized sites list.
 *
 * @see https://10up.github.io/distributor/dt_authorized_sites.html
 *
 * @param array  $authorized_sites Authorized sites.
 * @param string $context Push or pull.
 *
 * @return array
 */
function client_prefix_filter_authorized_sites( array $authorized_sites, string $context ) : array {
	return array_filter(
		$authorized_sites,
		function( $site ) {
			return '/' === $site['site']->path;
		}
	);
}
```

### Remove canonical links for both Internal and External Connections

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

### Push original publication date

```php
/**
 * Keep the publication date on the new pushed post.
 *
 * This filter is used to filter the arguments sent to the remote server during a push. The below code snippet passes the original published date to the new pushed post and sets the same published date instead of setting it as per the current time.
 */
add_filter( 'dt_push_post_args', function( $post_body, $post ) {
    $post_body['post_date'] = $post->post_date;

    return $post_body;
}, 10, 2 );
```

### Set custom meta data on distributed content.

Site owners may wish to modify the meta data on distributed content upon pushing or pulling.

To set an item of meta data you can use the `dt_after_set_meta` hook.

```php
/**
 * Automatically store custom meta data on distributed posts.
 */
add_action( 'dt_after_set_meta', function( $meta, $existing_meta, $post_id ) {
	update_post_meta( $post_id, 'myplugin_custom_meta', 'some_value', true );
}, 10, 3 );

/**
 * Automatically unlink a post once it is distributed.
 *
 * This prevents updates to the original content from modifying the copies
 * distributed on other sites.
 */
add_action( 'dt_after_set_meta', function( $meta, $existing_meta, $post_id ) {
	update_post_meta( $post_id, 'dt_unlinked', '1', true );
}, 10, 3 );
```
