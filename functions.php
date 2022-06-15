<?php
	function check_login($conn)
	{
		if(isset($_SESSION['Account']))
		{
			$Account = $_SESSION['Account'];
			$stmt = $conn->prepare("SELECT * FROM users WHERE Account = :Account LIMIT 1");
			$stmt->execute(array("Account"=>$Account));
			if($stmt->rowCount() > 0)
			{
				$user_data = $stmt -> fetch(PDO::FETCH_ASSOC);
				$stmt = $conn->prepare("SELECT ST_AsText(location) AS location FROM users WHERE Account = :Account");
				$stmt->execute(array("Account"=>$Account));
				$l_data = $stmt -> fetch(PDO::FETCH_ASSOC);
				$_SESSION['user_location'] = $l_data['location'];

				return $user_data;
			}
		}
		//redirect to login
		header("Location: login.php");
		die;
	}

	function check_shop($conn)
	{
		if(isset($_SESSION['Account']))
		{
			$Account = $_SESSION['Account'];
			$stmt = $conn->prepare("SELECT * FROM shop WHERE Account = :Account LIMIT 1");
			$stmt->execute(array("Account"=>$Account));
			if($stmt->rowCount() > 0)
			{
				$shop_data = $stmt -> fetch(PDO::FETCH_ASSOC);
				$stmt = $conn->prepare("SELECT ST_AsText(location) AS location FROM shop WHERE Account = :Account");
				$stmt->execute(array("Account"=>$Account));
				$l_data = $stmt -> fetch(PDO::FETCH_ASSOC);
				$_SESSION['shop_location'] = $l_data['location'];

				return $shop_data;
			}
		}
	}

	function  check_meal($conn,$shopname)
	{
		$stmt = $conn->prepare("SELECT * FROM meal WHERE shopname = :shopname ");
		$stmt->execute(array("shopname"=>$shopname));
		$meal_data = $stmt -> fetchAll(PDO::FETCH_ASSOC);
		return $meal_data;
	}
	