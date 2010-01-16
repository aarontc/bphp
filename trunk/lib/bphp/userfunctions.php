<?php


	function UserGetSalt ( $username ) {
		$result = $db -> Execute ( "SELECT salt FROM users WHERE LOWER(username)=?", strtolower ( $username ) );

		if ( $result !== FALSE )
			return $result->fields[0];
		else
			return FALSE;
	}


	// Returns a user's hashed password
	// FIXME: If salt is not passed, it will be looked up from the database.
	// Generally the salt only needs to be specified when the user is being created and their salt
	// isn't in the database.
	function UserHashPass ( $password, $salt ) {
		return ( hash ( 'sha512', $salt . ':' . $password . ':' . $CONFIG['Application']['PasswordSalt'] ) );
	}

	function UserRequireLogin() {
		$userid = UserSessionID();
		if ( $userid === false ) {
			FlashMessage ( "You need to be logged in to do that" );
			header ( "Location: /user/login");
			exit;
		}
	}

	function UserRequireRole ( $role ) {
		UserRequireLogin ();
		if ( ! UserVerifyRole ( UserSessionId(), $role ) ) {
			FlashMessage ( "You need to have role " . $role . " to do that." );
			header ( "Location: /user/login" );
			exit;
		}
	}

	// Validates a user's name and password
	// Returns user's database record, or false
	function UserValidateLogin ( $username, $password ) {
		$salt = UserGetSalt ( $username );
		$query = sprintf ( "SELECT * FROM users WHERE LOWER(username)='%s' AND password='%s'",
							pg_escape_string ( strtolower ( $username ) ),
							pg_escape_string ( UserHashPass ( $password, $salt ) )
						);
		//echo $query;
		$res = DatabaseQuery ( $query );
		if ( pg_num_rows ( $res ) == 1 ) {
			$row = pg_fetch_assoc ( $res );
			$_SESSION['username'] = $username;
			$_SESSION['password'] = $password;
			return $row;
		} else {
			return false;
		}
	}

	function UserLogout () {
		session_destroy ();
	}


	// reads 32 bytes from /dev/random for the salt
	function UserGenerateSalt () {
		$length = rand ( 13, 64 );

		$rs = "";

		if ( ( $fhandle = fopen ( '/dev/urandom', 'rb' ) ) != FALSE ) {

    		for ( $i=0; $i < $length; $i++ ) {
      			$val = ord ( fgetc ( $fhandle ) );
      			if ( $val <= 0x0f ) {
					$rb = sprintf("0%X",$val);
      			} else {
					$rb = sprintf("%X",$val);
				}

				$rs .= $rb;
			}
			fclose ( $fhandle );
    	} else {
			die ( "Unable to open /dev/random for reading" );
		}

		return $rs;

  	}


	// Returns whether there are any real users in the database,
	// used to determine whether
	// Returns true if genesis is required (no real users exist)
	function UserGenesisCheck () {
		global $db;
		$result = &$db -> Execute ( "SELECT COUNT(userid) AS num FROM users" );
		$num = $result -> fields[0];
		if ( $num == 0 )
			return true;
		else
			return false;
	}


	// Returns currently-logged user record, or false if no user
	function UserSession() {
		if ( isset ( $_SESSION['username'] ) ) {
			return ( UserValidateLogin ( $_SESSION['username'], $_SESSION['password'] ) );
		} else
			return false;
	}

	function UserSessionID() {
		$result = UserSession();
		if ( $result === false )
			return false;
		else
			return $result['userid'];
	}

	function UserGetUserInfo( $username ) {
		$query = sprintf("SELECT * FROM users WHERE username='%s'", pg_escape_string( $username ));
		$res = DatabaseQuery( $query );
		if ($res)
		{
			$row = pg_fetch_assoc ( $res );
			return $row;
		}
		return false;
	}


	function UserCreateUser ( $username, $password, $email, $firstname, $lastname ) {

		if ( strlen ( $username ) < 1 )
			return ( "Username must be at least 1 character" );

		if ( strlen ( $password ) < 1 )
			return ( "Password cannot be blank" );

		if ( !validEmail ( $email ) )
			return ( "Email address format invalid or domain does not exist" );

		if ( strlen ( $firstname ) < 1 )
			return ( "First name cannot be blank" );

		if ( strlen ( $lastname ) < 1 )
			return ( "Last name cannot be blank" );

		$salt = UserGenerateSalt();
		//$db -> Execute ( "START TRANSACTION" );
		$result = &$db -> Execute ( "INSERT INTO users (username, password, salt, first_name, last_name) VALUES (?,?,?,?,?)",
				array ( $username,
				UserHashPassword ( $password, $salt ),
				$salt,
				$firstname,
				$lastname )
			);

		if ( $result !== false ) {
			// insert email address
			// first get user's ID
			$result = &$db -> Execute ( "SELECT userid FROM users WHERE username=?", array($username) );
			if ( $result === false )
				die ( "Could not get newly inserted user's ID" );
			$userid = $result->fields[0];
			$emailkey = UserGenerateEmailKey();
			$result = &$db -> Execute ( "INSERT INTO user_emails (userid, email, activation_key, activated) VALUES(?,?,?,?)",
				array($userid, $email, $emailkey, FALSE) );
			if ( $result !== FALSE ) {
				// send email
				UserSendEmailActivationMessage ( $username, $email );
			}
			return TRUE;
		else
			return $db -> ErrorMsg();
	}

	function UserSendEmailActivationMessage ( $username, $email ) {
		$result = &$db -> Execute ( "SELECT activation_key FROM user_emails JOIN users ON users.userid = user_emails.userid WHERE users.username=? AND user_emails.email=? AND activated=0",
			array ( $username, $email ) );
		if ( $result !== FALSE ) {
			$message = "Hello! You, or someone pretending to be you, has\n" .
						"added the email address [" . $email . "]\n" .
						"to the account [" . $username . "] at www.myvwan.com.\n".
						"\n" .
						"Before this address can be used, it must be activated by\n"
						"following this link:\n" .
						"\n" .
						"http://www.myvwan.com/user/" . $username . "/activate/" . $result->fields[0] . "\n" .
						"\n" .
						"If this link is not clickable, please copy and paste it into your\n" .
						"web browser.";

			mail ( $email, "Email Activation for MyVWAN.com", $message, "From: www.myvwan.com Activation <noreply@myvwan.com>" );
		} else {
			die ( "Unable to send activation email" );
		}
	}

	function UserGetUserID ( $username ) {
		$query = sprintf ( "SELECT userid FROM users WHERE username='%s'", pg_escape_string ( $username ) );
		$res = DatabaseQuery ( $query );
		if ( pg_num_rows ( $res ) > 0 ) {
			$row = pg_fetch_assoc ( $res );
			return $row['userid'];
		} else {
			return false;
		}
	}

	function UserGetUsers () {
		$users = array ();
		$query = ( "SELECT userid, username, firstname, lastname FROM users ORDER BY LOWER(username) ASC" );
		$res = DatabaseQuery ( $query );
		if ( pg_num_rows ( $res ) > 0 ) {
			while ( $row = pg_fetch_assoc ( $res ) ) {
				$users[] = $row;
			}
		}
		return $users;
	}

	function UserGetUserDropdown () {
		$userdropdown = array();
		foreach ( UserGetUsers() as $user ) {
			$userdropdown[$user['userid']] = $user['username'] . " [" . $user['firstname'] . " " . $user['lastname'] . "]";
		}
		return $userdropdown;
	}

	function UserVerifyRole( $userid, $role)
	{
		$returnVal = false;
		$query = sprintf( "SELECT users.userid FROM users, roles, user_roles WHERE users.userid=user_roles.userid AND user_roles.roleid=roles.roleid AND roles.name='%s' AND users.userid='%s'",
				pg_escape_string( $role ),
				pg_escape_string( $userid ) );
		$res = DatabaseQuery ( $query );
		if ( pg_num_rows ( $res ) > 0 )
		{
			$returnVal = true;
		}
		return $returnVal;
	}

	function UserCreateRole( $role )
	{
		$username = UserSessionID();
		if ($username === false)
		{
			$query = sprintf( "SELECT userid FROM users WHERE username = 'adam'" );
		}
		else
		{
			$query = sprintf( "SELECT userid FROM users WHERE username = '%s'", pg_escape_string ( $username ) );
		}
		$res = DatabaseQuery($query);
		if ( pg_num_rows ( $res ) > 0 )
		{
			$row = pg_fetch_assoc ( $res );
			$userid = $row['userid'];
		}


		$query = sprintf( "INSERT INTO roles (name, created_by, updated_by) VALUES ('%s', '%d', '%d')",
							pg_escape_string ( $role ),
							pg_escape_string ( $userid ),
							pg_escape_string ( $userid ) );
		$res = DatabaseQuery($query);
	}

	function UserRoleExists( $role )
	{
		$result = false;
		$query = sprintf( "SELECT name FROM roles WHERE name='%s'", pg_escape_string( $role ) );
		$res = DatabaseQuery ( $query );
		if ( pg_num_rows ( $res ) > 0 )
		{
			$result = true;
		}
		return ( $result );
	}

	function RoleGetRoles( )
	{
		$query = sprintf( "SELECT roleid, name FROM roles" );
		$res = DatabaseQuery ( $query );
		$myArray = array();
		while ( $row = pg_fetch_assoc( $res ) )
		{
			$myArray[] = $row;
		}
		return $myArray;
	}

	function UserAddRoleToUser( $userid, $roleid )
	{
		if ($userid !== false)
		{
			$query = sprintf( "INSERT INTO user_roles (userid, roleid, created_by, updated_by) VALUES ('%d', '%d', '%d', '%d')",
						pg_escape_string( $userid ),
						pg_escape_string( $roleid ),
						pg_escape_string( $userid ),
						pg_escape_string( $userid ) );
			$res = DatabaseQuery ( $query );
		}
	}
	function UserRemoveRoleFromUser( $userid, $roleid )
	{
		if ($userid !== false)
		{
			$query = sprintf( "DELETE FROM user_roles WHERE userid='%d' AND roleid='%d'",
						pg_escape_string( $userid ),
						pg_escape_string( $roleid ) );
			$res = DatabaseQuery ( $query );
		}
	}

	function UserGetRoleID( $rolename)
	{
		$result = false;
		$query = sprintf( "SELECT roleid FROM roles WHERE name='%s'", pg_escape_string( $rolename ) );
		$res = DatabaseQuery ( $query );
		if ( pg_num_rows ( $res ) > 0 )
		{
			$row = pg_fetch_assoc ( $res );
			return $row['roleid'];
		} else
		{
			return false;
		}
	}

	function UserEditProfileInformation( $info )
	{
		$query = sprintf( "UPDATE users SET firstname='%s', lastname='%s', email='%s' WHERE username='%s'",
					pg_escape_string( $info['firstname'] ),
					pg_escape_string( $info['lastname'] ),
					pg_escape_string( $info['email'] ),
					pg_escape_string( $info['username'] ) );
		$res = DatabaseQuery( $query );
	}

	function UserGetUsersRoleList( $userid )
	{
		$rolesList = RoleGetRoles( );
		foreach ($rolesList as &$current)
		{
			$current['userHasRole'] = false;
			if (UserVerifyRole($userid, $current['name']) === true)
			{
				$current['userHasRole'] = true;
			}
		}
		return $rolesList;
	}

	// Returns an associative array of all
	function UserGetRoles ( $userid ) {
		$query = sprintf ( "SELECT name, roles.roleid FROM user_roles JOIN roles ON roles.roleid=user_roles.roleid WHERE userid='%d'", pg_escape_string ( $userid ) );
		$res = DatabaseQuery ( $query );
		$roles = array ();
		while ( $row = pg_fetch_assoc ( $res ) ) {
			$roles[$row['name']] = $row['roleid'];
		}
		return ( $roles );
	}

	function UserUpdateUserRoles( $userid, $roleList )
	{
		if (count($roleList) > 0)
		{
			$tempString = "";

			foreach($roleList as $role)
			{
				$tempString = $tempString . $role['roleid'] . ", ";
			}
			$stringLen = strlen($tempString);
			$tempString = substr($tempString, 0, $stringLen - 2);
			$userCreatorID = UserSessionID();

			$query = sprintf( "DELETE FROM user_roles WHERE userid='%s' AND roleid NOT IN (%s)",
						pg_escape_string( $userid ),
						pg_escape_string( $tempString ) );
			$res = DatabaseQuery( $query );

			$query = sprintf( "SELECT roleid FROM roles WHERE roleid NOT IN (SELECT roles.roleid FROM roles, users, user_roles WHERE roles.roleid IN (%s) AND users.userid='%d' AND user_roles.roleid = roles.roleid AND user_roles.userid = users.userid) AND roleid IN (%s)",
						pg_escape_string( $tempString ),
						pg_escape_string( $userid ),
						pg_escape_string( $tempString ) );

			$res = DatabaseQuery( $query );
			if ( pg_num_rows ( $res ) > 0 )
			{
				$row = pg_fetch_assoc ( $res );
			}
			else
			{
				return;
			}

			$query = "INSERT INTO user_roles (roleid, userid, created_by, updated_by) VALUES ";
			foreach ( $row as $thing )
			{
				$query .= sprintf("('%s', '%s', '%s', '%s'), ", pg_escape_string( $thing ), pg_escape_string( $userid ), pg_escape_string( $userCreatorID ), pg_escape_string( $userCreatorID ) );
			}
			$query = substr($query, 0, -2);

			$res = DatabaseQuery ( $query );
		}
		else
		{
			$query = sprintf( "DELETE FROM user_roles WHERE userid='%s'", pg_escape_string( $userid ) );
		}
	}



	/**
Validate an email address.
Provide email address (raw input)
Returns true if the email address has the email
address format and the domain exists.

From http://www.linuxjournal.com/article/9585
*/
function validEmail($email)
{
   $isValid = true;
   $atIndex = strrpos($email, "@");
   if (is_bool($atIndex) && !$atIndex)
   {
      $isValid = false;
   }
   else
   {
      $domain = substr($email, $atIndex+1);
      $local = substr($email, 0, $atIndex);
      $localLen = strlen($local);
      $domainLen = strlen($domain);
      if ($localLen < 1 || $localLen > 64)
      {
         // local part length exceeded
         $isValid = false;
      }
      else if ($domainLen < 1 || $domainLen > 255)
      {
         // domain part length exceeded
         $isValid = false;
      }
      else if ($local[0] == '.' || $local[$localLen-1] == '.')
      {
         // local part starts or ends with '.'
         $isValid = false;
      }
      else if (preg_match('/\\.\\./', $local))
      {
         // local part has two consecutive dots
         $isValid = false;
      }
      else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain))
      {
         // character not valid in domain part
         $isValid = false;
      }
      else if (preg_match('/\\.\\./', $domain))
      {
         // domain part has two consecutive dots
         $isValid = false;
      }
      else if
(!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/',
                 str_replace("\\\\","",$local)))
      {
         // character not valid in local part unless
         // local part is quoted
         if (!preg_match('/^"(\\\\"|[^"])+"$/',
             str_replace("\\\\","",$local)))
         {
            $isValid = false;
         }
      }
      if ($isValid && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A")))
      {
         // domain not found in DNS
         $isValid = false;
      }
   }
   return $isValid;
}

?>
