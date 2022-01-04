<?php
/**
 * Update functions for version 6.0.0.
 *
 * @package LifterLMS/Functions/Updates
 *
 * @since [version]
 * @version [version]
 */

namespace LLMS\Updates\Version_6_0_0;

defined( 'ABSPATH' ) || exit;

/**
 * Retrieves the DB version of the migration.
 *
 * @since [version]
 *
 * @return string
 */
function _get_db_version() {
	return '6.0.0-alpha.1';
}

/**
 * Migrate deprecated meta values for earned achievements.
 *
 * @since [version]
 *
 * @return bool Returns `true` if more records need to be updated and `false` upon completion.
 */
function migrate_achievements() {
	return _migrate_awards( 'achievement' );
}

/**
 * Migrate deprecated meta values for earned certificates.
 *
 * @since [version]
 *
 * @return bool Returns `true` if more records need to be updated and `false` upon completion.
 */
function migrate_certificates() {
	return _migrate_awards( 'certificate' );
}

/**
 * Migrates meta data for achievement and certificate template posts.
 *
 * @since [version]
 *
 * @return bool Returns `true` if more records need to be updated and `false` upon completion.
 */
function migrate_award_templates() {

	$per_page = llms_update_util_get_items_per_page();

	$query = new \WP_Query(
		array(
			'orderby'        => array( 'ID' => 'ASC' ),
			'post_status'    => 'any',
			'post_type'      => array( 'llms_achievement', 'llms_certificate' ),
			'posts_per_page' => $per_page,
			'no_found_rows'  => true, // We don't care about found rows since we'll run the query as many times as needed anyway.
			'meta_query'     => array(
				'relation' => 'OR',
				array(
					'key'     => '_llms_achievement_image',
					'compare' => 'EXISTS',
				),
				array(
					'key'     => '_llms_certificate_image',
					'compare' => 'EXISTS',
				),
				array(
					'key'     => '_llms_achievement_content',
					'compare' => 'EXISTS',
				),
			),
		)
	);

	$legacy_option_added = false;

	foreach ( $query->posts as $post ) {
		_migrate_image( $post->ID, llms_strip_prefixes( $post->post_type ) );
		if ( 'llms_achievement' === $post->post_type ) {
			_migrate_achievement_content( $post->ID );
		} elseif ( 'llms_certificate' === $post->post_type && ! $legacy_option_added ) {
			_add_legacy_opt();
			$legacy_option_added = true;
		}
	}

	// If there was 50 results assume there's another page and run again, otherwise we're done.
	return ( count( $query->posts ) === $per_page );

}

/**
 * Shows an admin welcome notice.
 *
 * @since [version]
 *
 * @return boolean
 */
function show_notice() {

	$notice_id = sprintf( 'v%s-welcome-msg', str_replace( array( '.', '-' ), '', _get_db_version() ) );

	$html = sprintf(
		'<strong>%1$s</strong><br><br>%2$s<br><br>%3$s',
		__( 'Welcome to LifterLMS 6.0.0!', 'lifterlms' ),
		__( 'Welcome text goes here.', 'lifterlms' ), // @todo Add welcome text.
		sprintf(
			// Translators: %1$s = Opening anchor tag to the welcome blog post on lifterlms.com; %2$s = Closing anchor tag.
			__( '%1$sRead More%2$s', 'lifterlms' ),
			'<a class="button" href="https://blog.lifterlms.com/6-0/" target="_blank" rel="noopener">', // @todo Get real link.
			'</a>'
		)
	);

	\LLMS_Admin_Notices::add_notice(
		$notice_id,
		$html,
		array(
			'type'             => 'success',
			'dismiss_for_days' => 0,
			'remindable'       => false,
		)
	);
	return false;

}

/**
 * Update db version to 6.0.0.
 *
 * @since [version]
 *
 * @return boolean
 */
function update_db_version() {
	\LLMS_Install::update_db_version( _get_db_version() );
	return false;
}

/**
 * Migrate deprecated meta values for user awards by type.
 *
 * Queries 50 earned awards at a time and migrates their data by moving meta data
 * to the new location and then deleting the deprecated meta values.
 *
 * @since [version]
 *
 * @param string $type Award type, either "achievement" or "certificate".
 * @return boolean Returns `true` if there are more results and `false` if there are no further results.
 */
