<?php
    include("connection.php");
    try {
        if (!isset($_REQUEST['Account']) || empty($_REQUEST['Account']))
        {
        echo 'FAILED';
        exit();
        }
        
        $Account=$_REQUEST['Account'];
        
        $stmt = $conn->prepare("SELECT Account FROM users WHERE Account=:Account");
        $stmt->execute(array('Account' => $Account));
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