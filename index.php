<?php 
  session_start();
    include("connection.php");
    include("functions.php");
    include("select.php");
    $Account = $_SESSION['Account'];
    $user_data = check_login($conn);
    $pagecount = 0;
    if (isset($_POST['edit_location']))
    {
      $latitude = $_POST['latitude'];
      $longitude = $_POST['longitude'];
      if( (!empty($latitude)||$latitude==0) && (!empty($longitude)||$longitude==0))
      {
        //read from database
        if( ($latitude < -90 || $latitude > 90) && ($longitude < -180 || $longitude > 180) ){
          echo "<script>alert('緯度與經度範圍不正確')</script>";
        }
        else if($latitude < -90 || $latitude > 90){
          echo "<script>alert('緯度範圍不正確')</script>";
        }
        else if($longitude < -180 || $longitude > 180){
          echo "<script>alert('經度範圍不正確')</script>";
        }
        else{
          $location =$longitude.' '.$latitude;
          $stmt = $conn->prepare("UPDATE users SET location = ST_GeomFromText(:point), latitude = :latitude, longitude = :longitude WHERE Account = :Account");
          $stmt->execute(array("Account"=>$Account, "latitude"=>$latitude, "longitude"=>$longitude, ':point' => 'POINT(' .$location. ')'));
          $user_data = check_login($conn);
        }
      }
      else{
        if(empty($latitude) && empty($longitude)){
          echo "<script>alert('緯度與經度空白')</script>";

        }
        else if(empty($latitude)){
          echo "<script>alert('緯度範圍空白')</script>";

        }
        else if(empty($longitude)){
          echo "<script>alert('經度範圍空白')</script>";
        }
      }
    }
    if (isset($_POST['deposit']))
    {
      $extra_money = $_POST['money'];
      try{
        $conn->beginTransaction();  
          if(preg_match("/^[1-9][0-9]*$/" ,$extra_money)){
            $stmt = $conn->prepare("UPDATE users SET wallet = :wallet  WHERE Account= :Account");
            $money = $extra_money + $user_data['wallet'];
            $stmt->execute(array( "Account"=>$Account, "wallet"=> $money ));
            $action = "Recharge";
            date_default_timezone_set('Asia/Taipei');
            $now = date("Y-m-d H:i:s");
            $add = '+'.$extra_money;
            $stmt10=$conn->prepare("INSERT into record (owner, action, time, trader, amount_change) values (:user, :action, :now, :user, :add )");
            $stmt10->execute(array("user"=>$user_data['Account'], "action"=>$action, "now"=>$now, "add"=>$add));
            $user_data = check_login($conn);
            echo "<script>alert('儲值成功')</script>";
          }else{
            echo "<script>alert('輸入須為正整數')</script>";
          }
        $conn->commit();
      }
      catch(Exception $e)
      {
          if ($conn->inTransaction())
          $conn->rollBack();
          $msg=$e->getMessage();
          echo <<<EOT
          <!DOCTYPE html>
          <html>
              <body>
              <script>
              alert("$msg");
              window.location.replace("index.php");
              </script>
              </body>
          </html>
          EOT;
      }

      
    }
    $rows = "none";
    if (isset($_POST['search']))
    {

      $s_shop = $_POST['s_shop'];
      $s_category = $_POST['s_category'];
      $s_distance = $_POST['s_distance'];
      $s_meal = $_POST['s_meal'];
      $s_lowerprice = $_POST['s_lowerprice'];
      $s_upperprice = $_POST['s_upperprice'];
      $s_sort = $_POST['s_sort'];
      
      // 處理店家 filter
      if(empty($s_shop)){
        $s_shop = "%";
      }
      else{
        $s_shop = "%".$s_shop."%";
      }

      if(empty($s_category)){
        $s_category = "%";
      }
      else{
        $s_category = "%".$s_category."%";
      }

      $user_data_longitude = $user_data['longitude'];
      $user_data_latitude = $user_data['latitude'];
      if(empty($s_distance)){
        $location_condition = "";
      }
      else{
        if($s_distance == "near"){
          $location_condition = " AND ST_Distance_Sphere(POINT(:user_data_longitude,:user_data_latitude),location) <= 2000 ";
        }
        else if($s_distance == "medium"){
          $location_condition = " AND ST_Distance_Sphere(POINT(:user_data_longitude,:user_data_latitude),location) > 2000 AND ST_Distance_Sphere(POINT(:user_data_longitude,:user_data_latitude),location) < 5000 ";

        }
        else if($s_distance == "far"){
          $location_condition = " AND ST_Distance_Sphere(POINT(:user_data_longitude,:user_data_latitude),location) >= 5000 ";
        }
      }
      $arr = array( 's_shop'=>$s_shop, 's_category'=>$s_category, 'user_data_longitude'=> $user_data_longitude, 'user_data_latitude'=> $user_data_latitude );

      //處理餐點 filter
      $same_condition = "";
      $meal_condition = "";
      $price_condition = "";
      if( ! ( empty($s_meal) && empty($s_lowerprice) && empty($s_upperprice) ) ) //meal 全部為空以外 都要處理
      {
        $same_condition = " AND shopname = name ";
        if(!empty($s_meal)){
          $s_meal = "%".$s_meal."%";
          $meal_condition = " AND mealname LIKE :s_meal ";
          $arr['s_meal'] = $s_meal;
        }
        if(!empty($s_lowerprice) && !empty($s_upperprice)){
          $price_condition = " AND price > :s_lowerprice AND price < :s_upperprice ";
          $arr['s_lowerprice'] = $s_lowerprice;
          $arr['s_upperprice'] = $s_upperprice;
        }
        else if(!empty($s_lowerprice) && empty($s_upperprice)){
          $price_condition = " AND price >= :s_lowerprice ";
          $arr['s_lowerprice'] = $s_lowerprice;
        }
        else if(!empty($s_upperprice) && empty($s_lowerprice) ){
          $price_condition = $price_condition." AND price <= :s_upperprice ";
          $arr['s_upperprice'] = $s_upperprice;
        }
      }
      if(empty($s_sort)){
        $sort_condition = "";
      }
      else{
        if($s_sort == "Shop_Name_Ascending"){
          $sort_condition = "ORDER BY name";
        }
        else if($s_sort == "Shop_Name_Descending"){
          $sort_condition = "ORDER BY name DESC";
        }
        else if($s_sort == "Shop_Category_Ascending"){
          $sort_condition = "ORDER BY category";
        }
        else if($s_sort == "Shop_Category_Descending"){
          $sort_condition = "ORDER BY category DESC";
        }
        else if($s_sort == "Distance_Ascending"){
          $sort_condition = "ORDER BY ST_Distance_Sphere(POINT(:user_data_longitude,:user_data_latitude),location)";
        }
        else if($s_sort == "Distance_Descending"){
          $sort_condition = "ORDER BY ST_Distance_Sphere(POINT(:user_data_longitude,:user_data_latitude),location) DESC";
        }
      }
      $s_query = 
      "SELECT distinct name, category, ST_Distance_Sphere(POINT(:user_data_longitude,:user_data_latitude),location) AS distant 
      FROM shop,meal
      WHERE name LIKE :s_shop
      AND category LIKE :s_category
      $location_condition
      $same_condition
      $meal_condition
      $price_condition
      $sort_condition
      ";

      $stmtt = $conn->prepare($s_query);
      $stmtt->execute($arr);
      $s_result_count = $stmtt->rowCount() ;
      $pagecount = ceil($s_result_count / 5); //取得總頁數
      if(isset($_SESSION['Account'])){

      }
      if(isset($_SESSION['Account'])){

      }

      $_SESSION['pagecount'] = $pagecount;
      $_SESSION['s_shop'] = $s_shop;
      $_SESSION['s_category'] = $s_category;
      $_SESSION['user_data_longitude'] = $user_data_longitude;
      $_SESSION['s_meal'] = $s_meal;
      $_SESSION['s_lowerprice'] = $s_lowerprice;
      $_SESSION['s_upperprice'] = $s_upperprice;
      $_SESSION['user_data_latitude'] = $user_data_latitude;
      $_SESSION['s_query'] = $s_query;
      $_SESSION['arr'] = $arr;

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
  <link rel="stylesheet" href="css/new.css">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
  <!-- <script src="page.js"></script> -->
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
      <li class="active"><a>Home</a></li>
      <li><a href="shop.php">Shop</a></li>
      <li><a href="myOrder.php">My Order</a></li>
      <li><a href="shopOrder.php">Shop Order</a></li>
      <li><a href="transaction.php">Transaction Record</a></li>
      <li><a href="logout.php" tite="Logout">Logout</a></li>
    </ul>

    <div class="tab-content">
      <div id="home" class="tab-pane fade in active">
        <h3>Profile</h3>
        <div class="row">
          <div class="col-xs-12">
            <div>
              Account: <?php echo $user_data['Account']; ?>, Name: <?php echo $user_data['name']; ?><br>
              PhoneNumber: <?php echo $user_data['phonenumber']; ?><br>
              Longitude: <?php echo str_replace("POINT","",$user_data['longitude']); ?>, 
              Latitude: <?php echo str_replace("POINT","",$user_data['latitude']); ?>
            </div>
            <button type="button " style="margin-left: 5px;" class="btn btn-info " data-toggle="modal" data-target="#location">Edit location</button><br>
            <div class="modal fade" id="location"  data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdropLabel" aria-hidden="true">
              <div class="modal-dialog  modal-sm">
                <div class="modal-content">
                  <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Edit location</h4>
                  </div>
                  <form method="post">
                    <div class="modal-body">
                      <label class="control-label " for="longitude">longitude</label>
                      <input type="text" class="form-control" id="longitude" name="longitude" placeholder="enter longitude">
                      <br>
                      <label class="control-label " for="latitude">latitude</label>
                      <input type="text" class="form-control" id="latitude" name="latitude" placeholder="enter latitude">
                    </div>
                  <div class="modal-footer">
                    <input type="submit" name="edit_location" value="Edit" class="btn btn-default">
                  </div>
                  </form> 
                </div>
              </div>
            </div>
            <div>
              walletbalance: <?php echo $user_data['wallet']; ?><br>
            </div>
            <!-- Modal -->
            <button type="button " style="margin-left: 5px;" class=" btn btn-info " data-toggle="modal"
              data-target="#myModal">Add value</button>
            <div class="modal fade" id="myModal"  data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdropLabel" aria-hidden="true">
              <div class="modal-dialog  modal-sm">
                <div class="modal-content">
                  <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Add value</h4>
                  </div>
                  <form method="post">
                    <div class="modal-body">
                      <input type="text" class="form-control" id="value" name="money" placeholder="enter add value">
                    </div>
                    <div class="modal-footer">
                      <!--<button type="button" class="btn btn-default" data-dismiss="modal">Add</button>-->
                      <input type="submit" name="deposit" value="Add" class="btn btn-default">
                    </div>
                  </form> 
                </div>
              </div>
            </div>
          </div>
        </div>

        <h3>Search</h3>
        <div class=" row col-xs-8">
          <form class="form-horizontal" method="post">
            <div class="form-group">
              <label class="control-label col-sm-1" for="Shop">Shop</label>
              <div class="col-sm-5">
                <input type="text" class="form-control" name="s_shop" placeholder="Enter Shop name" value="<?php if(isset($_POST['s_shop'])){ echo $_POST['s_shop']; } ?>">
              </div>
              <label class="control-label col-sm-1" for="distance">distance</label>
              <div class="col-sm-5">
                <select class="form-control" name="s_distance" id="sel1">
                  <option></option>
                  <option id="near">near</option>
                  <option id="medium">medium</option>
                  <option id="far">far</option>
                </select>
              </div>
            </div>
            <div class="form-group">
              <label class="control-label col-sm-1" for="Price">Price</label>
              <div class="col-sm-2">
                <input type="text" name="s_lowerprice" class="form-control" value="<?php if(isset($_POST['s_lowerprice'])){ echo $_POST['s_lowerprice']; } ?>">
              </div>
              <label class="control-label col-sm-1" for="~">~</label>
              <div class="col-sm-2">
                <input type="text" name="s_upperprice" class="form-control" value="<?php if(isset($_POST['s_upperprice'])){ echo $_POST['s_upperprice']; } ?>">
              </div>
              <label class="control-label col-sm-1" for="Meal">Meal</label>
              <div class="col-sm-5">
                <!-- <input type="text" list="Meals" name="s_meal" class="form-control" id="Meal" placeholder="Enter Meal"> -->
                <input type="text" name="s_meal" class="form-control" id="Meal" placeholder="Enter Meal" value="<?php if(isset($_POST['s_meal'])){ echo $_POST['s_meal']; } ?>">
                <!-- <datalist id="Meals">
                  <option value="Hamburger">
                  <option value="coffee">
                </datalist> -->
              </div>
            </div>
            <div class="form-group">
              <label class="control-label col-sm-1" for="category"> Category</label>
                <div class="col-sm-5">
                  <input type="text" list="categorys" name="s_category" class="form-control" id="category" placeholder="Enter shop category" value="<?php if(isset($_POST['s_category'])){ echo $_POST['s_category']; } ?>">
                  <datalist id="categorys">
                    <option value="fastfood">
                    <option value="drink">
                    <option value="spaghetti">
                    <option value="dumpling">
                    <option value="steak">
                  </datalist>
                </div>
                <label class="control-label col-sm-1" for="sort"> Sort</label>
                <div class="col-sm-5">
                  <select class="form-control" name="s_sort">
                    <option></option>
                    <option id="Shop_Name_Ascending">Shop_Name_Ascending</option>
                    <option id="Shop_Name_Descending">Shop_Name_Descending</option>
                    <option id="Shop_Category_Ascending">Shop_Category_Ascending</option>
                    <option id="Shop_Category_Descending">Shop_Category_Descending</option>
                    <option id="Distance_Ascending">Distance_Ascending</option>
                    <option id="Distance_Descending">Distance_Descending</option>
                  </select>
                </div>
            </div>
            <input type="submit" name="search" value="Search" class="btn btn-primary" style="margin-left: 18px;">
          </form>
        </div>

        <div class="row">
          <div class=" col-xs-8">
            <table class="table" style=" margin-top: 15px;">
                <thead>
                  <tr>
                    <th scope="col">#</th>
                    <th scope="col">shop name</th>
                    <th scope="col">shop category</th>
                    <th scope="col">Distance</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                    if (!isset($_GET["page"])){ //假如$_GET["page"]未設置
                        $page = 1 ; //則在此設定起始頁數
                    } else {
                        $page = intval($_GET["page"]); //確認頁數只能夠是數值資料
                    }
                    $start = ($page - 1) * 5;
                    if(isset($_SESSION['s_shop'])){
                      $s_shop = $_SESSION['s_shop'];
                    }
                    if(isset($_SESSION['s_category'])){
                      $s_category = $_SESSION['s_category'];
                    }
                    if(isset($_SESSION['user_data_longitude'])){
                      $user_data_longitude = $_SESSION['user_data_longitude'];
                    }
                    if(isset($_SESSION['user_data_latitude'])){
                      $user_data_latitude = $_SESSION['user_data_latitude'];
                    }
                    if(isset($_SESSION['s_meal'])){
                      $s_meal = $_SESSION['s_meal'];
                    }
                    if(isset($_SESSION['s_lowerprice'])){
                      $s_lowerprice = $_SESSION['s_lowerprice'];
                    }
                    if(isset($_SESSION['s_upperprice'])){
                      $s_upperprice = $_SESSION['s_upperprice'];
                    }
                    if(isset($_SESSION['s_query']) && isset($_SESSION['arr'])){
                      $s_query = $_SESSION['s_query'];
                      $arr = $_SESSION['arr'];
                      $new_s_query = $s_query."LIMIT ".$start .",5";
                      $stmttt = $conn->prepare($new_s_query);
                      $stmttt->execute($arr);
                      $rows = $stmttt->fetchAll(PDO::FETCH_ASSOC);                    
                    }
                    if($rows != "none"){
                      $i = 0;
                      foreach ($rows as $row){
                        $i++;
                  ?>
                  <tr>
                    <td><?php echo $i?></td>
                    <td><?php echo $row['name']?></td>
                    <td><?php echo $row['category']?></td>
                    <td>
                      <?php
                        //echo $row['distant']
                        if($row['distant'] <= 2000){
                          printf("near");
                        }
                        else if($row['distant'] >= 5000){
                          printf("far");
                        }
                        else{
                          printf("medium");
                        }
                        printf("(".$row['distant']."m)")
                      ?>
                    </td>
                    <td>
                        <input type="button" class="btn btn-info openmenu" id="<?php echo $row['name'] ?>" name="<?php echo $row['distant'] ?>" value="Open menu"></button>
                    </td>
                  </tr>
                  <?php
                      }
                    }
                  ?>
                </tbody>

              </table>

              <?php
                  //分頁頁碼
                  if(isset($_SESSION['pagecount'])){
                    $pagecount = $_SESSION['pagecount'];
                  }
                  for($k = 1; $k <= $pagecount ; $k++) {
                    echo "<a href=?page=".$k.">".$k."</a> ";
                  }
              ?>

                  <!-- Modal Start -->
                  <div class="modal fade" id="MenuModal" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                    <div class="modal-dialog" id="menulist">
                    </div>
                  </div>
                  <!-- Modal End -->
          </div>  
        </div>

      </div>
    </div>
  </div>

  <script>
    $(document).ready(function () {

      $('.openmenu').click(function(){
        var shop_name_menu = $(this).attr("id");
        var distance = $(this).attr("name");
        $.ajax({
        url: "select.php",
        type: "post",
        data: {shop_name_menu : shop_name_menu, distance : distance},
        success: function(data) {
          $('#menulist').html(data);
          $('#MenuModal').modal("show");
        }
        })
      });

    var sort_option = document.getElementById("<?php if(isset($_POST['s_sort'])){ echo $_POST['s_sort']; } ?>");
    var distance_option = document.getElementById("<?php if(isset($_POST['s_distance'])){ echo $_POST['s_distance']; } ?>");
    if(sort_option){
      sort_option.setAttribute('selected', 'selected');
    }
    if(distance_option){
      distance_option.setAttribute('selected', 'selected');
    }

   })
  </script>
</body>

</html>