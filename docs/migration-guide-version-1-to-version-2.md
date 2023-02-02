Version 2 of Distributor includes a number of breaking changes that will require updates to custom code you may have written for distributor.

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
	add_action( 'get_canonical_url', '\\Distributor\\Hooks\\get_canonical_url', 10, 2 );
	add_action( 'wpseo_canonical', '\\Distributor\\Hooks\\wpseo_canonical', 10, 2 );
} );
```
