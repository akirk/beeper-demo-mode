<?php
/**
 * Plugin Name: Beeper Demo Mode
 * Description: Anonymizes names and images across Personal CRM and related plugins for screenshots and demos. Enable via Settings → Beeper Demo Mode, or apply the personal_crm_demo_mode filter directly.
 * Version:     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Activate demo mode when the option is enabled.
 */
add_filter( 'personal_crm_demo_mode', function ( $enabled ) {
	return $enabled || (bool) get_option( 'demo_mode_enabled', false );
} );

/**
 * Provide the default fake name lists.
 *
 * Override via:
 *   add_filter( 'personal_crm_demo_names', function( $names ) {
 *       $names['first'] = [ 'Jean', 'Pierre', ... ];
 *       return $names;
 *   } );
 */
add_filter( 'personal_crm_demo_names', function ( $names ) {
	return [
		'first' => [
			_x( 'Alice',  'demo mode fake first name', 'beeper-demo-mode' ),
			_x( 'Bob',    'demo mode fake first name', 'beeper-demo-mode' ),
			_x( 'Carol',  'demo mode fake first name', 'beeper-demo-mode' ),
			_x( 'David',  'demo mode fake first name', 'beeper-demo-mode' ),
			_x( 'Emma',   'demo mode fake first name', 'beeper-demo-mode' ),
			_x( 'Frank',  'demo mode fake first name', 'beeper-demo-mode' ),
			_x( 'Grace',  'demo mode fake first name', 'beeper-demo-mode' ),
			_x( 'Henry',  'demo mode fake first name', 'beeper-demo-mode' ),
			_x( 'Isabel', 'demo mode fake first name', 'beeper-demo-mode' ),
			_x( 'James',  'demo mode fake first name', 'beeper-demo-mode' ),
			_x( 'Kate',   'demo mode fake first name', 'beeper-demo-mode' ),
			_x( 'Liam',   'demo mode fake first name', 'beeper-demo-mode' ),
			_x( 'Maya',   'demo mode fake first name', 'beeper-demo-mode' ),
			_x( 'Noah',   'demo mode fake first name', 'beeper-demo-mode' ),
			_x( 'Olivia', 'demo mode fake first name', 'beeper-demo-mode' ),
			_x( 'Peter',  'demo mode fake first name', 'beeper-demo-mode' ),
			_x( 'Quinn',  'demo mode fake first name', 'beeper-demo-mode' ),
			_x( 'Rachel', 'demo mode fake first name', 'beeper-demo-mode' ),
			_x( 'Sam',    'demo mode fake first name', 'beeper-demo-mode' ),
			_x( 'Tara',   'demo mode fake first name', 'beeper-demo-mode' ),
		],
		'last' => [
			_x( 'Smith',    'demo mode fake last name', 'beeper-demo-mode' ),
			_x( 'Johnson',  'demo mode fake last name', 'beeper-demo-mode' ),
			_x( 'Williams', 'demo mode fake last name', 'beeper-demo-mode' ),
			_x( 'Brown',    'demo mode fake last name', 'beeper-demo-mode' ),
			_x( 'Jones',    'demo mode fake last name', 'beeper-demo-mode' ),
			_x( 'Garcia',   'demo mode fake last name', 'beeper-demo-mode' ),
			_x( 'Miller',   'demo mode fake last name', 'beeper-demo-mode' ),
			_x( 'Davis',    'demo mode fake last name', 'beeper-demo-mode' ),
			_x( 'Wilson',   'demo mode fake last name', 'beeper-demo-mode' ),
			_x( 'Moore',    'demo mode fake last name', 'beeper-demo-mode' ),
			_x( 'Taylor',   'demo mode fake last name', 'beeper-demo-mode' ),
			_x( 'Anderson', 'demo mode fake last name', 'beeper-demo-mode' ),
			_x( 'Thomas',   'demo mode fake last name', 'beeper-demo-mode' ),
			_x( 'Jackson',  'demo mode fake last name', 'beeper-demo-mode' ),
			_x( 'White',    'demo mode fake last name', 'beeper-demo-mode' ),
			_x( 'Harris',   'demo mode fake last name', 'beeper-demo-mode' ),
			_x( 'Martin',   'demo mode fake last name', 'beeper-demo-mode' ),
			_x( 'Thompson', 'demo mode fake last name', 'beeper-demo-mode' ),
			_x( 'Young',    'demo mode fake last name', 'beeper-demo-mode' ),
			_x( 'Clark',    'demo mode fake last name', 'beeper-demo-mode' ),
		],
	];
} );

/**
 * Provide the placeholder image URL for demo mode.
 *
 * Override via:
 *   add_filter( 'personal_crm_demo_placeholder_image', function( $url ) {
 *       return 'https://example.com/my-placeholder.png';
 *   } );
 */
add_filter( 'personal_crm_demo_placeholder_image', function ( $url ) {
	return $url ?: plugin_dir_url( __FILE__ ) . 'placeholder.svg';
} );

add_action( 'admin_menu', function () {
	add_options_page(
		__( 'Beeper Demo Mode', 'beeper-demo-mode' ),
		__( 'Beeper Demo Mode', 'beeper-demo-mode' ),
		'manage_options',
		'beeper-demo-mode',
		function () {
			include plugin_dir_path( __FILE__ ) . 'settings.php';
		}
	);
} );

add_action( 'admin_init', function () {
	register_setting( 'demo_mode', 'demo_mode_enabled', [
		'type'    => 'boolean',
		'default' => false,
	] );
} );
