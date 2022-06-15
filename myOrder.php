<?php 
  session_start();
    include("connection.php");
    include("functions.php");
    include("select.php");
    $cond = "";
    $Account = $_SESSION['Account'];
    $s_query = "SELECT * FROM orders WHERE user_account = :Account :cond";
    $stmt = $conn->prepare($s_query);
    $stmt->execute(array("Account"=>$Account, "cond"=>$cond));
    if (isset($_POST['refresh']))
    {
      $status = $_POST['status'];
      if(!empty($status))
      {
        if($status == "All"){
            //echo "<script>alert('all')</script>";
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
        $statusChange = "Cancel";
        $statusConfirm = "Not_Finish";
        try{
          $conn->beginTransaction();
          $stmt_d = $conn->prepare("UPDATE orders SET status = :statusChange WHERE OID = :whichOrderCancel AND status = :statusConfirm");
          $stmt_d->execute(array("statusChange"=>$statusChange, "whichOrderCancel"=>$whichOrderCancel, "statusConfirm"=>$statusConfirm));
          $conn->commit();
        }
        catch(Exception $e){
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
                  <select class="form-control" name="status">
                    <option id="All">All</option>
                    <option id="Finished">Finished</option>
                    <option id="Not_Finish">Not_Finish</option>
                    <option id="Cancel">Cancel</option>
                  </select>
                </div>
            </div>
            <input type="submit" name="refresh" value="Refresh" class="btn btn-primary" style="margin-left: 18px;">
          </form>

          <div class="row">
            <div class=" col-xs-8">
                <table class="table" style=" margin-top: 15px;">
                    <thead>
                    <tr>
                        <th scope="col">Order ID</th>
                        <th scope="col">Status</th>
                        <th scope="col">Start</th>
                        <th scope="col">End</th>
                        <th scope="col">Shop Name</th>
                        <th scope="col">Total Price</th>
                        <th scope="col">Order Details</th>
                        <th scope="col">Action</th>
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
                        <td><?php echo $i?></td>
                        <td><?php echo $row['status']?></td>
                        <td><?php echo $row['start']?></td>
                        <td><?php echo $row['end']?></td>
                        <td><?php echo $row['shop_name']?></td>
                        <td><?php echo $row['total_price']?></td>
                        <td>
                            <input type="button" class="btn btn-info openDetails" id="id<?php echo $row['OID']?>" value="Order Details"></button>
                        </td>
                        <td>
                          <?php if($row['status'] == "Not_finish"){ ?>
                            <form method="post">
                              <input type="hidden" name="whichOrderCancel" value="<?php echo $row['OID']?>">
                              <input type="submit" class="btn btn-danger" name="Cancel" value="Cancel">
                            </form>
                          <?php } ?>
                        </td>
                    </tr>
                    <?php
                        }
                    ?>
                    </tbody>
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