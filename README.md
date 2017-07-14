Distributor
=============

Distributor is a WordPress plugin allowing you to syndicate content to and from external websites and within multisite blogs.

<p align="center">
<a href="http://10up.com/contact/"><img src="https://10updotcom-wpengine.s3.amazonaws.com/uploads/2016/10/10up-Github-Banner.png" width="850"></a>
</p>

## Requirements

* PHP 5.6+
* [WordPress](http://wordpress.org) 4.7+

## Testing

The plugin contains a standard test suite compatible with PHPUnit. If you want to test across multiple PHP versions, a [Dockunit](https://github.com/dockunit/dockunit) file is included.

## Plugin Usage and Extensive Documentation
This lives in the [internal docs site](https://internal.10up.com/docs/distributor-plugin) for now.

## Debugging

You can define a constant `DISTRIBUTOR_DEBUG` to `true` to increase the ease of debugging in Distributor. This will make all remote requests blocking and expose the subscription post type.
