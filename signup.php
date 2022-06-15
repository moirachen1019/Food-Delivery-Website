<?php 

//session_start();
	include("connection.php");
	include("functions.php");
	if($_SERVER['REQUEST_METHOD'] == "POST")
	{
		//something was posted
		$name = $_POST['name'];
		$phonenumber = $_POST['phonenumber'];
		$Account = $_POST['Account'];
		$password = $_POST['password'];
		$repassword = $_POST['repassword'];
		$latitude = $_POST['latitude'];
		$longitude = $_POST['longitude'];
		if( (empty($name)&&$name!=0) || empty($phonenumber) || (empty($Account)&&$Account!=0) || (empty($password)&&$password!=0) || (empty($repassword)&&$repassword!=0) || (empty($latitude)&&$latitude!=0) || (empty($longitude)&&$longitude!=0) )
		{
			$error = '';
			if(empty($name)&&$name!=0){
				$tmp_name = "name ";
				$error = $error.$tmp_name;
			}
			if(empty($phonenumber)){
				$tmp_phonenumber = "phonenumber ";
				$error = $error.$tmp_phonenumber;
			}
			if(empty($Account)&&$Account!=0){
				$tmp_Account = "Account ";
				$error = $error.$tmp_Account;
			}
			if (empty($password)&&$password!=0){
				$tmp_password = "password ";
				$error = $error.$tmp_password;
			}
			if(empty($repassword)&&$repassword!=0){
				$tmp_repassword = "repassword ";
				$error = $error.$tmp_repassword;
			}
			if((empty($latitude)&&$latitude!=0)){
				$tmp_latitude = "latitude ";
				$error = $error.$tmp_latitude;
			}
			if((empty($longitude)&&$longitude!=0)){
				$tmp_longitude = "longitude ";
				$error = $error.$tmp_longitude;
			}
			$tmp_all = "is empty";
			$error.= $tmp_all;
			echo '<script> alert("'.$error.'")</script>';
		}
		else if( $password != $repassword)
		{
			echo "<script>alert('密碼和密碼驗證不相符')</script>";
		}
		else if (!is_numeric($phonenumber) || strlen($phonenumber) != 10  )
		{
			echo "<script>alert('手機格式錯誤')</script>";
		}
		else if(preg_match("/^[A-Za-z0-9]+$/",$Account)==false){
			echo "<script>alert('帳號僅能包含大小寫英文及數字')</script>";
		}
		else if(preg_match("/^[A-Za-z0-9]+$/",$password)==false){
			echo "<script>alert('密碼僅能包含大小寫英文及數字')</script>";
		}
		else if(preg_match("/^[A-Za-z]+$/",$name)==false){
			echo "<script>alert('名字僅能包含大小寫英文')</script>";
		}
		else if($latitude < -90 || $latitude > 90){
			echo "<script>alert('緯度範圍不正確')</script>";
		}
		else if($longitude < -180 || $longitude > 180){
			echo "<script>alert('經度範圍不正確')</script>";
		}
		else //if(!empty($Account))
		{
			$stmt = $conn->prepare("SELECT * FROM users WHERE Account = :Account LIMIT 1");
			$stmt->execute(array("Account"=>$Account));

			if($stmt->rowCount() > 0)
			{
				echo "<script>alert('帳號已被註冊')</script>";
			}
			else{
			$password = password_hash($password, PASSWORD_DEFAULT);
			$location =$longitude.' '.$latitude;
			$stmt = $conn->prepare("INSERT INTO users (name,phonenumber,Account,password,location,latitude,longitude)
			VALUES (:name,:phonenumber,:Account,:password,ST_GeomFromText(:point),:latitude,:longitude)");
			$stmt->execute(array("name"=>$name, "phonenumber"=>$phonenumber, "Account"=>$Account, "password"=>$password, ':point' => 'POINT(' .$location. ')', "latitude"=>$latitude, "longitude"=>$longitude));
			header("Refresh:0.5 ; url=login.php");
			echo "<script>alert('註冊成功')</script>";
			die;
			}
		}
	}

?>


<!DOCTYPE html>
<html class="no-js">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<title>DB_HW_Signup</title>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="description" content="Free HTML5 Template by FreeHTML5.co" />
		<meta name="keywords" content="free html5, free template, free bootstrap, html5, css3, mobile first, responsive" />
		<meta name="author" content="FreeHTML5.co" />
		<!-- Facebook and Twitter integration -->
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
		<script src="js/modernizr-2.6.2.min.js"></script>
	</head>
	<body>
		<div class="container">
			<div class="row">
				<div class="col-md-4 col-md-offset-4">
					<!-- Start Sign In Form -->
					<form method="post" class="fh5co-form animate-box">
						<h2>Sign Up</h2>
						<div class="form-group">
							<label for="name" class="sr-only">Name</label>
							<input type="text" class="form-control" id="name" name="name" placeholder="Name" autocomplete="off">
						</div>
						<div class="form-group">
							<label for="name" class="sr-only">phonenumber</label>
							<input type="text" class="form-control" id="phonenumber" name="phonenumber" placeholder="PhoneNumber" autocomplete="off">
						</div>
						<div class="form-group">
							<label for="Account" class="sr-only">Account</label>
							<input type="text" class="form-control" id="Account" name="Account" placeholder="Account" autocomplete="off" oninput="check_account(this.value);"><label id="msg"></label><br>
						</div>
						<div class="form-group">
							<label for="password" class="sr-only">Password</label>
							<input type="password" class="form-control" id="password" name="password" placeholder="Password" autocomplete="off">
						</div>
						<div class="form-group">
							<label for="repassword" class="sr-only">Re-type Password</label>
							<input type="password" class="form-control" id="repassword" name="repassword" placeholder="Re-type Password" autocomplete="off">
						</div>
						<div class="form-group">
							<label for="longitude" class="sr-only">longitude</label>
							<input type="text" class="form-control" id="longitude" name="longitude" placeholder="longitude" autocomplete="off">
						</div>
						<div class="form-group">
							<label for="latitude" class="sr-only">latitude</label>
							<input type="text" class="form-control" id="latitude" name="latitude" placeholder="Latitude" autocomplete="off">
						</div>

				
						<div class="form-group">
							<p>Already registered? <a href="login.php">Sign In</a></p>
						</div>
						<div class="form-group">
							<input type="submit" value="Sign Up" class="btn btn-primary">
						</div>
					</form>
					<!-- END Sign In Form -->
				</div>
			</div>
		</div>

		<script>
			function check_account(Account)
			{
				if (Account != ""){
					//alert("test");
					var xhttp = new XMLHttpRequest();
					xhttp.onreadystatechange = function() {
						var message;
						if (this.readyState == 4 && this.status == 200) {
							switch(this.responseText) {
							case 'YES':
							message='此帳號可被使用';
							break;
							case 'NO':
							message='此帳號已被註冊';
							break;
							default:
							message='Oops. There is something wrong.';
							break;
							}
							document.getElementById("msg").innerHTML = message;
							}
					};
					xhttp.open("POST", "check_account.php", true);
					xhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
					xhttp.send("Account="+Account);
				}
				else
				document.getElementById("msg").innerHTML = "";
			}
		</script>

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

