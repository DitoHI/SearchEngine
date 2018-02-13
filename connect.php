<?php
	function db() {
		static $conn;
		$host='localhost';
		$user='root';
		$pass='';
		$database='personal';
		if (is_null($conn)){
			$conn=mysqli_connect($host,$user,$pass,$database);
		}
		return $conn;
	}
?>