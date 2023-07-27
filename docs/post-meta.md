---

### Table of Contents
- [Source post meta](#source-post-meta)
	- [Network connections](#network-connections)
- [Distributed post meta](#distributed-post-meta)
	- [Connects a Source Post to a Subscription](#connects-a-source-post-to-a-subscription)
	- [Network connections](#network-connections-1)
	- [External connections](#external-connections)
- [Distributed post meta](#distributed-post-meta-1)
- [Connections post type post meta](#connections-post-type-post-meta)
	- [External connections](#external-connections-1)

---

### Source post meta

- `dt_unlinked` Whether this post is linked to the original version. For the original post this is set to true.

#### Network connections

- `dt_connection_map`

---

### Distributed post meta

- `dt_original_post_id`
- `dt_original_media_url`
- `dt_original_media_id`
- `dt_original_source_id`
- `dt_original_post_deleted`
- `dt_original_post_parent`
- `dt_original_site_name`
- `dt_original_site_url`
- `dt_original_post_url`
- `dt_original_deleted` Whether the original post has been deleted.
- `dt_unlinked` Whether this post is linked to the original version.
- `dt_original_file_path` Only saved if filter `dt_process_media_save_source_file_path` is used.
- `dt_syndicate_time`

#### Connects a Source Post to a Subscription
- `dt_subscriptions`
- `dt_subscription_update`

#### Network connections

- `dt_original_blog_id`

#### External connections

- `dt_full_connection`

---

### Distributed post meta

For non-public `dt_subscription` post type.

- `dt_subscription_remote_post_id`
- `dt_subscription_signature`
- `dt_subscription_remote_post_id`
- `dt_subscription_target_url`
- `dt_subscription_post_id`

---

### Connections post type post meta

#### External connections

- `dt_sync_log`
- `dt_external_connection_type`
- `dt_external_connection_allowed_roles`
- `dt_external_connection_check_time`
- `dt_external_connection_url`
- `dt_external_connection_auth`
- `dt_external_connections` Stores what we can do with a given external connection (push or pull).
