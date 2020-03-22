<?php
// Copyright (c) 2020 Hari Saksena <hari.mail@protonmail.ch>
// 
// This software is released under the MIT License.
// https://opensource.org/licenses/MIT
namespace Antiquete\SQLWrapper;

use mysqli;
use Exception;
use DateTime;

class Database
{
	private $conn;

	function __construct($server, $dbuser, $dbpass, $dbname)
	{
		$this->conn = new mysqli($server, $dbuser, $dbpass, $dbname);
		if (!$this->conn) {
			trigger_error("MYSQL - Connecton Error: " . $this->conn->connect_error, E_USER_ERROR);
		} else {
			mysqli_set_charset($this->conn, 'utf8');		// Set proper charset for non-english font recognition.
		}
	}

	function __destruct()
	{
		$this->conn->close();
	}
	
	// -- SELECT
	
	function select($table, $wheres = [], $orderBy = "", $orderAsc = TRUE)
	{
		$sql = "select * from `$table`";
		reset($wheres);
		if (current($wheres) !== FALSE) {
			$sql .= " where `" . key($wheres) . "`='" . $this->conn->real_escape_string(current($wheres)) . "'";
			while (next($wheres) !== FALSE) {
				$sql .= " and `" . key($wheres) . "`='" . $this->conn->real_escape_string(current($wheres)) . "'";
			}
		}
		if ($orderBy != "") {
			$sql .= " ORDER BY `$orderBy`";
			if (!$orderAsc) {
				$sql .= " DESC";
			}
		}
		return $this->execute($sql);
	}

	function getRow($table, $wheres = [])
	{
		return $this->select($table, $wheres)->fetch_assoc();
	}
	
	function getRowById($table, $id)
	{
		return $this->select($table, array("id" => $id))->fetch_assoc();
	}
	
	function getVal($table, $wheres = [], $column)
	{
		return $this->getRow($table, $wheres)[$column];
	}
	
	// -- JOINS
	
	/**
	 * Returns a sql result array with for joined tables
	 *
	 * @param string $table1
	 * @param string $table2
	 * @param array $ons - List of all clauses within ON in "column" => "column" format
	 * @param array $wheres = [] - List of al clauses within WHERE in "column" => "value" format, defaults to no condition
	 * @param string $orderBy = "" - List of all ORDER BY in "column1, column2...." format, defaults to no order
	 * @param boolean $orderAsc = TRUE - Whether to order in ascending format, defaults to true
	 * @param string $extraConditions = "" - Any extra condition to apply on query in string format, defaults to nothing
	 * @param string $joinType = "INNER JOIN" - Type of join to use in string format, defaults to INNER JOIN
	 * @return void
	 */
	function selectJoin2($table1, $table2, $ons, $wheres = [], $orderBy = "", $orderAsc = TRUE, $extraConditions = "", $joinType = "INNER JOIN")
	{
		reset($ons);
		$fk1 = key($ons);
		$fk2 = current($ons);
		$sql = "select * from `$table1` $joinType `$table2` on $table1.$fk1 = $table2.$fk2";
		reset($wheres);
		if (current($wheres) !== FALSE) {
			$sql .= " where `" . key($wheres) . "`='" . $this->conn->real_escape_string(current($wheres)) . "'";
			while (next($wheres) !== FALSE) {
				$sql .= " and `" . key($wheres) . "`='" . $this->conn->real_escape_string(current($wheres)) . "'";
			}
		}
		$sql .= " " . $extraConditions;
		if ($orderBy != "") {
			$sql .= " ORDER BY `$orderBy`";
			if (!$orderAsc) {
				$sql .= " DESC";
			}
		}
		return $this->execute($sql);
	}
	
	// -- INSERT
	
	function insert($table, $inserts)
	{
		foreach ($inserts as &$val) {
			$val = $this->conn->real_escape_string($val);
		}

		$values = array_values($inserts);
		$keys = array_keys($inserts);
		return $this->execute('insert into `' . $table . '` (`' . implode('`,`', $keys) . '`) values (\'' . implode('\',\'', $values) . '\')');
	}
	
	function insert_id()
	{
		return $this->conn->insert_id;
	}
	
	// -- UPDATE
	
	function update($table, $vals, $wheres)
	{
		reset($vals);
		reset($wheres);
		if (current($vals) !== FALSE && current($wheres) !== FALSE) {
			$sql = "update `$table`";

			$sql .= " set `" . key($vals) . "`='" . $this->conn->real_escape_string(current($vals)) . "'";
			while (next($vals) !== FALSE) {
				$sql .= ",`" . key($vals) . "`='" . $this->conn->real_escape_string(current($vals)) . "'";
			}

			$sql .= " where `" . key($wheres) . "`='" . $this->conn->real_escape_string(current($wheres)) . "'";
			while (next($wheres) !== FALSE) {
				$sql .= " and `" . key($wheres) . "`='" . $this->conn->real_escape_string(current($wheres)) . "'";
			}

			return $this->execute($sql);
		} else {
			return FALSE;
		}
	}
	
	// -- DELETE
	
	function delete($table, $wheres)
	{
		reset($wheres);
		$sql = "delete from `$table`";
		if (current($wheres) !== FALSE) {
			$sql .= " where `" . key($wheres) . "`='" . $this->conn->real_escape_string(current($wheres)) . "'";
			while (next($wheres) !== FALSE) {
				$sql .= " and `" . key($wheres) . "`='" . $this->conn->real_escape_string(current($wheres)) . "'";
			}
			return $this->execute($sql);
		} else {
			return FALSE;
		}
	}
	
	// -- Transactions
	
	function startTransaction()
	{
		$this->query("START TRANSACTION");
	}

	function commit()
	{
		$this->query("COMMIT");
	}

	function rollback()
	{
		$this->query("ROLLBACK");
	}
	
	// -- Setting
	
	/*
	CREATE TABLE `settings` (
		`skey` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
		`sval` text COLLATE utf8mb4_unicode_ci NOT NULL,
		PRIMARY KEY (`skey`)
		)
		*/
		function getSetting($skey)
		{
			return $this->getVal("settings", array("skey" => $skey), "sval");
		}
		
	// -- Logging
	
	// Section Start - Logging Functions
	// Table `logs` required in database. Format defined below.
	// SQL Start
	/*
		CREATE TABLE `logs` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`title` text NOT NULL,
		`content` text NOT NULL,
		`log_time` datetime NOT NULL,
		PRIMARY KEY (`id`)
		) 
		*/
	// SQL End.

	function log($title, $content = "")
	{
		return $this->insert("logs", array("title" => $title, "content" => $content, "log_time" => $this->phptime()));
	}
	
	// -- Misc
	
	function real_escape($str)
	{
		return $this->conn->real_escape_string($str);
	}
	
	function phptime()
	{
		return (new DateTime())->format("Y-m-d H:i:s");
	}
	
	function execute($sqlquery) // NOTE: UNESCAPED SQL QUERY EXECUTE, use with caution
	{
		$result = $this->conn->query($sqlquery) or trigger_error("MYSQL - Query Failed! SQL: $sqlquery - Error: " . $this->conn->error, E_USER_ERROR);
		return $result;
	}
	
	function query($query) // NOTE: UNESCAPED SQL QUERY, use with caution
	{
		$result = $this->conn->query($query);
		if (!$result) {
			throw new Exception("SQL Error - Query: $query - Error: " . $this->conn->error);
		}
		return $result;
	}
}
