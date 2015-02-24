<?php

/*

Route api instruction
=====================

Route::add(path, interface)
---------------------------
Path should be a string. Which can contains wild card (*) to match none-slash character.

Interface can be a closure.

Interface can be a class name. Route service will find if the class has properly static method get/post/put/delete. If the class has static method guard, route service will call it before calling post/put/delete methods.


Route::start()
--------------
Start routing. This is called from App::start().

*/

class Route {
	private static $jar = null;

	private static $route = null;

	private static $route_root = null;

	public static function add($path, $interface) {
		if (self::$jar === null) {
			self::$jar = [];
		}
		self::$jar[$path] = $interface;
	}

	public static function get_interface($path) {

		foreach (self::$jar as $route => $interface) {
			$re = preg_quote($route, '/');
			$re = str_replace('\*', '*', $re);
			$re = str_replace('*', '([^\/]*)', $re);

			$result = preg_match("/^$re$/", $path, $matches);

			if ($result) {
				return [
					'interface' => $interface,
					'matches' => $matches
				];
			}

			if ($result === 0) {
				continue;
			}

			if ($result === false) {
				throw new Exception('Regex error: ' . $re);
			}
		}

		return null;
	}

	public static function start() {
		try {
			echo self::routing();
		} catch (Exception $e) {
			echo $e->getMessage();
			die;
		}
	}

	private static function routing() {

		$url = substr($_SERVER["REQUEST_URI"], strlen(dirname($_SERVER["SCRIPT_NAME"])));
		$path = explode('?', $url)[0];

		$o = self::get_interface($path);

		if (!$o) {
			throw new Exception('404 - Invalid url');
		}

		$interface = $o['interface'];
		$matches = $o['matches'];

		array_shift($matches);

		if (is_array($matches) && count($matches) == 1) {
			$matches = $matches[0];
		}

		// Method
		$method = $_SERVER["REQUEST_METHOD"];

		// Page
		preg_match('/[^\/]*$/', $path, $m);
		$page = $m[0];

		// Parse filter
		$filter = $_GET;
		foreach ($filter as &$f) {
			if ($f === null) {
				$f = true;
			}
		}
		unset($f);

		// Parse data
		$data = null;
		if ($method == 'POST') {
			$data = $_POST;
		}
		if (!$data && $method != 'GET') {
			$data = json_decode(file_get_contents("php://input"), true);
		}

		// Get callback
		$o = self::get_interface_callback($interface, $method);

		if (!$o) {
			throw new Exception('500 - Can\'t find interface callback');
		}

		$callback = $o['callback'];
		$guard = $o['guard'];

		// Build param
		$options = [
			'method' => $method,
			'data' => $data,
			'filter' => $filter,
			'page' => $page
		];

		// Check guard
		if ($method != 'GET' && isset($guard)) {
			try {
				$pass = call_user_func($guard, $matches, $options);
			} catch (Exception $e) {
				throw new Exception('Guard error: ' . $e->getMessage());
			}
			if (is_string($pass)) {
				throw new Exception('Guard error: ' . $pass);
			}
			if (!$pass) {
				throw new Exception('Guard error: Permission denied');
			}
		}

		// Get reply
		$result = call_user_func($callback, $matches, $options);

		if ($result === null) {
			return 'Routing error: No reply';
		}

		if (is_array($result)) {
			return json_encode($result, JSON_UNESCAPED_UNICODE);
		}

		if (is_string($result)) {
			return $result;
		}

		if (is_numeric($result)) {
			return $result;
		}

		throw new Exception('Routing error: Unknown result type');
	}

	private static function get_interface_callback($interface, $method) {
		if (is_callable($interface, false, $name)) {
			if (strpos($name, '::') !== false) {
				$guard = explode('::', $name)[0] . '::guard';
				if (is_callable($guard)) {
					return [
						'guard' => $guard,
						'callback' => $interface
					];
				}
			}

			return [
				'guard' => null,
				'callback' => $interface
			];
		}

		$callback = [$interface, strtolower($method)];

		if (is_callable($callback)) {
			$guard = [$interface, 'guard'];
			if (is_callable($guard)) {
				return [
					'guard' => $guard,
					'callback' => $callback
				];
			} else {
				return [
					'guard' => null,
					'callback' => $callback
				];
			}
		}
	}
}
