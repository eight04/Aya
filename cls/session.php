<?php

class Session {

	public static $started = false;

	public static $clean = false;

	public static $ns = null;

	public static function start(){

		if (self::$started) {
			return;
		}

		session_start();

		self::$ns = Config::get('NAMESPACE');

		if (!self::$ns) {
			throw new Exception('Cant\'t start session. Need session namespace');
		}

		if (!isset($_SESSION[self::$ns])) {
			$_SESSION[self::$ns] = [];
		}
	}

	public static function set($key, $value) {
		$_SESSION[self::$ns][$key] = $value;
	}

	public static function get($key) {
		if (isset($_SESSION[self::$ns][$key])) {
			return $_SESSION[self::$ns][$key];
		}
		return null;
	}

	public static function clean() {
		self::$clean = true;
	}

	public static function stop() {
		if (self::$clean) {
			$_SESSION[self::$ns] = null;
		}
	}

	public static function raw() {
		return $_SESSION[self::$ns];
	}
}
