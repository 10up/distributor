<?php
/**
 * Enqueue scripts for all admin pages.
 *
 * @since 2.8.0
 * @hook admin_enqueue_scripts
 *
 * @param {string} $hook_suffix The current admin page.
 */
do_action( 'admin_enqueue_scripts', $hook_suffix );

/**
 * Filters the title tag content for an admin page.
 *
 * @since 3.1.0
 * @hook admin_title
 *
 * @param {string} $admin_title The page title, with extra context added.
 * @param {string} $title       The original page title.
 * @returns {string} The title
 */
$admin_title = apply_filters( 'admin_title', $admin_title, $title );
