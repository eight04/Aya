<?php

/*

DB API instruction
==================

class ForeignContastrainError
-----------------------------
This was used to track insertinon/deletion foreign key error. But seems it's not reliable on MySQL.

Currently no use.


DB::con()
---------
Get the PDO object.


DB::active(id)
--------------
Use different database if you have multiple databse defined in config.php.


DB::last_id()
-------------
Get auto_increment id for last query.


DB::Q(string query, params...)
------------------------------
Involve a query.

Param could be arrays. They will be flatten before send to PDOStatement->execute().

Return PDOStatement->rowCount().


DB::get_row(string query, params...)
------------------------------------
Get a single row. Return DB::get_rows(query, params...)[0].


DB::get_rows(string query, params...)
-------------------------------------
Return the array of PDOStatement->fetchAll(PDO::FETCH_ASSOC).

*/

class ForeignConstraintError extends Exception {}

class DB {
	private static $pool = null;
	private static $current = null;
	public static function con() {
		if (!self::$pool) {
			self::$pool = [];
		}
		if (!self::$current) {
			$db = Config::get("DATABASE");
			reset($db);
			self::$current = key($db);
		}
		if (!isset(self::$pool[self::$current])) {
			$info = Config::get("DATABASE")[self::$current];

			try {
				$con = new PDO($info["DSN"], $info["USER"], $info["PASS"], [
					PDO::ATTR_EMULATE_PREPARES => false
				]);
			} catch (Exception $e) {
				throw new Exception('PDO Constructor failed');
			}

			self::$pool[self::$current] = $con;
		}
		return self::$pool[self::$current];
	}
	public static function active($id) {
		if (!isset(Config::get("DATABASE")[$id])) {
			throw new Exception("DB error: Invalid database");
		}
		self::$current = $id;
	}
	public static function close($id = null) {
		if ($id === null) {
			$id = self::$current;
		}
		if ($id === null) {
			return;
		}
		self::$pool[self::$current] = null;
	}
	public static function last_id() {
		return self::con()->lastInsertId();
	}
	private static function prepare($query, $param) {
		// Prepare statement
		$stmt = self::con()->prepare($query);
		if (!$stmt) {
			$error_info = self::con()->errorInfo();
			$message = "DB error: $error_info[2]\nError code: $error_info[0]";
			if ($error_info[0] == '23000') {
				throw new ForeignConstraintError($message);
			}
			throw new Exception($message);
		}

		// Execute
		$re = $stmt->execute($param);
		if (!$re) {
			throw new Exception('DB Error: Executing query failed');
		}

		return $stmt;
	}
	private static function throw_error($type) {
		$error = self::con()->errorInfo();
		$message = $error[2];
		$code = $error[0];
		$err = "$type $code\n$message";
		if ($code == '23000') {
			throw new ForeignConstraintError($err);
		}
		throw new Exception($err);
	}
	public static function error() {
		return self::con()->errorInfo()[2];
	}
	private static function query() {
		$args = func_get_args();

		$query = array_shift($args);

		$param = [];

		// Flatten params
		foreach ($args as $arg) {
			if (is_array($arg)) {
				$param = array_merge($param, $arg);
			} else {
				$param[] = $arg;
			}
		}

		return self::prepare($query, $param);
	}
	public static function Q() {
		return call_user_func_array('self::query', func_get_args())->rowCount();
	}
	public static function get_row() {
		$rows = call_user_func_array('self::get_rows', func_get_args());
		return isset($rows[0]) ? $rows[0] : null;
	}
	public static function get_rows() {
		$stmt = call_user_func_array('self::query', func_get_args());
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$rows = self::clean_type($rows, $stmt);


		return $rows;
	}
	private static function clean_type($rows, $stmt) {
		$col_count = $stmt->columnCount();

		// Get metas
		for ($i = 0; $i < $col_count; $i++) {
			$meta = $stmt->getColumnMeta($i);
			$name = $meta['name'];

			// Clean pass_hash
			if ($name == 'pass_hash' && $col_count > 1) {
				foreach ($rows as &$row) {
					unset($row[$name]);
				}
			}

			// Transform BIT to boolean
			if ($meta['native_type'] == 'BIT') {
				foreach ($rows as &$row) {
					$row[$name] = $row[$name] === null ? null : !!$row[$name];
				}
			}

			// Transform DECIMAL to float
			if ($meta['native_type'] == 'NEWDECIMAL') {
				foreach ($rows as &$row) {
					$row[$name] = (float) $row[$name];
				}
			}
		}

		return $rows;
	}
}

