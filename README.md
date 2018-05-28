Distributor
=============

Distributor is a WordPress plugin that makes it easy to syndicate and reuse content across your websites — whether in a single multisite or across the web. 

*You can learn more about Distributor's features at [DistributorPlugin.com](https://distributorplugin.com).*

<a href="http://distributorplugin.com/"><img src="https://distributorplugin.com/wp-content/themes/distributor-theme/assets/img/push-screenshot.jpg" alt="Push the content you’re editing or viewing to any of your other sites from the admin bar" width="600"></a>

<a href="http://distributorplugin.com/"><img alt="Distributor intuitively presents the origin and status of any reused content" class="browser-frame" src="https://distributorplugin.com/wp-content/themes/distributor-theme/assets/img/syndicated-screenshot.jpg" width="600"></a>

## Requirements

* PHP 5.6+
* [WordPress](http://wordpress.org) 4.7+
* External connections require HTTP Basic Authentication or [WordPress.com OAuth2](https://developer.wordpress.com/docs/oauth2/) (must be on VIP) be set up on the remote website. For Basic Auth, we recommend the [Application Passwords](https://wordpress.org/plugins/application-passwords/) plugin.
* For external connections, Distributor needs to be installed on BOTH sides of the connection.

## Install

For production use, we recommend officially [registering for and downloading the plugin from DistributorPlugin.com](https://distributorplugin.com/#cta) – it's 100% free. You will be emailed a direct link to download the latest, production-ready build. Alternatively, you can [download the latest master build from GitHub](https://github.com/10up/distributor/archive/master.zip). 

You can upload and install the archived (zip) plugin via the WordPress dashboard (Plugins > Add New -> Upload Plugin) or manually inside of the `wp-content/plugins` directory, and activate on the Plugins dashboard.

### Registration

To help inform our roadmap, keep adopters apprised of major updates and changes that could impact their websites, and solicit opportunities for beta testing and feedback, we’re asking for a little bit of information in exchange for a free key that unlocks update notifications and 1-click upgrades inside the WordPress dashboard. Your information is kept confidential. You can [register here](https://distributorplugin.com/#cta) and input your key in Distributor settings in the dashboard (network dashboard for multisite users).

## Plugin Usage

Distributor supports content sharing via "pushing" and "pulling". Within the edit post screen, any post (or custom post type) that can be distributed will have a `Distributor` admin bar item that empowers an editor to share that content to any `connection`.

There are two connection types: `external` and `internal`. External connections are external websites. Sharing between external websites is powered via the JSON REST API. External connections can be added via a top level menu item in the admin. Internal connections are other blogs within a multisite network.

Under the top level Distributor menu item is a link to the pull screen. The pull content screen lets an editor share content from any connection to the current site.

## Known Caveats/Issues

__Post Meta Associations__ - A distributed post will contain all the post meta from the original. Sometimes post meta references an ID for another piece of content on the original site. Distributor does not "bring along" the referenced content and does not update those references. This type of situation can be handled on a case by case basis by extending the plugin with custom code. This is very common with the ACF plugin. For external connections, you can manually handle post meta associations on [this hook](https://github.com/10up/distributor/blob/master/includes/classes/ExternalConnections/WordPressExternalConnection.php#L512). For internal connections, you can manually handle post meta associations on [this hook](https://github.com/10up/distributor/blob/master/includes/classes/InternalConnections/NetworkSiteConnection.php#L102).

__Deleting Distributed Posts__ - When a post that has been distributed is deleted, the distributed copies will become unlinked from the original post and thus become editable. When a post is unpublished, distributed copies will not be unpublished.

__Remote Request Timeouts__ - With external connections, HTTP requests are sent back and forth - creating posts, transfering images, syncing post updates, etc. In certain situations e.g. large amounts of images, poorly configured servers, and issues with other plugins, Distributor requests can fail. Although we do some error handling, there are certain cases in which Distributor can fail silently. If requests are taking a long time to load and failing, take a look at upping the timeout for that request. You can filter the timeout for pushing external posts [here](https://github.com/10up/distributor/blob/master/includes/classes/ExternalConnections/WordPressExternalConnection.php#L487).

## Changelog

* 1.2.0 - Gutenberg support, public release.
* 1.1.0 - Enable WordPress.com Oauth2 authentication.
* 1.0 - Initial closed release.

## Developers

### Testing

The plugin contains a standard test suite compatible with PHPUnit. If you want to test across multiple PHP versions, a [Dockunit](https://github.com/dockunit/dockunit) file is included.

### Debugging

You can define a constant `DISTRIBUTOR_DEBUG` to `true` to increase the ease of debugging in Distributor. This will make all remote requests blocking and expose the subscription post type.

### Work with us

<p align="center">
<a href="http://10up.com/contact/"><img src="https://10updotcom-wpengine.s3.amazonaws.com/uploads/2016/10/10up-Github-Banner.png" width="850"></a>
</p>
