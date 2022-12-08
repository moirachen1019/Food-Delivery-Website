<?php
	session_start();
	if(isset($_SESSION['Account']))
	{
		session_destroy();
		session_unset();
	}
	header("Location: login.php");
die;