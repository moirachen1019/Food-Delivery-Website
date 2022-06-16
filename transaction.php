<?php 
  session_start();
    include("connection.php");
    include("functions.php");
    include("select.php");
    $cond = "";
    $owner = $_SESSION['Account'];
    $s_query = "SELECT * FROM record WHERE owner = :owner :cond";
    $stmt = $conn->prepare($s_query);
    $stmt->execute(array("owner"=>$owner, "cond"=>$cond));
    //if (isset($_POST['refresh']))
    //{
      $status = $_POST['status'];
      if(!empty($status))
      {
        if($status == "All"){
            $s_query = "SELECT * FROM record WHERE owner = :owner :cond";
        }
        else{
            if($status == "Payment"){
                $cond = "Payment";
            }
            else if($status == "Receive"){
                $cond = "Receive";
            }
            else if($status == "Recharge"){
                $cond = "Recharge";
            }
            $s_query = "SELECT * FROM record WHERE (owner = :owner and action = :cond)";
        }
        $stmtt = $conn->prepare($s_query);
        $stmtt->execute(array("owner"=>$owner, "cond"=>$cond));
      }
    //}
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
      <li><a href="myOrder.php">My Order</a></li>
      <li><a href="shopOrder.php">Shop Order</a></li>
      <li class="active"><a>Transaction Record</a></li>
      <li><a href="logout.php" tite="Logout">Logout</a></li>
    </ul>

    <div class="tab-content">
      <div id="home" class="tab-pane fade in active">
        <h3>Order</h3>

        <div class=" row col-xs-8">
          <form id="submit1" class="form-horizontal" method="post">
            <div class="form-group">
                <label class="control-label col-sm-1" for="record">Record</label>
                <div class="col-sm-5">
                  <select class="form-control" name="status" onchange="this.form.submit()">
                    <option id="All"></option>
                    <option id="All">All</option>
                    <option id="Payment">Payment</option>
                    <option id="Receive">Receive</option>
                    <option id="Recharge">Recharge</option>
                  </select>
                </div>
            </div>
            <!--<input type="submit" name="refresh" value="Refresh" class="btn btn-primary" style="margin-left: 18px;">-->
          </form>

          <div class="row">
            <div class=" col-xs-8">
                <table class="table" style=" margin-top: 15px;">
                    <thead>
                    <tr>
                        <th scope="col">Record ID</th>
                        <th scope="col">Action</th>
                        <th scope="col">Time</th>
                        <th scope="col">Trader</th>
                        <th scope="col">Amount change</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                        $stmttt = $conn->prepare($s_query);
                        $stmttt->execute(array("owner"=>$owner, "cond"=>$cond));
                        $rows = $stmttt->fetchAll(PDO::FETCH_ASSOC);                    
                        $i = 0;
                        foreach ($rows as $row){
                            $i++;
                    ?>
                    <tr>
                        <td><?php echo $i?></td>
                        <td><?php echo $row['action']?></td>
                        <td><?php echo $row['time']?></td>
                        <td><?php echo $row['trader']?></td>
                        <td><?php echo $row['amount_change']?></td>
                    </tr>
                    <?php
                        }
                    ?>
                    </tbody>
                </table>
            </div>  
        </div>


        </div>
      </div>
    </div>
  </div>



</body>

</html>