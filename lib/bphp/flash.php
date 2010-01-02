<?php

	function FlashMessage ( $message ) {
		if ( ! isset ( $_SESSION['flash'] ) )
			$_SESSION['flash'] = array();

		$_SESSION['flash'][] = $message;
	}

	function GetFlash () {
		if ( isset ( $_SESSION['flash'] ) ) {
			$flash = $_SESSION['flash'];
			unset ( $_SESSION['flash'] );
			return $flash;
		} else {
			return array();
		}
	}
?>