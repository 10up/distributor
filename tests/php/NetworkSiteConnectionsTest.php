<?php

namespace Distributor\InternalConnections;

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

    /**
     * Test failure of wp insert post
     * @since 0.8
     */
    public function test_push_is_wp_error(){

    	\WP_Mock::userFunction( 'get_post', [
            'return' => (object) [
                'post_content' => '',
                'post_excerpt' => '',
                'post_type' => '',
            ]
        ] );

        \WP_Mock::userFunction( 'wp_insert_post', [
        	'return' => new \WP_Error('','')
        ] );

        $this->connection_obj->site->blog_id = 2;

        $this->assertEquals( $this->connection_obj->push( 1 ), new \WP_Error( '', '' ) );

    }

    /**
     * Pull returns an array of Post IDs on success. This test simulates sending an
     * array containing three IDs (integers) will receive an array containing
     * three integers.
     *
     * @since  0.8
     * @return
     */
    public function test_pull(){

        $this->connection_obj->site->blog_id = 2;

        \WP_Mock::userFunction( 'get_permalink' );

        \WP_Mock::userFunction( 'get_post_meta', [
            'return' => (object) [
                'key' => []
            ]
        ] );

        \WP_Mock::userFunction( 'get_post', [
            'return' => (object) [
                'post_tite' => 'My post title',
                'meta'      => [],
            ]
        ] );

        \WP_Mock::userFunction( 'wp_insert_post', [
            'return' => [4, 3, 2]
        ] );

        $this->assertTrue( count( $this->connection_obj->pull( [ 2, 3, 4 ] ) ) === 3 );

    }

}