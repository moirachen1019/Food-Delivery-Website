<?php
    session_start();
    include("connection.php");
    if(isset($_SESSION['shop_name_menu'])){
        $shop_name_menu = $_SESSION['shop_name_menu'];
    }
    if(isset($_SESSION['arr_q'])){
        $arr_q = $_SESSION['arr_q'];
        $arrLength = count($arr_q);
    }
    if(isset($_SESSION['arr_m'])){
        $arr_m = $_SESSION['arr_m'];
    }
    if(isset($_SESSION['money'])){
        $money = $_SESSION['money'];
    }
    if(isset($_SESSION['fee'])){
        $fee = $_SESSION['fee'];
    }
    if(isset($_POST['Order'])){
        $now = date("Y-m-d H:i:s");
        $Account = $_SESSION['Account'];
        $total = $fee+$money;
        $status = "Not_finish";
        try
        {
            $conn->beginTransaction();  
                # 產生 order 紀錄
                $stmt=$conn->prepare("INSERT into orders (status, start, end, shop_name, user_account, fee, total_price) values (:status, :now, :now, :shop_name_menu, :Account, :fee, :total )");
                $stmt->execute(array("status"=>$status, "now"=>$now, "shop_name_menu"=>$shop_name_menu, "Account"=>$Account, "fee"=>$fee, "total"=>$total));
                # get order ID
                $stmt2=$conn->prepare("SELECT LAST_INSERT_ID() as 'OID'");
                $stmt2->execute();
                $OID_now = $stmt2 -> fetch(PDO::FETCH_ASSOC);
                $OID_now = $OID_now['OID'];
                for($ii = 0; $ii < $arrLength; $ii++) {
                    # 分別取得 meal 資料
                    $stmt3 = $conn->prepare("SELECT * FROM meal WHERE shopname = :shop_name_menu and mealname = :mealn ");
                    $stmt3->execute(array("shop_name_menu"=>$shop_name_menu, "mealn"=>$arr_m[$ii]));
                    #echo "<script>alert('$arr_m[$ii]')</script>";
                    $m_data = $stmt3 -> fetch(PDO::FETCH_ASSOC);
                    $q = $arr_q[$ii];
                    $S_ID = $m_data['ID'];
                    $new_q = $m_data['quantity'] - $q;
                    # order 的 meal list
                    $stmt4=$conn->prepare("INSERT into content (OID, MID, quantity) values (:OID_now, :S_ID, :q)");
                    $stmt4->execute(array("OID_now"=>$OID_now, "S_ID"=>$S_ID, "q"=>$q));
                    # meal 數量更新
                    $stmt5=$conn->prepare("UPDATE meal SET quantity = :new_q WHERE shopname = :shop_name_menu and mealname = :mealn ");
                    $stmt5->execute(array("new_q"=>$new_q, "shop_name_menu"=>$shop_name_menu, "mealn"=>$arr_m[$ii]));
                    try{
                        $stmt3 = $conn->prepare("SELECT * FROM meal WHERE shopname = :shop_name_menu and mealname = :mealn ");
                        $stmt3->execute(array("shop_name_menu"=>$shop_name_menu, "mealn"=>$arr_m[$ii]));

                    }catch(Exception $e){
                        if ($conn->inTransaction())
                        $conn->rollBack();
                        $msg=$e->getMessage();
                        echo <<<EOT
                        <!DOCTYPE html>
                        <html>
                            <body>
                            <script>
                            alert("商品已被移除");
                            window.location.replace("index.php");
                            </script>
                            </body>
                        </html>
                        EOT;

                    }
                }
                # 使用者扣款
                $stmt6=$conn->prepare("SELECT wallet FROM users WHERE Account= :Account");
                $stmt6->execute(array("Account"=>$Account));
                $wallet = $stmt6 -> fetch(PDO::FETCH_ASSOC);
                $wallet = $wallet['wallet'];
                
                if($wallet - $total < 0){
                    echo "<script>alert('餘額不足')</script>";
                    $conn->rollBack();          
                }else if(preg_match("/^((-\d+)|(0+))$/" ,$q)){
                    echo "<script>alert('輸入的數量需為正整數')</script>";
                    $conn->rollBack();
                }else if($new_q<0){
                    echo "<script>alert('店家商品數量不足')</script>";
                    $conn->rollBack();
                }
                else{
                    $stmt7=$conn->prepare("UPDATE users SET wallet = (:wallet - :total) WHERE Account= :Account");
                    $stmt7->execute(array("wallet"=>$wallet, "total"=>$total, "Account"=>$Account));
                }

                

                # 店家收款
                $stmt8=$conn->prepare("SELECT Account FROM shop WHERE name= :shop_name_menu");
                $stmt8->execute(array("shop_name_menu"=>$shop_name_menu));
                $shop_Account = $stmt8 -> fetch(PDO::FETCH_ASSOC);
                $shop_Account = $shop_Account['Account'];
                $stmt9=$conn->prepare("UPDATE users SET wallet = ((SELECT wallet FROM users WHERE Account = :shop_Account) + :total) WHERE Account= :shop_Account");
                $stmt9->execute(array("shop_Account"=>$shop_Account, "total"=>$total));
                # 使用者交易紀錄
                $action = "Payment";
                $sub = '-'.$total;
                $stmt10=$conn->prepare("INSERT into record (owner, action, time, trader, amount_change) values (:Account, :action, :now, :shop_name_menu, :sub )");
                $stmt10->execute(array("Account"=>$Account, "action"=>$action, "now"=>$now, "shop_name_menu"=>$shop_name_menu, "sub"=>$sub));
                # 店家交易紀錄
                $action = "Receive";
                $add = '+'.$total;
                $stmt10=$conn->prepare("INSERT into record (owner, action, time, trader, amount_change) values (:shop_Account, :action, :now, :Account, :add )");
                $stmt10->execute(array("Account"=>$Account, "action"=>$action, "now"=>$now, "shop_Account"=>$shop_Account, "add"=>$add));
            $conn->commit();
            header("Refresh:0.5 ; url=index.php");
            echo "<script>alert('訂購成功')</script>";
            die;

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
    <div class="modal-content" style="margin : 50px 200px;">
            <form method="post">
            <div class="modal-header">
                <a href="index.php" class="close">x</a>
                <h4 class="modal-title">Order</h4>
            </div>

            <div class="modal-body">
                <div class="row">
                    <div class="col-xs-12">
                        <table class="table" style=" margin-top: 15px;">
                            <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Picture</th>
                                <th scope="col">Meal name</th>
                                <th scope="col">Price</th>
                                <th scope="col">Quantity</th>
                            </tr>
                            </thead>
                            <tbody>
                                <?php
                                    for($i = 0; $i < $arrLength; $i++) {
                                        $stmt = $conn->prepare("SELECT * FROM meal WHERE shopname = :shop_name_menu and mealname = :mealn ");
                                        $stmt->execute(array("shop_name_menu"=>$shop_name_menu, "mealn"=>$arr_m[$i]));
                                        $m_data = $stmt -> fetch(PDO::FETCH_ASSOC);
                                        $img = $m_data["myFile"];
                                        $logodata = $img;
                                ?>
                                    <tr>
                                        <th scope="row"><?php echo $i+1?></th>
                                        <td><img src="data:<?php echo $m_data['myFile']?>;base64,<?php echo $logodata?>" width="70" height="70" /></td>
                                        <td><?php echo $m_data['mealname']?></td>
                                        <td><?php echo $m_data['price']?></td>
                                        <td><?php echo $arr_q[$i]?></td>
                                    </tr>
                                    <?php } ?>
                            </tobody>
                        </table>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <div> Subtotal $ <?php echo $money?></div>
                    <div> Delivery fee $ <?php echo $fee?></div>
                    <div> Total Price $ <?php echo $fee+$money?></div>
                    <input type="submit" name="Order" value="Order" class="btn btn-default">
                </div>
        </form>
        </div>
</body>