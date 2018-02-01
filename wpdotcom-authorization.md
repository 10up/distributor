## Authenticating with the WordPress.com REST API.

Distributor enables connecting to websites hosted on the WordPress.com platform including WordPress VIP (Classic) via [Oauth2](https://developer.wordpress.com/docs/oauth2/).

### Creating a WordPress.com application
1. Visit the [WordPress.com application manager](https://developer.wordpress.com/apps/) and click the 'Create New Application' button.
2. Name your application. In the "Redirect URLs" field enter your wp-admin post.php urg, eg. `https://mydomain.com/wp-admin/post.php`.
3. Once created, note the application's Client ID and Client Secret.

### Oath2 Authentication Flow
1. Add a new external connection. Give the connection a name and select "WordPress.com REST API" from the External Connection Type dropdown. Click the "Authorize Connection" button.
2. Enter the Client ID and Client Secrets from your application. Click the 'Authorize Connection with WordPress.com' button.
3. On the wordpress.com oauth2 page, click 'Approve' to authorize the connection.
4. Enter the External connection URL to connect to. Click "Update Connection" to save.
