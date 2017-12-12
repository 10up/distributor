Distributor
=============

Distributor is a WordPress plugin allowing you to syndicate content to and from external websites and within multisite blogs.

<p align="center">
<a href="http://10up.com/contact/"><img src="https://10updotcom-wpengine.s3.amazonaws.com/uploads/2016/10/10up-Github-Banner.png" width="850"></a>
</p>

## Requirements

* PHP 5.6+
* [WordPress](http://wordpress.org) 4.7+
* External connections require HTTP Basic Authentication be set up on the remote website. We recommend the [Application Passwords](https://wordpress.org/plugins/application-passwords/) plugin. Also, for full functionality Distributor will need to be installed on BOTH sides of the connection.

## Install

1. Clone or [download](https://github.com/10up/distributor/archive/master.zip) and extract the plugin into `wp-content/plugins`. Make sure you use the `master` branch which contains the latest stable release.
1. Activate the plugin via the dashboard or WP-CLI.

## Testing

The plugin contains a standard test suite compatible with PHPUnit. If you want to test across multiple PHP versions, a [Dockunit](https://github.com/dockunit/dockunit) file is included.

## Plugin Usage

Coming soon!

## Debugging

You can define a constant `DISTRIBUTOR_DEBUG` to `true` to increase the ease of debugging in Distributor. This will make all remote requests blocking and expose the subscription post type.
