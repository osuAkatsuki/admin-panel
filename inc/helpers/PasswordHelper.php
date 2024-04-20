<?php

class PasswordHelper {
	public static $dumb_passwords = [
		'password',
		'12345678',
		'123456789',
		'iloveyou',
		'adobe123',
		'1234567890',
		'photoshop',
		'sunshine',
		'password1',
		'princess',
		'trustno1',
		'passw0rd',
		'princess',
		'1234567890',
		'football',
		'jennifer',
		'superman',
	];

	public static function ValidatePassword($pass, $pass2 = null) {
		// Check password length
		if (strlen($pass) < 8) {
			return 'That password is <b>way</b> too short! Please make it at least 8 characters long.';
		}
		// Check if passwords match
		if ($pass2 !== null && $pass != $pass2) {
			return "barney is a dinosaur your password doesn't maaatch!";
		}
		// god damn i hate people
		if (in_array($pass, self::$dumb_passwords)) {
			return "D'ya know? Your password is dumb. It's also one of the most used around the entire internet. yup.";
		}

		return -1;
	}

	public static function CheckPass($u, $pass, $is_already_md5 = true) {
		if (empty($u) || empty($pass)) {
			return false;
		}
		if (!$is_already_md5) {
			$pass = md5($pass);
		}
		$uPass = $GLOBALS['db']->fetch('SELECT password_md5 FROM users WHERE username_safe = ?', [safeUsername($u)]);
		// Check it exists
		if ($uPass === false) {
			return false;
		}
		$res = password_verify($pass, $uPass['password_md5']);
		$additional_schiavo_text = "(fail)";
		if ($res) {
			$additional_schiavo_text = "(success)";
		}
		@Schiavo::Bunk("Login request from **" . getIP() . "** for user **" . $u . "** " . $additional_schiavo_text);
		return $res;
		exit;
		return true;
	}
}
