---

### Table of Contents
- [Limit to certain post types](#limit-to-certain-post-types)
- [Limit to certain user capabilities](#limit-to-certain-user-capabilities)
- [Limit to certain sites on the network](#limit-to-certain-sites-on-the-network)
- [Remove canonical links for both Internal and External Connections](#remove-canonical-links-for-both-internal-and-external-connections)
- [Push original publication date](#push-original-publication-date)
- [Automatically unlink posts](#automatically-unlink-posts)
- [Modify custom meta data](#modify-custom-meta-data)
- [Exclude meta key from distribution](#exclude-meta-key-from-distribution)

---

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

### Automatically unlink posts
```php
/**
 * Auto unlink distributor posts automatically.
 *
 * Runs on the `dt_after_set_meta` hook.
 *
 * @param mixed $meta          All received meta for the post
 * @param mixed $existing_meta Existing meta for the post
 * @param mixed $post_id       Post ID
 * @return void
 */
function client_prefix_auto_unlink_distributed_posts( $meta, $existing_meta, $post_id ) {
	$post = get_post( $post_id );

	if ( ! $post ) {
		return;
	}

	$is_distributed = get_post_meta( $post->ID, 'dt_original_post_id', true ) ? true : false;

	if ( ! $is_distributed ) {
		return;
	}

	update_post_meta( $post->ID, 'dt_unlinked', true );
}
add_action( 'dt_after_set_meta', 'client_prefix_auto_unlink_distributed_posts', 10, 3 );
```

### Modify custom meta data

```php
/**
 * Set default post meta if not set in the original.
 *
 * @param {array} $meta          All received meta for the post
 * @param {array} $existing_meta Existing meta for the post
 * @param {int}   $post_id       Post ID
 */
function client_prefix_modify_meta( $meta, $existing_meta, $post_id ) {
	// Set post meta if not set.
	if ( ! isset( $existing_meta['my_meta_key'] ) ) {
		add_post_meta( $post_id, 'my_meta_key', 'my meta value' );
	}
}
add_action( 'dt_after_set_meta', 'client_prefix_modify_meta', 10, 3 );
```

### Exclude meta key from distribution

```php
/**
 * Denylist a meta key from distribution.
 */
add_filter( 'dt_excluded_meta', function( $meta_keys ) {
	$meta_keys[] = 'my_meta_key';
	return $meta_keys;
} );
```

### Turn off automatic updates for distributed content

```php
/**
 * Prevent auto-updates from happening for network connections.
 */
add_action(
  'init',
  function() {
    remove_action( 'wp_after_insert_post', [ '\Distributor\InternalConnections\NetworkSiteConnection', 'update_syndicated' ], 99 );
  }
);

/**
 * Prevent auto-updates from happening for external connections.
 */
add_action(
  'init',
  function() {
    remove_action( 'wp_after_insert_post', 'Distributor\Subscriptions\send_notifications', 99 );
  }
);