function _migrate_awards( $type ) {

	$per_page = llms_update_util_get_items_per_page();

	$query_args = array(
		'orderby'        => array( 'ID' => 'ASC' ),
		'post_type'      => "llms_my_{$type}",
		'post_status'    => 'any',
		'posts_per_page' => $per_page,
		'no_found_rows'  => true, // We don't care about found rows since we'll run the query as many times as needed anyway.
		'fields'         => 'ids', // We just need the ID for the updates we'll perform.
		'meta_query'     => array(
			'relation' => 'OR',
			array(
				'key'     => "_llms_{$type}_title",
				'compare' => 'EXISTS',
			),
			array(
				'key'     => "_llms_{$type}_template",
				'compare' => 'EXISTS',
			),
			array(
				'key'     => "_llms_{$type}_image",
				'compare' => 'EXISTS',
			),
		),
	);

	if ( 'achievement' === $type ) {
		$query_args['meta_query'][] = array(
			'key'     => '_llms_achievement_content',
			'compare' => 'EXISTS',
		);
	}

	$query = new \WP_Query( $query_args );

	// Don't trigger deprecations.
	remove_filter( 'get_post_metadata', 'llms_engagement_handle_deprecated_meta_keys', 20, 3 );

	// Don't trigger save hooks.
	remove_action( "save_post_llms_my_{$type}", array( 'LLMS_Controller_Awards', 'on_save' ), 20 );

	$legacy_option_added = false;

	foreach ( $query->posts as $post_id ) {

		_migrate_award( $post_id, $type );

		if ( 'certificate' === $type && ! $legacy_option_added ) {
			_add_legacy_opt();
			$legacy_option_added = true;
		}
	}
	// Re-enable deprecations.
	add_filter( 'get_post_metadata', 'llms_engagement_handle_deprecated_meta_keys', 20, 3 );

	// Re-enabled save hooks.
	add_action( "save_post_llms_my_{$type}", array( 'LLMS_Controller_Awards', 'on_save' ), 20 );

	// If there was 50 results assume there's another page and run again, otherwise we're done.
	return ( count( $query->posts ) === $per_page );

}

/**
 * Migrate meta values for a single award.
 *
 * Performs the following updates:
 *   + Copies lifterlms_user_postmeta user data to the post_author property.
 *   + Moves the title from postmeta to the post_title property.
 *   + Moves the template relationship from meta to the post_parent property.
 *   + Moves the award image from custom meta to the post's featured image.
 *
 * And then deletes the previous metadata after performing the necessary updates.
 *
 * @since [version]
 *
 * @param int    $post_id WP_Post ID.
 * @param string $type    Award type, either "achievement" or "certificate".
 * @return void
 */
function _migrate_award( $post_id, $type ) {

	$obj = 'achievement' === $type ? new \LLMS_User_Achievement( $post_id ) : new \LLMS_User_Certificate( $post_id );

	$updates = array(
		'awarded' => $obj->get_earned_date( 'Y-m-d H:i:s' ),
		'author'  => $obj->get_user_id(),
	);

	$title = get_post_meta( $post_id, "_llms_{$type}_title", true );
	if ( $title ) {
		$updates['title'] = $title;
	}

	$template = get_post_meta( $post_id, "_llms_{$type}_template", true );
	if ( $template ) {
		$updates['parent'] = $template;
	}
	$obj->set_bulk( $updates );

	_migrate_image( $post_id, $type );

	if ( 'achievement' === $type ) {
		_migrate_achievement_content( $post_id );
	}

	delete_post_meta( $post_id, "_llms_{$type}_title" );
	delete_post_meta( $post_id, "_llms_{$type}_template" );

}

/**
 * Migrate the achievement content legacy post meta to post_content.
 *
 * @since [version]
 *
 * @param int $post_id WP_Post ID.
 * @return void
 */
function _migrate_achievement_content( $post_id ) {
	$meta_key = '_llms_achievement_content';
	$content  = get_post_meta( $post_id, $meta_key, true );
	if ( $content ) {
		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => $content,
			)
		);
	}

	delete_post_meta( $post_id, $meta_key );

}

/**
 * Migrate the attachment image id from the legacy post meta location
 * to the WP core's featured image.
 *
 * @since [version]
 *
 * @param int    $post_id WP_Post ID.
 * @param string $type    Award type, either "achievement" or "certificate".
 * @return void
 */
function _migrate_image( $post_id, $type ) {

	$image = get_post_meta( $post_id, "_llms_{$type}_image", true );
	if ( $image ) {
		set_post_thumbnail( $post_id, $image );
	}

	delete_post_meta( $post_id, "_llms_{$type}_image" );

}

/**
 * Adds an option used to determine if the site has at least one legacy certificate template.
 *
 * @since [version]
 *
 * @return void
 */
function _add_legacy_opt() {
	update_option( 'lifterlms_has_legacy_certificates', 'yes', 'no' );
}