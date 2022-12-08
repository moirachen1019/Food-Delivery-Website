<?php 
  session_start();
    include("connection.php");
    include("functions.php");
    $Account = $_SESSION['Account'];
    $user_data = check_login($conn);
    $cond = "";
    $s_query = "SELECT * FROM orders WHERE user_account = :Account :cond";
    $stmt = $conn->prepare($s_query);
    $stmt->execute(array("Account"=>$Account, "cond"=>$cond));
    if (isset($_POST['status']))
    {
      $status = $_POST['status'];
      if(!empty($status))
      {
        if($status == "All"){
            $s_query = "SELECT * FROM orders WHERE user_account = :Account :cond";
        }
        else{
            if($status == "Finished"){
                $cond = "Finished";
            }
            else if($status == "Not_Finish"){
                $cond = "Not_Finish";
            }
            else if($status == "Cancel"){
                $cond = "Cancel";
            }
            $s_query = "SELECT * FROM orders WHERE (user_account = :Account and status = :cond)";
        }
        $stmtt = $conn->prepare($s_query);
        $stmtt->execute(array("Account"=>$Account, "cond"=>$cond));
      }
    }
    if (isset($_POST['Cancel']))
    {
      $whichOrderCancel = $_POST['whichOrderCancel'];
      cancel_one($whichOrderCancel);
    }
    if (isset($_POST['CancelAll']))
    {
      $stmt_all = $conn->prepare($s_query);
      $stmt_all->execute(array("Account"=>$Account, "cond"=>$cond));
      $rows = $stmt_all->fetchAll(PDO::FETCH_ASSOC);                    
      foreach ($rows as $row){
        $temp = "n".$row['OID'];
        if (isset($_POST[$temp])){
          $arr = explode("n", $temp);
          $whichOrderCancel = $arr[1];
          cancel_one($whichOrderCancel);
        }
      }
    }
    function cancel_one($whichOrderCancel)
    {
      include("connection.php");
      date_default_timezone_set('Asia/Taipei');
      $now = date("Y-m-d H:i:s");
      $statusChange = "Cancel";
      $stmt_confirm = $conn->prepare("SELECT * FROM orders WHERE OID = :whichOrderCancel");
      $stmt_confirm->execute(array("whichOrderCancel"=>$whichOrderCancel));
      $order_data =  $stmt_confirm -> fetch(PDO::FETCH_ASSOC);
      if($order_data['status'] == "Cancel"){
        echo "<script>alert('店家已取消訂單，刷新頁面即可')</script>";
      }
      else if($order_data['status'] == "Finished"){
        echo "<script>alert('店家已完成訂單，不可取消')</script>";
      }
      else{
        try
        {
          $conn->beginTransaction();
            $stmt_select_content = $conn->prepare("SELECT * FROM content WHERE OID =:whichOrderCancel");
            $stmt_select_content->execute(array("whichOrderCancel"=>$whichOrderCancel));
            $all_content = $stmt_select_content->fetchAll(PDO::FETCH_ASSOC);
            foreach($all_content as $content)
            {
              $stmt_addMeal = $conn->prepare("UPDATE meal SET quantity = (SELECT quantity FROM meal WHERE ID = :MID) + :add_q WHERE ID = :MID");
              $stmt_addMeal->execute(array("MID"=>$content['MID'], "add_q"=>$content['quantity']));   
            }
            $stmt_cancel = $conn->prepare("UPDATE orders SET status = :statusChange, end = :now WHERE OID = :whichOrderCancel");
            $stmt_cancel->execute(array("statusChange"=>$statusChange,"now"=>$now, "whichOrderCancel"=>$whichOrderCancel));
            # 使用者拿回錢
            $stmt_user_get=$conn->prepare("UPDATE users SET wallet = (SELECT wallet FROM users WHERE Account= :user) + :total WHERE Account= :user");
            $stmt_user_get->execute(array("user"=>$order_data['user_account'], "total"=>$order_data['total_price']));
            # 店家退款
            $stmt_shop_loss=$conn->prepare("UPDATE users SET wallet = (SELECT wallet FROM users WHERE Account= :shop_user) - :total WHERE Account= :shop_user");
            $stmt_shop_loss->execute(array("shop_user"=>$order_data['shop_account'], "total"=>$order_data['total_price']));
            # 使用者交易紀錄
            $action = "Receive";
            $add = '+'.$order_data['total_price'];
            $stmt10=$conn->prepare("INSERT into record (owner, action, time, trader, amount_change) values (:user, :action, :now, :shop_name, :add )");
            $stmt10->execute(array("user"=>$order_data['user_account'], "action"=>$action, "now"=>$now, "shop_name"=>$order_data['shop_name'], "add"=>$add));
            # 店家交易紀錄
            $action = "Payment";
            $sub = '-'.$order_data['total_price'];
            $stmt10=$conn->prepare("INSERT into record (owner, action, time, trader, amount_change) values (:shop_user, :action, :now, :user, :sub )");
            $stmt10->execute(array("shop_user"=>$order_data['shop_account'], "action"=>$action, "now"=>$now, "user"=>$order_data['user_account'], "sub"=>$sub));
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
      <li><a href="index.php">Home</a></li>
      <li><a href="shop.php">Shop</a></li>
      <li class="active"><a>My Order</a></li>
      <li><a href="shopOrder.php">Shop Order</a></li>
      <li><a href="transaction.php">Transaction Record</a></li>
      <li><a href="logout.php" tite="Logout">Logout</a></li>
    </ul>

    <div class="tab-content">
      <div id="home" class="tab-pane fade in active">
        <h3>Order</h3>

        <div class=" row col-xs-8">
          <form class="form-horizontal" method="post">
            <div class="form-group">
                <label class="control-label col-sm-1" for="status">Status</label>
                <div class="col-sm-5">
                <select class="form-control" name="status" onchange="this.form.submit()">
                    <option id="All">All</option>
                    <option id="Finished">Finished</option>
                    <option id="Not_Finish">Not_Finish</option>
                    <option id="Cancel">Cancel</option>
                  </select>
                </div>
            </div>
          </form>

          <div class="row">
            <div class=" col-xs-8">
            <form method="post">
            <input type="submit" class="btn btn-danger" name="CancelAll" value="Cancel Selected Orders">
              <table>
                <thead>
                  <tr>
                    <td>
                      <table class="table" style=" margin-top: 15px;">
                          <thead>
                            <tr>
                                <th scope="col"></th>
                                <th scope="col">Order ID</th>
                                <th scope="col">Status</th>
                                <th scope="col">Start</th>
                                <th scope="col">End</th>
                                <th scope="col">Shop Name</th>
                                <th scope="col">Total Price</th>
                                <th scope="col">Order Details</th>
                            </tr>
                          </thead>
                          <tbody>
                                <?php
                                  $stmttt = $conn->prepare($s_query);
                                  $stmttt->execute(array("Account"=>$Account, "cond"=>$cond));
                                  $rows = $stmttt->fetchAll(PDO::FETCH_ASSOC);                    
                                  $i = 0;
                                  foreach ($rows as $row){
                                      $i++;
                              ?>
                              <tr>
                                <?php if($row['status'] == "Not_finish"){ ?>
                                  <td><input type="checkbox" name="n<?php echo $row['OID']?>"></td>
                                <?php } 
                                else {?>
                                  <td>  </td>
                                <?php } ?>
                                <td><?php echo $i?></td>
                                <td><?php echo $row['status']?></td>
                                <td><?php echo $row['start']?></td>
                                <td><?php echo $row['end']?></td>
                                <td><?php echo $row['shop_name']?></td>
                                <td><?php echo $row['total_price']?></td>
                                <td>
                                    <input type="button" class="btn btn-info openDetails" id="id<?php echo $row['OID']?>" value="Order Details"></button>
                                </td>
                              </tr>
                              <?php
                                  }
                              ?>
                            </form>
                          </tbody>
                      </table>
                    </form>
                    <td>
                      <table class="table" style=" margin-top: 15px;">
                        <thead>
                          <tr>
                            <th scope="col"><br>Action</th>
                            <th></th>
                          </tr>
                        </thead>
                        <tbody>
                              <?php
                                    $stmttt = $conn->prepare($s_query);
                                    $stmttt->execute(array("Account"=>$Account, "cond"=>$cond));
                                    $rows = $stmttt->fetchAll(PDO::FETCH_ASSOC);                    
                                    $i = 0;
                                    foreach ($rows as $row){
                                        $i++;
                              ?>
                              <tr>
                                <td>
                                  <form method="post">
                                    <?php if($row['status'] == "Not_finish"){ ?>
                                        <input type="hidden" name="whichOrderCancel" value="<?php echo $row['OID']?>">
                                        <input type="submit" class="btn btn-danger" name="Cancel" value="Cancel">
                                    <?php } ?>
                                  </form>
                                </td>
                                <td><br><br><br>
                                </td>
                              </tr>
                              <?php }?>
                        </tbody>
                      </table>
                   </td>
                  <tr>
                </thead>
              </table>
            </div>  
        </div>

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


  <script>
    $(document).ready(function () {
      $('.openDetails').click(function(){
        var OID = $(this).attr("id");
        $.ajax({
        url: "details.php",
        type: "post",
        data: {OID : OID},
        success: function(data) {
          $('#menulist').html(data);
          $('#MenuModal').modal("show");
        }
        })
      });
      var option = document.getElementById("<?php if(isset($_POST['status'])){ echo $_POST['status']; } ?>");
      if(option){
          option.setAttribute('selected', 'selected');
      }
   })
  </script>

</body>

</html>