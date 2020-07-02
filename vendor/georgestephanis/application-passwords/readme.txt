=== Application Passwords ===
Contributors: georgestephanis, valendesigns, kraftbj, kasparsd
Tags: application-passwords, rest api, xml-rpc, security, authentication
Requires at least: 4.4
Tested up to: 5.3
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Creates unique passwords for applications to authenticate users without revealing their main passwords.


== Description ==

Use Application Passwords to authenticate users without providing their passwords directly. Instead, a unique password is generated for each application without revealing the user's main password. Application passwords can be revoked for each application individually.

**Important:** Application Passwords can be used only for authenticating API requests such as [REST API](https://developer.wordpress.org/rest-api/) and [XML-RPC](https://codex.wordpress.org/XML-RPC_WordPress_API), and they won't work for regular site logins.


= Contribute =

- Translate the plugin [into your language](https://translate.wordpress.org/projects/wp-plugins/application-passwords/).
- Report issues, suggest features and contribute code [on GitHub](https://github.com/WordPress/application-passwords).


= Creating Application Password Manually =

1. Go the User Profile page of the user that you want to generate a new application password for.  To do so, click *Users* on the left side of the WordPress admin, then click on the user that you want to manage.
2. Scroll down until you see the Application Passwords section.  This is typically at the bottom of the page.
3. Within the input field, type in a name for your new application password, then click *Add New*.
   **Note:** The application password name is only used to describe your password for easy management later.  It will not affect your password in any way.  Be descriptive, as it will lead to easier management if you ever need to change it later.
4. Once the *Add New* button is clicked, your new application password will appear.  Be sure to keep this somewhere safe, as it will not be displayed to you again.  If you lose this password, it cannot be obtained again.


= Two Factor Support =

Application Passwords can be used together with the [Two Factor plugin](https://wordpress.org/plugins/two-factor/) as long as you disable the extra protection added by the Two Factor plugin which disables API requests with password authentication _for users with Two Factor enabled_.

Use the `two_factor_user_api_login_enable` filter to allow API requests with password-based authentication header for all users:

    add_filter( 'two_factor_user_api_login_enable', '__return_true' );

This is not required if the user associated with the application password doesn't have any of the Two Factor methods enabled.


= Requesting Password for Application =

To request a password for your application, redirect users to:

	https://example.com/wp-admin/admin.php?page=auth_app

and use the following `GET` request parameters to specify:

- `app_name` (required) - The human readable identifier for your app. This will be the name of the generated application password, so structure it like ... "WordPress Mobile App on iPhone 12" for uniqueness between multiple versions. If omitted, the user will be required to provide an application name.
- `success_url` (recommended) - The URL that you'd like the user to be sent to if they approve the connection. Two GET variables will be appended when they are passed back -- `user_login` and `password` -- these credentials can then be used for API calls. If the `success_url` variable is omitted, a password will be generated and displayed to the user, to manually enter into your application.
- `reject_url` (optional) - If included, the user will get sent there if they reject the connection. If omitted, the user will be sent to the `success_url`, with `?success=false` appended to the end. If the `success_url` is omitted, the user will be sent to their dashboard.


= Testing an Application Password =

We use [curl](https://curl.haxx.se) to send HTTP requests to the API endpoints in the examples below.

#### WordPress REST API

Make a REST API call to update a post. Because you are performing a `POST` request, you will need to authorize the request using your newly created base64 encoded access token. If authorized correctly, you will see the post title update to "New Title."

    curl --user "USERNAME:APPLICATION_PASSWORD" -X POST -d "title=New Title" https://LOCALHOST/wp-json/wp/v2/posts/POST_ID

When running this command, be sure to replace `USERNAME` and `APPLICATION_PASSWORD` with your credentials (curl takes care of base64 encoding and setting the `Authorization` header), `LOCALHOST` with the hostname of your WordPress installation, and `POST_ID` with the ID of the post that you want to edit.

#### XML-RPC

Unlike the WordPress REST API, XML-RPC does not require your username and password to be base64 encoded. Send an XML-RPC request to list all users:

    curl -H 'Content-Type: text/xml' -d '<methodCall><methodName>wp.getUsers</methodName><params><param><value>1</value></param><param><value>USERNAME</value></param><param><value>PASSWORD</value></param></params></methodCall>' https://LOCALHOST/xmlrpc.php

In the above example, replace `USERNAME` with your username, `PASSWORD` with your new application password, and `LOCALHOST` with the hostname of your WordPress installation. This should output a response containing all users on your site.


= Plugin History =

This is a feature plugin that is a spinoff of the main [Two-Factor Authentication plugin](https://github.com/WordPress/two-factor/).


== Changelog ==

See the [release notes on GitHub](https://github.com/WordPress/application-passwords/releases).


== Installation ==

Search for "Application Passwords" under "Plugins" → "Add New" in your WordPress dashboard to install the plugin.

Or install it manually:

1. Download the [plugin zip file](https://downloads.wordpress.org/plugin/application-passwords.zip).
2. Go to *Plugins* → *Add New* in your WordPress admin.
3. Click on the *Upload Plugin* button.
4. Select the file you downloaded.
5. Click *Install Plugin*.
6. Activate.

= Using Composer =

Add this plugin as a [Composer](https://getcomposer.org) dependency [from Packagist](https://packagist.org/packages/georgestephanis/application-passwords):

    composer require georgestephanis/application-passwords


== Screenshots ==

1. New application passwords has been created.
2. After at least one Application Password for you account exists, you'll see a table displaying them, allowing you to view usage and revoke them as desired.
