<?php
  session_start();
  include("connection.php");
  include("functions.php");
  $user_data = check_login($conn);
  $shop_data = check_shop($conn);
  $rows = "none";
  if($shop_data)
  {
    $temp = str_replace('POINT(',"",$_SESSION['shop_location']);
    $temp = str_replace(')',"",$temp);
    $temp_array = explode(" ",$temp);
    $true_latitude = $temp_array[1];
    $true_longitude = $temp_array[0];
    $rows = check_meal($conn,$shop_data['name']);
    $counts = check_meal($conn,$shop_data['name']);
    
    foreach ($counts as $count)
    {
      $meal_now = $count["ID"];
      if (isset($_POST['edit'.$meal_now]))
      {
        $edit_price = $_POST['p'.$meal_now];
        $edit_quantity = $_POST['q'.$meal_now];
        if(!(empty($edit_price)&&$edit_price!=0) && !(empty($edit_quantity)&&$edit_quantity!=0))
        {
          if(preg_match("/^((\d+)|(0+))$/",$edit_price)==false && preg_match("/^((\d+)|(0+))$/",$edit_quantity)==false){
            echo "<script>alert('價錢與數量須為非負整數')</script>";
          }
          else if(preg_match("/^((\d+)|(0+))$/",$edit_price)==false){
            echo "<script>alert('價錢須為非負整數')</script>";
          }
          else if(preg_match("/^((\d+)|(0+))$/",$edit_quantity)==false){
            echo "<script>alert('數量須為非負整數')</script>";
          }
          else{
            $stmt = $conn->prepare("UPDATE meal SET price = :edit_price, quantity = :edit_quantity WHERE ID = :meal_now");
            $stmt->execute(array("edit_price"=>$edit_price, "edit_quantity"=>$edit_quantity, "meal_now"=>$meal_now));
            $rows = check_meal($conn,$shop_data['name']);
            $counts = check_meal($conn,$shop_data['name']);
          }
        }
        else{
          if((empty($edit_price)&&$edit_price!=0) && (empty($edit_quantity)&&$edit_quantity!=0)){
            echo "<script>alert('價格與數量空白')</script>";
          }
          else if((empty($edit_price)&&$edit_price!=0)){
            echo "<script>alert('價格空白')</script>";
          }
          else if((empty($edit_quantity)&&$edit_quantity!=0)){
            echo "<script>alert('數量空白')</script>";
          }
        }
      }
      if (isset($_POST['d'.$meal_now])) { 
        $notfinish = "Not_finish";
        $stmt_confirm = $conn->prepare("SELECT * FROM content,orders WHERE MID = :MID AND content.OID = orders.OID AND orders.status = :notfinish");
        $stmt_confirm->execute(array("MID"=>$count["ID"],"notfinish"=>$notfinish));
        if($stmt_confirm->rowCount()==0)
        {
          $stmt = $conn->prepare("DELETE FROM meal WHERE ID = :meal_now");
          $stmt->execute(array("meal_now"=>$meal_now));
          $rows = check_meal($conn,$shop_data['name']);
          $counts = check_meal($conn,$shop_data['name']);
        }
        else
        {
          echo "<script>alert('尚有包含此餐點的訂單未完成，請勿刪除')</script>";
        }
      }
    }
  }
  $Account = $_SESSION['Account'];
  if (isset($_POST['shop_register']))
  {
    $shopname = $_POST['sname'];
    $category = $_POST['category'];
    $slatitude = $_POST['slatitude'];
    $slongitude = $_POST['slongitude'];
    if(empty($shopname) || empty($category) || (empty($slatitude)&&$slatitude!=0) || (empty($slongitude)&&$slongitude!=0))
    {
      
      $error = '';
			if(empty($shopname)){
				$tmp_shopname = "shop_name ";
				$error = $error.$tmp_shopname;
			}
			if(empty($category)){
				$tmp_category = "shop_category ";
				$error = $error.$tmp_category;
			}
			if((empty($slatitude)&&$slatitude!=0)){
				$tmp_slatitude = "latitude ";
				$error = $error.$tmp_slatitude;
			}
			if((empty($slongitude)&&$slongitude!=0)){
				$tmp_slongitude = "longitude ";
				$error = $error.$tmp_slongitude;
			}
			$tmp_all = "is empty";
			$error.= $tmp_all;
			echo '<script> alert("'.$error.'")</script>';
      //echo "<script>alert('欄位空白')</script>";
    }
    else if($slatitude < -90 || $slatitude > 90){
			echo "<script>alert('緯度範圍不正確')</script>";
		}
		else if($slongitude < -180 || $slongitude > 180){
			echo "<script>alert('經度範圍不正確')</script>";
		}
    else
    {
      $stmt = $conn->prepare("SELECT * FROM shop WHERE name = :shopname LIMIT 1");
			$stmt->execute(array("shopname"=>$shopname));

      if($stmt->rowCount() > 0)
      {
      echo "<script>alert('店名已被註冊')</script>";
      }
      else
      {
        $location =$slongitude.' '.$slatitude;

        $stmt = $conn->prepare("INSERT INTO shop (Account,name,category,location,latitude,longitude)
        VALUES (:Account, :shopname, :category, ST_GeomFromText(:point), :slatitude, :slongitude)");
        $stmt->execute(array("Account"=>$Account, "shopname"=>$shopname, "category"=>$category, 'point' => 'POINT(' .$location. ')', "slatitude"=>$slatitude, "slongitude"=>$slongitude));


        echo "<script>alert('註冊成功')</script>";
        $shop_data = check_shop($conn);
        $temp = str_replace('POINT(',"",$_SESSION['shop_location']);
        $temp = str_replace(')',"",$temp);
        $temp_array = explode(" ",$temp);
        $true_latitude = $temp_array[1];
        $true_longitude = $temp_array[0];
        header("shop.php");
      }
    }

  }
  if (isset($_POST['Add']))
  {
    $shopname=$shop_data['name'];
    $mname = $_POST['mname'];
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];

    //開啟圖片檔
    $file = "";
    $fileContents = "";
    if(file_exists($_FILES["myFile"]["tmp_name"])){
      $file = fopen($_FILES["myFile"]["tmp_name"], "rb");
      // 讀入圖片檔資料
      $fileContents = fread($file, filesize($_FILES["myFile"]["tmp_name"])); 
      //關閉圖片檔
      fclose($file);
      //讀取出來的圖片資料必須使用base64_encode()函數加以編碼：圖片檔案資料編碼
      $fileContents = base64_encode($fileContents);
    }
      if(empty($mname) || (empty($price)&&$price!=0) || (empty($quantity)&&$quantity!=0) || empty($fileContents))
      {
        $error = '';
        if(empty($mname)){
          $tmp_mname = "name ";
          $error = $error.$tmp_mname;
        }
        if((empty($price)&&$price!=0)){
          $tmp_price = "price ";
          $error = $error.$tmp_price;
        }
        if((empty($quantity)&&$quantity!=0)){
          $tmp_quantity = "quantity ";
          $error = $error.$tmp_quantity;
        }
        if(empty($fileContents)){
          $tmp_fileContents = "image ";
          $error = $error.$tmp_fileContents;
        }
        $tmp_all = "is empty";
        $error.= $tmp_all;
        echo '<script> alert("'.$error.'")</script>';
      }
      else if(preg_match("/^((\d+)|(0+))$/",$price)==false){
        echo "<script>alert('價錢須為非負整數')</script>";
      }
      else if(preg_match("/^((\d+)|(0+))$/",$quantity)==false){
        echo "<script>alert('數量須為非負整數')</script>";
      }
      else
      {
        $stmt = $conn->prepare("INSERT INTO meal (shopname,mealname,price,quantity,myFile)
        VALUES (:shopname,:mname,:price,:quantity,:fileContents)");
        $stmt->execute(array("shopname"=>$shopname, "mname"=>$mname, "price"=>$price, "quantity"=>$quantity, "fileContents"=>$fileContents));
        $rows = check_meal($conn,$shop_data['name']);
      }
  }


?>


<!doctype html>
<html lang="en">

<head>
	<!-- Required meta tags -->
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<!-- Bootstrap CSS -->
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
  <script src="check_name.js"></script>
	<title>DB_HW_UberEats</title>
</head>

<body>
  <nav class="navbar navbar-inverse">
    <div class="container-fluid">
      <div class="navbar-header">
        <a class="navbar-brand " href="#">DB_HW_UberEats</a>
      </div>
    </div>
  </nav>

  <div class="container">
    <ul class="nav nav-tabs">
      <li><a href="index.php">Home</a></li>
      <li class="active"><a>Shop</a></li>
      <li><a href="myOrder.php">My Order</a></li>
      <li><a href="shopOrder.php">Shop Order</a></li>
      <li><a href="transaction.php">Transaction Record</a></li>
      <li><a href="logout.php" tite="Logout">Logout</a></li>
    </ul>

    <div class="tab-content">
      <div id="shop" class="tab-pane fade in active">
        <div id="shop_reg_form">
          <h3> Start a business </h3>
            <form method ="post">
              <div class="form-group ">
                <div class="row">
                  <div class="col-xs-2">
                    <label for="sname">shop name</label>
                    <input type="text" class="form-control shop_reg" id="sname" name="sname" oninput="check_name(this.value);" ><label id="msg"></label><br>
                  </div>
                  <div class="col-xs-2">
                    <label for="ex6">shop category</label>
                    <input class="form-control shop_reg" id="ex6" name="category" type="text" >
                  </div>
                  <div class="col-xs-2">
                    <label for="ex8">longitude</label>
                    <input class="form-control shop_reg" id="ex8" name="slongitude" type="text" >
                  </div>
                  <div class="col-xs-2">
                    <label for="ex7">latitude</label>
                    <input class="form-control shop_reg" id="ex7" name="slatitude" type="text" >
                  </div>
                </div>
              </div>
              <div class=" row" style=" margin-top: 25px;">
                <div class=" col-xs-3">
                  <input type="submit" name="shop_register" value="register" class="btn btn-primary">
                </div>
              </div>
            </form>
        </div>

        <div id="shop_reg_display" style="display:none">
          <h3> My shop </h3>
            <div class="form-group ">
              <div class="row">
                <div class="col-xs-2">
                  <label>shop name</label>
                  <input class="form-control shop_reg" placeholder="<?php echo $shop_data['name']; ?>" type="text" readonly="readonly">
                </div>
                <div class="col-xs-2">
                  <label>shop category</label>
                  <input class="form-control shop_reg" placeholder="<?php echo $shop_data['category']; ?>" type="text" readonly="readonly">
                </div>
                <div class="col-xs-2">
                  <label>longitude</label>
                  <input class="form-control shop_reg" placeholder="<?php echo $true_longitude; ?>" type="text" readonly="readonly">
                </div>
                <div class="col-xs-2">
                  <label>latitude</label>
                  <input class="form-control shop_reg" placeholder="<?php echo $true_latitude; ?>" type="text" readonly="readonly">
                </div>
              </div>
            </div>
            <div class=" row" style=" margin-top: 25px;">
              <div class=" col-xs-3">
                <input type="submit" name="shop_register" value="register" class="btn btn-primary" disabled="disabled">
              </div>
            </div>
        </div>

        <hr>
        <h3>ADD</h3>
        <form class="form-group" method="post" enctype="multipart/form-data">
          <div class="row">
            <div class="col-xs-6">
              <label for="ex1">meal name</label>
              <input class="form-control" id="ex1" name="mname" type="text">
            </div>
          </div>
          <div class="row" style=" margin-top: 15px;">
            <div class="col-xs-3">
              <label for="ex2">price</label>
              <input class="form-control" id="ex2" name="price" type="text">
            </div>
            <div class="col-xs-3">
              <label for="ex3">quantity</label>
              <input class="form-control" id="ex3" name="quantity" type="text">
            </div>
          </div>
          <div class="row" style=" margin-top: 25px;">

            <div class=" col-xs-3">
              <label for="ex4">上傳圖片</label>
              <input id="myFile" type="file" name="myFile" multiple class="file-loading">
            </div>
            <div class=" col-xs-3">
              <input type="submit" name="Add" class="btn btn-primary" value="Add">
            </div>
          </div>
          </form>

        <div class="row">
          <div class="  col-xs-8">
            <table class="table" style=" margin-top: 15px;">
              <thead>
                <tr>
                  <th scope="col">#</th>
                  <th scope="col">Picture</th>
                  <th scope="col">meal name</th>
                  <th scope="col">price</th>
                  <th scope="col">Quantity</th>
                  <th scope="col">Edit</th>
                  <th scope="col">Delete</th>
                </tr>
              </thead>

              <tbody>
                <?php
                  if($rows != "none"){
                    $i = 0;
                    foreach ($rows as $row){
                      $i++;
                ?>
                  <tr>
                    <td><?php echo $i?></td>
                    <td><?php $img=$row["myFile"];
                              $logodata = $img;
                                  echo '<img src="data:'. $row['myFile'].';base64,' . $logodata . '"  width="70" height="70" />';?></td>
                    <td><?php echo $row['mealname']?></td>
                    <td><?php echo $row['price']?></td>
                    <td><?php echo $row['quantity']?></td>
                    <td><button type="button" class="btn btn-info" data-toggle="modal" data-target="#id<?php echo $row['ID']?>" >Edit</button></td>
                    <!-- Modal -->
                      <div class="modal fade" id="id<?php echo $row['ID']?>" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                        <form method="post">
                          <div class="modal-dialog" role="document">
                            <div class="modal-content">
                              <div class="modal-header">
                                <h5 class="modal-title" id="staticBackdropLabel"><?php echo $row['mealname']?> Edit</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                  <span aria-hidden="true">&times;</span>
                                </button>
                              </div>
                              <div class="modal-body">
                                <div class="row" >
                                  <div class="col-xs-6">
                                    <label>price</label>
                                    <input class="form-control" name="p<?php echo $row['ID']?>" type="text">
                                  </div>
                                  <div class="col-xs-6">
                                    <label>quantity</label>
                                    <input class="form-control" name="q<?php echo $row['ID']?>" type="text">
                                  </div>
                                </div>

                              </div>
                              <div class="modal-footer">
                                <input type="submit" class="btn btn-secondary" name="edit<?php echo $row['ID']?>" value="Edit">
                              </div>
                            </div>
                          </div>
                        </form>
              </div>
                      <form method="post">
                        <td>
                          <input type="submit" class="btn btn-danger" name="d<?php echo $row['ID']?>" value="Delete">
                        </td>
                      </form>
                    </tr>
                <?php
                  }
                }
                ?>
              </tbody>

            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
  <button>

  <script>
    $(document).ready(function () {
      var shop_exist = '<?php echo $shop_data["name"] ?>'
      //alert(shop_exist);
      if(shop_exist){
        document.getElementById('shop_reg_form').style.display='none';
        document.getElementById('shop_reg_display').style.display='block';
      }
    });
  </script>

</body>

</html>
