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