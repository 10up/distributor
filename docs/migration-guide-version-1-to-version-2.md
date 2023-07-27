Version 2 of Distributor includes a number of breaking changes that will require updates to custom code you may have written for distributor.

## External connections require a minimum of version 2.0

It is recommended that both ends of an external connection run the same version of Distributor.

Version 2.0 of Distributor will prevent the pulling of posts from sites running Version 1.9.x or lower of Distributor.

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
