# Welcome to the Distributor Developer Documentation

This resource is generated documentation on actions and filters found in the Distributor plugin. Use the sidebar to browse and navigate.

For more information about using Distributor with WordPress, please see the [Distributor website](https://distributorplugin.com/).

## Migrating to version 2.0

Version 2.0 of Distributor contains breaking changes. Please review the <a href="./tutorial-migration-guide-version-1-to-version-2.html">migration guide</a> tutorial and follow any steps required.

To report an issue with Distributor or contribute back to the project, please visit the [GitHub repository](https://github.com/10up/distributor/).

## Developers

### Running Locally

If you are compiling Distributor locally, note that the recommended version of Node.js is version 16.x. The minimum version required is Node.js 8.10.

An `.nvmrc` file is included in the plugin repository. It's recommended you install [fnm (fast node manager)](https://github.com/Schniz/fnm/), [nvm (node version manager)](https://github.com/nvm-sh/nvm) or similar when developing locally.

### Testing

The plugin contains a standard test suite compatible with PHPUnit. If you want to test across multiple PHP versions, a [Dockunit](https://github.com/dockunit/dockunit) file is included.

### Debugging

You can define a constant `DISTRIBUTOR_DEBUG` to `true` to increase the ease of debugging in Distributor. This will make all remote requests blocking and expose the subscription post type.

Enabling this will also provide more debugging information in your error log for image side loading issues. The specific logging method may change in the future.

### Application Passwords and WordPress 5.6

Application passwords are only available for live sites running over an HTTPS connection.

For your local development environment, you will need these snippets to enable application passwords without the need for an HTTPS connection.  A local development environment is one that "can reach the internet but **is not reachable from the internet**".

```php
// In your local environment's wp-config.php file.
define( 'WP_ENVIRONMENT_TYPE', 'local' );

// In a custom plugin on your local environment.
add_filter( 'wp_is_application_passwords_available', '__return_true' );

add_action( 'wp_authorize_application_password_request_errors', function( $error ) {
    $error->remove( 'invalid_redirect_scheme' );
} );
```
<a href="http://10up.com/contact/" class="banner"><img src="https://10updotcom-wpengine.s3.amazonaws.com/uploads/2016/10/10up-Github-Banner.png" width="850"></a>
