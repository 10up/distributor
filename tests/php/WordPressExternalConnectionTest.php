<?php
namespace Syndicate\ExternalConnections;
use \Syndicate\Authentications\WordPressBasicAuth as WordPressBasicAuth;
use \Syndicate\Mappings\WordPressRestPost as WordPressRestPost;

class WordPressExternalConnectionTest extends \TestCase {

	/**
	 * Test creating a WordPressExternalConnection object
	 *
	 * @since  1.0
	 * @group WordPressExternalConnection
	 */
	public function test_construct() {
		try {
			$connection = new WordPressExternalConnection();
		} catch ( \Exception $e ) {
			// Requires arguments
			$this->assertTrue( true );
		}

		try {
			$connection = new WordPressExternalConnection( 'name', 'url', 1, new \stdClass(), new \stdClass() );
		} catch ( \TypeError $e ) {
			// Improper auth and mapping objects pass
			$this->assertTrue( true );
		}

		// Now test a successful creation
		$auth = new WordPressBasicAuth( array() );
		$mapping = new WordPressRestPost();

		$connection = new WordPressExternalConnection( 'name', 'url', 1, $auth, $mapping );

		$this->assertTrue( is_a( $connection, '\Syndicate\ExternalConnection' ) );

		// Check connection properties
		$this->assertTrue( ! empty( $connection->name ) );
		$this->assertTrue( ! empty( $connection->base_url ) );
		$this->assertTrue( ! empty( $connection->id ) );
		$this->assertTrue( ! empty( $connection->auth_handler ) );
		$this->assertTrue( ! empty( $connection->mapping_handler ) );
	}

}