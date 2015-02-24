<?php

class Aya {
	public static $inited = false;

	public static $root = null;

	public static $app_root = null;

	public static function init ($root) {

		if (self::$inited) {
			return;
		}

		self::$inited = true;

		// App root
		self::$app_root = $root;

		// Aya root
		self::$root = __DIR__;

		// Register autoloader
		spl_autoload_register('Aya::autoload');

		// Include constants
		include(self::$app_root . '/const.php');

		// Include helpers
		include(self::$app_root . '/helper.php');

		// Load config
		Config::init();
	}

	private static function autoload ($cls) {

		$path = self::$root . '/cls/' . $cls . '.php';
		if (file_exists($path)) {
			include($path);
			return;
		}

		$path = self::$app_root . '/cls/' . $cls . '.php';
		if (file_exists($path)) {
			include($path);
			return;
		}

		return false;
	}

	public static function start ($root = null) {

		self::init(root);

		// Start routing
		include(self::$app_root . '/start.php');

		Session::start();

		Route::start();

		Session::stop();
	}

}
