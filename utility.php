<?php
/*
function date_convert($str)
  { //преобразует дату вида 1 января 2009 в 2009-01-01
    global $mon_rus, $mon_rus_koi;
    if (!$str) return '';
    if (preg_match('/^(\d{1,2})\s+(\w+)\s+(\d{2,4})/',$str,$m))
    {
       $rus_mon=array_flip($mon_rus);
       $mon=$rus_mon[$m[2]]+1;
      $str="$m[3]-$mon-$m[1]";
    }
    if (preg_match('/^(\d{1,2})-(\d{1,2})-(\d{2,4})/',$str,$m)){$str="$m[3]-$m[2]-$m[1]";  }
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{2,4})/',$str,$m)){$str="$m[3]-$m[1]-$m[2]";  } //ехсел 
      return $str;
 //--------------------------------------------------------------------- 
 }
 */
  function addpay_noff_xls($fname)
  {   //  вносит платежи в таблицу payments_noff на stat и transaction на radius noff  из файла xls
    // A!       B!            C!               D!            E!     F
    // Договор    Дата платежа     Сумма    Абонент (ФИО)  Примечание
     // С восклицательным знаком - обязательные поля !
    $fname='uploads/'.$fname;   
    $user_login='bvv';
      error_reporting(E_ALL ^ E_NOTICE);
     $today=set_today_str_m();
     $f2e='./result/addpay_noff'."$today".'.txt';
     $out=fopen($f2e,'a');
      require_once 'Excel/excel_reader2.php';
     $db = new MySQL();  
     $db->connectToDb();
     $db_R = new MySQL_Radius();  
     $db_R->connectToDb();
     // читаем входной xls файл   
     $data = new Spreadsheet_Excel_Reader($fname,false,'CP1251');
      $nr=$data->rowcount(0); 
     $reseller=$data->val(1,'C',0); 
     // Находим идентификатор реселлера по имени из таблицы
     $sql= "select id, longname from user where name='$reseller' and active='1' limit 1";
      if (!$result = @mysql_query($sql, $db_R->dbConn)) {trigger_error('Query failed: ' . mysql_error($db_R->dbConn).' SQL: '.$sql); die(); }
      $row=mysql_fetch_assoc($result); //возвращается только одна строка
      if($row['id'] > 0){  $reseller_id=$row['id']; list($res_family, $res_name, $ww)=explode(' ',$row['longname']);}
       else { print "Реселлер $reseller в списках не найден!"; exit;} 
       $res_fam=iconv('windows-1251','UTF-8',$res_family);
    $total=0;
      for ($n=1;$n <=$nr; $n++)
   {
  //  if ($total >= 1) break;
$n_dog=$data->val($n,'A',0);
if (!preg_match("/^\s*\d{4,6}\s*$/",$n_dog)){continue;}; //будут обрабатываться строки с номерами Договоров
 unset($w_a); 
    //   A!       B!            C!               D!            E!     F
  // Договор    Дата платежа     Сумма    Абонент (ФИО)  Примечание
     $n_dog=trim($n_dog);
     if($n_dog < 10000){$system='_00'.$n_dog;} else {$system='_0'.$n_dog;}
     $date=$data->val($n,'B',0);
     $date_pay=date_convert($date);
     $sum_pay=$data->val($n,'C',0);
     $abon_name=$data->val($n,'D',0);
     $primech=$data->val($n,'E',0);
    $sum_pay=trim($sum_pay);
    $credit=round($sum_pay*100);
     $prim="0#g#$abon_name, $primech, платит $res_family";
     $cmnt="$abon_name, $primech, платит $res_family";
     $sql_stat="insert into payments_noff values(null,'$system','$date_pay','$sum_pay','0.0','0.0','$prim','8.0')";
     // найдем id абонента
     $sql= "select id from user where contract='$n_dog'";
   if (!$result = @mysql_query($sql, $db_R->dbConn)) {trigger_error('Query failed: ' . mysql_error($db_R->dbConn).' SQL: '.$sql); die(); }
      $row=mysql_fetch_assoc($result); //возвращается только одна строка
     if($row['id'] > 0){  $abon_id=$row['id']; } else { print "<div>Договор N $n_dog не найден.</div>"; continue;}
     $sql_rad="insert into transaction values (null,'1','$abon_id','$date_pay','noff','0','$credit','0','$cmnt',null)" ;
      if (!$result = @mysql_query($sql_stat, $db->dbConn)) { print '<div>Query failed: ' . mysql_error($db->dbConn).' SQL_stat: '.$sql_stat.'</div>'; continue; }
      if (!$res2 = @mysql_query($sql_rad, $db_R->dbConn)) { print '<div>Query failed: ' . mysql_error($db_R->dbConn).' SQL_rad: '.$sql_rad.'</div>'; continue; }
   $wss="$total \t $n_dog\t $system\t$date_pay \t $sum_pay \t  $cmnt\n" ;          
    $responce->rows[$total]['id']=$total;                
    $cmnt=iconv('windows-1251','UTF-8',$cmnt);
    $abon_name=iconv('windows-1251','UTF-8',$abon_name);
    $primech=iconv('windows-1251','UTF-8',$primech);
    //['№','Дата','№ Договора', 'Система', 'Ф.И.О.','Сумма','Прим', 'Ресселер'],
     $responce->rows[$total]['cell']=array($total,$date_pay,$n_dog,$system,$abon_name,$sum_pay, $primech,$res_fam);
       $total++;
  fwrite($out,$wss);      
   } // for ($n=1  
     fclose($out);
     $page=0;
   $total_pages = 1;
   $responce->page = $page;
  $responce->total = $total_pages;
  $responce->records = $total;
  echo json_encode($responce);
  return;   
  }  // addpay_noff_xls() 
  function import_dogovor2abonents($fname)
  {  // Перенос данных абонентов из таблицы xls договоров с таблицу doсuments.abonents
     // В таблицу user на radius   ничего не вносится!! 
     // Порядок стобцов в xls таблице
     // A       B!            C!           D!         E!              F                     G             H           I         J
     // № ПП    №№ догов.    Система    IP адресс    Дата заключ    Абонент (ФИО полное)    Адрес     моб. телефон    email    Скорость 
     // С восклицательным знаком - обязательные поля !
      
      $user_login='bvv';
      error_reporting(E_ALL ^ E_NOTICE);
     $today=set_today_str_m();
     $f2e='./result/import_dog'."$today".'.txt';
     $out=fopen($f2e,'a');
require_once 'Excel/excel_reader2.php';
  $db = new MySQL();  
    $db->dbName='documents';
  $db->connectToDb();
  $sql_update="update abonents set Who_modify='$user_login', N_dogovora='?', Director='?',{Date_reg='?'},{Tel='?'},
  {Name_org='?'}, {Post_addr='?'}, {Systema='?'}, {e_mail='?'} where id=?".'$id'."?";
  $sql_insert="insert into abonents  set Who_modify='$user_login', Is_dogovor='Да',Valid_dogovor='Да', N_dogovora='?', Director='?',{Date_reg='?'},{Tel='?'},
  {Name_org='?'}, {Post_addr='?'}, {Systema='?'}, {e_mail='?'}" ;
  $fname='uploads/'.$fname;
  $data = new Spreadsheet_Excel_Reader($fname,false,'CP1251');
  $nr=$data->rowcount(0); 
  $reseller=$data->val(1,'C',0); 
  $total=0;
  for ($n=1;$n <=$nr; $n++)
{
    //if ($total >= 2) break;
$n_dog=$data->val($n,'B',0);
if (!preg_match("/^\s*\d{4,6}\s*$/",$n_dog)){continue;}; //будут обрабатываться строки с номерами Договоров
 unset($w_a); 
 
   // A       B!            C!           D!         E!              F                     G             H           I         J
 //№ ПП    №№ догов.    Система    IP адресс    Дата заключ    Абонент (ФИО полное)    Адрес     моб. телефон    email    Скорость 
// Для таблицы Abonents не используются поля с IP адресом и Скоростью
 $w_a['N_dogovora']=$n_dog;
 $w_a['Systema']=$data->val($n,'C',0);
 $sys= $w_a['Systema'];
 $date=$data->val($n,'E',0);
 if (strlen($date) > 8) {
    $w_a['Date_reg']=date_convert($date); //дата договора
    $date=$w_a['Date_reg']; //дата договора
   } else {$date=set_today_str_m(); $w_a['Date_reg']=$date;}
 $w_a['Tel']=$data->val($n,'H');
 $phone= $w_a['Tel'];
 $w_a['Post_addr']=$data->val($n,'G');
 $address= $w_a['Post_addr'];
 $w_a['Name_org']=$data->val($n,'F'); //Имя клиента
 $longname=$w_a['Name_org'];
 if (strlen($longname) < 4) {continue;} //если имя Абонента отсутствует - Договор в базу вносится не будет !!!
 $w_a['Director']=$reseller;
 $e_mail=$data->val($n,'I');
if(!is_validemail($e_mail)){$e_mail="$sys@mail.donbass.net";}
$w_a['e_mail']=$e_mail;

 foreach($w_a as $key=>$value) {$w_a[$key]=mysql_real_escape_string($value,$db->dbConn);}
 
 $is_insert=false;
   $sql2= "select id from abonents where Systema='$sys' and N_dogovora='$n_dog' limit 1";
   if (!$result = @mysql_query($sql2, $db->dbConn)) {trigger_error('Query failed: ' . mysql_error($db->dbConn).' SQL: '.$sql2); die(); }
   $row=mysql_fetch_assoc($result); //возвращается только одна строка
   if($row['id'] > 0){  $id=$row['id']; $sql=$sql_update;} else { $sql=$sql_insert; $is_insert=true;}
 $w_a['id']=$id;
 
 $sql=make_sql_query($sql,$w_a);
  if(@mysql_query($sql)){
      $Reply=$is_insert?'Добавлен':'Обновлен';
      $wss="$total \t $date\t $Reply \t $n_dog \t  $sys \t $longname \t $address \t $phone \n" ;
      $responce->rows[$total]['id']=$total;                
      $longname=iconv('windows-1251','UTF-8',$longname);
      $address=iconv('windows-1251','UTF-8',$address);
      $Reply=iconv('windows-1251','UTF-8',$Reply);
      $responce->rows[$total]['cell']=array($total,$Reply,$date,$n_dog,$sys,$longname,$address,$phone); 
      fwrite($out,$wss);      
      $total++;
        } else
        { 
            trigger_error('Query failed: ' . mysql_error($db->dbConn).' SQL: '.$sql);  
        }
} //fo
 $page=0;
   $total_pages = 1;
   $responce->page = $page;
  $responce->total = $total_pages;
  $responce->records = $total;
  echo json_encode($responce);
  fwrite($out,"End\n");
  fclose($out);
  return;
  } //function change_dogovor
  
  function change_dogovor_Demeshko($fname)
  {      // Только для абонетов Демещенко, которым изменили имя системы с символьного на цифровой
  // Часть абонетов уже была внесена в список, остальных прилось добавлять - работает только один раз 
      //  Порядок стобцов в xls таблице
      //  A        B             C          D      E           F               G              H             I          J           K            L          M
      //№ ПП    №№ догов.    Устройство    %%    Система    Новая система    IP адресс    Дата заключ    Абонент    Адрес     моб. телефон    email    Скорость 
      // Перенос данных абонентов из таблицы xls договоров с таблицу doсuments.abonents
    // В таблицу user на radius   ничего не вносится!!
      $user_login='bvv';
      error_reporting(E_ALL ^ E_NOTICE);
     $today=set_today_str_m();
     $f2e='./result/change_dog'."$today".'.txt';
     $out=fopen($f2e,'a');
require_once 'Excel/excel_reader2.php';
  $db = new MySQL();  
    $db->dbName='documents';
  $db->connectToDb();
  $sql_update="update abonents  set Who_modify='$user_login', N_dogovora='?', Director='?',{Date_reg='?'}, {Comment_payment='?'}, {Tel='?'},
  {Name_org='?'},{Post_addr='?'},{Systema='?'} where id=?".'$id'."?";
  $sql_insert="insert into abonents  set Who_modify='$user_login', N_dogovora='?', Director='?',{Date_reg='?'}, {Comment_payment='?'}, {Tel='?'},
  {Name_org='?'},{Post_addr='?'},{Systema='?'}" ;
  $fname='uploads/'.$fname;
  $data = new Spreadsheet_Excel_Reader($fname,false,'CP1251');
  $nr=$data->rowcount(0); 
  $reseller=$data->val(1,'C',0); 
  $total=0;
  for ($n=1;$n <=$nr; $n++)
{
 //   if ($total >= 2) break;
$n_dog=$data->val($n,'B',0);
if (!preg_match("/^\s*\d{4,6}\s*$/",$n_dog)){continue;}; //будут обрабатываться строки с номерами Договоров
 unset($w_a); 
 $w_a['N_dogovora']=$n_dog;
 $old_sys=$data->val($n,'E',0);
 $w_a['Systema']=$data->val($n,'F',0);
 $sys= $w_a['Systema'];
 $date=$data->val($n,'H',0);
 $w_a['Date_reg']=date_convert($date);
 $date=$w_a['Date_reg'];
 $w_a['Comment_payment']=$data->val($n,'D'); //Процент от платежа абонента
 $w_a['Tel']=$data->val($n,'K');
 $phone= $w_a['Tel'];
 $w_a['Post_addr']=$data->val($n,'J');
 $address= $w_a['Post_addr'];
 $w_a['Name_org']=$data->val($n,'I'); //Имя клиента
 $longname=$w_a['Name_org'];
 $w_a['Director']='rs_demesh';
 $is_insert=false;
   $sql2= "select id from abonents where Systema='$old_sys' and N_dogovora='$n_dog' limit 1";
   if (!$result = @mysql_query($sql2, $db->dbConn)) {trigger_error('Query failed: ' . mysql_error($db->dbConn).' SQL: '.$sql2); die(); }
   $row=mysql_fetch_assoc($result); //возвращается только одна строка
   if($row['id'] > 0){  $id=$row['id']; $sql=$sql_update;} else { $sql=$sql_insert; $is_insert=true;}
 $w_a['id']=$id;
 $sql=make_sql_query($sql,$w_a);
  if(@mysql_query($sql)){
      $Reply=$is_insert?'Добавлен':'Обновлен';
      $wss="$total \t $date\t $Reply \t $n_dog \t $old_sys \t $sys \t $longname \t $address \t$phone \n" ;
      $responce->rows[$total]['id']=$total;                
      $longname=iconv('windows-1251','UTF-8',$longname);
      $address=iconv('windows-1251','UTF-8',$address);
      $Reply=iconv('windows-1251','UTF-8',$Reply);
      $responce->rows[$total]['cell']=array($total,$Reply,$date,$n_dog,$old_sys, $sys,$longname,$address,$phone); 
      fwrite($out,$wss);      
      $total++;
        } else
        { 
            trigger_error('Query failed: ' . mysql_error($db->dbConn).' SQL: '.$sql);  
        }
} //fo
 $page=0;
   $total_pages = 1;
   $responce->page = $page;
  $responce->total = $total_pages;
  $responce->records = $total;
  echo json_encode($responce);
  fwrite($out,"End\n");
  fclose($out);
  return;
  } //function change_dogovor
  function import_dogovor_radius($fname)
{   // Перенос данных абонентов из таблицы xls договоров с таблицу user на radius 
    // В таблицу doucuments.abonents ничего не вносится!!
        //инструкция для jqGrid http://www.trirand.com/blog/jqgrid/jqgrid.html  
        //и http://www.scribd.com/doc/17094846/jqGrid
   //$responce= Object();
   // Этот скрипт работает только с локальной машины в 608 к, т.к. с //92.242.119.57 вход на MySQL с radius закрыт.
    $user_login='bvv';
    global $user_login;
   $today=set_today_str_m();
     $f2e='./result/import_dog'."$today".'.txt';
     $out=fopen($f2e,'a'); 
 error_reporting(E_ALL ^ E_NOTICE);
require_once 'Excel/excel_reader2.php';
  $db = new MySQL_Radius();  
  $db->connectToDb();
  $sql_update="update user  set changedby='$user_login', name='?', {date_start='?'}, {phone='?'},{longname='?'},
  {address='?'},{name='?'}, {mail='?'} where id=?".'$id'."?";
  $sql_insert="insert into user set groupid='3', speed='2M', pool='pool1', price_groupid='8', active='0', ip=inet_aton('?".'$ip'."?'), changedby='$user_login', 
  password='?',  contract='?', name='?', radgroup='?', parentid='?', date_start='?',{share='?'}, {phone='?'},{longname='?'},
  {address='?'}, {mail='?'}";
$w_a=array(); $pswd=array();
$fname='uploads/'.$fname;
$data = new Spreadsheet_Excel_Reader($fname,false,'CP1251');
$nr=$data->rowcount(0); 
$reseller=$data->val(1,'C',0); 
// Находим идентификатор реселлера по имени из таблицы
$sql= "select id from user where name='$reseller' and active='1' limit 1";

   if (!$result = @mysql_query($sql, $db->dbConn)) {trigger_error('Query failed: ' . mysql_error($db->dbConn).' SQL: '.$sql); die(); }
   $row=mysql_fetch_assoc($result); //возвращается только одна строка
   if($row['id'] > 0){  $reseller_id=$row['id'];}
     else { print "Реселлер $reseller в списках не найден!"; exit;}
     
$total=0;
for ($n=1;$n <=$nr; $n++)
{
    //if ($total >= 2) break;
   unset($w_a);  
$n_dog=$data->val($n,'B',0);
if (!preg_match("/^\s*\d{4,6}\s*$/",$n_dog)){continue;}; //будут обрабатываться строки с номерами Договоров
 //  $sql= "select id from abonents where N_dogovora='$n_dog' and Director='$reseller'";
$ip=$data->val($n,'D'); //IP клиента
if (strlen($ip) <=6) {$ip='0.0.0.0';} // IP должен быть обязательно
$w_a['ip']=$ip; //IP клиента 
   $sql= "select id from user where contract='$n_dog' and parentid='$reseller_id'";
   if (!$result = @mysql_query($sql, $db->dbConn)) {trigger_error('Query failed: ' . mysql_error($db->dbConn).' SQL: '.$sql); die(); }
   $row=mysql_fetch_assoc($result); //возвращается только одна строка
   if($row['id'] > 0){  $sql=$sql_update;  $is_insert=false;  $id=$row['id']; }
     else { $sql=$sql_insert;  $is_insert=true;  $id=0;  }
   // A       B!            C!           D!         E!              F                     G             H           I         J
 //№ ПП    №№ догов.    Система    IP адресс    Дата заключ    Абонент (ФИО полное)    Адрес     моб. телефон    email    Скорость 
$w_a['contract']=$n_dog;  //номер договора
$w_a['name']=$data->val($n,'C',0); $sys=$w_a['name'];       //система
$date=$data->val($n,'E');
if (strlen($date) > 8) {
$w_a['date_start']=date_convert($date); //дата договора
$date_start=$w_a['date_start']; //дата договора
} else {$date_start=set_today_str_m(); $w_a['date_start']=$date_start;}
$w_a['phone']=$data->val($n,'H');
$phone=$data->val($n,'H');
$mail=$data->val($n,'I');
if(!is_validemail($mail)){$mail="$sys@mail.donbass.net";}
$w_a['mail']=$mail;
$w_a['address']=$data->val($n,'G');
$address=$data->val($n,'G');
$w_a['longname']=$data->val($n,'F'); //Имя клиента
$longname=$data->val($n,'F'); //Имя клиента
$w_a['parentid']=$reseller_id;  
$w_a['groupid']='3';  
if ($is_insert){ $w_a['password']=auth_gen_password(10,10);  $psw=$w_a['password'];} else {$psw='no change';}
$w_a['id']=$id;
$velocity=$data->val($n,'J'); //Скорость клиента 
if ($is_insert)
{
if (!preg_match("/\d{1,3}[MKМК]+/",$velocity,$match)) {$radgroup='mt_1M_n1_grv';}
else {
    $velocity=str_replace('М','M',$velocity); //тупо меняем Русскую М на латинскую M - иначе rlike не работает
    $velocity=str_replace('К','K',$velocity); //тупо меняем Русскую К на латинскую К- иначе rlike не работает
    $sql2="select groupname from radgroup where longname like '$velocity%' limit 1";  
    if (!$res2 = @mysql_query($sql2, $db->dbConn)) {trigger_error('Query failed: ' . mysql_error($db->dbConn).' SQL: '.$sql2); die(); }
   $r2=mysql_fetch_array($res2); //возвращается только одна строка
   if($r2[0]){  $radgroup=$r2[0];} else { $radgroup='mt_1M_n1_grv'; }
}
$w_a['radgroup']=$radgroup;
} else {$radgroup='no change';}
 foreach($w_a as $key=>$value) {$w_a[$key]=mysql_real_escape_string($value,$db->dbConn);}
$sql=make_sql_query($sql,$w_a);
   if(@mysql_query($sql)){
  $Reply=$is_insert?'Добавлен':'Обновлен';
  //$responce->rows[$total]['id']=$id;      
   $wss="$total \t $date_start\t $Reply \t $n_dog \t  $sys \t $psw  \t $longname \n" ;          
  $responce->rows[$total]['id']=$total;                
  $longname=iconv('windows-1251','UTF-8',$longname);
  $address=iconv('windows-1251','UTF-8',$address);
  $Reply=iconv('windows-1251','UTF-8',$Reply);
 //['№','Дата','№ Договора', 'Действие','Система', 'IP','Пароль','Ф.И.О.','Адрес','Телефон','Mail','Скорость','RadGroup'],
  $responce->rows[$total]['cell']=array($total,$date_start,$n_dog,$Reply,$sys,$ip,$psw,$longname,$address,$phone,$mail,$velocity,$radgroup);
       $total++;
  fwrite($out,$wss);      
   } else
        { 
            trigger_error('Query failed: ' . mysql_error($db->dbConn).' SQL: '.$sql);  
        }
}
   //$page = $_GET['page'];
   fclose($out);
   $page=0;
   $total_pages = 1;
   $responce->page = $page;
  $responce->total = $total_pages;
  $responce->records = $total;
  echo json_encode($responce);
  return;   
}// end  import_dogovor
// ---------------  Платежи --------------------
function calc_balance()
{
 $out=fopen($f2e,'a');
   $in=fopen('platezhi/narabotka_system.txt','r'); // наработка систем на 13.02.2012
  $db = new MySQL_Radius();  
  $db->connectToDb();   
  $total=1;
  if ($in) {
    while (!feof($in)) {
//     if ($total >=2) break;
    $ws = fgets($in, 4096);
    if(strlen(trim($ws))< 4) continue;
    list($n, $name, $narabotka, $comment)=split(",",$ws);
    $work=round($narabotka*100);
    $sql="select sum(transaction.credit) as sumpay  from transaction, user where transaction.debtor= user.id and user.name='$name' group by transaction.debtor";
    } // while (!feof($in))
  } //  if ($in)
}  //  function calc_balance()     
function pay2transaction()
{   // запускается только на локальной машине в 608!!
    // переносит платежи из таблицы user_pay в transatcion
  $db = new MySQL_Radius();  
  $db->connectToDb();
  $db2 = new MySQL_Radius();  
  $db2->connectToDb();
  $total=0;
     $sql="select name as system, value_pay, date_pay, kind_pay, comment, (select id from user where user.name=system) as debtor  from user_pay where id >=0";
  if (!$result = @mysql_query($sql, $db->dbConn)) {
       trigger_error('Query failed: ' . mysql_error($db->dbConn).' SQL: '.$sql); die(); }
    while ($row=mysql_fetch_assoc($result)) {
   //  if ($total>0) return;
     $total++;
   $system=$row['name']; $pay=$row['value_pay'];
   $date_pay=$row['date_pay']; $kind_pay=$row['kind_pay'];
    $comment=iconv('windows-1251','UTF-8',$row['comment']);
    $debtor=$row['debtor'];
    $sql2= "insert into transaction values(null,'1','$debtor','$date_pay','$kind_pay','0','$pay',
    '0','$comment',null)";
    if (!$res2 = @mysql_query($sql2, $db2->dbConn)) {print 'Query failed: ' . mysql_error($db2->dbConn).' SQL: '.$sql2."<br>"; continue; }
     // $r2=mysql_fetch_assoc($res2); //возвращается только одна строка
    } // while ($row=
    print "Расчет окончен!";
} //pay2transaction()
function compare_pay_by_user()
  {      // запускается только на локальной машине в 608!!
  $db = new MySQL_Radius();  
  $db->connectToDb();
  $db2 = new MySQL_Radius();  
  $db2->connectToDb();
  $total=0;
   $list_found='';
  $nmax=sizeof($date_beg);
  print "<table class='grtable' style='font-size:10px, font-family: arial'>";
    $sql="select distinct name  from user_pay where id >=0 order by name";
  if (!$result = @mysql_query($sql, $db->dbConn)) {
       trigger_error('Query failed: ' . mysql_error($db->dbConn).' SQL: '.$sql); die(); }
    while ($row=mysql_fetch_assoc($result)) {
   $system=$row['name']; 
   // $comment=iconv('windows-1251','UTF-8',$comment);
    $sql2= "select id, longname, parentid as p_id, (select longname from user where id=p_id) as reseller from user where name='$system'";
    if (!$res2 = @mysql_query($sql2, $db2->dbConn)) {print 'Query failed: ' . mysql_error($db2->dbConn).' SQL: '.$sql2."<br>"; continue; }
      $r2=mysql_fetch_assoc($res2); //возвращается только одна строка
      if($r2['id'] > 0){  $id_usr=$r2['id']; $longname=$r2['longname']; } else { continue;} 
      $id_usr=$r2['id'];  
      //$longname=iconv('windows-1251','UTF-8',$longname);
      $reseller=$r2['reseller'];
      //$reseller=iconv('windows-1251','UTF-8',$r2['reseller']);
      mysql_freeresult($res2);
      print "<tr><td colspan='2'>$system , $longname , $id_usr , $reseller</td></tr>";
      print "<tr><td width='50%'><table class='gr2table'>";
      $sum_noff=0; $sum_bank=0; $sum_rad=0;
      // собираем платежи для данного клиента
       $sql3= "select * from user_pay where name ='$system' order by date_pay";    
       if(!$res3=@mysql_query($sql3, $db2->dbConn)){print 'Query 2 failed: ' . mysql_error($db2->dbConn).' SQL: '.$sql3."<br>"; continue; }
       while($r3=mysql_fetch_assoc($res3))
       {  $date_stat=$r3['date_pay']; $pay=$r3['value_pay']; $kind_pay=$r3['kind_pay'];  $comment=$r3['comment']; 
         if ($kind_pay == 'noff') {$sum_noff+=$pay;} else {$sum_bank+=$pay;}
         print "<tr><td>$date_stat</td><td>$pay</td><td>$kind_pay</td><td>$comment</td></tr>";
       }
       $sum=$sum_bank+$sum_noff;
       print "<tr><td colspan='4'> Всего noff=$sum_noff; bank = $sum_bank ; Итого: $sum </td></tr>";
       print "</table></td><td><table class='gr2table'>";
       // теперь - то, что занесено в transaction
       mysql_freeresult($res3);
       //$sql3= "select * from transaction where debtor='$id_usr' and credit='$pay' and changed >='$date_b' and changed <='$date_e'";    
       $sql3= "select * from transaction where debtor='$id_usr' order by changed";    
       if(!$res3=@mysql_query($sql3, $db2->dbConn)){print 'Query 2 failed: ' . mysql_error($db2->dbConn).' SQL: '.$sql3."<br>"; continue; }
       while($r3=mysql_fetch_assoc($res3))
       { $pay_rad=$r3['credit']; $date_rad=$r3['changed'];  $list_found.=(strlen($list_found)>0)?','.$r3['id']:$r3['id'];
         $total++;   $nc++; $sum_rad+=$pay_rad;
       // $responce->rows[$total]['id']=$total;                      
    //  $Reply=iconv('windows-1251','UTF-8',$Reply);
    //'№','Дата','Система','Примечание','Сумма Stat','Вид','Дата Radius','Сумма Radius', 'ФИО','Реселлер','Действие','Коментарий'
    //  $responce->rows[$total]['cell']=array($total,$date_stat,$system,$comment, $pay,$kind_pay,$date_rad,$pay_rad,$longname, $reseller,'+',' -'); 
       print "<tr><td> $date_rad </td><td> $pay_rad </td><td>$longname </td> </tr>\n"; 
       }
       mysql_freeresult($res3);  
       print "<tr><td colspan='3'> Всего : $sum_rad  </td></tr>";
       print "</table></td></tr>";
    }
  print "</table>\n";
  return;
  } //function change_dogovor
 function compare_pay_by_month()
  {      // запускается только на локальной машине в 608!!
  $db = new MySQL_Radius();  
  $db->connectToDb();
  $db2 = new MySQL_Radius();  
  $db2->connectToDb();
  $date_beg=array('2011-10','2011-11','2011-12','2012-01','2012-02');
  $total=0;
  $nmax=sizeof($date_beg);
  print "<table class='grtable'>";
  for ($nd=0;$nd<$nmax; $nd++)
  { 
      $date_b=$date_beg[$nd].'-01';
      $date_e=$date_beg[$nd].'-31';
    $sql="select *  from user_pay where id >=0 and date_pay >='$date_b' and date_pay<='$date_e' order by name";
  if (!$result = @mysql_query($sql, $db->dbConn)) {
       trigger_error('Query failed: ' . mysql_error($db->dbConn).' SQL: '.$sql); die(); }
    while ($row=mysql_fetch_assoc($result)) {
   $system=$row['name']; $date_stat=$row['date_pay'];
   $pay=$row['value_pay']; $kind_pay=$row['kind_pay']; 
   $comment=$row['comment']; 
   // $comment=iconv('windows-1251','UTF-8',$comment);
    $sql2= "select id, longname, parentid as p_id, (select longname from user where id=p_id) as reseller from user where name='$system'";
    if (!$res2 = @mysql_query($sql2, $db2->dbConn)) {print 'Query failed: ' . mysql_error($db2->dbConn).' SQL: '.$sql2."<br>"; continue; }
      $r2=mysql_fetch_assoc($res2); //возвращается только одна строка
      if($r2['id'] > 0){  $id_usr=$r2['id']; $longname=$r2['longname']; } else { continue;}  
      $id_usr=$r2['id'];
      //$longname=iconv('windows-1251','UTF-8',$longname);
      $reseller=$r2['reseller'];
      //$reseller=iconv('windows-1251','UTF-8',$r2['reseller']);
      mysql_freeresult($res2);
       //$sql3= "select * from transaction where debtor='$id_usr' and credit='$pay' and changed >='$date_b' and changed <='$date_e'";    
       $sql3= "select * from transaction where debtor='$id_usr' and changed >='$date_b' and changed <='$date_e' order by debtor";    
       if(!$res3=@mysql_query($sql3, $db2->dbConn)){print 'Query 2 failed: ' . mysql_error($db2->dbConn).' SQL: '.$sql3."<br>"; continue; }
       $ws='';
       $nc=0;
       while($r3=mysql_fetch_assoc($res3))
       { $pay_rad=$r3['credit']; $date_rad=$r3['changed'];
         $total++;   $nc++;
        $responce->rows[$total]['id']=$total;                      
    //  $Reply=iconv('windows-1251','UTF-8',$Reply);
    //'№','Дата','Система','Примечание','Сумма Stat','Вид','Дата Radius','Сумма Radius', 'ФИО','Реселлер','Действие','Коментарий'
    //  $responce->rows[$total]['cell']=array($total,$date_stat,$system,$comment, $pay,$kind_pay,$date_rad,$pay_rad,$longname, $reseller,'+',' -'); 
       print "<tr><td>$total </td><td> $date_stat </td><td>$comment</td><td>$id_usr</td><td>$system </td><td> $pay </td><td> $kind_pay </td><td> $date_rad </td><td> $pay_rad </td><td> $longname </td><td> $reseller </td></tr>\n"; 
       }
       if ($nc == 0)
       {      //платеж не найден
       $total++;   
        $responce->rows[$total]['id']=$total;                      
       // $responce->rows[$total]['cell']=array($total,$date_stat,$system,$pay,$kind_pay,'-','-','-', '-','+',' -'); 
       print "<tr><td>$total </td><td> $date_stat </td><td>$comment</td><td>$id_usr</td><td>$system </td><td> $pay </td><td> $kind_pay </td><td> - </td><td> - </td><td> $longname </td><td> $reseller </td></tr>\n"; 
       }      
    }
     $total++;   
     $responce->rows[$total]['id']=$total;                      
     $responce->rows[$total]['cell']=array($total,'*','*','*','*','*','*','*', '*','*','*'); 
     print "<tr><td>$total </td><td> - </td><td>-</td><td>- </td><td> -</td><td> - </td><td> - </td><td> - </td><td> - </td><td>-</td></tr>\n"; 
  } // for ($nd )
  print "</table>\n";
 $page=0;
   $total_pages = 1;
   $responce->page = $page;
  $responce->total = $total_pages;
  $responce->records = $total;
 // echo json_encode($responce);
  return;
  } //function change_dogovor
  function test_select($w_arr)
  {
   $i=0;   
    if(isset($_GET['view'])){$class=$_GET['view']; }
    if(isset($_GET["page"])){$page=$_GET['page'];}
     if(isset($_GET["rows"])){$rows=$_GET['rows'];}
      if(isset($w_arr['view'])){$class=$w_arr['view'];}
      $i=0;
      $j=0;
    $view = new iView($class);        
    //$view->SQL_Select="select id, debtor, datepay, typepay, credit, old,new, comment ";
    if (isset($w_arr['date_begin'])) $w_arr['date_begin']=date_convert($w_arr['date_begin']);
     if (isset($w_arr['date_end'])) $w_arr['date_end'] =date_convert($w_arr['date_end']);
    $Total_list=($w_arr['parentid']=='total')? true:false;
    $total=false;
    if ($w_arr['parentid'] == 'all') 
    {$view->SQL_Select='select transaction.id as id, transaction.datepay as datepay, round(transaction.credit /100, 2) as credit, user.longname as longname, user.contract as contract, user.name as name, 
    transaction.comment as comment, transaction.typepay as typepay, transaction.debtor as debtor from transaction, user where  user.id=transaction.debtor  and transaction.datepay >=\'?$date_begin?\' and transaction.datepay <=\'?$date_end?\' {and transaction.debtor = \'?$debtor?\'} {and transaction.typepay = \'?$typepay?\'} {?!isset($debtor)? and user.longname like "?%$longname?%"}
    order by transaction.datepay, user.longname  ';
    } elseif($w_arr['parentid']=='total') //построение суммарной таблицы платежей по всем абонентам для всех реселлеров
    { $total=true;
      $d_b=$w_arr['date_begin'];$d_e=$w_arr['date_end'];
    $view->SQL_Select="call C41('".$w_arr['date_begin']."','".$w_arr['date_end']."')";
    //$view->SQL_Select="call C41('2012-10-01','2012-10-31')";
    } else {
    $view->SQL_Select='select transaction.id as id, transaction.datepay as datepay, round(transaction.credit /100,2) as credit, user.longname as longname, 
    user.contract as contract, user.name as name, transaction.comment as comment, transaction.typepay as typepay, transaction.debtor as debtor from transaction, user 
    where  user.id=transaction.debtor and transaction.datepay >=\'?$date_begin?\' and transaction.datepay <=\'?$date_end?\'  {?=isset($parentid)? and transaction.debtor in (select id from user where parentid=\'?$parentid?\')} {and transaction.typepay = \'?$typepay?\'}
    order by transaction.datepay, user.longname';
    }
    //where  user.id=transaction.debtor and transaction.datepay >=\'?$date_begin?\' and transaction.datepay <=\'?$date_end?\'  {?=isset($parentid)? and transaction.debtor in (select id from user where parentid=?$parentid?)} {and transaction.typepay = \'?$typepay?\'}';
     
      $sql_in= make_sql_query($view->SQL_Select, $w_arr);
       $arr_sql=explode(';',$sql_in);
    $nc=count($arr_sql); 
     for ($n=0;$n < $nc-1; $n++)
     {
      $sql=$arr_sql[$n];
      if (!$result = mysqli_query($view->db->dbConn,$sql)) 
    //if (!$result = @mysql_real_escape_string($sql, $this->db->dbConn))
   {  trigger_error('Query failed: ' . mysqli_error($view->db->dbConn).' SQL: '.$sql);  die(); }
   }
    $sql=($nc>1)?$arr_sql[$nc-1]:$sql_in; // Предполагается, что необходимый select будет последней командой
    if (!$result = mysqli_query($view->db->dbConn,$sql)) 
    //if (!$result = @mysql_real_escape_string($sql, $this->db->dbConn))
   {  trigger_error('Query failed: ' . mysqli_error($view->db->dbConn).' SQL: '.$sql);  die(); }
  // $num_field=mysql_num_fields($result);
  $xml='';
 //  $xml='<ROWSET setdata="'.$this->tab_name.'">';
 $total=0; $sum=0; $num=0;$cnt_tot=0;$tot_sum=0;
 if($Total_list)
 {
    while ($r=mysqli_fetch_assoc($result))
   {
     $sum+=$r['tot_sum'];
     if (($total >=($page-1)*$rows) and ($total < ($page*$rows))) {
     foreach( $r as $k=>$v){if($k != 'comment'){$r[$k]=iconv('windows-1251','UTF-8',$v);}}
     $responce->rows[$num]['cell']=array(($total+1),$r['res_name'],$r['cnt_bank'],$r['sum_bank'],
     $r['cnt_noff'],$r['sum_noff'],$r['cnt_trans'],$r['sum_trans'],$r['cnt_tot'],$r['tot_sum']);
     $responce->rows[$num]['id']=$r['rid']; 
     $num++;
     $cnt_tot+=$r['cnt_tot']; $tot_sum+=$r['tot_sum'];
     }
     $total++;
   }
 } else
 {
   /*  
   while ($r=mysqli_fetch_assoc($result))
   {
     $sum+=$r['credit'];
     if (($total >=($page-1)*$rows) and ($total < ($page*$rows))) {
     foreach( $r as $k=>$v){if($k != 'comment'){$r[$k]=iconv('windows-1251','UTF-8',$v);}}
     $responce->rows[$num]['cell']=array(($total+1),$r['datepay'],$r['name'],$r['contract'],$r['longname'],$r['credit'],$r['typepay'],$r['comment']);
     $responce->rows[$num]['id']=$r['id']; 
     $num++;
     }
     $total++;
   }
   */
   unset($users); /* дома Гребёнкина */
   $i=0;
   while ($r=mysqli_fetch_assoc($result))
   {
     $sum+=$r['credit'];
     if (($total >=($page-1)*$rows) and ($total < ($page*$rows))) {
     foreach( $r as $k=>$v)
     {
    //  if($k != 'comment'){$r[$k]=iconv('windows-1251','UTF-8',$v);}
      $users[$i][$k]=$r[$k];
     }
     $i++;
     } 
     $total++;
   }  
   $cnt_tot=$total;$tot_sum=$sum;
 } 
   $responce->page = $page;
    $responce->total = ceil($total/$rows);
     $responce->records = $total;
     $responce->setdata = $users;
     $list_cmnt="Поиcк с <b>".$w_arr['date_begin']."</b> по :<b>".$w_arr['date_end']."</b><br/>";
      if ($total == 0) {$list_cmnt.='Платежей не найдено';} else 
      { $list_cmnt.="Найдено платежей <b> $cnt_tot</b> на сумму <b>$tot_sum </b> грв.";
      }; 
     /*
      if ($total == 0) {$list_cmnt['finded_rec']='Платежей не найдено';} else 
      { $list_cmnt['finded_rec']="Найдено платежей <b> $total</b>"; 
        $list_cmnt['tot_sum']=$sum; 
      }
      $list_cmnt['date_search']='Поиcк с <b>'+$w_arr['date_begin']+ '</b> по :<b>'+$w_arr['date_end']+'</b>';
      foreach( $list_cmnt as $k=>$v){$list_cmnt[$k]=iconv('windows-1251','UTF-8',$v);}
      */
      $list_cmnt=iconv('windows-1251','UTF-8',$list_cmnt);
      $responce->list_cmnt= $list_cmnt;
     echo json_encode($responce);
  } //test_select
  function select_AC($w_arr)
   {   // используется для выполненя запросов autocompleet и $.json для операции "Добавить платеж"""
      // находит данные для autocompleet, возвращает строку
      global $is_work;
      
   $AC_Select['dog_number']='select distinct contract as text, id,longname, name, parentid from user where id >=0 {and contract like \'?$quest?%\'}';   
   $AC_Select['lastname']='select distinct longname as text, id, contract, name, parentid, address as addr_20, round(account/100,2) as acnt from user where id >=0 {and longname like \'?$quest?%\'}';
   $AC_Select['name']='select distinct name as text, id, contract, name, parentid from user where id >=0 {and name like \'?$quest?%\'}';
   $sql= make_sql_query($AC_Select[$w_arr['AC_name']], $w_arr); //ищется частичное совпадение
   if ($is_work) {$db = new MySQL_Radius();  }
   else {$db = new MySQL();}
    $db->connectToDb();
   if (!$result = @mysql_query($sql, $db->dbConn))
   {
     trigger_error('Query failed: ' . mysql_error($db->dbConn).' SQL: '.$sql);
     die();
   }
        $nr=0; $comma='';
      $json_res='['; $json_res_arr=array();
   while ($row=mysql_fetch_assoc($result)){
       if($nr > 0) $comma=",";  $nr++;
       $json_arr = array();
     foreach ($row as $k=>$v){
         $i=0;
      if ($is_work) {$v=iconv('windows-1251','UTF-8',$v);}
     $json_arr[$k] = $v;
     }
      $json_res.= $comma.'[' . join(',', $json_arr) . ']';
      $json_res_arr[]=$json_arr;
      unset($json_arr);
   }
   echo json_encode($json_res_arr);
   
   }
   function add_user_pay($w_arr)
   {
        global $is_work;
       foreach($w_arr as $key => $value) {$$key=$value;}
       $datepay=date_convert($datepay);
       $credit_n=$credit*100;
       $credit_n=round($credit_n+0.01);
       $sql="insert into transaction values(null,'1','$debtor','$datepay','$typepay','0','$credit_n','0','$comment',null)";
   if ($is_work){    $db = new MySQL_Radius();  }
   else {$db = new MySQL();  }
    $db->connectToDb();
   if (!$result = @mysql_query($sql, $db->dbConn))
   {
     trigger_error('Query failed: ' . mysql_error($db->dbConn).' SQL: '.$sql);
     die();
   }
   $new_id=mysql_insert_id($db->dbConn); 
   if ($add_stat ==1 ){
       $dbn= new MySQL();  
       $dbn->connectToDb();
   if ($typepay == 'bank')
   {
     $sql="insert into payments_bank values(null,'$system','$datepay','$credit','$name','$comment','','','','')";
     if (!$result = @mysql_query($sql, $dbn->dbConn))
     {trigger_error('Query failed: ' . mysql_error($dbn->dbConn).' SQL: '.$sql);  die();}
   }
   if ($typepay == 'noff')
   {
       $remark="0#g#$name $comment";
       $sql="insert into payments_noff values(null,'$system','$datepay','$credit','0.0','0.0','$remark','8.0')";
       if (!$result = @mysql_query($sql, $dbn->dbConn))
     {trigger_error('Query failed: ' . mysql_error($dbn->dbConn).' SQL: '.$sql);  die();}
   }   
   }
   
   $ws='Запись успешно добавлена!';
    $ws=iconv('windows-1251','UTF-8',$ws);
    $response->reply=$ws;
    $response->id=$new_id;
    echo json_encode($response);
    
 } // end function
 function user2abonents($date_beg, $date_end)
 {   //Программа переносит данные из таблицы radius.user в таблицу на documents.abonents
  $db = new My_SQLi_A('documents');
   //$db->dbName='documents'; 
   //$db->connectToDb(); 
  $db2 = new My_SQLi_A('documents');  
  // $db2->dbName='documents'; $db2->connectToDb(); 
  $user_login='bvv';
  $today=set_today_str_m();
  $f2e='./result/update_dogovor_'."$today".'.txt';
  $out=fopen($f2e,'a');
  $sql_update="update abonents set Who_modify='auto', {Director='?'},{Date_reg='?'},{Tel='?'},
  {Name_org='?'}, {Post_addr='?'}, {Systema='?'} where N_dogovora like '?'";
  /*
  $sql_insert="insert into abonents  set Who_modify='auto', Is_dogovor='Да',Valid_dogovor='Да', N_dogovora='?', {Date_reg='?'},{Tel='?'}, {Name_org='?'}, {Post_addr='?'}, {Systema='?'}, {Director='?'}" ;  
  $sql_update_prp="update abonents set Who_modify='auto', Date_reg=?,Tel=?,
  Name_org=?, Post_addr=?, Systema=?, Director=? where N_dogovora like ?";
  $sql_insert_prp="insert into abonents  set Who_modify='auto', Is_dogovor='Да',Valid_dogovor='Да',  
  Date_reg=?,Tel=?, Name_org=?, Post_addr=?, Systema=?, Director=?, N_dogovora=?" ;
  */
  $sql_mod= "select  max(modify) as modify_beg from abonents where who_modify='auto'";
     if (!$res = @mysqli_query($db->dbConn, $sql_mod)) {trigger_error('Query failed: ' . mysqli_error($db->dbConn).' SQL: '.$sql_modify); die(); }
       $row=mysqli_fetch_assoc($res); //возвращается только одна строка
       $modify_beg=$row['modify_beg'];
       if (!$modify_beg) {$modify_beg='2012-10-25 0:0:0';}
  // Радиус                                      
  $dbr = new MySQLi_Radius();  
  //$dbr = new MySQLi();  
 // $dbr->dbName='radius';
  //$dbr->connectToDb();
  //$sqlr_select="select  contract,name,parentid as p_id, (select longname from user where id = p_id) as reseller, date_start,phone,longname,address from user where id >=0 and active = '1' and date_start >= '".$date_beg."' and date_start <= '".$date_end."'";
  $sqlr_select="select  contract,name,parentid as p_id, (select longname from user where id = p_id) as reseller, date_start,phone,longname,address, changed from user where changed > '$modify_beg' and groupid = '3'";
  if (!$result = @mysqli_query($dbr->dbConn,$sqlr_select)){trigger_error('Query failed: ' . mysqli_error($dbr->dbConn).' SQL: '.$sqlr_select);die(); }
  $total=0;
   while ($row=mysqli_fetch_assoc($result)){
    if ($total > 1) break;
   unset($w_a); $id=0;
   if (strlen($row['longname'])==0) continue;
   if (strlen($row['contract']) < 4) continue;
   if (!preg_match('/^_/',$row['name'])) continue; // если имя системы не начинается на _, то данные по договору не обрабатываются
   $changed=$row['changed'];
   $n_dog=$row['contract'];
   $sql2= "select id, modify from abonents where N_dogovora='$n_dog' limit 1";
   $is_insert=false;
     if (!$res_2 = @mysqli_query($db2->dbConn,$sql2)) {trigger_error('Query failed: ' . mysqli_error($db2->dbConn).' SQL: '.$sql2); die(); }
     $row_2=mysqli_fetch_assoc($res_2); //возвращается только одна строка
     if($row_2['id'] > 0){  $id=$row_2['id']; $sql=$sql_update;} else { $sql=$sql_insert; $is_insert=true;}
     if(!$is_insert and ($row_2['modify']>= $changed)) continue;
          
     //------------------------  
     foreach ($row as $k=>$v)
     { 
     // if (!$v) continue;   
     // if ($v == 0) continue;  
     $v=trim($v);  
      if (strlen($v)==0) continue;   
   //   $v1=iconv('UTF-8','windows-1251',$v);   $v=$v1;
     if($k=='contract' and strlen($v)>0){$w_a['N_dogovora']=$v; }
     if($k=='name' and strlen($v)>0){$w_a['Systema']=$v;}
     if($k=='date_start' and strlen($v)>0){$w_a['Date_reg']=$v;}
     if($k=='longname' and strlen($v)>0){$w_a['Name_org']=$v;}
     if($k=='phone' and strlen($v)>0){$w_a['Tel']=$v;}
     if($k=='address' and strlen($v)>0){$w_a['Post_addr']=$v;}
     if($k=='reseller' and strlen($v)>0){$w_a['Director']=$v;}
     }
     //foreach($w_a as $key=>$value) {$w_a[$key]=mysql_real_escape_string($value,$dbr->dbConn);}
     //  они уже в этой таблице хранятся с \. Добавление еще одного веде к ошибке в SQL запросе.
     $sys=$w_a['Systema'];   $n_dog=$w_a['N_dogovora'];
  //$sql2= "select id from abonents where Systema='$sys' and N_dogovora='$n_dog' limit 1";
       $w_a['id']=$id;
       $sql=make_sql_query($sql,$w_a);
       if($res_3=mysqli_query($db2->dbConn,$sql)){
       $n_afct=mysqli_affected_rows($db2->dbConn);
       if ($n_afct == 0) continue;    
      $Reply=$is_insert?'Добавлен':'Обновлен';
       foreach($w_a as $key=>$value){$w_a[$key]=iconv('windows-1251','UTF-8',$value);}; 
       if (!isset($w_a['Date_reg'])) $w_a['Date_reg']='-';
       if (!isset($w_a['Name_org'])) $w_a['Name_org']='-';
       if (!isset($w_a['Tel'])) $w_a['Tel']='-';
       if (!isset($w_a['Director'])) $w_a['Director']='-';
       if (!isset($w_a['Post_addr'])) $w_a['Post_addr']='-';
       
      $wss=$total."\t".$Reply."\t".$w_a['N_dogovora']."\t".$w_a['Systema']."\t".$w_a['Name_org']."\t".$changed."\t".$w_a['Date_reg']."\t".$w_a['Post_addr']."\t".$w_a['Tel']."\n";
      $Reply=iconv('windows-1251','UTF-8',$Reply);
      //$wss=$w_a['N_dogovora']\t$w_a['Systema']\t $w_a['Name_org] \t $w_a['Date_reg']\t $w_a['Post_addr'] \t $w_a['Tel']\n" ;
      $responce->rows[$total]['cell']=array($total,$Reply, $w_a['N_dogovora'],$w_a['Systema'],$w_a['Name_org'],$changed,$w_a['Date_reg'],$w_a['Post_addr'],$w_a['Director']);
      $responce->rows[$total]['id']=$total+1;                
   //   $responce->rows[$total]['cell']=array($total,$Reply,$date,$n_dog,$sys,$longname,$address,$phone); 
      fwrite($out,$wss);      
      $total++;
        } else
        { 
            trigger_error('Query failed: ' . mysqli_error($db->dbConn).' SQL: '.$sql);  
        }
   }  // while ($row)
     if ($total == 0){ $responce['response']= iconv('windows-1251','UTF-8','Новых договоров  не найдено !') ;}
     echo json_encode($responce);
   fwrite($out,"-------\n");      
   fclose($out);
 } // function user2abonents

  ?>