<?php

  	// Aaron's <aaron@madebyai.com> custom function include. Very handy, feel free to steal (but give credit where credit is due)

	function PrintSearchBox ( $q = "" ) {
		?>
		<script type="text/javascript">
			<!-- // Hiding
			function SearchForm ( formobj ) {
				window.location.href="<?=A('search/')?>"+formobj.q.value;
			}
			// Hiding -->
		</script>
		<form class="search" method="post" action="<?=A('search')?>">
			<input name="q" type="text" value="<?= ( isset ( $q ) ? htmlentities ( $q ) : "" ) ?>">
			<input type="submit" value="Search" onclick="javascript:SearchForm(this);return false;">
		</form>
		<?php
	}



	function RandomMessageOfDoom() {
		include_once ( 'failure.inc.php' );
		$random_failure_message = $failure[rand(0, count($failure)-1)];
		return "<br/><span style='font-style:italic;'>" . $random_failure_message . "</span>";
	}

	function FormatSize ( $size, $long = false ) {
		$multiplier = 0;
		while ( $size > 1024 ) {
			$multiplier++;
			$size /= 1024;
		}
		$prefixes_long = array (
			"Bytes",
			"Kilobytes",
			"Megabytes",
			"Gigabytes",
			"Terabytes",
			"Petabytes" );
		$prefixes_short = array (
			"B",
			"KB",
			"MB",
			"GB",
			"TB",
			"PB" );

		$size = round ( $size, 2 );
		if ( $long )
			return $size . ' ' . $prefixes_long[$multiplier];
		else
			return $size . ' ' . $prefixes_short[$multiplier];
	}



	if ( ! function_exists('array_map_recursive') ) {
		function array_map_recursive($function, $data) {
			foreach ( $data as $i => $item ) {
				$data[$i] = is_array($item)
					? array_map_recursive($function, $item)
					: $function($item) ;
			}
			return $data ;
		}
	}

	function A ( $url ) {
		// Rewrites an URI to reflect virtual directory structure
		return ( TOP_LEVEL . $url );
	}

	function IsolateLastPathElement ( $x ) {
		if ( ! preg_match ( "/(?<=\/)[a-zA-Z0-9]+(?:\.[a-zA-Z0-9]+)?$/", $x, $out ) )
		$out = $x;
		if ( is_array ( $out ) ) $out = $out[0];
		if ( ! strpos ( $out, '.' ) === FALSE )
		$out = substr ( $out, 0, strpos ( $out, '.' ) );
		return $out;
	}

	function undo_magic_quotes( ) {
		if ( get_magic_quotes_gpc( ) ) {
			$_GET = array_map_recursive('stripslashes', $_GET) ;
			$_POST = array_map_recursive('stripslashes', $_POST) ;
			$_COOKIE = array_map_recursive('stripslashes', $_COOKIE) ;
			$_REQUEST = array_map_recursive('stripslashes', $_REQUEST) ;
		}
	}


	function is_valid_email_address ( $email ) {
		$qtext = '[^\\x0d\\x22\\x5c\\x80-\\xff]';
		$dtext = '[^\\x0d\\x5b-\\x5d\\x80-\\xff]';
		$atom = '[^\\x00-\\x20\\x22\\x28\\x29\\x2c\\x2e\\x3a-\\x3c\\x3e\\x40\\x5b-\\x5d\\x7f-\\xff]+';
		$quoted_pair = '\\x5c\\x00-\\x7f';
		$domain_literal = "\\x5b($dtext|$quoted_pair)*\\x5d";
		$quoted_string = "\\x22($qtext|$quoted_pair)*\\x22";
		$domain_ref = $atom;
		$sub_domain = "($domain_ref|$domain_literal)";
		$word = "($atom|$quoted_string)";
		$domain = "$sub_domain(\\x2e$sub_domain)*";
		$local_part = "$word(\\x2e$word)*";
		$addr_spec = "$local_part\\x40$domain";

		return preg_match("!^$addr_spec$!", $email) ? true : false;
	}


	function HASH_PASSWORD ( $plaintext ) {
    	$SALT = "yayOIT_1s_teh_r0x0rz!@#%";
    	$result = exec ( "echo " . escapeshellarg ( $plaintext . $SALT ) . " | sha512 -x " );
    	return ( $result );
	}

  	function ShowError ( $field )   {
    	global $posterror;
    	if ( isset ( $posterror[$field] ) )
      		return ( '<span class="error">' . $posterror[$field] . '</span>' );
    	else
      	return ( "" );
  	}

	function F ( $field, $default = "" ) {
		if ( isset ( $_POST[$field] ) )
			return ( htmlentities ( $_POST[$field] ) );
		else
			return ( $default );
  	}
?>