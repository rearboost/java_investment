    <?php
        // Database Connection
        require '../include/config.php';
        require '../include/check.php';

        if(isset($_POST['checkFlag'])){
            // $Availability = mysqli_query($conn, "SELECT * FROM temp_collection");
            // $RowCount = mysqli_num_rows($Availability);

            // if($RowCount==0){
            //     echo 1;
            // }else{
            //     echo 0;
            // }
            echo 1;
        }

        // // Row Add Function 
        // if(isset($_POST['addrow'])){

        //     $li_date     = $_POST['li_date'];
        //     $payment     = $_POST['payment'];
        //     $arrears     = $_POST['arrears'];
        //     $totalPaid   = $_POST['totalPaid'];
        //     $outstanding = $_POST['outstanding'];
        //     $loan_no     = $_POST['loan_no'];

        //     $checkSql = mysqli_query($conn, "SELECT * FROM temp_collection WHERE li_date='$li_date' AND loanNo=$loan_no ");
        //     $count1 = mysqli_num_rows($checkSql);

        //     if($count1>0){
        //         echo 2;
        //     }else{
        //         $tempInsert = mysqli_query($conn, "INSERT INTO temp_collection(li_date,paid,arrears,total_paid,outstanding,loanNo) VALUES('$li_date','$payment','$arrears','$totalPaid','$outstanding',$loan_no)");
                
        //         if($tempInsert){
        //             echo 1;
        //         }else{
        //             echo  mysqli_error($conn);
        //         }
        //     }
            
        // }

        /////////Add function from dashboard items

        // Table Empty Function 
        if(isset($_POST['tmpEmpty'])){
            
            $empty_temp = "TRUNCATE temp_collection;";
            mysqli_query($conn,$empty_temp);   
            
        }

        // Save Function 
        if(isset($_POST['save'])){

            $li_date   = $_POST['li_date'];
            $center_id = $_POST['center_id'];

            $tot_collection  = $_POST['total_amt'];
            $tot_arrears     = $_POST['total_arr'];
            $tot_outstanding = $_POST['total_out'];

            $date_val = explode('-', $li_date);
            $year  = $date_val['0'];
            $month = $date_val['1'];

            
            $sql_collect = mysqli_query($conn,"INSERT INTO collection(centerID,li_date,year,month,tot_collection,tot_arrears,tot_outstanding) VALUES ($center_id,'$li_date','$year','$month','$tot_collection','$tot_arrears','$tot_outstanding')");


            //// update Summary ////

            $year =  date("Y");
            $month = date("m");
            $createDate = date("Y-m-d");

            $querySummary = "SELECT id ,debtAMT FROM summary WHERE year='$year' AND month='$month' ";
            $resultSummary = mysqli_query($conn ,$querySummary);

            $countSummary =mysqli_num_rows($resultSummary);

            if($countSummary>0){

                while($rowSummary = mysqli_fetch_array($resultSummary)){

                    $oldDebtAMT = $rowSummary['debtAMT'];
                    $id = $rowSummary['id'];
                }

                $newDebtAMT = ($oldDebtAMT +$tot_collection);

                $queryRow ="UPDATE summary SET debtAMT='$newDebtAMT' WHERE id='$id' ";
                $rowRow =mysqli_query($conn,$queryRow);

            }else{

                $query ="INSERT INTO  summary (year,month,debtAMT,createDate)  VALUES (?,?,?,?)";

                $stmt =mysqli_stmt_init($conn);
                if(!mysqli_stmt_prepare($stmt,$query))
                {
                    echo "SQL Error";
                }
                else
                {
                    mysqli_stmt_bind_param($stmt,"ssss",$year,$month,$tot_collection,$createDate);
                    $result =  mysqli_stmt_execute($stmt);
                }

                for ($x = 1; $x < 13; $x++) {
              
                    if($month !=str_pad($x, 2, "0", STR_PAD_LEFT)){

                      $queryDefult ="INSERT INTO  summary (year,month,createDate)  VALUES (?,?,?)";

                      $stmt =mysqli_stmt_init($conn);
                      if(!mysqli_stmt_prepare($stmt,$queryDefult))
                      {
                          echo "SQL Error";
                      }
                      else
                      {
                          mysqli_stmt_bind_param($stmt,"sss",$year,str_pad($x, 2, "0", STR_PAD_LEFT),$createDate);
                          $result =  mysqli_stmt_execute($stmt);
                      }

                    }
                }
            }

            //// insert data to the loan installements table from temp_collection table

            $sqlMax =mysqli_query($conn,"SELECT id FROM collection ORDER BY id DESC LIMIT 1");
            $row_get = mysqli_fetch_assoc($sqlMax);
            $collectionID = $row_get['id'];

            ////////////// sales item values /////////////////
            $AllData =$_POST['AllData'];
            $x = json_decode($AllData, true);

            for($i=0;$i<sizeof($x);$i++)
            {
              $loanNo   =$x[$i]['loanNo'];
              $paid     =$x[$i]['paid'];
              $arrears  =$x[$i]['arrears'];
              $balance  =$x[$i]['balance'];

              $insert_data = mysqli_query($conn,"INSERT INTO loan_installement (collectionID,li_date,paid,arrears,outstanding,loanNo) VALUES ('$collectionID','$li_date','$paid','$arrears','$balance',$loanNo)");

              // update loan status when outstanding balance = 0
              if($balance<=0){
                $updateStatus = mysqli_query($conn, "UPDATE loan SET status=0 WHERE loan_no=$loanNo ");
              }
            }

            if($sql_collect){
                echo 1;
                $empty_temp = "TRUNCATE temp_collection;";
                mysqli_query($conn,$empty_temp); 

            }else{
                echo  mysqli_error($conn);		
            }
        }
       
    ?>