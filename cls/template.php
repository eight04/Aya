<?php

/*

Template API instruction
========================

string Template::get ( string $filename [, array $param ] )
-----------------------------------------------------------
Get specified template.

$param will be extract to context before including the template file.


void Template::put ( string $filename [, array $param ] )
---------------------------------------------------------
Print the template. Something like echo Template::get($filename, $param).

*/

class Template {

	private static $context = 0;

	public static $top = null;

	public static $path = null;

	public static function get($file, $param = null) {

		if (!self::$path) {
			self::$path = Config::get('TEMPLATE_PATH');
			if (!self::$path) {
				self::$path = 'template';
			}
		}

		$path = Aya::$app_root . '/' . self::$path . '/' . $file;

		if (file_exists($path)) {
			ob_start();

			if (is_array($param)) {
				extract($param);
			}

			self::context_start($file);

			include($path);

			self::context_end($file);
			return ob_get_clean();

		} else {
			return 'Template error: 404 not found. ' . $path;
		}
	}

	public static function redirect($path) {

		if ($path[0] == '/') {
			$path = Aya::$app_root . $path;
		}

		header('Location: ' . $path);
	}

	public static function put($file, $param = null) {
		echo self::get($file, $param);
	}

	private static function context_start($file) {

		if (self::$context == 0) {
			self::$top = $file;
		}
		self::$context++;
	}

	private static function context_end($file) {
		self::$context--;

		if (self::$context == 0) {
			self::$top = null;
		}
	}
}
