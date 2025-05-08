Distributor often deals with data stored as ID references. This is common in WordPress, where IDs are used in block attributes, shortcodes, or post meta by popular plugins like ACF, Elementor, and Gravity Forms. However, when content is distributed to another site, these stored IDs may not match, leading to data loss or unexpected content.

To address this, Distributor provides a helper function: `distributor_register_data`. This function allows developers to register data references, specify where the data is located, define how to extract the ID, and register callbacks to process the data on both the source and target sites.

## Overview

The `distributor_register_data` function streamlines the process of updating stored ID references by:

- **Identifying the data location:** Whether in post meta or within post content (shortcodes or blocks).
- **Extracting the reference:** Defining how to locate the specific attribute or meta key holding the ID.
- **Processing the data:** Using pre-distribution and post-distribution callbacks to prepare and update the reference IDs, ensuring they point to the correct data on the target site.

#### **Important:** Ensure that this data registration code is added to both the source and target sites. Also, verify that your Distributor plugin version is the same on both sites and equal to or greater than the required version (x.x.x).

## Function Definition

### `distributor_register_data`

This function registers a data piece by providing details about its location and the callbacks needed to process it during distribution.

#### Parameters

- **`$data_name`** (`string`):
  The unique identifier for the data.

- **`$args`** (`array`):
  An array of parameters that describe:

  - **`location`** (`string`):
    Where the data is stored. Accepted values are `'post_meta'` or `'post_content'`.

  - **`attributes`** (`array`):
    Additional details depending on the location:
    - **For post meta:**
      - `meta_key` (`string`): The post meta key. **(Required)**
    - **For shortcodes:**
      - `shortcode` (`string`): The shortcode tag. **(Required)**
      - `shortcode_attribute` (`string|array`): The attribute(s) containing the data. **(Required)**
    - **For blocks:**
      - `block_name` (`string`): The block name. **(Required)**
      - `block_attribute` (`string|array`): The attribute(s) containing the data. **(Required)**

  - **`type`** (`string`):
    Type of data. Accepted values are `'media'`, `'post'`, or `'term'`. If set, default callbacks can be used.
    **Note:** This parameter cannot be used in conjunction with custom callbacks. If custom callbacks are provided, they will take precedence over the default behavior.

  - **`pre_distribute_cb`** (`callable`):
    A callback function that prepares the data on the source site before distribution. It should return extra data that is required for processing on the target site.

  - **`post_distribute_cb`** (`callable`):
    A callback function that processes the extra data on the target site and returns the updated ID to replace the source reference.

---

## Examples

Below are several examples demonstrating how to use the `distributor_register_data` function in different scenarios.

### 1. Handling an Image ID Stored in Post Meta

In this example, we register an image ID stored in post meta under the key `example_image_id`.

#### With Custom Callback Functions

```php
distributor_register_data( 'example_post_meta_data', array(
	'location'   => 'post_meta',
	'attributes' => array(
		'meta_key' => 'example_image_id',
	),
	// Callback function to prepare the data before sending it from the source site.
	// $image_id is the ID stored in post meta (example_image_id).
	// $source_post_id is the ID of the post being distributed.
	'pre_distribute_cb' => function ( $image_id, $source_post_id ) {
		if ( ! $image_id ) {
			return array();
		}

		$image_url = wp_get_attachment_image_url( $image_id, 'full' );

		// Return extra data required for processing on the target site.
		return array(
			'image_url' => $image_url,
		);
	},
	// Callback function to process the data before saving it on the target site.
	// $extra_data is the data returned from pre_distribute_cb.
	// $source_data is the original data stored in post meta.
	// $post_data is the distributed post's data.
	'post_distribute_cb' => function ( $extra_data, $source_data, $post_data ) {
		if ( ! isset( $extra_data['image_url'] ) ) {
			return;
		}

		$source_image_url = $extra_data['image_url'];
		$source_image_id  = $source_data;

		// Check if the media already exists on the target site. If yes, return its ID.
		$image_id = Utils\get_attachment_id_by_original_data( $source_image_id, $source_image_url );

		// If the media is not found, process and save it.
		if ( ! $image_id ) {
			$image_id = Utils\process_media( $source_image_url, 0, [] );
			update_post_meta( $image_id, 'dt_original_media_id', wp_slash( $source_image_id ) );
			update_post_meta( $image_id, 'dt_original_media_url', wp_slash( $source_image_url ) );
		}

		// Return the media ID to replace the source reference.
		return $image_id;
	},
) );
```

**Explanation**

1. **Pre-Distribution Callback (`pre_distribute_cb`):**
   - Retrieves the URL of the image using the stored ID.
   - Returns an array with the image URL to be used on the target site.

