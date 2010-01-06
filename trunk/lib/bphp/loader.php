<?php

	// Get directory containing ourself...
	$path = explode ( '/', dirname ( __FILE__ ) );	// FIXME: PHP 5.3.0 will introduce __DIR__
	array_pop ( $path );
	array_pop ( $path );
	$path = implode ( '/', $path );
	// So that we can add the application base URL to the include path
	// Append the normal PHP include path
	$path .= ':' . ini_get ( 'include_path' );
	// Update the system include path
	ini_set ( 'include_path', $path );

	require_once ( 'cfg/bphp.config.php' );

	if ( DEBUG )
		ini_set ( 'error_reporting', E_ALL );
	else
		ini_set ( 'error_reporting', E_ERROR );

	// PHP should be bashed over the head again and again and again for ever coming up with this idea...
	undo_magic_quotes ();

	ob_start ();
	session_start ();

	require_once ( 'lib/bphp/database.php' );
	require_once ( 'lib/bphp/userfunctions.php' );
	require_once ( 'lib/bphp/flash.php' );
	require_once ( 'lib/bphp/route.php' );

?>