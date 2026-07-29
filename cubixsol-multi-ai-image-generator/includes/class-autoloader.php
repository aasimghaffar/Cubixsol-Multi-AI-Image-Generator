<?php
/**
 * Class autoloader.
 *
 * @package AIISP
 */

namespace AIISP;

// Security check: block direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Autoloads AIISP\ classes from /includes using the WordPress
 * "class-{kebab-case-name}.php" convention.
 */
final class Autoloader {

	/**
	 * Register the autoloader with SPL.
	 *
	 * @return void
	 */
	public static function register() {
		spl_autoload_register( array( __CLASS__, 'autoload' ) );
	}

	/**
	 * Resolve a fully-qualified class name to a file path and load it.
	 *
	 * @param string $class Fully qualified class name, e.g. AIISP\Admin\Meta_Box.
	 * @return void
	 */
	public static function autoload( $class ) {
		// Check: only handle our own namespace.
		if ( 0 !== strpos( $class, __NAMESPACE__ . '\\' ) ) {
			return;
		}

		// Strip the root namespace and split into path segments.
		$relative = substr( $class, strlen( __NAMESPACE__ ) + 1 );
		$parts    = explode( '\\', $relative );

		// The final segment is the class; everything before it is a directory.
		$class_name = array_pop( $parts );
		$sub_dir    = strtolower( implode( '/', $parts ) );

		// Class_Name -> class-class-name.php.
		$file_name = 'class-' . str_replace( '_', '-', strtolower( $class_name ) ) . '.php';

		$path = AIISP_PLUGIN_DIR . 'includes/' . ( $sub_dir ? $sub_dir . '/' : '' ) . $file_name;

		// Check: only require files that actually exist and are readable.
		if ( is_readable( $path ) ) {
			require_once $path;
		}
	}
}
