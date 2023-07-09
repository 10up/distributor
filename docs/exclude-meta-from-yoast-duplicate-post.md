When duplicating a source post using Yoast Duplicate Post, a distributed post can be connected to two sources if the subscription meta is not excluded.

[External reference](https://developer.yoast.com/duplicate-post/filters-actions/#duplicate_post_excludelist_filter)

```php
/**
 * Filters out custom fields from being duplicated in addition to the defaults.
 *
 * @param array $meta_excludelist The default exclusion list, based on the “Do not copy these fields” setting, plus some other field names.
 *
 * @return array The custom fields to exclude.
 */
function client_prefix__custom_fields_filter( $meta_excludelist ) {
	// Merges the defaults array with our own array of custom fields.
	return array_merge(
		$meta_excludelist, [
			'dt_connection_map',
			'dt_subscription_update',
			'dt_subscriptions',
			'dt_subscription_signature',
		]
	);
}

add_filter( 'duplicate_post_excludelist_filter', 'client_prefix__custom_fields_filter' );
```