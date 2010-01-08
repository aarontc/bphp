<?php

	// Get directory containing ourself...
	$path = explode ( '/', dirname ( __FILE__ ) );	// FIXME: PHP 5.3.0 will introduce __DIR__
	array_pop ( $path );
	array_pop ( $path );
	$path = implode ( '/', $path );
	// So that we can add the application base URL to the include path
	define ( 'APP_PATH', $path );

	// Append the normal PHP include path
	$path .= ':' . ini_get ( 'include_path' );
	// Update the system include path
	ini_set ( 'include_path', $path );

	require_once ( 'cfg/bphp.config.php' );

	if ( DEBUG )
		ini_set ( 'error_reporting', E_ALL );
	else
		ini_set ( 'error_reporting', E_ERROR );

	require_once ( 'lib/bphp/customfunctions.php' );

	// PHP should be bashed over the head again and again and again for ever coming up with this idea...
	undo_magic_quotes ();

	// Load application specific session
	if ( file_exists ( APP_PATH . '/lib/session.php' ) ) {
		include_once ( APP_PATH . '/lib/session.php' );
	} else {
		// Start session
		ob_start ();
		session_start ();
	}

	require_once ( 'lib/bphp/database.php' );
	require_once ( 'lib/bphp/userfunctions.php' );
	require_once ( 'lib/bphp/flash.php' );

	// Load application specific file
	if ( file_exists ( APP_PATH . '/lib/loader.php' ) )
		include_once ( APP_PATH . '/lib/loader.php' );

	// Dispatch request via router
	require_once ( 'lib/bphp/route.php' );

?>