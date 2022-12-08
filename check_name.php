<?php
    include("connection.php");
    try {
        if (!isset($_REQUEST['sname']) || empty($_REQUEST['sname']))
        {
        echo 'FAILED';
        exit();
        }
        
        $sname=$_REQUEST['sname'];
        
        $stmt = $conn->prepare("SELECT name FROM shop WHERE name=:sname");
        $stmt->execute(array('sname' => $sname));
        if ($stmt->rowCount() == 0)
        {
        echo 'YES';
        }
        else
        {
        echo 'NO';
        }
        
    }
    catch(Exception $e)
    { 
    echo 'FAILED';
    }

?>