<?php
    //error_reporting(E_ALL || ~E_NOTICE);
    session_start();
    include("connection.php");
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
                                $MIDs =  $stmt->fetchAll(PDO::FETCH_ASSOC);
                                $ii = 0;
                                foreach ($MIDs as $MID){
                                    $stmtt = $conn->prepare("SELECT * FROM meal WHERE ID = :MID ");
                                    $stmtt->execute(array("MID"=>$MID['MID']));
                                    $count =  $stmtt -> fetch(PDO::FETCH_ASSOC);
                                    $img=$count["myFile"];
                                    $logodata = $img;
                                    $ii++;
                            ?>
                                <tr>
                                    <th scope="row"><?php echo $ii?></th>
                                    <td><img src="data:<?php echo $count['myFile']?>;base64,<?php echo $logodata?>" width="70" height="70" /></td>
                                    <td><?php echo $count['mealname']?></td>
                                    <td><?php echo $count['price']?></td>
                                    <td><?php echo $MID['quantity']?></td>
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
