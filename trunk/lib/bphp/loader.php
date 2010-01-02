<?php

	define ( 'DEBUG', TRUE );

	// Get directory containing ourself...
	$pathx = substr ( __FILE__, 0, strrpos ( __FILE__, '/' ) );
	$pathy = explode ( '/', $pathx );
	array_pop ( $pathy );
	array_pop ( $pathy );
	$pathy = implode ( '/', $pathy );
	// Append the normal PHP include path
	$pathx .= ':' . $pathy . ':' . ini_get ( 'include_path' );
	// Update the system include path
	ini_set ( 'include_path', $pathx );

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

	require ( 'cfg/osoasis.config.php' );
	require ( 'lib/bphp/database.php' );
	require ( 'lib/bphp/userfunctions.php' );
	require ( 'lib/bphp/flash.php' );

	require_once ( 'lib/php/route.php' );

?>