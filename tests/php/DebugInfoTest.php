<?php

namespace Distributor;

use WP_Mock\Tools\TestCase;

class DebugInfoTest extends TestCase {
	public function test_add_debug_info() {
		define( 'WP_PLUGIN_DIR', '/wp-content/plugins' );
		define( 'DT_PLUGIN_FILE', 'distributor/distributor.php' );
		define( 'DT_IS_NETWORK', false );

		\WP_Mock::userFunction(
			'get_plugin_data',
			[
				'times'  => 1,
				'args'   => [ \WP_Mock\Functions::type( 'string' ) ],
				'return' => [
					'Name'       => 'Distributor',
					'Version'    => '1.5.0',
					'TextDomain' => 'distributor'
				],
			]
		);

		\WP_Mock::userFunction(
			'wp_parse_args',
			[
				'times'  => 1,
				'args'   => [ \WP_Mock\Functions::type( 'array' ), \WP_Mock\Functions::type( 'array' ) ],
				'return' => [
					'email'                  => '',
					'valid_license'          => false,
					'license_key'            => '',
					'override_author_byline' => true,
					'media_handling'         => 'featured',
				],
			]
		);

		\WP_Mock::userFunction(
			'get_option',
			[
				'times'  => 1,
				'args'   => [ \WP_Mock\Functions::type( 'string' ) ],
				'return' => [],
			]
		);

		\WP_Mock::userFunction(
			'wp_json_encode',
			[
				'args'   => [ [] ],
				'return' => '[false]',
			]
		);

		\WP_Mock::userFunction(
			'get_post_meta',
			[
				'args'   => [ \WP_Mock\Functions::type( 'object' ), \WP_Mock\Functions::type( 'string' ), true ],
				'return' => 'none',
			]
		);

		$info = DebugInfo\add_debug_info( [] );
		$this->assertArrayHasKey( 'distributor', $info );
		$this->assertArrayHasKey( 'label', $info['distributor'] );
		$this->assertArrayHasKey( 'fields', $info['distributor'] );
		$this->assertEquals( 'Distributor', $info['distributor']['label'] );
		$this->assertEquals( 6, count( $info['distributor']['fields'] ) );
	}
}
