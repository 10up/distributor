=== Distributor ===
Contributors:      10up
Tags:              content, distribution, syndication, management
Requires at least: 5.7
Tested up to:      6.4
Requires PHP:      7.4
Stable tag:        2.0.4
License:           GPLv2 or later
License URI:       http://www.gnu.org/licenses/gpl-2.0.html

Distributor makes it easy to distribute and reuse content across your websites — whether in a single multisite or across the web.

== Description ==

*You can learn more about Distributor's features at [DistributorPlugin.com](https://distributorplugin.com) and documentation at the [Distributor documentation site](https://10up.github.io/distributor/).*

_Note:_ The latest stable version of the plugin is the _stable_ branch. [Download the stable branch]((https://github.com/10up/distributor/archive/stable.zip)) if you are intending to use the plugin in a Production environment.

**Features**

* Distributor supports safe, SEO-friendly content reuse and sharing via "pushing" and "pulling".
* While logged in and editing or viewing any single post (or custom post type) that can be distributed, a `Distributor` admin bar item will appear, that will facilitate sharing ("pushing") that content to any `connection`.
* In the admin dashboard, a top level Distributor menu item links to the "pull" screen. Here, editors can share ("pull") content from any `connection` into the current site.
* Content this is distributed (via Push or Pull) is connected to the original. Reposted content receives updates from the original, canonical source automatically.

**Connections**

There are two connection types: `internal` and `external`.

* Internal connections are other sites inside of the same multisite network. Any user logged into the network can distribute any content in the network to any other sites in the network where that user has permission to publish posts (assuming the site supports the same post type).
* External connections are external websites, connected by the JSON REST API. External connections can be added in the WordPress admin dashboard under `Distributor` > `External Connections`. Administrators can decide which user roles are allowed to distribute content to and from that connection (Editors and Administrators by default). All users with those roles will inherit the permissions of the user account used to establish the remote connection.
* External connections require HTTP Basic Authentication or [WordPress.com OAuth2](https://developer.wordpress.com/docs/oauth2/) (must be on VIP) be set up on the remote website. For Basic Auth, we recommend the [Application Passwords](https://make.wordpress.org/core/2020/11/05/application-passwords-integration-guide/#Getting-Credentials) feature built in to WordPress.
* For external connections, Distributor needs to be installed on BOTH sides of the connection.

== Upgrade Notice ==

= 2.0.0 =
**Distributor now requires PHP 7.4 or later and WordPress 5.7 or later.**