2. **Post-Distribution Callback (`post_distribute_cb`):**
   - Checks whether the media already exists on the target site by comparing the source image data.
   - If not found, processes (uploads) the media and updates the relevant post meta with the original source data.
   - Returns the new image ID for the distributed post.

#### Using the `type` Parameter to Utilize the Default Callback

```php
distributor_register_data( 'example_post_meta_data', array(
	'location'   => 'post_meta',
	'attributes' => array(
		'meta_key' => 'example_image_id',
	),
	'type'       => 'media'
) );
```

### 2. Handling Term ID in a Block Attribute

This example demonstrates how to handle a term ID stored in a block attribute.

#### With Custom Callback Functions

```php
distributor_register_data(
	'example_block_data',
	array(
		'location'           => 'post_content',
		'attributes'         => array(
			'block_name'      => 'example/block-name',
			'block_attribute' => 'id',
		),
		'pre_distribute_cb'  => function( $source_term_id ) {
			if ( ! $source_term_id ) {
				return array();
			}

			$term = get_term_by( 'id', $source_term_id, 'category' );
			if ( ! $term ) {
				return array();
			}

			// Return extra data required for processing on the target site.
			return array(
				'name'        => $term->name,
				'slug'        => $term->slug,
				'description' => $term->description,
				'taxonomy'    => $term->taxonomy,
			);
		},
		'post_distribute_cb' => function( $extra_data, $source_data, $post_data ) {
			if ( ! isset( $extra_data['name'] ) && ! isset( $extra_data['taxonomy'] ) ) {
				return;
			}

			$existing_term = get_term_by( 'name', $extra_data['name'], $extra_data['taxonomy'] );
			if ( $existing_term ) {
				return $existing_term->term_id;
			}

			// Create the term on the target site.
			$term = wp_insert_term(
				$extra_data['name'],
				$extra_data['taxonomy'],
				array(
					'description' => $extra_data['description'],
					'slug'        => $extra_data['slug'],
				)
			);

			if ( is_wp_error( $term ) ) {
				return;
			}

			return $term['term_id'];
		},
	)
);
```

#### Using the `type` Parameter to Utilize the Default Callback

```php
distributor_register_data(
	'example_block_data',
	array(
		'location'   => 'post_content',
		'attributes' => array(
			'block_name'      => 'example/block-name',
			'block_attribute' => 'id',
		),
		'type'       => 'term'
	)
);
```


### 3. Handling Post ID in a Shortcode Attribute

This example shows how to manage a post ID stored within a shortcode attribute.

#### With Custom Callback Functions
```php
// Distributor data registration for the post ID stored in shortcode attribute.
distributor_register_data( 'example_post_data', array(
	'location'   => 'post_content',
	'attributes' => array(
		'shortcode'           => 'extra_shortcode',
		'shortcode_attribute' => 'example_post_id',
	),
	'pre_distribute_cb' => function( $post_id ) {
		if ( ! $post_id ) {
			return array();
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return array();
		}

		return array(
			'post_type'    => $post->post_type,
			'post_title'   => $post->post_title,
			'post_status'  => $post->post_status,
			'post_content' => $post->post_content,
		);
	},
	'post_distribute_cb' => function( $extra_data, $source_data, $post_data ) {
		if ( ! isset( $extra_data['post_type'] ) && ! isset( $extra_data['post_title'] ) ) {
			return $source_data;
		}

		$posts = get_posts(
			array(
				'post_type'              => $extra_data['post_type'],
				'meta_query'             => array(
					array(
						'key'     => 'dt_original_post_id',
						'value'   => $source_data,
						'compare' => '=',
					),
				),
				'post_status'            => 'any',
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'numberposts'            => 1,
				'orderby'                => 'post_date ID',
				'order'                  => 'ASC',
			)
		);

		if ( ! empty( $posts ) ) {
			return $posts[0];
		}

		// Create the post.
		$post_id = wp_insert_post( $extra_data );

		if ( is_wp_error( $post_id ) ) {
			return;
		}

		// Store the original post ID in post meta to check for existing posts on subsequent distributions.
		update_post_meta( $post_id, 'dt_original_post_id', wp_slash( $source_data ) );

		return $post_id;
	},
) );
```

#### Using the `type` Parameter to Utilize the Default Callback
```php
// Distributor data registration for the post ID stored in shortcode attribute.
distributor_register_data( 'example_post_data', array(
	'location'   => 'post_content',
	'attributes' => array(
		'shortcode'           => 'extra_shortcode',
		'shortcode_attribute' => 'example_post_id',
	),
	'type'       => 'post'
) );
```

If you have any doubts, encounter any different scenarios to handle, or need further assistance, please feel free to report an issue on our [GitHub repository](https://github.com/10up/distributor) and we would be happy to help.
