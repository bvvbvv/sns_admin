<? 
// --------------- Часть стандарнтых функций --------------     
function make_sql_query($sql,$arr)
  {
    reset($arr);
  while (list($key, $value)= each($arr)) {$$key=$value;}
   $reg_arr=array('/\{(.*?)\}/','/\?\$(\w*)\?/','/[,|\s*](\w*)\s*(like|\>=|=|<=|\!=)\s*\'[%_]?\?[%_]?\'/');
   for($nreg=0;$nreg<3;$nreg++)
   {
    $reg=$reg_arr[$nreg];
    //$reg=$reg_arr[2];
   //$n=preg_match_all('/\{(.*?)\}/',$sql, $match,PREG_SET_ORDER); //список вариантных полей
   $n=preg_match_all($reg,$sql, $match,PREG_SET_ORDER);
   if ($n ==0) continue;
   foreach($match as $opt)
   {
      $pos=$opt[0];
      if($nreg==0){$par=$opt[1];} else {$par=$pos;}
      $n_pos='';
      if(preg_match('/\?([^\?]*)\?/',$par, $m_par) > 0) //вариант поиска между двух знаков ? выисление функции и подстановка переменных
      {
         $n++;
         $ws=substr($m_par[0],0,2);
         //$ws2=substr($m_par[1],1,1);
         switch ($ws) {
           case '?!': //знак ?! если следующее за ним выражение не справедливо - то вариантное выражение остается.
           case '?=':  //знак ?= если следующее за ним выражение справедливо - то вариантное выражение остается.
           $ww= substr($m_par[1],1);
            eval("\$test=$ww;");
            if($ws=='?!'){$test=!$test;}
            if($test){$n_pos=preg_replace('/\?[^\?]*\?/','',$par);} else {$n_pos='';}
          break;
          case '?#': //знак ?# ледующее за ним выражение нужно вычислить (eval) и подставить
            $ww= substr($m_par[1],1);
            eval("\$value=$ww;");
            if(isset($value)){$n_pos=preg_replace('/\?[^\?]*\?/',$value,$par);} else {$n_pos='';}
          break;
          case '?$': //знак ?$ cледующее за ним выражение - имя переменной значение которой нужно взять из массива $arr['name'] и подставить   на это место
                     // если оно отсутствует - вариантная часть удаляется вместе с {}
           $name___=substr($m_par[1],1);
           if (isset($$name___)) {  $value=$$name___; $n_pos=preg_replace('/\?[^\?]*\?/',$value,$par); }
           elseif($nreg >0){ trigger_error("Make_sql: Missing parameters $name___ in $sql");  die();}
           break;
           default:
           continue;
         }
      }else{
        $n++;
       //if(preg_match('/\'\S*\?\S*\'/',$par, $m_par) > 0)
       if(preg_match('/\'\W*\?\W*\'/',$par, $m_par) > 0)
       {  // вариант вида '%?%' -
      //на  место ? вставляется значение переменной с именем поля в левой части оператора (system like $system).
           //значение берется из массива $arr

           $i=preg_match('/^(and|or|,)?\s*([\.\w]*)\s*(like|\>=|=|<=|\!=)?/',$par,$m_val);
           $name___=$m_val[2];
           if (isset($arr[$name___])) {  $value=$arr[$name___];  $n_pos=preg_replace('/\?/',$value,$par); }
           elseif($nreg > 0){ trigger_error("Make_sql: Missing parameters $name___ in $sql"); die();}
      }
      }
     $sql=str_replace($pos,$n_pos,$sql);
   }
   }
   //убираем лишние запятые перед select, where и внутри
   preg_match('/,\s*,/',$sql,$m_z);
   while(count($m_z)>0){
   $sql=preg_replace('/,\s*,/',',',$sql);//убираем двойные  множественные запятые:
   preg_match('/,\s*,/',$sql,$m_z);
   }
   $sql=preg_replace('/ select\s*,/',' select ',$sql);//убираем лишние запятые после select
   $sql=preg_replace('/ set\s*,/',' set ',$sql);//убираем лишние запятые после set
   $sql=preg_replace('/,\s*from/',' from' ,$sql);//убираем запятые перед from
   $sql=preg_replace('/,\s*where/',' where' ,$sql);//убираем запятые перед where
   $sql=preg_replace('/,\s*$/','' ,$sql);//убираем запятые в конце предложения (бывает)
   $sql=preg_replace('/\swhere\s*and\s/',' where ' ,$sql);//убираем and после where   (бывает)
   $sql=preg_replace('/\swhere\s*or\s/',' where ' ,$sql);//убираем or после where  (бывает) 
   return $sql;
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
   

// ---------------- окончание стандартных функций ----------
class View
{ // Класс для работы  c Представлениями простых таблиц
  var $db;
  var $def_name;
  var $view_name; //Название представление в котором участвует данная таблица
  var $tab_name;
  var $tab_head;
  var $SQL_Select=null;
  var $SQL_Select_GBN=null;
  var $SQL_Update=null;
  var $SQL_Insert=null;
  var $SQL_Delete=null;
  var $List_Select=array(); //запросы на select из всех таблиц   
  var $AC_Select=array(); //запросы на AutoComplete из всех таблиц   
  function View($view_name)
  { //Конструктор таблицы
    $this->db = new MySQL();
    $this->Page_Size=20;
    $this->view_name=$view_name;
    $this->tab_name=$view_name;
    $this->get_view_property();
    //Используются для записи журнала
    $this->curr_id=NULL; //Номер текущей записи
   } //  function Table($table_name)
   //  вставка новой части
    function get_list_select($w_arr)
   {   // возвращает json - наборы данных для использования в select 
    $list_name=$w_arr['list_name'];
    $sql=$this->List_Select[$list_name];
    if (!isset($sql)) return;
    $sql= make_sql_query($sql, $w_arr);
    if (!$result = @mysql_query($sql, $this->db->dbConn))
   {
     trigger_error('Query failed: ' . mysql_error($this->db->dbConn).' SQL: '.$sql);
     die();
   }
        $nr=0; $comma='';
      $json_res='['; $json_res_arr=array();
   while ($row=mysql_fetch_assoc($result)){
       if($nr > 0) $comma=",";  $nr++;
       $json_arr = array();
     foreach ($row as $k=>$v){$v=iconv('windows-1251','UTF-8',$v);
     $json_arr[$k] = $v;
     }
      $json_res.= $comma.'[' . join(',', $json_arr) . ']';
      $json_res_arr[]=$json_arr;
      unset($json_arr);
   }
   echo json_encode($json_res_arr);
   }
      function get_field_list($data)
{  //получаем список полей таблицы , значенмя полей пустые, кроме даты
  preg_match("/^select\s+\*\s+from/",$this->SQL_Select,$m1); 
  if(sizeof($m1) > 0)
  { // находим список полей из наблицы
      $sql= "show columns from $this->tab_name";
      if (!$result = @mysql_query($sql, $this->db->dbConn)){  trigger_error('Query failed: ' . mysql_error($this->db->dbConn).' SQL: '.$sql);  die(); }
      while ($row=mysql_fetch_assoc($result))
     {    
       $field_list[]=$row['Field'];
     }//   while ($row=mysql_fetch_assoc($result))
      mysql_free_result($result);  
  } else
  { // находим список полей из запроса Select
        $w_arr=explode(" from", $this->SQL_Select);   
        $ws=$w_arr[0].',';
        preg_match_all("/\s+(\w*)\s*,/" , $ws, $match);
        $field_list=$match[1];  
  }
   $xml='<ROWSET setdata="'.$this->tab_name.'">';
   $xml.='<ROW>';
   $date=set_today_str();
   foreach($field_list as $field)
   {
        $value='';
        $field=strtolower($field);
       if ($field == 'date'){$value=$date;}
       if ($field == 'id'){$value=0;}
       $w='<'.$field.'>'.$value.'</'.$field.'>';
        $xml.=$w;  
   }
    $xml.='</ROW>';
    $xml.='</ROWSET>';
   return $xml;
}
   function select_AC($w_arr)
   {   // используется для выполненя запросов autocompleet и $.json
      // находит данные для autocompleet, возвращает строку
  $sql= make_sql_query($this->AC_Select[$w_arr['AC_name']], $w_arr); //ищется частичное совпадение
   if (!$result = @mysql_query($sql, $this->db->dbConn))
   {
     trigger_error('Query failed: ' . mysql_error($this->db->dbConn).' SQL: '.$sql);
     die();
   }
        $nr=0; $comma='';
      $json_res='['; $json_res_arr=array();
   while ($row=mysql_fetch_assoc($result)){
       if($nr > 0) $comma=",";  $nr++;
       $json_arr = array();
     foreach ($row as $k=>$v){$v=iconv('windows-1251','UTF-8',$v);
     $json_arr[$k] = $v;
     }
      $json_res.= $comma.'[' . join(',', $json_arr) . ']';
      $json_res_arr[]=$json_arr;
      unset($json_arr);
   }
   echo json_encode($json_res_arr);
   }
   
   function get_shema()
   {   // получема шаблоны таблиц данных, схемы редактирования edit_shema и схемы сохранения записей представления view_data
        global $user_group;
       $filename = "./sklad_new_cfg.xml"; //таблица с со свойствами представления.
       $html=file_get_html($filename);
       $ws="div[group*='".$user_group."'] div[view*='".$this->view_name."']";
       $shema_set=$html->find($ws,0);    
       $table_templ=$shema_set->find('div[name=table_template]',0)->innertext;
       $edit_shema=$shema_set->find('div[name=edit_shema]',0)->innertext;
       $view_shema=$shema_set->find('div[name=view_shema]',0)->innertext;
       return array($table_templ, $edit_shema, $view_shema);   
   }
   function get_view_property(){
       global $user_group;
       $filename = "sklad_new_cfg.xml"; //таблица с со свойствами представления.   
       $html=file_get_html($filename);  
       $ws="div[group*='".$user_group."'] div[name='select_list']";
       $list_set=array();
       $l_set=$html->find($ws,0);
       if($l_set){$list_set=$l_set->find('div[list_name]');}
       foreach ($list_set as $elem)
       {
          $l_name=$elem->getAttribute('list_name');
          $this->List_Select[$l_name]=html_entity_decode($elem->plaintext); 
       }
       //Список AutoComplete запросов
        $ws="div[group*='".$user_group."'] div[name='ac_list']";
       $list_set=array();
       $l_set=$html->find($ws,0);
       if($l_set){$list_set=$l_set->find('div[ac_name]');}
       foreach ($list_set as $elem)
       {
          $l_name=$elem->getAttribute('ac_name');
          $this->AC_Select[$l_name]=html_entity_decode($elem->plaintext); 
       }
       
       $ws="div[group*='".$user_group."'] div[view*='".$this->view_name."'] div[name='sql']";
       $sql_set=$html->find($ws,0);        
       if ($sql_set)
       {
         $sql=$sql_set->find('div[sql=sql_select]',0);  
       if(count($sql)>0) { $this->SQL_Select=$sql->plaintext;}
       $sql=$sql_set->find('div[sql=sql_select_group_by_name]',0);  
       if(count($sql)>0) { $this->SQL_Select_GBN=$sql->plaintext;}
       $sql=$sql_set->find('div[sql=sql_select_id]',0);
       if(count($sql)>0) { $this->SQL_Select_ID=$sql->plaintext;}
       $sql=$sql_set->find('div[sql=sql_update]',0);
       if(count($sql)>0) { $this->SQL_Update=$sql->plaintext;}
       $sql=$sql_set->find('div[sql=sql_insert]',0);
       if(count($sql)>0) { $this->SQL_Insert=$sql->plaintext;}
       $sql=$sql_set->find('div[sql="sql_delete"]',0);
       if(count($sql)>0) { $this->SQL_Delete=$sql->plaintext;}
       $sql=$sql_set->find('div[sql=sql_select_AC]',0);
       if(count($sql)>0) { $this->SQL_Select_AC=$sql->plaintext;}
       $sql=$sql_set->find('div[sql="sql_search_all"]',0);
       if(count($sql)>0) {$this->SQL_Search_All_Field=$sql->plaintext;
       $this->SQL_Search_All_Field=html_entity_decode($this->SQL_Search_All_Field);} 
     //  $this->SQL_Field_List=$sql_set->find('div[sql=sql_field_list]',0)->plaintext;
       $this->SQL_Select=html_entity_decode($this->SQL_Select);
       }
               
  }
  function select($data)
{ // находит данные по запросу , данные берутся из $_REQUEST
// в массиве data передаются все величины из полей input;
// в addition - из selection и отмеченные checbox из формы
  //global $_REQUEST;
  global $user_level, $user_login, $addition, $is_today;
  reset($_REQUEST);
  while (list($key, $value)= each($_REQUEST)) {$$key=$value;}
  $w_arr=array(); $setdata=''; $total_sum=0.0; $nr=0; $nr2=0; $nr3=0;
   $group_pay='detailed'; $setdata2=''; $total_sum2=0.0;
  $w_arr['user_level']=$user_level;  $w_arr['user_login']=$user_login;
  $w_res1=array();  $w_res2=array(); $w_res3=array();
  if(isset($data)){ reset($data); foreach($data as $opt){if(strlen($opt['value'])>0){$w_arr[$opt['name']]=$opt['value'];}}}  
  if(isset($addition)){ reset($addition); foreach($addition as $opt){$$opt['name']=$opt['value'];$w_arr[$opt['name']]=$opt['value'];}}
    if (isset($w_arr['date_begin'])) $w_arr['date_begin']=date_convert($w_arr['date_begin']);
  if (isset($w_arr['date_end'])) $w_arr['date_end'] =date_convert($w_arr['date_end']);
   $is_today=1;
    if (isset($w_arr['date_end'])){ $date_oper=trim($w_arr['date_end']);  $today=trim(date('Y-n-d')); $is_today=($today == $date_oper)?1:0;}
   $setdata='<ROWSET setdata="'.$this->view_name.'"></ROWSET>';      //если ничего не будет найдено - отправляется пустой набор данных
   if(isset($group_by_name)){$sql= make_sql_query($this->SQL_Select_GBN, $w_arr);}
   else {
      if (isset($w_arr['id_in'])){ $sql= make_sql_query($this->SQL_Select_ID, $w_arr);}
      else 
       {$sql= make_sql_query($this->SQL_Select, $w_arr);}
   }
   $sql=stripslashes($sql); 
   list($setdata,$nr)=$this->select_XML($sql);
   $setdata='<ROWSET setdata="'.$this->view_name.'">'.$setdata.'</ROWSET>';
   $setdata.= "<c update_fld='y' setdata='list_cmnt'>"; // аттрибут update_fld используется для того, чтобы указать, что в setdata
  // с именем list_cmnt нужно обновить только те поля, которые будут переданы в этом наборе. Остальные не меняются.
   $ws=($nr >0)?" Найдено записей:<b>$nr</b>":' Записей не найдено.';
   $setdata.="<finded_rec>$ws</finded_rec>";
   if (isset($w_arr['date_begin'])) {$ws='Поиск с <b>'.$w_arr['date_begin'].'</b>';}
   if (isset($w_arr['date_end'])){$ws.=' по <b>'.$w_arr['date_end'].'</b>'; }
   $setdata.='</c>';
  return $setdata;
}
function Select_XML($sql_in)
  {
      global $is_today;
      //$sql=$this->SQL_Select;
     $arr_sql=explode(';',$sql_in);
    $nc=count($arr_sql); 
     for ($n=0;$n < $nc-1; $n++)
     {
      $sql=$arr_sql[$n];
      if (!$result = mysql_query($sql, $this->db->dbConn)) 
    //if (!$result = @mysql_real_escape_string($sql, $this->db->dbConn))
   {  trigger_error('Query failed: ' . mysql_error($this->db->dbConn).' SQL: '.$sql);  die(); }
   }
    $sql=($nc>1)?$arr_sql[$nc-1]:$sql_in; // Предполагается, что необходимый select будет последней командой
    if (!$result = mysql_query($sql, $this->db->dbConn)) 
    //if (!$result = @mysql_real_escape_string($sql, $this->db->dbConn))
   {  trigger_error('Query failed: ' . mysql_error($this->db->dbConn).' SQL: '.$sql);  die(); }
  // $num_field=mysql_num_fields($result);
  $xml='';
 //  $xml='<ROWSET setdata="'.$this->tab_name.'">';
   while ($row=mysql_fetch_assoc($result))
   {
     $xml.='<ROW>';
     foreach ($row as $name => $value)
     {  $value=htmlspecialchars($value);
       $class='';
         $w='<'.$name.'>'.$value.'</'.$name.'>';
         if (strtolower($name)== 'rest') {
         if ($value >0)
          {  $img=($is_today > 0)?'image/add_20.gif':'image/t_dot.gif';
          } else // Если нет - нельзя
          {    $img='image/t_dot.gif'; //прозрачный img 1x1
          }  $w.="<rest_sign>$img</rest_sign>";
         }
         $xml.=$w;
     } //  foreach ($field_list as $name) 
     $xml.='</ROW>';  
    } //   while ($row=mysql_fetch_assoc($result))  
    $nr=mysql_num_rows($result);
    mysql_free_result($result);
    return array($xml,$nr);
  }
  function update($w_arr)
  {
       global $Reply, $user_login, $user_level;   //в $Reply заносится то, что будет показываться в allert или confirm в javascript
       
        $is_insert=false;
        if (isset($w_arr['date'])) $w_arr['date']=date_convert($w_arr['date']);
        if (isset($w_arr['date_oper'])) $w_arr['date_oper']=date_convert($w_arr['date_oper']);
        $w_arr['user_login']=$user_login; $w_arr['user_level']=$user_level;
        if($w_arr['id']<=0) {$sql=$this->SQL_Insert;$is_insert=true;}
        else{ $sql=$this->SQL_Update;}
        $sql= make_sql_query($sql,$w_arr);
         if(@mysql_query($sql))
        {
            $Reply=$is_insert?'Запись успешно добавлена!':'Данные успешно обновлены !';
        } else
        {
          trigger_error('Query failed: ' . mysql_error($this->db->dbConn).' SQL: '.$sql);
           die();
        }
        // нужно возвратить строку с такими же тэгами, которые вставляются в запросе select
//        $sql=$this->SQL_Select_ID;
        $sql=$this->SQL_Select_ID;
        $w_arr_n['id']=$w_arr['id'] ;
        if($is_insert) {$w_arr_n['id']=mysql_insert_id($this->db->dbConn);}
        $sql= make_sql_query($sql,$w_arr_n);
        list($ds,$nr)=$this->select_XML($sql);
        $ws=''; $cmd='update';
        if($is_insert)  {  $ws='update_search=\'0\''; $cmd='insert'; }
          $setdata='<ROWSET update_row=\'id\' '.$ws.' action="'.$cmd.'" setdata="'.$this->tab_name.'">'.$ds.'</ROWSET>';

     //   $setdata='<ROWSET setdata="update_row">'.$setdata.'</ROWSET>';
    //   $setdata=preg_replace('/^<ROWSET/','<ROWSET update_row=\'id\'',$setdata);
   //  $ws= recalc_field($w_arr,$cmd);
 //    $setdata.= $ws;

      return $setdata;
  } //  function update($xml)
  function delete($w_arr)
  {  //Удаляет из таблицы записи с номером из тега ID
     global $Reply, $error;   //в $Reply заносится то, что будет показываться в allert или confirm в javascript
       /*
        $error=$this->check_delete($w_arr);
       if(strlen($error) >0) { return;}
        */
        $error='';
        $sql=$this->SQL_Delete;
        $sql= make_sql_query($sql,$w_arr);
         if(@mysql_query($sql))
        { $Reply='Запись удалена!';
        } else
        {   trigger_error('Query failed: ' . mysql_error($this->db->dbConn).' SQL: '.$sql);
            die();
        }
       // $Reply='Запись удалена!';
         $setdata='<ROWSET update_row=\'id\' action=\'delete\' setdata="'.$this->tab_name.'"><id>'.$w_arr['id'].'</id></ROWSET>';
      //   $setdata.=recalc_field($w_arr,'delete');
        return $setdata;
      
  }
   // ------------ конец вставки новой части
   function check_update($arrs)
   {
    return;
   }
   function check_delete($arrs)
   {
    return;
   } 
} //  class View
class sklad_expense extends View
{
  function transfer($xml)
  {global $user_login;
    $this->update($xml); // записываем расход
    //Теперь делаем приход
    //Сначала получим новые параметры
    $arrM=XMLtoArray($xml); //здесь $arr должен быть одномерным хеш-мпссивом
     $arr=$arrM['ROW'];
     reset($arr);
     while($war=each($arr))
     {
       list($key,$value)=$war;
       $key=strtolower($key);
       $arrs[$key]=$value;
     }
     $prihod_id=$arrs['prihod_id'];
     $quantity=$arrs['quantity'];
     $who_take=$arrs['sklad_name_old'];
//     $sklad_name_old=$arrs['sklad_name_old'];
//     $sklad_name_new=$arrs['sklad_name_new'];
     $sklad_id_new=$arrs['sklad_id_new'];
     // Получим предыдущие данные по этому товару
     $sql="select * from sklad_prihod where id=$prihod_id";
     $result = @mysql_query($sql, $this->db->dbConn);
     $arrn=mysql_fetch_assoc($result);
     $arrn['user_login']=$user_login;
     $arrn['place_id']=$sklad_id_new;
     $arrn['quantity']=$quantity;
     $arrn['who_take']=$who_take;
     $arrn['date_oper']=date("Y-m-d");
     $sklad_prihod=new sklad_prihod('sklad_prihod');
     $sql=$sklad_prihod->SQL_Insert;
     $sql=subst_param($sql, $arrn);
     if(@mysql_query($sql))
     {
       $new_ID=mysql_insert_id($sklad_prihod->db->dbConn);
       settype($new_ID,"string");
       return "OK. Операция проведена";
     } else
     {
       trigger_error('Query failed: ' . mysql_error($sklad_prihod->db->dbConn).' SQL: '.$sql);
       die();
     }
  } // function transfer
} //class sklad_expense
class sklad_rest  extends View
{
   function Select_XML_old($where)
   { //
    global $OS, $user_level, $user_group, $usr_sklad;
//    $sql1=($this->SQL_Select)?$this->SQL_Select:'';
 if (isset($_POST['group_by_name']))
 {
   if($_POST['group_by_name'] == 'yes')
   {
     list($xmls, $nrec)=$this->Select_XML_group($where);
     return array($xmls,$nrec);
   }
 }
$add_where='';
$is_today=1;
if (isset($_POST['date_oper']))
{ $date_oper=trim($_POST['date_oper']);
  $add_where=" and sklad_prihod.date_oper <= '$date_oper'";
  $today=trim(date('Y-m-d'));
  $is_today=($today == $date_oper)?1:0;
}
$show_zero=false;
if (isset($_POST['show_zero']))
{
  $show_zero=($_POST['show_zero'] == 'yes')?true:false;
}
//$sql1="select sklad_prihod.id as id, sklad_prihod.name as name, sklad_prihod.sklad_number as sklad_number, sklad_prihod.serial_number as serial_number, sklad_group.name as group_name, sklad_group.kode as kode, sklad_prihod.quantity as prihod, sklad_prihod.supplayer_id as supplayer_id, sklad_unit.name as unit_name, sklad_place.name as sklad_name, sklad_prihod.place_id as place_id from sklad_prihod, sklad_unit, sklad_group, sklad_place where sklad_group.id=sklad_prihod.tovar_id and sklad_place.id=sklad_prihod.place_id and sklad_unit.id=sklad_group.unit_id $add_where order by sklad_prihod.date_oper, name";
$sql1="select sklad_prihod.id as id, sklad_prihod.name as name, sklad_prihod.sklad_number as sklad_number, sklad_prihod.serial_number as serial_number, sklad_group.name as group_name, sklad_group.kode as kode, sklad_prihod.quantity as prihod, sklad_prihod.supplayer_id as supplayer_id, sklad_unit.name as unit_name, sklad_place.name as sklad_name, sklad_prihod.place_id as place_id sklad_prihod.comment as comment from sklad_prihod, sklad_unit, sklad_group, sklad_place where sklad_group.id=sklad_prihod.tovar_id and sklad_place.id=sklad_prihod.place_id and sklad_unit.id=sklad_group.unit_id $add_where order by sklad_prihod.date_oper, name";
    $arrs['user_level']=$user_level;
    $sql=subst_param($sql1, $arrs);
    $add_where='';
    if (preg_match("/ where /", $sql))
    {
      if(strlen($where) > 1) {$add_where.=' and '. $where;}
    } else
    {
       if(strlen($where) > 0) {$add_where.=" where $where";}
    }
      if(preg_match('/\sgroup by|\shaving|\sorder by/', $sql)) //Если в sql условия поиска нужно вставить в средину запроса (сразу перед дополнительными условиями)- втавляем
      {
        $replace="$add_where \$1";
        $sql=preg_replace('/(\sgroup by|\shaving|\sorder by)/',$replace,$sql,1);
      } else // Если нет - Добавляем в конец
      {
        $sql.=$add_where;
      }

      if (!$result = @mysql_query($sql, $this->db->dbConn))
      {
        trigger_error('Query failed: ' . mysql_error($this->db->dbConn).' SQL: '.$sql);
       die();
      }
      $num_field=mysql_num_fields($result);
      /*
      $xml_empty='<ROWSET><ROW><numrec/></rest_sign></expense></rest>'; //$xml_empty используется для получения новых записей в таблицах
   for ($i=0; $i<$num_field;$i++)
   {
     $name=mysql_field_name($result,$i);
     $xml_empty.='<'.$name.'/>';
     $field_list[]=$name;
   }
   $xml_empty.='</ROW></ROWSET>';
   */

   $add_where='';
   if (isset($_POST['date_oper']))
  { $date_oper=$_POST['date_oper'];
    $add_where=" and sklad_expense.date_oper <= '$date_oper'";
  }
   $xml='<ROWSET>';
   $nrec=0;
   while ($row=mysql_fetch_assoc($result))
   {
     $id=$row['id'];
     $sql2="select sum(quantity) as expense from sklad_expense where prihod_id='$id' $add_where";
     if(!$res2 = @mysql_query($sql2, $this->db->dbConn))
     {
        trigger_error('Query failed: ' . mysql_error($this->db->dbConn).' SQL: '.$sql2);
       die();
      }
     $row2=mysql_fetch_assoc($res2);
     $expense=(isset($row2['expense']))?$row2['expense']:'';
     mysql_free_result($res2);
     $xmlw='<ROW>';
     $xmlw.="<expense>$expense</expense>";
      while(list($name,$value) = each($row))
      {
       $value=$row[$name];
       if($value == 'null') {$value='';}
       $value=htmlspecialchars($value);
       $w='<'.$name.'>'.$value.'</'.$name.'>';
       $xmlw.=$w;
       if(strtolower($name)  =='prihod') //Если остаток > 0 - можно выдавать
       {
          $rest=$value-$expense;
          $xmlw.="<rest>$rest</rest>";
          if ($rest >0)
          {
            $img=($is_today > 0)?'image/add_20.gif':'image/t_dot.gif';
          } else // Если нет - нельзя
          {
           $img='image/t_dot.gif'; //прозрачный img 1x1
          }
        }
      } //  foreach ($field_list as $name)
      /*
      if(($user_group == 'System') and ($row['sklad_name'] != '407')) // Выдать только со склада 407
      {
        $img='image/t_dot.gif'; //прозрачный img 1x1
      }
       if(($user_group == 'User'))  //Ничего он выдать не сможет
      {
        $img='image/t_dot.gif'; //прозрачный img 1x1
      }
      */
      // Разрешение на Выдачу товара
       $can_edit_row='true';
      if ($usr_sklad == 'Нет')  //Если склад "Никакой" - ничего никогда выдаваться не будет
      {
         $img='image/t_dot.gif'; //прозрачный img 1x1
         $can_edit_row='false';
      }
      elseif($usr_sklad =='Все')  { }//Оставляем без изменений
      else
     {
      if($usr_sklad != $row['sklad_name']) {$img='image/t_dot.gif'; $can_edit_row='false';} //Если склад нет в списке - допуск ибираем
     }
     $xmlw.="<can_edit_row>$can_edit_row</can_edit_row>";
      $xmlw.="<rest_sign>$img</rest_sign>";
       if($rest >0 || $show_zero)
       {
        $nrec++;
        $xmlw.="<numrec>$nrec</numrec></ROW>";
        $xml.=$xmlw;
       }
   }//   while ($row=mysql_fetch_assoc($result))
   $xml.='</ROWSET>';
//   $xmls=($nrec > 0)?$xml:$xml_empty;
   $xmls=$xml;
//   $nr=mysql_num_rows($result);
   mysql_free_result($result);
   return array($xmls,$nrec);
  }

function Select_XML_group($where)
 { // Делает выборку с групировкой по имени
    global $OS, $user_level, $user_group, $usr_sklad;
//    $sql1=($this->SQL_Select)?$this->SQL_Select:'';
$add_where='';
$is_today=1;
if (isset($_POST['date_oper']))
{ $date_oper=trim($_POST['date_oper']);
  $add_where=" and sklad_prihod.date_oper <= '$date_oper'";
  $today=trim(date('Y-m-d'));
  $is_today=($today == $date_oper)?1:0;
}
$show_zero=false;
if (isset($_POST['show_zero']))
{
  $show_zero=($_POST['show_zero'] == 'yes')?true:false;
}

$sql1="select sklad_prihod.id as id, sklad_prihod.name as name,sklad_group.kode as kode, sklad_group.name as group_name, sklad_prihod.quantity as prihod, sklad_unit.name as unit_name, sklad_place.name as sklad_name, sklad_prihod.place_id as place_id from sklad_prihod, sklad_unit, sklad_group, sklad_place where sklad_group.id=sklad_prihod.tovar_id and sklad_place.id=sklad_prihod.place_id and sklad_unit.id=sklad_group.unit_id $add_where order by sklad_prihod.date_oper, name";
$arrs['user_level']=$user_level;
$sql=subst_param($sql1, $arrs);
$add_where='';
if (preg_match("/ where /", $sql))
{
   if(strlen($where) > 1) {$add_where.=' and '. $where;}
} else
{
   if(strlen($where) > 0) {$add_where.=" where $where";}
}
if(preg_match('/\sgroup by|\shaving|\sorder by/', $sql)) //Если в sql условия поиска нужно вставить в средину запроса (сразу перед дополнительными условиями)- втавляем
{
  $replace="$add_where \$1";
  $sql=preg_replace('/(\sgroup by|\shaving|\sorder by)/',$replace,$sql,1);
} else // Если нет - Добавляем в конец
{
  $sql.=$add_where;
}
if (!$result = @mysql_query($sql, $this->db->dbConn))
{
  trigger_error('Query failed: ' . mysql_error($this->db->dbConn).' SQL: '.$sql);
  die();
}
$add_where='';
if (isset($_POST['date_oper']))
{ $date_oper=$_POST['date_oper'];
  $add_where=" and sklad_expense.date_oper <= '$date_oper'";
}
$can_edit_row='false';
$img='image/t_dot.gif'; //прозрачный img 1x1
while ($row=mysql_fetch_assoc($result))
{
  $v_name=htmlspecialchars($row['name']);
  $id=$row['id'];
  $unit=$row['unit_name'];
  $sklad_name=$row['sklad_name'];
  $group_name=$row['group_name'];
  $prihod=$row['prihod'];
  $kode=$row['kode'];
  $sql2="select sum(quantity) as expense from sklad_expense where prihod_id='$id' $add_where";
  if(!$res2 = @mysql_query($sql2, $this->db->dbConn))
  {
    trigger_error('Query failed: ' . mysql_error($this->db->dbConn).' SQL: '.$sql2);
    die();
  }
  $row2=mysql_fetch_assoc($res2);
  $expense=(isset($row2['expense']))?$row2['expense']:0;
  mysql_free_result($res2);
  if ($show_zero or ( $expense < $prihod))
  {
    $can_edit_row='true';
    $img='image/add_20.gif';
    if(!isset($g_name[$v_name])) { $g_name[$v_name]='';} //Список имен
    if(!isset($g_id[$v_name])) { $g_id[$v_name]=$id;} else {$g_id[$v_name].='#'.$id;} //Список id
    if(!isset($g_sklad[$v_name])) { $g_sklad[$v_name]=$sklad_name;} else
    {
       if (!ereg($sklad_name, $g_sklad[$v_name])){  $g_sklad[$v_name].=', '.$sklad_name; }
     } //Список складов (только разные)
    if(!isset($g_group[$v_name])) { $g_group[$v_name]=$group_name;} //Групповое имя Одно!
    if(!isset($g_kode[$v_name])) { $g_kode[$v_name]=$kode;} //Кодовое имя Одно!
    if(!isset($g_unit[$v_name])) { $g_unit[$v_name]=$unit;} //Единица измерения Одна!
    if(!isset($g_prihod[$v_name])) { $g_prihod[$v_name]=$prihod;} else {$g_prihod[$v_name]+=$prihod;} //
    if(!isset($g_expense[$v_name])) { $g_expense[$v_name]=$expense;} else {$g_expense[$v_name]+=$expense;}
  } // if ($show_zero ...)
  //Суммарный расход
}//   while ($row=mysql_fetch_assoc($result))
if ($usr_sklad != 'Все')//Ограничения на Выдачу
{
  $img='image/t_dot.gif'; //прозрачный img 1x1
  $can_edit_row='false';
}
$xml='<ROWSET>';
$nrec=0;
while(list($name,$value) = each($g_name))
{
  $xmlw='<ROW>';
  $name1=htmlspecialchars($name); $xmlw.="<name>$name</name>";
  $val=$g_id[$name]; $xmlw.="<id>$val</id>";
  $val=$g_group[$name]; $val=htmlspecialchars($val);$xmlw.="<group_name>$val</group_name>";
  $val=$g_unit[$name];$val=htmlspecialchars($val); $xmlw.="<unit_name>$val</unit_name>";
  $val=$g_sklad[$name];$val=htmlspecialchars($val); $xmlw.="<sklad_name>$val</sklad_name>";
  $val=$g_kode[$name];$val=htmlspecialchars($val); $xmlw.="<kode>$val</kode>";
  $val=$g_prihod[$name]; $xmlw.="<prihod>$val</prihod>";
  $val1=$g_expense[$name]; $xmlw.="<expense>$val1</expense>";
  $rest=$val-$val1;$xmlw.="<rest>$rest</rest>";
  $xmlw.="<can_edit_row>$can_edit_row </can_edit_row>";
  $xmlw.="<rest_sign>$img</rest_sign>";
  if($rest >0 || $show_zero)
  {
    $nrec++;
    $xmlw.="<numrec>$nrec</numrec></ROW>";
    $xml.=$xmlw;
  }
} //  foreach ($field_list as $name)

   $xml.='</ROWSET>';
   $xmls=$xml;
//   $nr=mysql_num_rows($result);
   mysql_free_result($result);
   return array($xmls,$nrec);
  }
} // class sklad_rest
class sklad_prihod extends View
{
  function  search_table_js($xmlstr)
  { //посик в таблице по запросу из js-программ.
    // запрос передается ввиде xml строки
    global $OS;
    $xml_arr=XMLtoArray($xmlstr);
    $arr=$xml_arr['ROW'];
    $add_where='';
     while(list($name,$value) = each($arr))
     {
        $value=$arr[$name];
        $name2=strtolower($name);
        $where = make_search_where($name2,$value);
        if (strlen($where) > 0)
        {
          if(strlen($add_where) > 1) {$add_where.=' and ';}
          $add_where.= $where;
        } //while(list($name,$value) = each($arr))
     } //
     $this->SQL_Select="select name from sklad_prihod group by name";
     list($resp_xml,$nr)=$this->select_XML($add_where);
     if($nr == 0) {print "<ROWSET><ROW></ROW></ROWSET>"; return;}
     $xml_arr=XMLtoArray($resp_xml);
     $arr_w=$xml_arr['ROWSET']['ROW'];
     if($nr == 1){$arr_list[0]=$arr_w;} else {$arr_list=$arr_w;}
     $resp_xml='<ROWSET>';
      foreach ( $arr_list as $arr)
      {
        $resp_xml.='<ROW>';
        while(list($name,$value) = each($arr))
        {
          $value=$arr[$name];
          $name=strtolower($name);
          if ($name=='kode') {$kode_n=$value;}
          if (strstr($OS,'indows')){ $value=win3utf($value);}
          if ($name=='kode') {$kode=$value;}
          $resp_xml.='<'.$name.'>'.$value.'</'.$name.'>';
        } //
        $resp_xml.='</ROW>';
      } //      foreach ( $arr_list as $arr)

      //теперь найдем приход товара с таким кодом и найде его максимальный порядковый номер
      //Предполагается, что складской номер имеет вид: kode/порядковый_номер
//      $sql="select sklad_number, substring(sklad_number,(locate('-',sklad_number)+1))+1 as a from sklad_prihod where sklad_number like '$kode%' order by a desc limit 1";
//       $sql="select sklad_number, substring(sklad_number,(locate('-',sklad_number)+1))+1 as a from sklad_prihod where sklad_number like '$kode_n%' order by a desc limit 1";
          $sql="select sklad_number, substring(sklad_number,(locate('-',sklad_number)+1))+1 as a from sklad_prihod where $add_where order by a desc limit 1";
      if(!$result = @mysql_query($sql, $this->db->dbConn))
     {
        trigger_error('Query failed: ' . mysql_error($this->db->dbConn).' SQL: '.$sql);
        die();
      }
     $row=mysql_fetch_assoc($result);
     $s_number=(isset($row['a']))?$row['a']:0;
     //$s_number++;
     $s_num=($s_number>10)?$s_number:'0'.$s_number;
//     $w='<sklad_number_next>'.$kode.'-'.$s_num.'</sklad_number_next>';
     $w='<sklad_number_next>'.$s_num.'</sklad_number_next>';
     $resp_xml.='<ROW>'.$w.'</ROW>';
       $resp_xml.='</ROWSET>';
     print $resp_xml;
     return ;
  }  //  function  search_table_js($xmlstr)
  function check_delete($id)
  { //проверяет нет ли расходов по товару, котрый хотят удалить
     $prihod_id=$id; //номер записи в таблице списка прихода, которую нужно удалить
     $sql="select id from sklad_expense where prihod_id='$prihod_id'";
     if (!$result = @mysql_query($sql, $this->db->dbConn))
     {
       trigger_error('Query failed: ' . mysql_error($this->db->dbConn).' SQL: '.$sql);
        die();
     }
    $nr=mysql_num_rows($result);
     if($nr > 0) {
      $sql="select name, sklad_number from sklad_prihod where id='$prihod_id'";
      $res = @mysql_query($sql, $this->db->dbConn);
      $row=mysql_fetch_assoc($res);
      $name=$row['name'];
      $name=stripslashes($name);
      $sklad_number=$row['sklad_number'];
      $sklad_number=stripslashes($sklad_number);
      return "Ошибка. prihod_id=$prihod_id; nr=$nr; Товар $name с номером $sklad_number есть в списке расходов!\n";
      }
     return ;

  }
} //class sklad_tovar

?>
