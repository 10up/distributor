<?php

namespace Distributor\Tests;

use Distributor\Tests\Utils\TestCase;

class SubscriptionsTest extends TestCase {

	/**
	 * Test delete subscribed to post
	 *
	 * @since  1.0
	 * @group  Subscriptions
	 */
	public function test_delete_subscribed_post(): void {
		$this->markTestIncomplete();
	}

	/**
	 * Test delete original subscribing
	 *
	 * @since  1.0
	 * @group  Subscriptions
	 */
	public function test_delete_subscribing_post(): void {
		$this->markTestIncomplete();
	}

	/**
	 * Test send notifications when no subscriptions
	 *
	 * @since  1.0
	 * @group  Subscriptions
	 */
	public function test_send_notifications_none(): void {
		$this->markTestIncomplete();
	}

	/**
	 * Test send notifications when the remote post does not exist, should delete local subscription
	 *
	 * @since  1.0
	 * @group  Subscriptions
	 */
	public function test_send_notifications_no_remote_post(): void {
		$this->markTestIncomplete();
	}

	/**
	 * Test send notifications when the remote post does exist, should NOT delete local subscription
	 *
	 * @since  1.0
	 * @group  Subscriptions
	 */
	public function test_send_notifications_remote_post_exists(): void {
		$this->markTestIncomplete();
	}

	/**
	 * Test create subscription. This creates a subscription CPT locally that a
	 * remote post has subscribed to
	 *
	 * @since  1.0
	 * @group  Subscriptions
	 */
	public function test_create_subscription() {
		$this->markTestIncomplete();
	}

	/**
	 * Test create remote subscription. This creates a subscription CPT remotely that a
	 * local post has subscribed to
	 *
	 * @since  1.0
	 * @group  Subscriptions
	 */
	public function test_create_remote_subscription(): void {
		$this->markTestIncomplete();
	}

	/**
	 * Test a local subscription given a signature
	 *
	 * @since  1.0
	 * @group  Subscriptions
	 */
	public function test_delete_subscription_local() {
		$this->markTestIncomplete();
	}
}
