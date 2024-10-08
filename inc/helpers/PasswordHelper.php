<?php

class PasswordHelper
{
	public static function CheckPass($u, $pass, $is_already_md5 = true)
	{
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
		return $res;
		exit;
		return true;
	}
}
