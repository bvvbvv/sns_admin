  <?php
  function print_head()
{
?>    
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<title>MLM Интернет </title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<HEAD> 
<script src="./js/datatables.min.js"></script>
<script src="./js/jquery-3.7.1.min.js"></script>
 <!-- <script language='javascript' src="./JS_Lib/jquery.json-2.2.js"></script>
 <script language='javascript' src=".   /JS_Lib/JsHttpRequest.js"></script>
 <script language='javascript' src="./JS_Lib/jquery.listen-min.js"></script>
 <script language='javascript' src="./JS_Lib/jquery.keyfilter-1.7.min.js"></script>
 <script language='javascript' src="./JS_Lib/jquery-ui-1.8.16.custom.min.js"></script>  -->
 
 <!-- Мои скрипты -->
 <script language='javascript' src="abonents.js"></script>
 <link rel="stylesheet" type="text/css" href="./main_int.css">    
</HEAD>
<!-- <body onload="prepare_plugins();"> -->
<div id="debug"></div> 
<?php
}
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
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{2,4})/',$str,$m)){$str="$m[3]-$m[2]-$m[1]";  } //ехсел 
      return $str;

  }
  function convert_date($str)
  { //преобразует дату вида 2009-01-01 в 1 января 2009
    global $mon_rus;
    if (!$str) return '';
    if (! preg_match('/^(\d{4})-0?(\d{1,2})-(\d{1,2})/',$str,$m)) return;
     //$rus_mon=array_flip($mon_rus);
    $mon=$mon_rus[$m[2]-1];
    return "$m[3] $mon $m[1]";
  }
  ?>