<?php

	// Get directory containing ourself...
	$path = explode ( '/', dirname ( __FILE__ ) );	// FIXME: PHP 5.3.0 will introduce __DIR__
	print_r ( $path );
	array_pop ( $path );
	print_r ( $path );
	$path = implode ( '/', $path );
	print_r ( $path );
	// Append the normal PHP include path
	$path .= ':' . ini_get ( 'include_path' );
	print_r ( $path );
	// Update the system include path
	ini_set ( 'include_path', $path );

	require_once ( 'cfg/bphp.config.php' );

	if ( DEBUG )
		ini_set ( 'error_reporting', E_ALL );
	else
		ini_set ( 'error_reporting', E_ERROR );

	require_once ( 'lib/bphp/customfunctions.php' );
	//require_once ( 'adminfunctions.php' );

	// PHP should be bashed over the head again and again and again for ever coming up with this idea...
	undo_magic_quotes ();

	ob_start ();
	session_start ();

	require ( 'lib/bphp/database.php' );
	require ( 'lib/bphp/userfunctions.php' );
	require ( 'lib/bphp/flash.php' );

	require_once ( 'lib/php/route.php' );

?>