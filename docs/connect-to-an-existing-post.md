In a scenario where you have existing sites that share content and that content was in place prior to the addition of Distributor, it is useful to be able to connect that content together without having to delete one and push/pull the other one. Behind the scenes, Distributor stores a handful of details in post meta, so these details will need to be manually added for the links to work.

At it's simplest, you'll need to connect the two sites together and then add the proper meta (as detailed below) for the items that should be linked together, both on the source site and the receiving site.

## Network Connections

There are different pieces of data that will need to be set on each side of a connection, what we'll call as the source site and the receiving site.

The only piece of data needed on the source site to connect two items in Network Connections is the `dt_connection_map`. This is a serialized array of data that contains the mapping from the site ID to the post ID, along with a timestamp of when the item was linked. Note that the site ID and post ID should correspond to the data on the receiving site, not source site.

This data structure will look like the following:

```php
$data = [
  [
    'external' => [],
    'internal' => [
      2 => [
        'post_id' => 674,
        'time'    => 1693494494,
      ],
    ],
  ],
];
```

In the above example, the `external` array will always be empty, unless an item is also linked to an external site. For the `internal` array, each item in the array will have a key that corresponds to the site ID (2 in the example above) and then the `post_id` should be the destination post ID.

As a further example, if I have a post with an ID of 100 that lives on a site with an ID of 1 and I want that post to be linked to an existing post with an ID of 50 on the site with an ID of 2, the `dt_connection_map` data that is stored with the original item (ID of 100, site ID 1) would look like the following:

```php
$data = [
  [
    'external' => [],
    'internal' => [
      2 => [
        'post_id' => 50,
        'time'    => 1693494494,
      ],
    ],
  ],
];
update_post_meta( 100, 'dt_connection_map', $data );
```

And then on the receiving site, the following data is needed: `dt_original_post_id`, `dt_original_post_url`, `dt_original_blog_id` and `dt_syndicate_time`. These should all be fairly self explanatory but to use the same example from above:

- `dt_original_post_id` would be set to 100
- `dt_original_post_url` would be the full URL of that post with an ID of 100
- `dt_original_blog_id` would be set to 1
- `dt_syndicate_time` should match the same timestamp set in the connection map, in this case 1693494494

## External Connections

External Connections share a similar data structure as detailed above but they also contain a Subscriptions piece, which is more complicated to manually replicate (as such, full details on how to replicate the Subscription is not outlined here).

Similar to the above, there's a data mapping that is needed on the source site: `dt_connection_map`. This is a serialized array of data that contains the mapping from the external connection ID to the post ID, along with a timestamp of when the item was linked. Note that the post ID should correspond to the data on the receiving site, not source site.

This data structure will look like the following:

```php
$data = [
  [
    'external' => [
      2 => [
        'post_id' => 50,
        'time'    => 1693494494,
      ],
    ],
    'internal' => [],
  ],
];
```

In the above example, the `internal` array will always be empty, unless an item is also linked to an internal site. For the `external` array, each item in the array will have a key that corresponds to the connection ID (2 in the example above) and then the `post_id` should be the destination post ID.

As a further example, if I have a post with an ID of 100 that lives on a site with an ID of 1 and I want that post to be linked to an existing post with an ID of 50 on the site with a connection ID of 2, the `dt_connection_map` data that is stored with the original item (ID of 100, site ID 1) would look like the following:

```php
$data = [
  [
    'external' => [
      2 => [
        'post_id' => 50,
        'time'    => 1693494494,
      ],
    ],
    'internal' => [],
  ],
];
update_post_meta( 100, 'dt_connection_map', $data );
```

In addition, there needs to be a piece of data on the source site that contains the subscription information: `dt_subscriptions`. This contains a serialized array of data that links a hashed signature to the subscription post ID. This isn't something that is easily manually reproduced. Suggestion is to look at using the existing `Distributor\Subscriptions\create_subscription` function to replicate this data.

And then on the receiving site, the following data is needed: `dt_original_post_id`, `dt_original_post_url`, `dt_original_source_id`, `dt_original_site_name`, `dt_original_site_url`, `dt_subscription_signature`, `dt_full_connection` and `dt_syndicate_time`.

From the example above:

- `dt_original_post_id` would be set to 100
- `dt_original_post_url` would be the full URL of that post with an ID of 100
- `dt_original_source_id` would be set to 2 (the source connection ID)
- `dt_original_site_name` would be the name of the source site
- `dt_original_site_url` would be the URL of the source site
- `dt_subscription_signature` would be the signature of the subscription mentioned above
- `dt_full_connection` would be set to `true`
- `dt_syndicate_time` should match the same timestamp set in the connection map, in this case 1693494494
