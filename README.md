<img alt="Distributor icon" src="https://github.com/10up/distributor/blob/trunk/assets/img/icon.svg" height="45" width="45" align="left">

# Distributor

> Distributor is a WordPress plugin that makes it easy to distribute and reuse content across your websites — whether in a single multisite or across the web.

[![Support Level](https://img.shields.io/badge/support-active-green.svg)](#support-level) [![Build Status](https://travis-ci.org/10up/distributor.svg?branch=trunk)](https://travis-ci.org/10up/distributor)
[![Release Version](https://img.shields.io/github/release/10up/distributor.svg)](https://github.com/10up/distributor/releases/latest) ![WordPress tested up to version](https://img.shields.io/badge/WordPress-v5.7%20tested-success.svg) [![License](https://img.shields.io/github/license/10up/distributor.svg)](https://github.com/10up/distributor/blob/develop/LICENSE.md)

*You can learn more about Distributor's features at [DistributorPlugin.com](https://distributorplugin.com).*

_Note:_ The latest stable version of the plugin is the _stable_ branch. [Download the stable branch]((https://github.com/10up/distributor/archive/stable.zip)) if you are intending to use the plugin in production.

## Table of Contents
* [Features](#features)
* [Requirements](#requirements)
* [Installation](#installation)
  * [Registration](#registration)
  * [Setup External Connections](#setup-external-connections-using-application-passwords)
* [How to Distribute Content](#how-to-distribute-content)
  * [Pushing Content](#pushing-content)
  * [Pulling Content](#pulling-content)
* [Known Caveats/Issues](#known-caveatsissues)
* [Developers](#developers)
  * [Running Locally](#running-locally)
  * [Testing](#testing)
  * [Debugging](#debugging)
  * [Application Passwords](#application-passwords-and-wordpress-56)
* [Changelog](#changelog)
* [Contributing](#contributing)

## Features

Distributor supports safe, SEO-friendly content reuse and sharing via "pushing" and "pulling".

While logged in and editing or viewing any single post (or custom post type) that can be distributed, a `Distributor` admin bar item will appear, that will facilitate sharing ("pushing") that content to any `connection`.

<a href="http://distributorplugin.com/"><img src="https://distributorplugin.com/wp-content/themes/distributor-theme/assets/img/push-screenshot.jpg" alt="Push the content you’re editing or viewing to any of your other sites from the admin bar" width="600"></a>

In the admin dashboard, a top level Distributor menu item links to the "pull" screen. Here, editors can share ("pull") content from any `connection` into the current site.

<a href="http://distributorplugin.com/"><img src="https://distributorplugin.com/wp-content/themes/distributor-theme/assets/img/pull-screenshot.jpg" alt="Pull content from another site from the Distributor admin menu" width="600"></a>

Content this is distributed (via Push or Pull) is connected to the original.  Reposted content receives updates from the original, canonical source automatically.

<a href="http://distributorplugin.com/"><img alt="Distributor intuitively presents the origin and status of any reused content" class="browser-frame" src="https://distributorplugin.com/wp-content/themes/distributor-theme/assets/img/syndicated-screenshot.jpg" width="600"></a>

There are two connection types: `internal` and `external`.
* Internal connections are other sites inside of the same multisite network. Any user logged into the network can distribute any content in the network to any other sites in the network where that user has permission to publish posts (assuming the site supports the same post type).
* External connections are external websites, connected by the JSON REST API using [Application Passwords](https://wordpress.org/plugins/application-passwords/). External connections can be added in the WordPress admin dashboard under `Distributor` > `External Connections`. Administrators can decide which user roles are allowed to distribute content to and from that connection (Editors and Administrators by default). All users with those roles will inherit the permissions of the user account used to establish the remote connection.

### Extendability

Distributor is built with the same extensible approach as WordPress itself, with [fully documented hooks and filters](https://10up.github.io/distributor/) to customize its default behavior and create custom distribution workflows.  You can even create connections to other platforms.

## Requirements

* PHP 5.6+
* [WordPress](http://wordpress.org) 4.7+
* External connections require HTTP Basic Authentication or [WordPress.com OAuth2](https://developer.wordpress.com/docs/oauth2/) (must be on VIP) be set up on the remote website. For Basic Auth, we recommend the [Application Passwords](https://wordpress.org/plugins/application-passwords/) plugin (note that other plugins like JWT Auth will likely cause issues for external connections).
* For external connections, Distributor needs to be installed on BOTH sides of the connection.

## Installation

For Production use, we recommend [registering and downloading the plugin from DistributorPlugin.com](https://distributorplugin.com/#cta) – it's 100% free. You will be emailed a direct link to download the latest, production-ready build. Alternatively, you can [download the latest release from GitHub](https://github.com/10up/distributor/archive/trunk.zip).

You can upload and install the archived (zip) plugin via the WordPress dashboard (`Plugins` > `Add New` -> `Upload Plugin`) or manually inside of the `wp-content/plugins` directory, and activate on the Plugins dashboard.

### Registration

To help inform our roadmap, keep adopters apprised of major updates and changes that could impact their websites, and solicit opportunities for beta testing and feedback, we’re asking for a little bit of information in exchange for a free key that unlocks update notifications and 1-click upgrades inside the WordPress dashboard. Your information is kept confidential. You can [register here](https://distributorplugin.com/#cta) and input your key in Distributor settings in the dashboard (network dashboard for multisite users).  Note that you need to input the email address you used to register Distributor (included in the email with your registration key) as that is linked to the registration key.

### Setup External Connections using Application Passwords

1. Ensure that the current version of Distributor is active on BOTH sites being connected.  We'll refer to these as mainsite.com and remotesite.com.
1. On mainsite.com, navigate to `Distributor` > `External Connections` and click `Add New`.
1. Enter a label for the connection (e.g., `remotesite.com`), select `Username / Password` for the `Authentication Method`, and a username from remotesite.com.
1. On remotesite.com, ensure that [Application Passwords](https://wordpress.org/plugins/application-passwords/) is installed. (_Note: Using this plugin instead of a normal WordPress users password helps limit the use of your primary password and will allow you to revoke access to Distributor in the future if needed._) Then navigate to the user profile that will be used to create the External Connection on mainsite.com and then to the `Application Passwords` section of the user profile (not the `Account Management` section).  Add a label for the New Application Password Name (e.g., `mainsite.com`) and click `Add New`.  Now copy the password provided into mainsite.com's External Connections `Password` field.
1. On mainsite.com, add the `External Connection URL` (e.g., http://remotesite.com/wp-json).  You should see a green circle and "_Connection established._".
1. Ensure the roles selected in `Roles Allowed to Push` are the ones you want to support, then press the `Create Connection` button.  You should now be able to push from mainsite.com to remotesite.com.  If you want to pull from remotesite.com to mainsite.com, simply repeat these instructions swapping mainsite.com and remotesite.com.

## How to Distribute Content

There are two methods for distributing content between multiple WordPress sites, Push and Pull.  Pushing allows you to share content from your site to one or more connected sites while Pulling allows you to bring content into your site from one of your connected sites.  In either method, once content has been distributed it will stay in sync with any changes made to the origin post (when Pushing the origin is the site being Pushed from, when Pulling the origin is the site being Pulled from).

### Pushing Content

The `Distributor` menu in the WP Admin Bar will appear after a piece of content has been published.  Hovering over that menu item will expose the Push menu that displays the list of connected sites on the left, the list of sites that have been selected for push distribution on the right, and a button to `Distribute` the content to those selected sites.

<img src="/.github/screenshots/screenshot-1.png" alt="Push menu exposed when viewing published content on the front-end" width="300">

The same Push menu and set of Distributor options are also available after publishing a piece of content within the WordPress Block Editor.

<img src="/.github/screenshots/screenshot-2.png" alt="Push menu exposed when viewing published content in the Block Editor" width="300">

After you click the `Distribute` button, Distributor will push the content to the selected connected sites, showing a `View` link when those pieces of content have been distributed, and noting `Post successfully distributed.` once the content has completed distributing to selected sites.

<img src="/.github/screenshots/screenshot-3.png" alt="Push menu showing details after content distribution" width="300">

When viewing that piece of content in the Block Editor, there will be a Distributor notice in the `Status & visibility` section noting how many sites the content has been distributed to.

<img src="/.github/screenshots/screenshot-4.png" alt="Block Editor sidebar showing Distributor count of sites that content has been distributed to (via Push and Pull)" width="300">

The same Push menu is available in the WP Admin Bar if you are using the Classic Editor.  The Distributor notice is also available in the `Publish` metabox noting how many sites the content has been distributed to.

<img src="/.github/screenshots/screenshot-5.png" alt="Classic Editor showing the Push menu and metabox showing Distributor count of sites that content has been distributed to (via Push and Pull)" width="300">

### Pulling Content

Navigating to the `Distributor` > `Pull Content` screen in the WP Admin will present you with a dropdown to select any of the sites you are connected to and will display all available pieces of content that can be Pulled into the current site.  You can select Posts, Pages, and other Custom Post Types to filter on this screen; you can use the Bulk Edit menu to Pull or Skip more than one piece of content at a time; and you can use individual row actions on each piece of content pull, view, or skip each piece of content.

<img src="/.github/screenshots/screenshot-6.png" alt="Pull Content screen showing row actions and a single post selected for Pulling" width="300">

After you have Bulk Pulled several pieces of content or used the row actions to Pull a single piece of content, the Pull Content screen will show a confirmation message that the Pull action was successful and redirect you to the `Pulled` list view to see all the items that have been pulled into the current site.  The same process will happen if you opt to Skip specific piece(s) of content.

<img src="/.github/screenshots/screenshot-7.png" alt="Pull Content screen showing confirmation on content being pulled" width="300">

You can navigate to the `Posts` > `All Posts` table list view to see all content that has been pushed or pulled to the current site via the Distributor column denoted with the Distributor icon (<img alt="Distributor icon" src="https://github.com/10up/distributor/blob/trunk/assets/img/icon.svg" height="45" width="45">).  Rows that include the Distributor icon will link off that icon to the origin site and post where that content was either pushed or pulled from.

<img src="/.github/screenshots/screenshot-8.png" alt="All Posts screen showing Distributor links for pushed and pulled content" width="300">

## Support Level

**Active:** 10up is actively working on this, and we expect to continue work for the foreseeable future including keeping tested up to the most recent version of WordPress.  Bug reports, feature requests, questions, and pull requests are welcome.

## Known Caveats/Issues

__Gutenberg Fullscreen Mode__ - [Gutenberg 3.8](https://wptavern.com/gutenberg-3-8-released-adds-full-screen-mode) originally introduced `Fullscreen mode` for the editor and [WordPress 5.4](https://make.wordpress.org/core/2020/03/03/fullscreen-mode-enabled-by-default-in-the-editor/) and [Gutenberg 7.7](https://github.com/WordPress/gutenberg/pull/20611) made that the default setting.  Fullscreen mode creates a problem as the admin bar is no longer visible which means the Distributor push menu is no longer visible.  We are [working on researching a resolution to this issue](https://github.com/10up/distributor/issues/597), but in the meantime we recommend clicking on the three vertical dots in the upper right corner of Gutenberg and disabling fullscreen mode to ensure the admin bar and Distributor push menu is in view.

__Remote Request Timeouts__ - With external connections, HTTP requests are sent back and forth - creating posts, transfering images, syncing post updates, etc. In certain situations, mostly commonly when distributing posts with a large number of images (or very large file sizes), using poorly configured or saturated servers / hosts, or using plugins that add significant weight to post creation, Distributor requests can fail. Although we do some error handling, there are certain cases in which post distribution can fail silently. If distribution requests are taking a long time to load and/or failing, consider adjusting the timeout; you can filter the timeout for pushing external posts using the [`dt_push_post_timeout` filter](https://10up.github.io/distributor/dt_push_post_timeout.html). More advanced handling of large content requests, and improved error handling is on the road map for a future update.

__Post Meta Associations__ - A distributed post includes all of the post meta from the original version. Sometimes arbitrary post meta references an ID for another piece of content on the original site. Distributor _does not_ "bring along" the referenced content or update references for arbitrary post meta (it will take care of updating references in the case of core WordPress features, such as the featured image ID). This issue is very common when using field management plugins like Advanced Custom Fields (ACF). This can be addressed on a case by case basis by extending the plugin; for external connections, you can manually handle post meta associations using [the `dt_push_post` hook](https://github.com/10up/distributor/blob/f7b60740e679bce4671ccd69a670abadce4f2f93/includes/classes/ExternalConnections/WordPressExternalConnection.php#L646). For internal connections, use the [`dt_push_post` hook](https://10up.github.io/distributor/dt_push_post.html). Note that while named the same, these hooks accept different parameters.

__Deleting Distributed Posts__ - When a post that has been distributed is deleted, the distributed copies will become unlinked ("forked") from the original and thus become editable. Similarly, when a distributed post is unpublished, distributed copies will not be unpublished. More sophisticated "removal" workflow is on the road map for a future update.

__Gutenberg Block Mismatch__ - When distributing a Gutenberg post to another site that supports Gutenberg, if a block in the post does not exist on the receiving site, the block will be converted to a "Classic" HTML block.

__Parent Posts__ - Distributor does not "bring along" parent (or child posts). If your post (or custom post type) has a parent or a child, it will distribute it as if it's an orphan.

__Custom Post Type Support__ - Internal Connections (multisite) support multiple post types. In order for distribution to work with External Connections that have custom post type content, that post type needs to be registered with the argument `show_in_rest => true` on the external site.

__Unable to Push to New Custom Post Types__ - If new Custom Post Types are created after establishing an External Connection, you will only be able to `Pull` those from an External Connection. To ensure you are able to `Push` new Custom Post Types to an External Connection, you will need to update the External Connection by editing it and then clicking the `Update connection` button.

__Backwards Compatibility__ - While we strive to be mindful of backwards compatibility much the same way WordPress itself is, we do not currently guarantee continued interoperability between different versions of Distributor. We assume the current userbase for this plugin has a high degree of control over any site that has been set up as an external connection and urge you to keep Distributor up to date.

__Distributing Post content__ - By default, post content is rendered before being copied.  This means that shortcodes are expanded before being distributed and remote posts will not have the shortcode, but rather the expanded HTML content.

__Distributing Authors__ - By default, distributed stories reference the original site as the "author" with a link to it.  This can be altered by extending Distributor with custom code to make it sync authors.

__Distributing Post Date__ - By default, the "post date" on distributed stories is the date its published on the remote site, not the date published on the origin site.  This can be overridden by extending Distributor with custom code to make it preserve the post date.

__Distributing Canonical URL__ - By default, canonical URL of distributed post will point to original content, which corresponds to SEO best practices. This can be overridden by extending Distributor with custom code and removing Distributor's default front end canonical URL filtering (look for `'get_canonical_url'` and `'wpseo_canonical'`).

__Drafts as Preferred Status__ - By default, drafts are the preferred status and can't be changed at the source site.

## Developers

### Running Locally

If you are compiling Distributor locally, note that there is a minimum requirement of Node.js 8.10.  If you're using an older version of Node, then it will not compile correctly.

### Testing

The plugin contains a standard test suite compatible with PHPUnit. If you want to test across multiple PHP versions, a [Dockunit](https://github.com/dockunit/dockunit) file is included.

### Debugging

You can define a constant `DISTRIBUTOR_DEBUG` to `true` to increase the ease of debugging in Distributor. This will make all remote requests blocking and expose the subscription post type.

Enabling this will also provide more debugging information in your error log for image side loading issues. The specific logging method may change in the future.

### Application Passwords and WordPress 5.6

In WordPress 5.6, Application Passwords was merged into WordPress core with some limitations. From 5.6, Application Passwords is enabled by default only for live sites with HTTPS. To enable Application Passwords for development sites, you will need the following snippet:

```php
add_filter( 'wp_is_application_passwords_available', '__return_true' );

add_action( 'wp_authorize_application_password_request_errors', function( $error ) {
    $error->remove( 'invalid_redirect_scheme' );
} );
```

## Changelog

A complete listing of all notable changes to Distributor are documented in [CHANGELOG.md](https://github.com/10up/distributor/blob/develop/CHANGELOG.md).

## Contributing

Please read [CODE_OF_CONDUCT.md](https://github.com/10up/distributor/blob/develop/CODE_OF_CONDUCT.md) for details on our code of conduct and [CONTRIBUTING.md](https://github.com/10up/distributor/blob/develop/CONTRIBUTING.md) for details on the process for submitting pull requests to us.

## Like what you see?

<a href="http://10up.com/contact/"><img src="https://10up.com/uploads/2016/10/10up-Github-Banner.png" width="850"></a>
