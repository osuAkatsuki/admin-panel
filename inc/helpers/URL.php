<?php

class URL {
	public static function Avatar() {
		global $URL;

		return isset($URL['avatar']) ? $URL['avatar'] : 'https://a.akatsuki.pw';
	}
}
