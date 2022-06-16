<?php
    //error_reporting(E_ALL || ~E_NOTICE);
    session_start();
    include("connection.php");
    if(!isset($_SESSION['Account']))
    {
		header("Location: login.php");
		die;
    }
    if(isset($_POST['OID'])){  
        $arr = explode("id", $_POST['OID']);
        $OID = $arr[1];
    }

?>
<?php if(isset($_POST['OID'])){ ?>
    <div class="modal-content">
        <form method="post">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal">&times;</button>
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
                            if(isset($_POST['OID'])){
                                $stmt = $conn->prepare("SELECT * FROM content WHERE OID = :OID ");
                                $stmt->execute(array("OID"=>$OID));
                                $meal_data =  $stmt->fetchAll(PDO::FETCH_ASSOC);

                                $stmt1 = $conn->prepare("SELECT myFile FROM meal WHERE shopname = :shop_name_menu and mealname = :mealn ");
                                $stmt1->execute(array("OID"=>$OID));
                                $meal_data1 =  $stmt1->fetchAll(PDO::FETCH_ASSOC);

                                $ii = 0;
                                foreach ($meal_data as $meal){
                                    //$img=$_GET[$meal["myFile"]];
                                    //$logodata = $img;
                                    $ii++;
                                    // $sql = "SELECT myFile FROM content WHERE OID = :OID ";
                                    // $sth = $conn->query($sql);
                                    // $result=mysqli_fetch_array($sth);
                                    $sth = $conn->prepare("SELECT myFile FROM content WHERE OID = :OID ");
                                    $sth->execute(array("OID"=>$OID));
                                    $result =  $sth->fetchAll(PDO::FETCH_ASSOC);
                                    
                            ?>
                                <tr>
                                    <th scope="row"><?php echo $ii?></th>
                                    <td><?php echo '<img src="images/'.$meal_data1[$ii].'">';?></td>
                                    <td><?php echo $meal['mealname']?></td>
                                    <td><?php echo $meal['price']?></td>
                                    <td><?php echo $meal['quantity']?></td>
                                </tr>
                                <?php 
                                    } 
                                }?>
                        </tobody>
                    </table>
                    </div>
                 </div>
            </div>

            <div class="modal-footer">
                <?php
                    $stmttt = $conn->prepare("SELECT * FROM orders WHERE OID = :OID ");
                    $stmttt->execute(array("OID"=>$OID));
                    $O_data =  $stmttt -> fetch(PDO::FETCH_ASSOC);
                ?>
                <div> Subtotal $ <?php echo $O_data['total_price']-$O_data['fee']?></div>
                <div> Delivery fee $ <?php echo $O_data['fee']?></div>
                <div> Total Price $ <?php echo $O_data['total_price']?></div>
            </div>
        </form>
        </div>
    <?php } ?>
