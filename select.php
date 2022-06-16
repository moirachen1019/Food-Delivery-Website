<?php
    //error_reporting(E_ALL || ~E_NOTICE);
    session_start();
    include("connection.php");
    if(!isset($_SESSION['Account']))
    {
		header("Location: login.php");
		die;
    }
    $money = 0;
    $fee = 0;
    if(isset($_POST['shop_name_menu']) && isset($_POST['distance']))
    {   
        $shop_name_menu = $_POST['shop_name_menu'];
        $distance = $_POST['distance'];
        $fee = round(($distance/1000)*10);
        if($fee < 10){
            $fee = 10;
        }
        $_SESSION['fee'] = $fee;
        $_SESSION['shop_name_menu'] = $shop_name_menu;
    }
    if(isset($_POST['Calculate_The_Price']) /*&& isset($_POST['distance'])*/)
    {
        $recognize = $_POST['recognize']; //店名
        $type = $_POST['type'];
        if(!empty($type)){
            $stmtt = $conn->prepare("SELECT * FROM meal WHERE shopname = :recognize");
            $stmtt->execute(array("recognize"=>$recognize));
            if($stmtt->rowCount() > 0){
                $rows =  $stmtt->fetchAll(PDO::FETCH_ASSOC);
                $arr_q = array();
                $arr_m = array();
                foreach ($rows as $row){
                    $quantity = $_POST[$row["mealname"]."q"];
                    $meal = $row["mealname"];
                    if($quantity != 0){
                        array_push($arr_q,$quantity);
                        array_push($arr_m,$meal);
                        $money = $money + $quantity * $row['price'];
                    }
                }
            }
            if($type == "Pick-up"){
                $fee = 0;
                $_SESSION['fee'] = $fee;
            }
            $_SESSION['arr_q'] = $arr_q;
            $_SESSION['arr_m'] = $arr_m;
            $_SESSION['money'] = $money;
            header("Location: order.php");
            die;
        }
    }

?>
<?php if(isset($_POST['shop_name_menu'])){ ?>
    <div class="modal-content">
        <form method="post">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal">&times;</button>
            <h4 class="modal-title"><?php if(isset($_POST['shop_name_menu'])) {echo $shop_name_menu;} ?> Menu</h4>
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
                            <th scope="col">Order</th>
                        </tr>
                        </thead>
                        <tbody>
                            <?php
                            if(isset($_POST['shop_name_menu'])){
                                $stmt = $conn->prepare("SELECT * FROM meal WHERE shopname = :shop_name_menu ");
                                $stmt->execute(array("shop_name_menu"=>$shop_name_menu));
                                if($stmt->rowCount() > 0){
                                    $counts =  $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    $ii = 0;
                                    foreach ($counts as $count){
                                        $ii++;
                                        $img=$count["myFile"];
                                        $logodata = $img;
                            ?>
                                <tr>
                                    <th scope="row"><?php echo $ii?></th>
                                    <td><img src="data:<?php echo $count['myFile']?>;base64,<?php echo $logodata?>" width="70" height="70" /></td>
                                    <td><?php echo $count['mealname']?></td>
                                    <td><?php echo $count['price']?></td>
                                    <td><?php echo $count['quantity']?></td>
                                    <td>
                                        <input type="button" value="-" class="qtyminus" field="<?php echo $count['mealname']?>q" />
                                        <input type="text" name="<?php echo $count['mealname']?>q" value="0" class="qty" style="width: 40px;" />
                                        <input type="button" value="+" class="qtyplus" field="<?php echo $count['mealname']?>q" />
                                        <input type="hidden" value="<?php echo $shop_name_menu?>" name="recognize">
                                    </td>
                                </tr>
                                <?php } 
                                    }
                                }?>
                        </tobody>
                    </table>
                        <label class="control-label col-sm-1" for="type">type</label>
                        <div class="col-sm-5">
                            <select class="form-control" name="type">
                                <option></option>
                                <option id="Delivery">Delivery</option>
                                <option id="Pick-up">Pick-up</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <input type="submit" name="Calculate_The_Price" value="Calculate The Price" class="btn btn-default">
            </div>
        </form>
        </div>
    <?php } ?>
    <script>
        // This button will increment the value
        $(".qtyplus").click(function(e) {
        // Stop acting like a button
        e.preventDefault();
        // Get the field name
        fieldName = $(this).attr("field");
        // Get its current value
        var currentVal = parseInt($("input[name=" + fieldName + "]").val());
        // If is not undefined
        if (!isNaN(currentVal)) {
            // Increment
            $("input[name=" + fieldName + "]").val(currentVal + 1);
        } else {
            // Otherwise put a 0 there
            $("input[name=" + fieldName + "]").val(0);
        }
        });
        // This button will decrement the value till 0
        $(".qtyminus").click(function(e) {
        // Stop acting like a button
        e.preventDefault();
        // Get the field name
        fieldName = $(this).attr("field");
        // Get its current value
        var currentVal = parseInt($("input[name=" + fieldName + "]").val());
        if (!isNaN(currentVal) && currentVal > 0) {
            // Decrement one
            $("input[name=" + fieldName + "]").val(currentVal - 1);
        } else {
            // Otherwise put a 0 there
            $("input[name=" + fieldName + "]").val(0);
        }
        });
    </script>