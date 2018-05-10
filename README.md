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

Distributor supports content sharing via "pushing" and "pulling". Within the edit post screen, any post (or custom post type) that can be distributed will have a `Distributor` admin bar item that empowers an editor to share that content to any `connection`.

There are two connection types: `external` and `internal`. External connections are external websites. Sharing between external websites is powered via the JSON REST API. External connections can be added via a top level menu item in the admin. Internal connections are other blogs within a multisite network.

Under the top level Distributor menu item is a link to the pull screen. The pull content screen lets an editor share content from any connection to the current site.

## Known Caveats/Issues

__Post Meta Associations__ - A distributed post will contain all the post meta from the original. Sometimes post meta references an ID for another piece of content on the original site. Distributor does not "bring along" the referenced content and does not update those references. This type of situation can be handled on a case by case basis by extending the plugin with custom code. This is very common with the ACF plugin.

__Deleting Distributed Posts__ - When a post that has been distributed post is deleted, the distributed copies will become unlinked from the original post and thus become editable.

## Debugging

You can define a constant `DISTRIBUTOR_DEBUG` to `true` to increase the ease of debugging in Distributor. This will make all remote requests blocking and expose the subscription post type.

## Changelog

* 1.1.0 - Enable WordPress.com Oauth2 authentication.

* 1.0 - Initial release.
