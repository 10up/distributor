<?php

namespace Syndicate\InternalConnections;

class NetworkSiteConnectionsTest extends \TestCase {

    public function setUp(){
        $this->site_obj = \Mockery::mock( '\WP_Site', [
            'args' => 1,
            'return' => ''
        ] );

        $this->connection_obj = new NetworkSiteConnection( $this->site_obj );
    }

    /**
     * Push returns an post ID on success instance of WP Error on failure.
     *
     * @since  0.8
     */
    public function test_push(){

        \WP_Mock::userFunction( 'get_post', [
            'return' => (object) [
                'post_content' => '',
                'post_excerpt' => '',
                'post_type' => '',
            ]
        ] );

        \WP_Mock::userFunction( 'get_current_blog_id' );
        \WP_Mock::userFunction( 'get_current_user_id' );
        \WP_Mock::userFunction( 'switch_to_blog' );

        $this->connection_obj->site->blog_id = 2;

        \WP_Mock::userFunction( 'wp_insert_post', [
            'return' => 123
        ] );

        \WP_Mock::userFunction( 'update_post_meta' );
        \WP_Mock::userFunction( 'get_post_meta', [
            'return' => []
        ] );

        \WP_Mock::userFunction( 'restore_current_blog' );

        $this->assertTrue( is_int( $this->connection_obj->push( 1 ) ) );

    }

}