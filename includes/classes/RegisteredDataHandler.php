<?php
/**
 * Class for process registered data.
 *
 * @package  distributor
 */

namespace Distributor;

/**
 * This class is responsible for processing the registered data for the post content and post meta.
 *
 * @since 2.2.0
 */
class RegisteredDataHandler {
	/**
	 * The Connection data array.
	 *
	 * @since 2.2.0
	 * @var array
	 */
	public $connection_data = array();

	/**
	 * Constructor for the RegisteredDataHandler class.
	 *
	 * @since 2.2.0
	 *
	 * @param array $connection_data The connection data array.
	 */
	public function __construct( $connection_data = array() ) {
		$this->connection_data = $connection_data;
	}

	/**
	 * Search and replace inner content of a block with the provided replacements.
	 *
	 * @since 2.2.0
	 *
	 * @param array $block               The block to search and replace inner content.
	 * @param array $replacement_strings Array of search and replace strings for inner content.
	 * @return array The block with inner content replaced.
	 */
	public function search_replace_block_inner_content( $block, $replacement_strings ) {
		if ( empty( $replacement_strings ) ) {
			return $block;
		}

		foreach ( $replacement_strings as $replacement_string ) {
			$block['innerHTML']       = str_replace( $replacement_string['search'], $replacement_string['replace'], $block['innerHTML'] );
			$block['innerContent'][0] = str_replace( $replacement_string['search'], $replacement_string['replace'], $block['innerContent'][0] );
		}
		return $block;
	}

	/**
	 * Recursively process blocks for the registered data.
	 *
	 * Processes the blocks data recursively and calls the callback function provided in the registered data.
	 *
	 * @since 2.2.0
	 *
	 * @param array $blocks          Array of blocks.
	 * @param array $registered_data Array of registered data.
	 * @param array $extra_data      Array of extra data provided by source for the registered data.
	 * @param array $post_data       Array of post data.
	 * @param int   $index           Index of the extra data.
	 * @return array Array with 'blocks' (processed blocks) and 'modified' (bool).
	 */
	public function process_blocks_data_recursive( $blocks, $registered_data, $extra_data, $post_data, $index = 0 ) {
		$callback_fn     = $registered_data['post_distribute_cb'] ?? null;
		$attributes      = $registered_data['attributes'] ?? array();
		$block_name      = $attributes['block_name'] ?? '';
		$block_attribute = $attributes['block_attribute'] ?? '';
		$modified        = false;

		// Skip if the callback function is not provided or not callable.
		if ( empty( $callback_fn ) || ! is_callable( $callback_fn ) ) {
			return array(
				'blocks'   => $blocks,
				'modified' => $modified,
			);
		}

		foreach ( $blocks as &$block ) {
			if ( isset( $block['blockName'] ) && $block_name === $block['blockName'] ) {
				if ( is_array( $block_attribute ) ) {
					$source_data = array();
					foreach ( $block_attribute as $attribute ) {
						if ( isset( $block['attrs'][ $attribute ] ) ) {
							$source_data[ $attribute ] = $block['attrs'][ $attribute ];
						}
					}
					$current_extra_data = $extra_data[ $index ] ?? array();
					$replacement        = call_user_func_array( $callback_fn, array( $current_extra_data, $source_data, $post_data, $this->connection_data ) );
					if ( ! empty( $replacement ) ) {
						foreach ( $block_attribute as $attribute ) {
							if ( isset( $replacement[ $attribute ] ) ) {
								$block['attrs'][ $attribute ] = $replacement[ $attribute ];
							}
						}

						// Do replacement for innerHTML if it's set.
						if ( ! empty( $replacement['inner_content_replacements'] ) ) {
							$block = $this->search_replace_block_inner_content( $block, $replacement['inner_content_replacements'] );
						}

						$modified = true;
					}
					$index++;
				} elseif ( isset( $block['attrs'][ $block_attribute ] ) ) {
					$source_data        = $block['attrs'][ $block_attribute ];
					$current_extra_data = $extra_data[ $index ] ?? array();
					$replacement        = call_user_func_array( $callback_fn, array( $current_extra_data, $source_data, $post_data, $this->connection_data ) );
					if ( ! empty( $replacement ) ) {
						$block['attrs'][ $block_attribute ] = $replacement;

						// Handle inner content replacements for media blocks.
						$type = $registered_data['type'] ?? '';
						if ( 'media' === $type && ! empty( $replacement ) ) {
							$from_url = $current_extra_data['url'] ?? '';
							$to_url   = wp_get_attachment_url( $replacement );

							if ( ! empty( $from_url ) && ! empty( $to_url ) ) {
								$replacements = array(
									array(
										'search'  => $from_url,
										'replace' => $to_url,
									),
									array(
										'search'  => 'wp-image-' . $source_data,
										'replace' => 'wp-image-' . $replacement,
									),
								);
								// Try replacing the guid as well, due to the media url could be different based on from where it's being pulled.
								if ( ! empty( $current_extra_data['guid'] ) ) {
									$replacements[] = array(
										'search'  => $current_extra_data['guid'],
										'replace' => $to_url,
									);
								}
								$block = $this->search_replace_block_inner_content( $block, $replacements );
							}
						}

						$modified = true;
					}
					$index++;
				}
			}

			if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
				$inner_result = $this->process_blocks_data_recursive( $block['innerBlocks'], $registered_data, $extra_data, $post_data, $index );
				if ( $inner_result['modified'] ) {
					$block['innerBlocks'] = $inner_result['blocks'];
					$modified             = true;
				}
			}
		}

