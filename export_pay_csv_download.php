<?php
?>
<!DOCTYPE html>
<html lang="ru">
     <meta charset="UTF-8">
<head>
    <title> Экспорт платежей</title>
    <link rel="stylesheet" type="text/css" href="./css/datatables.css">
    <script type="text/javascript" src="./js/datatables.min.js"></script>
    <style type="text/css">
        .dataTables_wrapper {
            margin-top: 20px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <style>
        .left_25 { padding-left: 25px; }
        .right_25 { padding-left: 25px; }
        .up_25 { padding-top: 25px; }
        .down_25 { padding-bottom: 25px; }
        div { 
            font-size: x-small;
            color: blue;
        }
        table.dataTable {
            width: 100%;
            margin: 15px 0;
        }
        table.dataTable th, table.dataTable td {
            padding: 8px;
            text-align: left;
        }
    </style>
    

<?php
$date_list=[];
$date_list_contract=[];
$date_list_id=[];
$date_list_userid=[];
$date_list_okpo=[];
$okpo_list_payid=[]; //список id платежей в payments_tem для данного окпо
// Включаем отображение ошибок для отладки 
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);
//for ($nn=0; $nn< 5 ; $nn++){  print "1nn: $nn;  "; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['payfile'])) 
{
    // Каталог для сохранения файлов
    $uploadDir = __DIR__ . "/uploads/";
    // Убедимся, что каталог существует
    if (!is_dir($uploadDir)) {
        //mkdir($uploadDir, 0777, true);
        print "Каталог $uploadDir не существует. Создайте его вручную и задайте права на запись.";
        exit;
    }


    // Проверяем, был ли загружен файл
    if (isset($_FILES['payfile']) && $_FILES['payfile']['error'] === UPLOAD_ERR_OK)
    {
        $tmpName = $_FILES['payfile']['tmp_name'];   // временный файл
        $fileName = basename($_FILES['payfile']['name']); // оригинальное имя
        $destPath = $uploadDir . $fileName;
        $LogOut = str_replace('csv','log',$destPath);
        $LogOut = str_replace('uploads','logout',$LogOut);

        // Перемещаем во временный каталог
        if (move_uploaded_file($tmpName, $destPath)) {
            echo "✅ Файл успешно загружен: " . htmlspecialchars($fileName);
        } else {
            echo "❌ Ошибка при сохранении файла.";
        }
        
         // Подключение к базе
        $mysqli = new mysqli("localhost", "pr_perl", "vasya151", "radius");
        if ($mysqli->connect_error) {
            echo " ERROR: ". $mysqli->connect_error;
            die("Ошибка подключения: " . $mysqli->connect_error);
        }
        $mysqli->set_charset("utf8mb4"); // работа в UTF-8
        
        $mysqli2 = new mysqli("localhost", "pr_perl", "vasya151", "radius");
        if ($mysqli2->connect_error) {
            echo " ERROR: ". $mysqli2->connect_error;
            die("Ошибка подключения: " . $mysqli2->connect_error);
        }
        $mysqli2->set_charset("utf8mb4"); // работа в UTF-8
        print "<div> Подключение к базе успешно установлено.</div>";
        $loghandle = fopen($LogOut,"a");
        // Чтение CSV и вставка в БД
        if (($handle = fopen($destPath, "r")) !== false) 
        {
        ?>
            <div class="container">
            <table id="paymentsTable" class="display">
            <thead>
                <tr>
                    <th>№</th>
                    <th>Дата</th>
                    <th>Сумма</th>
                    <th>Договор</th>
                    <th>Имя</th>
                    <th>Детали</th>
                    <th>ID записи</th>
                    <th>Статус</th>
                </tr>
            </thead>
            <tbody>
            <?php
             $tot_number=0;
            $tot_sum=0.0;
            $tot_number_new=0;
            $tot_sum_new=0.0;

            while (($row = fgetcsv($handle, 1000, ";")) !== false) 
            {
                if (count($row) < 2) continue; // пропускаем строки без 2-х полей
                // Берем поля они в кодировке win1251
                if($row[0] === "ST_NUMB") continue;
                $tot_number++;
                $doc_date = $row[3] ;//Исходный формат 2025.09.19 или 19.09.2025
                if(preg_match('/^\d{4}\.\d{2}\.\d{2}$/',$doc_date)) {
                    //формат 2025.09.19 - год в начале
                    //ничего не делаем
                } elseif(preg_match('/^\d{2}\.\d{2}\.\d{4}$/',$doc_date)) {
                    //формат 19.09.2025 - год в конце
                    $parts = explode('.', $doc_date);
                    if(count($parts) == 3) {
                        $doc_date = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
                    }
                } else {
                    //неизвестный формат, пропускаем
                    echo "<div style='color:red'>❌ Неизвестный формат даты: $doc_date в строке $tot_number</div>";
                    continue;
                }

                $doc_date = preg_replace('/\./', '-', $doc_date);
                // if(!isset($date_list[$doc_date])) $date_list[$doc_date]=0;
                //$date_list[$doc_date]++;
                $number=$row[7]; //поле DOC_NO
                $name=$row[11]; //поле KOR_Name
                $okpo=$row[12]; //поле KOR_OKPO
                $detail=$row[13]; //поле Descript
				 $detail = mb_convert_encoding(trim($detail), "UTF-8", "Windows-1251");
                $sum=$row[15]; //поле SUM 
				$tot_sum += (float)$sum;
                //Иногда имя плательщика - это имя банка - тогда ищем имя в назначении
                if(preg_match('/\sбанк/', $name,$match_name)){
                    $split_detail=explode(' ', $detail);//Разбиваем по пробелам
                    if(count($split_detail)<3) continue; //Если мало слов - не интересно
                    $arr=array_slice($split_detail,-3); //Берем последние 3 слова
                    $name=implode(' ',$arr);//Собираем обратно, должнно быть полное имя
                }
                $detail=preg_replace('/\D202\d\D/',' ',$detail); //убираем текущий год, чтобы не путь его с номером договора из 4-цифр
                $detail_mem=$detail;//сохраняем  
                $contract='not_find';
                //  if(preg_match('/дог/', $detail))
                // {
                //     $detail=preg_replace('/рах/','',$detail); //убираем ссілку на рахунок
                // }
               // if(!preg_match('/рах/', $detail)  )
                //{
                    if(preg_match('/(\d{4,7})/', $detail,$match_contract) )
                    {
                        $contract=$match_contract[1];
                        $contract=preg_replace('/^0+/','',$contract);  //убираем ведущие нули
                        $contract=preg_replace('/^_/','',$contract);  //убираем ведущее подчеркивание
                        //if(!isset($date_list_contract[$doc_date])) $date_list_contract[$doc_date]=[];
                        //$date_list_contract[$doc_date][]=$contract;
                        // $date_list[$doc_date]++;
                  //  } 
                    }else {
                        if(!isset($date_list_okpo[$doc_date])) $date_list_okpo[$doc_date]=[];
                        $date_list_okpo[$doc_date][]=$okpo;
                        continue;
                    }
                 
                $doc_date=(string) $doc_date;
                $number=(string) $number;
                $sum=(string) $sum;
                $detail=(string) $detail_mem;
                $contract=(string) $contract;
                // if(!isset($date_list_contract[$doc_date])) $date_list_contract[$doc_date]=[];
                // $date_list_contract[$doc_date][]=$contract;
                $text1=$doc_date.$number.$detail;
                $uni_hash=md5($text1);
                $uni_hash=substr($uni_hash, 0, 16);
                //$number = mb_convert_encoding(trim($number), "UTF-8", "Windows-1251");
                $name = mb_convert_encoding(trim($name), "UTF-8", "Windows-1251");
                $contract = mb_convert_encoding(trim($contract), "UTF-8", "Windows-1251");
                echo "<tr>";
                echo "<td>" . htmlspecialchars($tot_number) . "</td>";
                echo "<td>" . htmlspecialchars($doc_date) . "</td>";
                echo "<td>" . htmlspecialchars($sum) . "</td>";
                echo "<td>" . htmlspecialchars($contract) . "</td>";
                echo "<td>" . htmlspecialchars($name) . "</td>";
                echo "<td>" . htmlspecialchars($detail) . "</td>";
                fwrite($loghandle," $tot_number  $doc_date,  $sum, Дог: $contract, Name=$name Detail = $detail ");
                // === 2. Записываем в БД ===
                $stmt = $mysqli->prepare("INSERT IGNORE INTO payments_temp (`date`, `name_plat`, number, detail, sum, contract, okpo, uni_hash) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                if ($stmt)
                {
                    //$stmt->bind_param("ss", $data1, $data2);
                    $stmt->bind_param("ssssssss", $doc_date, $name, $number, $detail, $sum, $contract, $okpo, $uni_hash);
                    if ($stmt->execute())
                    {
                        $last_id = $mysqli->insert_id;
                        if($last_id) {
                            echo "<td>" . htmlspecialchars($last_id) . "</td>";
                            echo "<td>Запись добавлена</td>";
                            echo "</tr>";
                            fwrite($loghandle," - payments_temp.ID: $last_id \n\n");
                            $tot_number_new++;
                            $tot_sum_new=$tot_sum_new+$sum;
                            if(!isset($date_list_id[$doc_date])) $date_list_id[$doc_date]=[];
                            $date_list_id[$doc_date][]=$last_id;
                            // Записываем номера договоров только для новых записей
                            if(!isset($date_list_contract[$doc_date])) $date_list_contract[$doc_date]=[];
                            $date_list_contract[$doc_date][]=$contract;
                            if(!isset($date_list[$doc_date])) $date_list[$doc_date]=0;
                            $date_list[$doc_date]++;
                            if(!isset($okpo_list_payid[$okpo])) $okpo_list_payid[$okpo]=[];
                            $okpo_list_payid[$okpo]=$last_id;
                        } else {
                            echo "<td>-</td>";
                            echo "<td>Запись уже существует</td>";
                            echo "</tr>";
                            fwrite($loghandle,"\n В payments_temp запись для $contract от $doc_date уже была\n\n");
                        }                        
                    } else 
                    {
                       echo "Дог $conract : Execute failed: (" . $stmt->errno . ") " . $stmt->error;
                       fwrite($loghandle,"Дог $conract : Execute failed:  $stmt->errno $stmt->error\n");
                    }
                    
                    $stmt->close();
                } else {
                    echo "Ошибка prepare: " . $mysqli->error . "<br>";
                } 
            }
            fclose($handle);
            fwrite($loghandle,"\n==============================\n");
            
        } else { //if (($handle = fopen($destPath, "r")) !== false)
            echo "❌ Не удалось открыть файл $destPath";
        } 

    ?>

        </tbody>
        </table>
    </div>
    <?php

    } else {// if (isset($_FILES['payfile']) && $_FILES['payfile']['error'] === UPLOAD_ERR_OK)
        echo "Файл не был загружен или произошла ошибка.";
    }
    //============================================
     //Теперь пройдемся по списку дат и попробуем найти систему по договорам
    //$date_list=[];//для отладки
    if(!empty($date_list))
    {
        echo "<div>Всего считано записей $tot_number из них новых  payments_temp: $tot_number_new,</div><div> Всего платежей на сумму $tot_sum; новых на сумму: $tot_sum_new</div>";
        fwrite($loghandle,"Всего считано записей $tot_number из них новых  payments_temp: $tot_number_new \n Всего платежей на сумму $tot_sum; новых на сумму: $tot_sum_new \n");
        foreach($date_list as $d=>$n)
        {
            echo "<div> Дата: $d, Количество новых записей c распознанными договорами: $n</div>";
            echo "<div>Распознавание систем для даты $d</div>";
            fwrite($loghandle,"Дата: $d, Количество новых записей c распознанными договорами: $n \n Распознавание систем для даты $d \n ");
             $doc_date=$d;
            //Распознавание по договорам из списка абонентов user
            if(isset($date_list_contract[$d])) 
            {
               $list_contract=$date_list_contract[$d];
               
               print "<div style='font-size:хх-small'> Записей  на дату $d: ".count($list_contract)." </div>";
               fwrite($loghandle," Записей на дату $d: ".count($list_contract)." \n ") ;
                //foreach($list_contract as $contract)
                for ($nn=0; $nn<count($list_contract); $nn++)
                {
                    $contract = $list_contract[$nn];
                    $sql = "SELECT id, name, longname, account1 FROM user where contract = '$contract' ORDER BY date_start desc LIMIT 1";
                    $result = $mysqli->query($sql);
                    if ($result->num_rows > 0) 
                    {
                        // Выводим данные каждой строки
                        $row = $result->fetch_assoc();
                        $system = (string) $row["name"];
                        $userid = (int) $row["id"];
                        $long_name = (string) $row["longname"];
                        $account1 = (int) $row["account1"];
                        if(!isset($date_list_userid[$doc_date])) $date_list_userid[$doc_date]=[];
                        $date_list_userid[$doc_date][]=$userid;
                        
                        print "<div>$nn, Договор: $contract; userid=$userid system  $system,  $long_name </div>";
                        fwrite($loghandle,"$nn, Договор: $contract; userid=$userid system  $system, $long_name \n") ;
                        $sql_up="UPDATE payments_temp SET userid='$userid', account1='$account1', system = '$system', name='$long_name', find_by='contract', is_find_system='1' WHERE contract = '$contract' AND date = '$d' AND is_find_system='0'";
                        $res_update = $mysqli->query($sql_up);
                        //Если нашли систему - можно платеж внести в tranactions, оттуда он по тригеру попадет в user на баланс
                    } else {
                        print "<div style='color:red'> Не найден договор: $contract в user</div>";
                        fwrite($loghandlem," !!!! НЕ найден договор $contract в user !!!!\n") ;
                    }
                }
            }
            //Распознавание по ОКПО из списка платежей
            /*
            if(isset($date_list_okpo[$d])) 
            {
                $list_okpo=$date_list_okpo[$d];
                foreach($list_okpo as $c)
                {
                    echo " OKPO: $c <br>";
                    $payid=isset($okpo_list_payid[$c]) ? $okpo_list_payid[$c] : 0;
                    if($payid==0) continue;
                    $sql = "SELECT system, userid FROM payments_temp where okpo = '$c' AND is_find_system='1' ORDER BY date_insert desc LIMIT 1";
                    $result = $mysqli->query($sql);
                    if ($result->num_rows > 0) 
                    {
                        //Строчка одна
                        $row = $result->fetch_assoc();
                        $system = (string) $row["system"];
                        $userid = (int) $row["userid"];
                        echo "OKPO:".$c." system: " .$system."userid=$userid;<br>";
                        $sql_up="UPDATE payments_temp SET system = '$system', userid='$userid' find_by='okpo', is_find_system='1' WHERE id='$payid' AND is_find_system='0'";
                        $res_update = $mysqli->query($sql_up);
                        //Если нашли систему - можно платеж внести в tranactions, оттуда он по тригеру попадет в user на баланс
                      
                        $sql = "SELECT account1 FROM user where id = '$userid' ";    
                        $res_id = $mysqli->query($sql);
                        if ($res_id->num_rows > 0) 
                        {
                            // Строчка одна
                            $row_id = $res_id->fetch_assoc();
                            $account1 = (int) $row_id["account1"];
                            $sql_up="UPDATE payments_temp SET account1= '$account1' WHERE id='$payid' ";
                            $res_update = $mysqli->query($sql_up);
                        }
                    }    
                } 
                
            }  */

        } //foreach date
        //Теперь пройдемся по добавленным ID платежей и внесем их в transactions
        if(!empty($date_list_id)) //есть добавленные ID payments_temp
        {
            echo "<div>Добавленные ID записей:</div>";
            fwrite($loghandle,"Добавленные ID записей:\n    ") ;
            foreach($date_list_id as $dd=>$ids)
            {
                //echo implode(", ", $ids);
                //echo "<br>";
                $list_id=$date_list_id[$dd];
                fwrite($loghandle, " --- Дата: $dd --- \n Всего записей на дату $dd: ".count($list_id)."\n");
                print " <div style='font-size:small'> Всего записей на дату $dd: ".count($list_id)." </div>";

                //$stmt_trans = $mysqli2->prepare("INSERT INTO transaction (creditor,debtor,datepay,typepay,cid1,cid2,old,credit,`new`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                //$stmt_trans = $mysqli->prepare("INSERT INTO transaction (creditor,debtor,datepay,typepay) VALUES (?, ?, ?, ? )");
                //$stmt_trans = $mysqli2->prepare("INSERT INTO `transaction` (`debtor`) VALUES (?)");
                //print " <div style='font-size:small'> Всего платежей на дату $dd: ".count($list_id)." </div>";
                foreach($list_id as $c)
                {
                    $sql = "SELECT userid, sum, date, account1, contract  FROM payments_temp where id = '$c' AND is_find_system='1'";
                    $result = $mysqli->query($sql);
                    if ($result->num_rows > 0) 
                    {
                        // Выводим данные каждой строки
                        $row = $result->fetch_assoc();
                        $userid = (string) $row["userid"];
                        $sum = (float) $row["sum"];
                        //echo "1 credit=$sum<br>";
                        $sum=$sum*100.0;
                        $credit=round($sum); //Переводиv вцелые
                        //echo "2 credit=$credit <br>";
                        $credit=(int) $credit;
                        //echo "3 credit=$credit <br>";
                        $account1 = (int) $row["account1"]; //уже целое
                        $new=$account1+$credit;
                        $date = $row["date"];
                        $contract = $row["contract"];
                        print " <div style='font-size:small'> Дата: $date, contract: $contract, userid: $userid,  old: $account1, credit: $sum , new=$new   </div>";
                        fwrite($loghandle, " Дата: $date, contract: $contract, userid: $userid,  old: $account1, credit: $sum , new=$new \n");
                        //Вносим в транзакции

                        $creditor = 1;
                        $debtor = $userid;
                        $datepay = $date;
                        $typepay = 'bank';
                        $cid1 = 1;
                        $cid2 = 1;
                        $old = $account1;
                       //  $credit_val = $credit;
                        // $new_val = (int) $new;
                       // 
                       $sql = "INSERT INTO `transaction` (creditor,debtor,datepay,`typepay`,cid1,cid2,old,credit,`new`) VALUES ('$creditor', '$debtor', '$datepay','$typepay','$cid1', '$cid2', '$old', '$credit','$new')";
                      // print " <div style='font-size:small'> SQL: $sql </div>";
                       $result_insert = $mysqli2->query($sql);
                        if ($result_insert) 
                        {
                           $trans_id=$mysqli2->insert_id;
                             print " <div style='font-size:small'> Платеж внесен в transactions с ID: $trans_id</div>";
                             fwrite($loghandle,"     Платеж внесен в transactions с ID: $trans_id\n");
                             $sql_pay = "Update payments_temp SET is_compleet='1' WHERE userid='$userid' ";
                                $res_pay = $mysqli->query($sql_pay);
                                if ($res_pay) {
                                    print " <div style='font-size:small'> Обновлен статус is_pay=1 в payments_temp для userid=$userid</div>";
                                    fwrite($loghandle," Обновлен статус is_pay=1 в payments_temp для contract=$contract, userid=$userid \n\n");
                                } else {
                                    print " <div style='font-size:small'> Ошибка обновления статуса is_pay=1 в payments_temp для userid=$userid $mysqli->error </div>";
                                    fwrite($loghandle," Ошибка Обновления статус is_pay=1 в payments_temp для contract=$contract, : $mysqli->error  \n\n");
                                }   
                        } else {
                            print " <div style='font-size:large'> Ошибка внесения платежа в transactions $mysqli2->error </div>";
                            fwrite($loghandle," Ошибка внесения платежа contract=$contract, : $mysqli2->error  \n\n");
                        }
                    }
                }
            }
        }
    }
?>

<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        $('#paymentsTable').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Russian.json"
            },
            "pageLength": 25,
            "order": [[0, "desc"]],
            "responsive": true
        });
    });
    </script>
    </body>
    </html>
<?php                 
    fwrite($loghandle,"###################\n##############\n");
    fclose($loghandle);
    $mysqli->close(); 
    $mysqli2->close(); 

    //============================================
} else // if ($_SERVER[""] === "POST"&& isset($_POST[
{
?>
<!-- HTML форма -->
<form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" enctype="multipart/form-data">
    <label for="payfile">Выберите CSV файл:</label>
    <input type="file" name="payfile" id="payfile" accept=".csv"><br><br>
    <button type="submit">SEND</button>
</form>
<?php
}
?>