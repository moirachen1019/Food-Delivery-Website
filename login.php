<?php 
session_start();
	include("connection.php");
	include("functions.php");
	if($_SERVER['REQUEST_METHOD'] == "POST")
	{
		//something was posted
		$Account = $_POST['Account'];
		$password = $_POST['password'];
		if( (!empty($Account)||$Account==0) && (!empty($password)||$password==0))
		{
			//read from database
			$stmt = $conn->prepare("SELECT * FROM users WHERE Account = :Account LIMIT 1");
			$stmt->execute(array("Account"=>$Account));
			if($stmt->rowCount() > 0)
			{
				$user_data = $stmt->fetch(PDO::FETCH_ASSOC);
				if(password_verify($password, $user_data['password']))
				{
					$_SESSION['Account'] = $user_data['Account'];
					header("Location: index.php");
					die;
				}
				else{
					echo "<script>alert('登入失敗')</script>";
				}
			}
			else{
				echo "<script>alert('登入失敗')</script>";
			}

		}else{
			echo "<script>alert('登入失敗')</script>";
		}
	}

?>


<!DOCTYPE html>
<html class="no-js">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<title>DB_HW_SignIn</title>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="description" content="Free HTML5 Template by FreeHTML5.co" />
		<meta name="keywords" content="free html5, free template, free bootstrap, html5, css3, mobile first, responsive" />
		<meta name="author" content="FreeHTML5.co" />
		<meta property="og:title" content=""/>
		<meta property="og:image" content=""/>
		<meta property="og:url" content=""/>
		<meta property="og:site_name" content=""/>
		<meta property="og:description" content=""/>
		<meta name="twitter:title" content="" />
		<meta name="twitter:image" content="" />
		<meta name="twitter:url" content="" />
		<meta name="twitter:card" content="" />
		<!-- Place favicon.ico and apple-touch-icon.png in the root directory -->
		<link rel="shortcut icon" href="favicon.ico">
		<link href='https://fonts.googleapis.com/css?family=Open+Sans:400,700,300' rel='stylesheet' type='text/css'>
		<link rel="stylesheet" href="css/bootstrap.min.css">
		<link rel="stylesheet" href="css/animate.css">
		<link rel="stylesheet" href="css/style.css">
		<!-- Modernizr JS -->
		<script src="js/modernizr-2.6.2.min.js"></script>
	</head>
	<body>
		<div class="container">
			<div class="row">
				<div class="col-md-4 col-md-offset-4">
					<!-- Start Sign In Form -->
					<form method="post" class="fh5co-form animate-box">
						<h2>Sign In</h2>
						<div class="form-group">
							<label for="Account" class="sr-only">Account</label>
							<input type="text" class="form-control" id="Account" name="Account" placeholder="Account" autocomplete="off">
						</div>
						<div class="form-group">
							<label for="password" class="sr-only">Password</label>
							<input type="password" class="form-control" id="password" name="password" placeholder="Password" autocomplete="off">
						</div>
						<div class="form-group">
							<p>Not registered? <a href="signup.php">Sign Up</a> </p>
						</div>
						<div class="form-group">
							<input type="submit" value="Sign In" class="btn btn-primary">
						</div>
					</form>
					<!-- END Sign In Form -->
				</div>
			</div>
		</div>
	<!-- jQuery -->
	<script src="js/jquery.min.js"></script>
	<!-- Bootstrap -->
	<script src="js/bootstrap.min.js"></script>
	<!-- Placeholder -->
	<script src="js/jquery.placeholder.min.js"></script>
	<!-- Waypoints -->
	<script src="js/jquery.waypoints.min.js"></script>
	<!-- Main JS -->
	<script src="js/main.js"></script>
	</body>
</html>