		return array(
			'blocks'   => $blocks,
			'modified' => $modified,
		);
	}

	/**
	 * Processes the registered data for the post content and post meta.
	 *
	 * Calls the callback function provided in the registered data and updates the post data.
	 *
	 * @since 2.2.0
	 *
	 * @param array $post_data The post data.
	 * @param bool  $is_rest   Whether the post data is from the REST API.
	 * @return array $post_data The processed post data.
	 */
	public function process_registered_data( $post_data, $is_rest = false ) {
		$unprocessed_post_data = $post_data;
		// Filter is documented in includes/classes/DistributorPost.php
		if ( ! apply_filters( 'dt_process_extra_data', true, $post_data ) ) {
			return $post_data;
		}

		// Get the registered data.
		$registered_data = distributor_get_registered_data();

		// Skip if no registered data is found.
		if ( empty( $registered_data ) ) {
			return $post_data;
		}

		foreach ( $registered_data as $data_key => $data ) {
			$location    = $data['location'];
			$attributes  = $data['attributes'];
			$callback_fn = $data['post_distribute_cb'] ?? null;

			// Skip if the callback function is not provided or not callable.
			if ( empty( $callback_fn ) || ! is_callable( $callback_fn ) ) {
				continue;
			}

			$extra_data = $post_data['distributor_extra_data'][ $data_key ] ?? array();

			if ( 'post_meta' === $location ) {
				$metadata_key = 'distributor_meta';
				if ( isset( $post_data['meta'] ) && ! empty( $post_data['meta'] ) ) {
					$metadata_key = 'meta';
				}

				$post_meta = $post_data[ $metadata_key ] ?? array();

				if ( empty( $post_meta ) ) {
					continue;
				}

				$post_data[ $metadata_key ] = $this->process_registered_post_meta_data( $post_meta, $data, $extra_data, $post_data );

			} elseif ( 'post_content' === $location ) {
				$content_key = 'post_content';
				if ( $is_rest ) {
					$content_key = 'content';
					if ( isset( $post_data['distributor_raw_content'] ) ) {
						$content_key = 'distributor_raw_content';
					}
				}

				$post_content = $post_data[ $content_key ] ?? '';
				$block_name   = $attributes['block_name'] ?? '';
				$shortcode    = $attributes['shortcode'] ?? '';

				if ( ( empty( $block_name ) && empty( $shortcode ) ) || empty( $post_content ) ) {
					continue;
				}

				if ( ! empty( $block_name ) && has_blocks( $post_content ) ) {
					$post_data[ $content_key ] = $this->process_registered_block_data( $post_content, $data, $extra_data, $post_data );
					$post_content              = $post_data[ $content_key ];
				}

				// Process the shortcode if shortcode is provided.
				if ( ! empty( $shortcode ) ) {
					$post_data[ $content_key ] = $this->process_registered_shortcode_data( $post_content, $data, $extra_data, $post_data );
				}
			}
		}

		/**
		 * Filter the post data after processing the registered data.
		 *
		 * @since 2.2.0
		 * @hook dt_after_registered_data_processed
		 *
		 * @param {array} $post_data             The post data after processing the registered data.
		 * @param {array} $registered_data       The distributor registered data.
		 * @param {array} $extra_data            The extra data for the given registered data.
		 * @param {array} $unprocessed_post_data The post data before processing the registered data.
		 * @return {array} $post_data The updated post data.
		 */
		$post_data = apply_filters( 'dt_after_registered_data_processed', $post_data, $registered_data, $post_data['distributor_extra_data'] ?? array(), $unprocessed_post_data );

		return $post_data;
	}

	/**
	 * Processes the registered data for the post meta.
	 *
	 * Calls the callback function provided in the registered data and updates the post meta data.
	 *
	 * @since 2.2.0
	 *
	 * @param array $post_meta       The post meta data.
	 * @param array $registered_data The distributor registered data.
	 * @param array $extra_data      The extra data for the given registered data.
	 * @param array $post_data       The post data.
	 * @return array $post_data The processed post data.
	 */
	public function process_registered_post_meta_data( $post_meta, $registered_data, $extra_data, $post_data ) {
		$attributes            = $registered_data['attributes'] ?? array();
		$callback_fn           = $registered_data['post_distribute_cb'] ?? null;
		$meta_key              = $attributes['meta_key'] ?? '';
		$unprocessed_post_meta = $post_meta;

		// Skip if the callback function is not provided or not callable.
		if ( empty( $callback_fn ) || ! is_callable( $callback_fn ) || empty( $meta_key ) ) {
			return $post_meta;
		}

		// Handle multiple meta keys.
		if ( is_array( $meta_key ) ) {
			$original_data = array();
			foreach ( $meta_key as $key ) {
				if ( isset( $post_meta[ $key ] ) ) {
					if ( is_array( $post_meta[ $key ] ) && 1 === count( $post_meta[ $key ] ) ) {
						$original_data[ $key ] = $post_meta[ $key ][0];
					} else {
						$original_data[ $key ] = $post_meta[ $key ];
					}
				}
			}
			$updated_meta = call_user_func_array( $callback_fn, array( $extra_data, $original_data, $post_data, $this->connection_data ) );

			if ( ! empty( $updated_meta ) ) {
				foreach ( $updated_meta as $key => $value ) {
					if ( is_array( $post_meta[ $key ] ) && 1 === count( $post_meta[ $key ] ) ) {
						$post_meta[ $key ] = array( $value );
					} else {
						$post_meta[ $key ] = $value;
					}
				}
			}
		} else {
			$original_data = isset( $post_meta[ $meta_key ] ) ? $post_meta[ $meta_key ] : '';
			if ( is_array( $original_data ) && 1 === count( $original_data ) ) {
				$original_data = $original_data[0];
			}
			$updated_meta = call_user_func_array( $callback_fn, array( $extra_data, $original_data, $post_data, $this->connection_data ) );

			if ( ! empty( $updated_meta ) ) {
				if ( is_array( $post_meta[ $meta_key ] ) && 1 === count( $post_meta[ $meta_key ] ) ) {
					$post_meta[ $meta_key ] = array( $updated_meta );
				} else {
					$post_meta[ $meta_key ] = $updated_meta;
				}
			}
		}

		/**
		 * Filter the post meta data after processing the registered data.
		 *
		 * @since 2.2.0
		 * @hook dt_after_registered_post_meta_processed
		 *
		 * @param {array} $post_meta             The post meta data.
		 * @param {array} $registered_data       The distributor registered data.
		 * @param {array} $extra_data            The extra data for the given registered data.
		 * @param {array} $post_data             The post data.
		 * @param {array} $unprocessed_post_meta The post meta data before processing the registered data.
		 * @return {array} $post_meta The updated post meta data.
		 */
		return apply_filters( 'dt_after_registered_post_meta_processed', $post_meta, $registered_data, $extra_data, $post_data, $unprocessed_post_meta );
	}

	/**
	 * Process the registered block data for the post content.
	 *
	 * @since 2.2.0
	 *
	 * @param string $post_content    The post content.
	 * @param array  $registered_data The distributor registered data.
	 * @param array  $extra_data      The extra data for the given registered data.
	 * @param array  $post_data       The post data.
	 * @return string $post_content The updated post content.
	 */
	public function process_registered_block_data( $post_content, $registered_data, $extra_data, $post_data ) {
		$attributes               = $registered_data['attributes'] ?? array();
		$block_name               = $attributes['block_name'] ?? '';
		$block_attribute          = $attributes['block_attribute'] ?? '';
		$unprocessed_post_content = $post_content;

		if ( ! empty( $block_attribute ) && has_block( $block_name, $post_content ) ) {
			$blocks = parse_blocks( $post_content );
			$result = $this->process_blocks_data_recursive( $blocks, $registered_data, $extra_data, $post_data );

			if ( $result['modified'] ) {
				$post_content = serialize_blocks( $result['blocks'] );
			};
		}

		/**
		 * Filter the post content blocks after processing the registered data.
		 *
		 * @since 2.2.0
		 * @hook dt_after_registered_block_data_processed
		 *
		 * @param {array} $post_content             The post content.
		 * @param {array} $registered_data          The distributor registered data.
		 * @param {array} $extra_data               The extra data for the given registered data.
		 * @param {array} $post_data                The post data.
		 * @param {array} $unprocessed_post_content The post content before processing the registered data.
		 * @return {array} $post_content The updated post content.
		 */
		return apply_filters( 'dt_after_registered_block_data_processed', $post_content, $registered_data, $extra_data, $post_data, $unprocessed_post_content );
	}

	/**
	 * Process the registered shortcode data for the post content.
	 *
	 * @since 2.2.0
	 *
	 * @param string $post_content    The post content.
	 * @param array  $registered_data The distributor registered data.
	 * @param array  $extra_data      The extra data for the given registered data.
	 * @param array  $post_data       The post data.
	 * @return string $post_content The updated post content.
	 */
	public function process_registered_shortcode_data( $post_content, $registered_data, $extra_data, $post_data ) {
		$attributes               = $registered_data['attributes'] ?? array();
		$shortcode                = $attributes['shortcode'] ?? '';
		$shortcode_attribute      = $attributes['shortcode_attribute'] ?? '';
		$callback_fn              = $registered_data['post_distribute_cb'] ?? null;
		$unprocessed_post_content = $post_content;

		if ( ! empty( $shortcode_attribute ) && has_shortcode( $post_content, $shortcode ) ) {
			$index        = 0;
			$pattern      = get_shortcode_regex( array( $shortcode ) );
			$post_content = preg_replace_callback(
				"/$pattern/",
				function ( $matches ) use ( &$index, $shortcode, $shortcode_attribute, $callback_fn, $extra_data, $post_data ) {
					if ( $matches[2] === $shortcode ) {
						$attrs = shortcode_parse_atts( $matches[3] );
						$i     = $index;
						$index++;

						if ( is_array( $shortcode_attribute ) ) {
							$source_data = array();
							foreach ( $shortcode_attribute as $key ) {
								if ( isset( $attrs[ $key ] ) ) {
									$source_data[ $key ] = $attrs[ $key ];
								}
							}
							$current_extra_data = $extra_data[ $i ] ?? array();
							$replacement        = call_user_func_array( $callback_fn, array( $current_extra_data, $source_data, $post_data, $this->connection_data ) );
							if ( ! empty( $replacement ) ) {
								foreach ( $shortcode_attribute as $key ) {
									if ( isset( $replacement[ $key ] ) ) {
										$attrs[ $key ] = $replacement[ $key ];
									}
								}
								$attrs_str = '';
								foreach ( $attrs as $key => $val ) {
									$attrs_str .= sprintf( ' %s="%s"', $key, esc_attr( $val ) );
								}
								return str_replace( $matches[3], $attrs_str, $matches[0] );
							}
						} elseif ( isset( $attrs[ $shortcode_attribute ] ) ) {
							$source_data        = $attrs[ $shortcode_attribute ];
							$current_extra_data = $extra_data[ $i ] ?? array();
							$replacement        = call_user_func_array( $callback_fn, array( $current_extra_data, $source_data, $post_data, $this->connection_data ) );
							if ( ! empty( $replacement ) ) {
								// Replace with the new target ID.
								$attrs[ $shortcode_attribute ] = $replacement;
								$attrs_str                     = '';
								foreach ( $attrs as $key => $val ) {
									$attrs_str .= sprintf( ' %s="%s"', $key, esc_attr( $val ) );
								}
								return str_replace( $matches[3], $attrs_str, $matches[0] );
							}
						}
						$index++;
					}
					return $matches[0];
				},
				$post_content
			);
		}

		/**
		 * Filter the post content shortcodes after processing the registered data.
		 *
		 * @since 2.2.0
		 * @hook dt_after_registered_shortcode_data_processed
		 *
		 * @param {array} $post_content             The post content.
		 * @param {array} $registered_data          The distributor registered data.
		 * @param {array} $extra_data               The extra data for the given registered data.
		 * @param {array} $post_data                The post data.
		 * @param {array} $unprocessed_post_content The post content before processing the registered data.
		 * @return {array} $post_content The updated post content.
		 */
		return apply_filters( 'dt_after_registered_shortcode_data_processed', $post_content, $registered_data, $extra_data, $post_data, $unprocessed_post_content );
	}

	/**
	 * Prepare the term extra data to be sent to the target site.
	 *
	 * @since 2.2.0
	 *
	 * @param int  $term_id     The term ID.
	 * @param bool $with_parent Whether to include the parent term data.
	 * @return array|int The term extra data.
	 */
	public function prepare_registered_data_term( $term_id, $with_parent = false ) {
		$term = get_term( $term_id );
		if ( ! $term || is_wp_error( $term ) ) {
			return 0;
		}

		$term_data = array(
			'term_id'     => $term->term_id,
			'name'        => $term->name,
			'slug'        => $term->slug,
			'description' => $term->description,
			'taxonomy'    => $term->taxonomy,
		);

		if ( ! empty( $term->parent ) && $with_parent && is_taxonomy_hierarchical( $term->taxonomy ) ) {
			$term_data['parent'] = $this->prepare_registered_data_term( $term->parent, $with_parent );
		}

		return $term_data;
	}

	/**
	 * Process the registered data for the term.
	 *
	 * @since 2.2.0
	 *
	 * @param array $term_data        The term data to be processed.
	 * @param bool  $process_parent   Whether to process the parent term.
	 * @param bool  $update_hierarchy Whether to update the term hierarchy.
	 * @return int The term ID of the processed term.
	 */
	public function process_registered_data_term( $term_data, $process_parent = false, $update_hierarchy = false ) {
		if ( empty( $term_data ) ) {
			return 0;
		}

		if ( ! is_array( $term_data ) ) {
			$term_data = (array) $term_data;
		}

		$process_parent = $process_parent && is_taxonomy_hierarchical( $term_data['taxonomy'] );
		$parent_term_id = 0;
		if ( $process_parent && ! empty( $term_data['parent'] ) ) {
			if ( ! empty( $term_data['parent']['term_id'] ) ) {
				$parent_term_id = $this->process_registered_data_term( $term_data['parent'], $process_parent, $update_hierarchy );
			}
		}

		// Check if the term exists on the target site already and return the term ID if it does.
		$taxonomy = $term_data['taxonomy'] ?? '';
		$term     = get_term_by( 'slug', $term_data['slug'], $taxonomy );
		if ( ! empty( $term ) && ! empty( $term->term_id ) ) {
			if ( $update_hierarchy && $process_parent && ! empty( $parent_term_id ) && $term->parent !== $parent_term_id ) {
				wp_update_term( $term->term_id, $taxonomy, array( 'parent' => $parent_term_id ) );
			}

			return $term->term_id;
		}

		$args = array(
			'slug'        => $term_data['slug'],
			'description' => $term_data['description'],
		);

		if ( $process_parent && ! empty( $parent_term_id ) ) {
			$args['parent'] = $parent_term_id;
		}

		$term = wp_insert_term(
			$term_data['name'],
			$taxonomy,
			$args
		);

		if ( is_wp_error( $term ) || empty( $term['term_id'] ) ) {
			return 0;
		}

		return $term['term_id'];
	}

	/**
	 * Pre-process registered "post" type data for external connections.
	 *
	 * Handles "post" type data when pushing to an external connection.
	 *
	 * Since the target site cannot pull the post, we push it to the target site,
	 * retrieve the remote post ID, and include it in the extra data sent to the target site.
	 *
	 * @param array                           $post_data The post data to process for the external connection.
	 * @param \Distributor\ExternalConnection $connection The connection object.
	 * @return array The processed extra data.
	 */
	public function pre_process_registered_data_post( $post_data, $connection ) {
		$extra_data = $post_data['distributor_extra_data'] ?? array();
		if ( empty( $extra_data ) || ! is_array( $extra_data ) || ! apply_filters( 'dt_process_extra_data', true, $post_data ) ) {
			return $post_data;
		}

		try {
			$registered_data      = distributor_get_registered_data();
			$registered_post_data = array_filter(
				$registered_data,
				function( $arr ) {
					return 'post' === $arr['type'];
				}
			);

			if ( ! empty( $registered_post_data ) && ! empty( $extra_data ) ) {
				$prevent_processing = function() {
					return false;
				};
				// Disable the process_extra_data filter to prevent infinite loop.
				add_filter( 'dt_process_extra_data', $prevent_processing, 9999 );

				foreach ( $registered_post_data as $key => $data ) {
					if ( empty( $extra_data[ $key ] ) || ! is_array( $extra_data[ $key ] ) ) {
						continue;
					}

					foreach ( $extra_data[ $key ] as $index => $current_extra_data ) {
						if ( ! empty( $current_extra_data['source_post_id'] ) ) {
							$source_post_id = absint( wp_unslash( $current_extra_data['source_post_id'] ) );
							$source_post    = get_post( $source_post_id );
							if ( empty( $source_post ) ) {
								continue;
							}

							// Push the source post ID to the remote site.
							$connection_map = get_post_meta( $source_post_id, 'dt_connection_map', true );
							if ( empty( $connection_map ) ) {
								$connection_map             = array();
								$connection_map['external'] = array();
							} else {
								$external_connections = $connection_map['external'] ?? array();
								if ( ! empty( $external_connections[ $connection->id ] ) && ! empty( $external_connections[ $connection->id ]['post_id'] ) ) {
									// If the post is already pushed to the remote site, skip it.
									$extra_data[ $key ][ $index ]['remote_post_id'] = (int) $external_connections[ $connection->id ]['post_id'];
									continue;
								}
							}

							$remote_post = $connection->push( $source_post_id, array( 'post_status' => $post_data['post_status'] ?? 'publish' ) );
							if ( ! is_wp_error( $remote_post ) && ! empty( $remote_post['id'] ) ) {
								$connection_map['external'][ (int) $connection->id ] = array(
									'post_id' => (int) $remote_post['id'],
									'time'    => time(),
								);

								$connection->log_sync( array( (int) $remote_post['id'] => $source_post_id ) );
								update_post_meta( $source_post_id, 'dt_connection_map', $connection_map );

								// Update the extra data with the remote post ID.
								$extra_data[ $key ][ $index ]['remote_post_id'] = (int) $remote_post['id'];
							}
						}
					}
				}

				// Remove the filter.
				remove_filter( 'dt_process_extra_data', $prevent_processing, 9999 );
			}
		} catch ( \Exception $e ) {
			// Ignore.
		}
		$post_data['distributor_extra_data'] = $extra_data;

		return $post_data;
	}
}
