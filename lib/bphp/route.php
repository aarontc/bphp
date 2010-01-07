<?php

	// Does routing for virtual paths

	/*
		After processing, $path is an array of strings indicating each element of the path.

		TOP_LEVEL is defined and can be prepended to URLs to reference the base of the site
		(Use this to avoid 'href="/blah"'... 'href="TOP_LEVEL . blah"')

		Actually, use the A function. 'href=' . A ( 'blah' ) . ''
	*/

	define ( 'ROUTE_BASE_PATH', APP_PATH . '/content' );

	if ( ! isset ( $_SERVER['SCRIPT_NAME'] ) )
		die ( "Error with server architecture: environment variable SCRIPT_NAME not set!" );
	if ( ! isset ( $_SERVER['REQUEST_URI'] ) )
		die ( "Error with server architecture: environment variable REQUEST_URI not set!" );

	// Requested path is everything after the common part of SCRIPT_NAME and REQUEST_URI in REQUEST_URI...
	// To save processing time, we assume the file in SCRIPT_NAME is at the path root
	$REQUESTPATH = substr ( $_SERVER['REQUEST_URI'] , strrpos ( $_SERVER['SCRIPT_NAME'], '/' ) );

	$x = strpos ( $REQUESTPATH, '?' );
	if ( ! $x === FALSE )
	    $REQUESTPATH = substr ( $REQUESTPATH, 0, $x );

	if ( ! isset ( $REQUESTPATH ) || substr ( $REQUESTPATH, 0, 1 ) != "/" )
    	die ( "Error collecting path!" );

	if ( $REQUESTPATH == "/index.php" )
    	die ( "Error: Loader called directly. This probably means mod_rewrite is broken or .htaccess file is not being honored. See http://code.google.com/p/bphp/wiki/ErrorLoaderCalledDirectly" );

	$REQUESTPATH = urldecode ( $REQUESTPATH );
	$path = array();

  	$getpath = explode ( '/', $REQUESTPATH );
  	foreach ( $getpath as $checkpath )
    	if ( $checkpath != "" ) $path[] = $checkpath;
  	unset ( $getpath );
  	if ( isset ( $path ) ) {
    	if ( substr ( $REQUESTPATH, -1 ) == "/" )
      		$numlevels = 1;
    	else
			$numlevels = 0;
    	define ( 'TOP_LEVEL', str_repeat ( '../', count ( $path ) - 1 + $numlevels ) );
    	unset ( $numlevels );
    	$flatpath = implode ( '/', $path );
  	} else {
    	define ( 'TOP_LEVEL', "" );
    	$path = array ( 'home' );
    	$flatpath = "home";
  	}

	// Check if "real" path exists
	if ( file_exists ( ROUTE_BASE_PATH . '/' . $flatpath . '.php' ) )
		$source = ( ROUTE_BASE_PATH . '/' . $flatpath . '.php' );
	else {
		// Work up to it!
		$try = $path;
		$found = FALSE;
		$test = array ();
		for ( $x = count ( $try ); $x > 0; $x-- ) {
			$test[] = implode ( '/', $try );
			array_pop ( $try );
		}
		foreach ( $test as $dir ) {
			if ( file_exists ( ROUTE_BASE_PATH . '/' . $dir . '.php' ) ) {
				$found = TRUE;
				$source = ( ROUTE_BASE_PATH . '/' . $dir . '.php' );
				break;
			}
		}
		unset ( $try );
		if ( ! $found ) {
			//echo "Whoops! Couldn't find $flatpath!";
			$source = ( ROUTE_BASE_PATH . '/errors/404.php' );
			header ( "HTTP/1.0 404 Not Found" );
		}
	}

	if ( ! ( isset ( $path[0] ) && $path[0] == "user" && isset ( $path[1] ) && $path[1] == "login" ) )
		$_SESSION['lastrequest'] = $_SERVER['REQUEST_URI'];

	include ( $source );
?>