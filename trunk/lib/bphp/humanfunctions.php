<?php
 
 	// Returns a human-readable date given a UNIX timestamp
	function HumanDate ( $timestamp ) {
		return date ( 'M jS, Y', $timestamp );
	}
	
	function HumanNumber ( $float ) {
		return number_format ( $float );
	}
?>