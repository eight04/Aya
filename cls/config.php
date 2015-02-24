<?php

class Config {
	
	public static $CONF = null;
	
	public static function init() {
		$CONF = [];
		include(Aya::$app_root . '/config.php');
		self::$CONF = $CONF;
	}
	
	public static function get($key) {
		if (isset(self::$CONF[$key])) {
			return self::$CONF[$key];
		}
		return null;
	}
	
	public static function set($key, $value) {
		if (is_array(self::$CONF)) {
			self::$CONF[$key] = $value;
		}
	}
}