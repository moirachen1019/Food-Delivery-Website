<?php  
  session_start();
  include("connection.php");
    if(isset($_POST['pageindex']))
    {   
        $pageindex = $_POST['pageindex'];  
        $rows = $_SESSION['rows'];              
        if($rows != "none"){
            $i = 0;
            foreach ($rows as $row){
            $i++;
        $output ='
        <tr>
        <td>'.$i.'</td>
        <td>'.$row['name'].'</td>
        <td>'.$row['category'].'</td>
        <td>';
        if($row['distant'] <= 2000){
            printf("near");
          }
          else if($row['distant'] >= 5000){
            printf("far");
          }
          else{
            printf("medium");
          }
        $output .='
            </td>
            <td>
            <button type="button" class="btn btn-info openmenu" id='.$row['name'].'>Open menu</button>
            </td>
        </tr>';
            }
        }
        echo $output;
    }     