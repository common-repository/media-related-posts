<?php
/**
 * Plugin Name: Media Related Posts
 * Description: Add a column in media list. Show all related posts for each media
 * Version: 1.1.0
 * Author: Matthieu Barbaresco
 * Author URI:  https://profiles.wordpress.org/hanzozerazor
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: mrp
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

add_action( 'init', 'mrp_load_plugin_textdomain' );
function mrp_load_plugin_textdomain() {
	$domain = 'mrp';
	$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

	load_plugin_textdomain( $domain, false, basename( dirname( __FILE__ ) ) . '/languages/' );
}

add_action( 'plugins_loaded', 'mrp_init', 11 );
function mrp_init() {
	add_action( 'admin_init', 'mrp_new_media_columns' );
}

function mrp_new_media_columns() {
	add_filter( 'manage_media_columns', 'mrp_whereIsPublished_column' );
	add_action( 'manage_media_custom_column', 'mrp_whereIsPublished_value', 10, 2 );
}

function mrp_whereIsPublished_column( $columnns ) {
	$columnns['mrp_whereIsPublished'] = __( "Related Posts", "mrp" );

	return $columnns;
}

function mrp_whereIsPublished_value( $column_name, $media_id ) {
	if ( $column_name === "mrp_whereIsPublished" ) {
		global $wpdb;

		$posts_id = array();
		$res      = $wpdb->get_row( "SELECT `post_parent` FROM `{$wpdb->prefix}posts` WHERE `ID` = $media_id;" );

		if ( $res->post_parent !== "0" ) {
			$posts_id[] = $res->post_parent;
		}

		$res = $wpdb->get_results( "SELECT DISTINCT `post_id` FROM {$wpdb->prefix}postmeta WHERE `meta_value` = $media_id AND `post_id` <> $res->post_parent ;" );

		if ( count( $res ) > 0 ) {
			foreach ( $res as $object ) {
				$posts_id[] = $object->post_id;
			}
		}

		if ( count( $posts_id ) > 0 ) {
			$to_exclude = implode( ",", $posts_id );
			$res        = $wpdb->get_results( "SELECT `ID` FROM `{$wpdb->prefix}posts` WHERE `post_content` LIKE '%wp-image-$media_id%' AND `ID` NOT IN ($to_exclude);" );
		} else {
			$res = $wpdb->get_results( "SELECT `ID` FROM `{$wpdb->prefix}posts` WHERE `post_content` LIKE '%wp-image-$media_id%';" );
		}

		if ( count( $res ) > 0 ) {
			foreach ( $res as $object ) {
				$posts_id[] = $object->ID;
			}
		}

		if ( count( $posts_id ) > 0 ) {
			echo "<ul>";
			foreach ( $posts_id as $post_id ) {
				if ( $post_id !== "0" ) {
					$title = get_the_title( $post_id );
					$link  = "post.php?post=$post_id&action=edit";
					echo "<li><a href='$link' target='_blank'>$title</a></li>";
				}
			}
			echo "</ul>";
		} else {
			echo __( "No related posts", "mrp" );
		}
	}
}