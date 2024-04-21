<?php

class URL {
	public static function Avatar() {
		global $URL;

		return isset($URL['avatar']) ? $URL['avatar'] : 'https://a.akatsuki.pw';
	}

	public static function Server() {
		global $URL;

		return isset($URL['server']) ? $URL['server'] : 'https://akatsuki.pw';
	}

	public static function Bancho() {
		global $URL;

		return isset($URL['bancho']) ? $URL['bancho'] : 'http://c.akatsuki.pw';
	}
}
