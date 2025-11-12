// здесь будут функции и переменные, перенесенные из main_lib.js
var    search_div = null; // div с формой для поиска
var    view_search = false;
var     view_new_firm=false;
var Cur_Tabs_div = null; //Текущий выбранный tabs, объект jquery
var Tabs_List=new Object();
var Cur_Tabs_id = '';// id текущего Tabs
var cache_AC = {} ; // кеширование для autocomplete
var AC_Change = false ;// флаг изменения поля ввода autoselect
var Time_Out=null;
var N_data=[], Tovar_data=[];
function prepare_plugins()
{     
    // -----Инициируем форму для расхода товара
    /*
    $("#expense_div").dialog({autoOpen: false, minHeight:210, height:'auto', width: 900,  modal: false, bgiframe: true,
                      close:function(event,ui){$(this).dialog('destroy');}});
    $("#expense_div").css("visibility","visible"); 
    */

  // Привязываем Табсы
      $('#main_tabs').tabs(); //иницируем
      $('#main_tabs').tabs('select',1);  //выбираем втрй tabs
   //   $('#main_tabs').tabs({ event: 'click' });
      $('#main_tabs').css('visibility','visible');
      /*
      $( '#main_tabs').bind( "tabsselect", function(event,ui) {  
          hide_search_form();// если открыто меню поиска - при переходе на другой табс оно должно закрыться
          Cur_Tabs_div=$(ui.panel);
      });
      */ 
      $('#main_tabs').bind( "tabsselect", function(event,ui){ 
          jj=0;
          if (ui.index == 0){
              jj=0;
             $("#expense_div").dialog({autoOpen: true, minHeight:210, height:'auto', width: 900,  modal: false, bgiframe: true,
                      close:function(event,ui){$(this).dialog('destroy');}});
                      $("#expense_div").css("visibility","visible");  
             $("#expense_div").parent().position({my:"center top", at:"center bottom", of: "#main_tabs", offset: "0 10"});
          }
              else {
              if ($("#expense_div").dialog("isOpen")) {$("#expense_div").dialog("destroy");}
              }
          Get_Cur_Tab(ui)});  //вызывается при инициализации
      $('#main_tabs').bind( "select", function(event,ui){
          ii=0;
       Get_Cur_Tab(ui);
      });  //вызывается при инициализации
   //----------------------------   
   //$('#exp_form_table').position({my:'center center', at: 'center top'});
   $("button").button();
// меню для всех Табсов
     //InitTabsMenu();
     // Возможность вызова меню будет только у текущего Tabs
  //
   //Подготовка select-ов для форм поиска
   $('select[list_name]').each(function(i){ html=get_select_list($(this).attr('list_name'));$(this).append(html);})
   
    //-------------------------------------
    // контроль ввода полей формы выдачи товара
   $('.input_give').keyfilter(/[0-9.,]/);
    
    
   
}
function bind_plugins()
{ //добавляет плагины к строке редактирования
    // добавляем plugins:
    // Date Entry
  //debugger  
  /* В этом проекте такой функционал не нужен 
 var minDate=new Date(2002,4,1);    var today=new Date();
 $('.date_input').dateEntry({dateFormat:'dmy-',initialField: 0, minDate:'-1y', maxDate: today, spinnerSize:[0,0,0] });
  // фильтр ввода для полей поиска
  */
// $('.input_sys').keyfilter(/[0-9a-z\-_]/);
 /*
 $('.input_okpo').keyfilter(/[0-9]/);
 $('.input_sum').keyfilter(/[0-9.]/);
 $('.input_name').keyfilter(/[0-9а-я\"\'\s]/i);
 */
 //  TextArea Expander
 //debugger
 
 //tt=$(".input_expand");
 $(".input_expand").TextAreaExpander();
 
}
function get_select_list(select_list)
{   // добавить проверку на необходимость запроса: если уже запрос создавался - запросить из кеша   
  var ws = $.DOMCached.get(select_list);
if(!ws){// делаем запрос на сервер 
  $.ajax(
     { async:false,
       url:URLto,
       data:{'search': 'select_list', 'w_arr':{'list_name':select_list}},
       dataType:'json',
       type:'GET',
       success:function(data, textStatus, XMLHttpRequest)
       {
         //  debugger;
         if(textStatus !='success') {alert ('getJSON get_select_list: '+select_list+'; '+textStatus); return;}
           var key=''; var v_data={};
           for (i=0;i<data.length;i++)
           { 
               ws+='<option '; v_data=data[i];
               for( var key in data[i])                                     
             {  if (key == 'text') continue;                     
               ws+=key+'="'+data[i][key]+'" ';  // здесь key всегда = 'value';
             }
             ws+='>' + data[i].text + '</option>';
           }
       },
       error:function(XMLHttpRequest, textStatus, errorThrown){
           //debugger;
           alert('func get_select_list: '+select_list+'; error: '+textStatus);
           
       }
     }
   )
   $.DOMCached.set(select_list,ws);
} 
  return ws; 
}
  function get_item_list(item_name)
{   // Делает запрос
// добавить проверку на необходимость запроса: если уже запрос создавался - запросить из кеша 
   var v_data=[];   
  $.ajax(
     { async:false,
       url:URLto,
       data:{'search': 'select_list', 'w_arr':{'list_name':item_name}},
       dataType:'json',
       type:'GET',
       success:function(data, textStatus, XMLHttpRequest)
       {
         //  debugger;
         if(textStatus !='success') {alert ('getJSON get_item_list: '+item_name+'; '+textStatus); return;}
           var key=''; 
           for (i=0;i<data.length;i++)
           {               v_data[i]={id_value: data[i].value, value:data[i].text};
           }
       },
       error:function(XMLHttpRequest, textStatus, errorThrown){
           //debugger;
           alert('func get_item_list: '+select_list+'; error: '+textStatus);           
       }
     }
   )
  return v_data; 
}

function select_update_branch(item)
{  // при изменении выбора в select - здесь item, будем меняться значение поля branch
//debugger;
var sel_index=item.selectedIndex; //Находим номер выбранного options
var sel_option=item.options[sel_index]; //Это сам options
var br_name=sel_option.getAttribute('br_name'); //Нужный атрибут
var tr=item.parentNode.parentNode.parentNode; //строка таблицы которя редактируется
$('div[datafld=br_name]:first',tr).text(br_name); //вставляем нужный текст
//$(div).text(br_name);    
}
function submit_form(event,input)
{
var ws=event.keyCode;
       if(ws == 13){   
           event.cancelBubble=true;
           form=$(input).closest('form');
           search_data(form);
       };
}
function Get_Cur_Tab(ui)
       {
         hide_search_form();// если открыто меню поиска - при переходе на другой табс оно должно закрыться
         $("#search_tab_img").remove();
         $("span:first-child",ui.tab).before("<img id='search_tab_img' src='image/search2.gif' style='cursor: pointer'>")
         //-------------------------
         //Сначала запомним переменные предыдущего Tabs
             if (Cur_Tabs_id)
             {
               Tabs_List[Cur_Tabs_id].Nrow_click=Nrow_click;
               Tabs_List[Cur_Tabs_id].Ncol_click=Ncol_click;
               Tabs_List[Cur_Tabs_id].Nrow_edit=Nrow_edit;
               Tabs_List[Cur_Tabs_id].Dataset_name=Dataset_name;
               Tabs_List[Cur_Tabs_id].Click_td=Click_td;
             //  Tabs_List[Cur_Tabs_id].Click_row=Click_row;
               Tabs_List[Cur_Tabs_id].Tr_edit=Tr_edit;
               Tabs_List[Cur_Tabs_id].edit_shema_DOM=edit_shema_DOM;  //не запоминается !!!! нужно вычислять
               Tabs_List[Cur_Tabs_id].edit_shema_html=edit_shema_html;
               Tabs_List[Cur_Tabs_id].view_shema_DOM=view_shema_DOM;
               // потом добавить все остально
             }
          Cur_Tabs_div=$(ui.panel);
         var id=$(Cur_Tabs_div).attr('id');
          //if (Tabs_List[id] == undefined) 
          //if (Tabs_List[id] ==null) 
          if (id in Tabs_List) //проверка существования метода в Объекте
          {   // открывается уже открытый раньше Tabs, восстанавливаем данные
             Nrow_click= Tabs_List[id].Nrow_click;
             Ncol_click= Tabs_List[id].Ncol_click;
             Nrow_edit=Tabs_List[id].Nrow_edit;
             Dataset_name=Tabs_List[id].Dataset_name;
             Click_td=Tabs_List[id].Click_td;
             Click_row=Click_td;
             Tr_edit= Tabs_List[id].Tr_edit;
             edit_shema_DOM=Tabs_List[id].edit_shema_DOM;
             edit_shema_html=Tabs_List[id].edit_shema_html;
             view_shema_DOM=Tabs_List[id].view_shema_DOM;
             // и т.д.        
          } else
         {
             // Новый Табs
             Tabs_List[id]=new Object();
             Tabs_List[id].Click_td=new Object;
             Tabs_List[id].Click_row=new Object;
             Tabs_List[id].Tr_edit=new Object;
             Tabs_List[id].edit_shema_DOM=new Object;
             Tabs_List[id].view_shema_DOM=new Object;
             // потом инициируем для нового Tabsa
             Nrow_click=-1; //номер ряда на котором кликнули
             Ncol_click=-1; //номер колонки на котором кликнули
             //Click_td=new Object;// ячейка на которой кликнули
             Click_td=null;// ячейка на которой кликнули
             //Click_row=new Object;// ряд на котором кликнули    
             Click_row=null;// ряд на котором кликнули    
             //Tr_edit=new Object;// Ряд в таблице, который редактируется (объект))           
             Tr_edit=null;// Ряд в таблице, который редактируется (объект))           
             edit_shema_DOM=new Object;
             Dataset_name=''; //имя редактируемого набора данных, совпадает с именем таблицы (представления)
             Tr_edit='';// Ряд в таблице, который редактируется (объект))
             Nrow_edit=0;//Номер редактируемой строки в таблице
             table_templ=''; ; data_set=''; view_shema=''; edit_shema_html=''; table_place=null;
             isEdit=false;  DataSet_DOM=''; view_shema_DOM=''; Table_html=''; 
          }
         Cur_Tabs_id=id; 
         div_Edit_Menu=null;
         tab_Edit_Menu=null;
        
      }
function print_edit_row_menu()
{
 s_head='';
 s_head+='<div name="edit_menu" class="div_menu-style" style="position: absolute;" >';
s_head+='<div name="tab_edit" style="position: absolute; top: 0px; left: 0px; z-index: 900; border-width: 1; border-style: solid; border-color:  #EB3BA9;">';
s_head+='<table name="tab_edit_menu" width="60" cellspacing="0" cellpadding="0" ><tr>';
s_head+='<td width="20"><img cmd="new_row" id="new_row" src="image/new_n.gif" onclick="click_edit_menu(this)" title="Новая запись" onmouseover="this.src=\'image/new_h.gif\'; this.style.cursor=\'hand\'" onmouseout="this.src=\'image/new_n.gif\'" ></td>';
s_head+='<td width="20"><img cmd="edit_row" src="image/edit_n.gif" onclick="click_edit_menu(this)" title="Редактировать строку" onmouseover="this.src=\'image/edit_h.gif\'; this.style.cursor=\'hand\'" onmouseout="this.src=\'image/edit_n.gif\'" ></td>';
s_head+='<td width="20"><img cmd="del_row" src="image/delete_n.gif" onclick="click_edit_menu(this)" title="Удалить строку" onmouseover="this.src=\'image/delete_h.gif\'; this.style.cursor=\'hand\'" onmouseout="this.src=\'image/delete_n.gif\'" ></td>';
s_head+='</tr></table></div>';
s_head+='<div name="tab_save" style="position: absolute; top: 0px; left: 0px; z-index: 800; border-width: 1; border-style: solid; border-color:  #EB3BA9;">';
s_head+='<table name="tab_save_menu" width="60" cellspacing="0" cellpadding="0" ssstyle="display: none"><tr>';
s_head+='<td width="20"><img cmd="clean" src="image/clean_n.gif" onclick="click_edit_menu(this)" alt="Очистить поля ввода" onmouseover="this.src=\'image/clean_h.gif\'; this.style.cursor=\'hand\'" onmouseout="this.src=\'image/clean_n.gif\'" ></td>';
s_head+='<td width="20"><img cmd="save_row" src="image/save_n.gif" onclick="click_edit_menu(this)" title="Сохранить строку и выйти" onmouseover="this.src=\'image/save_h.gif\'; this.style.cursor=\'hand\'" onmouseout="this.src=\'image/save_n.gif\'"></td>';
s_head+='<td width="20"><img cmd="restore_row" src="image/reload.gif" onclick="click_edit_menu(this)" title="Отменить редактирование" onmouseover="this.src=\'image/reload_h.gif\'; this.style.cursor=\'hand\'" onmouseout="this.src=\'image/reload.gif\'"></td>';
s_head+='</tr></table></div>';
s_head+='</div>';
return s_head;
}
function show_edit_menu(elm)
{ //функция переделана для работы в tabs, в каждом tabs независимо от остальных
 // показывает или перемещает меню редактирования сверху над центром элемента elm (tr или td)   
   var cont_table;
   var $div_rel=''; var id_edit_menu='edit_menu'; var id_tab_edit='tab_edit';
   
 //  $div_rel=$('body'); 
     var id=$(Cur_Tabs_div).attr('id'); 
     id_edit_menu+='_'+id;
     id_tab_edit+='_'+id; 
     $div_rel=Cur_Tabs_div; 
    if(!div_Edit_Menu) 
  {  var edit_menu=print_edit_row_menu();
     if ($("#main_tabs").length == 1) {
     $(Cur_Tabs_div).append(edit_menu);  
     } else {$('body').append(edit_menu);}      
  }
  
  var need_move=false;
   elm_top=$(elm).offset().top;
   elm_top-=$div_rel.offset().top;
  elm_left=$(elm).offset().left;
  elm_left-=$div_rel.offset().left;
  elm_width=elm.offsetWidth;
  elm_left+= Math.round(elm_width/2);

  if (div_Edit_Menu)
  {
    need_move=true;
  } else
  {
    //div_Edit_Menu=document.getElementById(id_edit_menu);
    div_Edit_Menu=$("div[name=edit_menu]",Cur_Tabs_div);
    //tab_Edit_Menu=document.getElementById(id_tab_edit);
    tab_Edit_Menu=$("div[name=tab_edit]", Cur_Tabs_div);
  }

//  div_height=tab_Edit_Menu.offsetHeight+2;
  div_height=$(tab_Edit_Menu).outerHeight()+2;
//  div_width=tab_Edit_Menu.offsetWidth;
  div_width=$(tab_Edit_Menu).outerWidth();

  elm_top-=div_height;
  elm_left-=Math.round(div_width/2);
//  debugger
 need_move=false;
  if (need_move){
    move_edit_menu(id_edit_menu, elm_left, elm_top);
  } else
  {
    $(div_Edit_Menu).css('top', elm_top);
    $(div_Edit_Menu).css('left', elm_left);
    $(div_Edit_Menu).css('visibility','visible');
    
    view_Edit_Menu=true;
  }
}
function move_edit_menu(id_edit_menu, x_end, y_end)
{ //Перемещает edit_menu в координаты x_end и y_end
  var rep=/px/;
  var speed=7;
    y_beg=div_Edit_Menu.style.top;    // y_beg-=$(Cur_Tabs_div).offset().top; 
    x_beg=div_Edit_Menu.style.left;   // x_beg-=$(Cur_Tabs_div).offset().left; 
    y_beg=y_beg.replace(rep,''); y_beg=y_beg*1.0;
    x_beg=x_beg.replace(rep,''); x_beg=x_beg*1.0;
    pathLen = Math.sqrt((Math.pow((x_beg - x_end), 2)) +
    (Math.pow((y_beg - y_end), 2)));
    speed=Math.round(pathLen/5);
    initSLAnime(id_edit_menu,x_beg, y_beg, x_end, y_end,speed);
}
function hide_edit_menu()
{ //Скрывает меню редактирования
  if(view_Edit_Menu)
  {
  view_Edit_Menu=false;
  //div_Edit_Menu.style.visibility='hidden';
  $("div[name=edit_menu]",Cur_Tabs_div).attr('visibility','hidden');
 // div_Edit_Menu=null;
  }
}
function click_edit_menu(item)
{//обрабатывает нажатие на одну из конопок "плавающего" меню
  //item - img
  var rep=/px/;
  var id=item.id;
  glob_src=null;
//  debugger
  var cmd=item.getAttribute('cmd');
//  debugger
  switch (cmd)
  {
    case 'new_row': //Новая запись
    Add_Row();
    if(inp_focus) $(inp_focus).focus();
    //if(Inp_focus) got_focus(Inp_focus);
    bind_plugins();
    return;
    break;
    case 'edit_row':
      //document.getElementById('tab_edit').style.zIndex=800;
      //document.getElementById('tab_save').style.zIndex=900;
      $("div[name=tab_edit]",Cur_Tabs_div).css('z-index','800');
      $("div[name=tab_save]",Cur_Tabs_div).css('z-index','900');
      
      // Cur_Table_Obj.Edit_Row()
   //   debugger
      //var form= $('[name="form_search_pay"]'); $(':input',form).attr('disabled','disabled');
      $('form  *').find(':input').attr('disabled','disabled'); //блокирует все поля ввода во всех формах на странице.
      edit_row(Click_td); 
      //if(Click_td.nodeName !='TD') Click_td=$(Click_td).closest('td');
      Click_td=inp_focus[0];
      show_edit_menu(Click_td);  
   //   debugger;
      if(inp_focus) $(inp_focus[0]).focus();       
      if(inp_focus) bind_autocompleet(inp_focus); //   Добавлено для IE !!!, т.к. в предыдущей строке got_focus не вызывается.
      bind_plugins();                              
      update_column_width(); // выравниваем ширину колонок
       SwapTabsMenu('disable'); //отключаем остальные табсы и меню текущего табса    
      //bind_autocompleet();
    break;
    case 'save_row':
       if(!confirm("Сохранить изменения данной записи?")) {return;}
       $('.date_input').dateEntry('destroy'); // снимает плагин
       $('form  *').find(':input').removeAttr('disabled'); //разблокирует все поля ввода во всех формах на странице
       SwapTabsMenu('enable'); //включаем остальные табсы и меню текущего табса    
       Datafld_Focus=null;
       $(Input_AC).autoComplete('destroy');
      Save_Row_start();
    break;
    case 'restore_row':
       if (!confirm("Отменить редактирование и выйти?")) {return;}
       $('.date_input').dateEntry('destroy'); // убирает функционал с поля ввода даты, иначе это меняет структуру шаблона
     //  debugger;  
       Ses_auto_begin=false; Datafld_Focus=null;//завершает сессию ввода данных с помощью autocomplete
       $(Input_AC).autoComplete('destroy'); //убирает вспомогательные элементы для autocompleet со страницы
        Restore_Row();
        $('form  *').find(':input').removeAttr('disabled'); //разблокирует все поля ввода во всех формах на странице
       //document.getElementById('tab_edit').style.zIndex=900;
       //document.getElementById('tab_save').style.zIndex=800;
       $("div[name=tab_edit]",Cur_Tabs_div).css('z-index','900');
       $("div[name=tab_save]",Cur_Tabs_div).css('z-index','800');
       SwapTabsMenu('enable'); //включаем остальные табсы и меню текущего табса    
    break;
    case 'del_row':
     if (!confirm('Удалить эту строку?')) {return;}
     Delete_Row_start();
    break;
    
  }
} //function click_edit_menu(item)
function Prihod_group()
{
 $("#div_prihod").css("visibility","visible");   //показываем форму для ввода
  $("#div_prihod").dialog({autoOpen: true, minHeight:210, height:'auto', width: 900,  modal: true, bgiframe: true,
                      close:function(event,ui){$(this).dialog('destroy');}});  // вставляем ее в диалог
  $("#div_prihod").parent().position({my:"center top", at:"center bottom", of: "#main_tabs", offset: "0 10"});
  //$("#save_suppl").button({icons:{primary:"ui-icon-disk"}}).children("span").css({left:2, top:2});  //добавляем кнопку сохранения
  $("#save_suppl").button({icons:{primary:"ui-icon-plus"}}).css({visibility:"hidden"}).children("span").position({my:"center center", at:"center center", of:"#save_suppl"});  //добавляем кнопку сохранения
  AC_combo_widget();
  $("#supplayer").combobox(); 
  $("#sklad_group").combobox(); 
  $("select.prihod_tovar").combobox();
  $("select.prihod_tovar ~input").css({'width':'270','height':'25'});
  
  $("#supplayer ~input").autocomplete("option","source",
     function(request, response) {
                if (request.term.length > 6) return;
                var term=request.term;
                if ( term in cache_AC) {response(cache_AC[term]); return;
                }
                $.ajax({
                    url: "sklad_new.php",
                    type: "GET",
                    dataType: "JSON",
                    data: {
                        search: "autocomplete",
                        AC_name: "supplayer",
                        quest: request.term
                    },
                    success: function(data) {
                       //response($.map(data, function(item) {
                       var n_data=$.map(data, function(item) {
                            ii=0;
                            return {value: item.text, value_id: item.value_id};
                        });
                        ii=0;
                        response(n_data);
                        cache_AC[term]=n_data;   
                    }
                    
                })
            }
);         
$("#supplayer ~input").bind("autocompleteselect", function(e,ui)
   { var id=ui.item.value_id; $('#supplayer_id').attr('value',id);
     AC_Change=false;$('#save_suppl').css({visibility:'hidden'}); 
  //   Time_Out=null; 
   } );
 $("#supplayer ~input").bind("autocompleteopen", function(e,ui)
  {AC_Change=false;
  //$('#save_suppl').css({visibility:'hidden'}); 
  //Time_Out=null; 
  } );
   $("#supplayer ~input").keypress(function(e){ 
    $("#sklad_group ~input").autocomplete("close"); //если имя предприятия редактируется - список групп закрывается
    $('#save_suppl').css({visibility:'visible'});
    AC_Change=true;
    //if (Time_Out){clearTimeout(Time_Out);}
   // else {Time_Out=setTimeout("$('#save_suppl').css({visibility:'visible'})",100)}
    
} );
$("#supplayer ~input").bind("autocompletechange", function(e,ui){ jj=0; if (!AC_Change) return; 
AC_Change=false;$('#save_suppl').css({visibility:'hidden'});
if(confirm('Изменено название. Сохранить?')){ii=0;} ;} );
$("#sklad_group ~input").bind("autocompleteselect", function(e,ui){ var id=ui.item.value_id; $('#tovar_id').attr('value',id);} );
$("select.prihod_tovar ~input").bind("autocompleteselect", function(e,ui){ 
    ii=0;
    var unit=ui.item.unit; 
    // var kode=ui.item.kode; 
    var tr=$(this).closest('tr');
    $('div[datafld=unit_name]',tr).text(unit);
} );
    
$("#supplayer ~input").bind("autocompleteclose", 
  function (e,ui){
  jj=0;
    //var id1=ui.item.value_id;  //получаем поставщика и выбираем группы товаров, которые он раньше поставлял
    //var id=$('#supplayer_id').text();
    var id=$('#supplayer_id').val();
    var n_data=[];  //список имен групп товаров и их id
     $.ajax({
        url: "sklad_new.php",
        type: "GET",
        dataType: "JSON",
        data: {
           search: "autocomplete",
           AC_name: "suppl_sklad_group",                                            
           quest: id
         },
         success: function(data) {
            N_data=$.map(data, function(item) { return {value: item.text, value_id: item.value_id};  });
            setTimeout('open_sklad_group()',100);
         }
    })
  });
 
 $("#sklad_group ~input").bind("autocompleteclose", 
  function (e,ui){
      jj=0;
      // после закрытия списка груп товаров получаем список имен товаров по id поставщика и id группы товаров, которые он раньше поставлял
    // var id_s=$('#supplayer_id').text();
    //var id_gr=$('#sklad_group_id').text();
    var id_gr=$('#tovar_id').val();
     var sup_id=$('#supplayer_id').val();
   // var n_data=[];  //список имен групп товаров и их id
     $.ajax({
        url: "sklad_new.php",
        type: "GET",
        dataType: "JSON",
        data: {
           search: "autocomplete",
           AC_name: "tovar_name",
           group_id: id_gr,
           supplayer_id: sup_id
         },
         success: function(data) {
            Tovar_data=$.map(data, function(item) { return {value: item.text, kode: item.kode, unit:item.unit};  });
            setTimeout('open_prihod_tovar()',100);
         }
    })
  });
  // 
  /*
  $("select.prihod_tovar ~input").bind("autocompleteclose", 
  function (e,ui){
      jj=0;
      var tr= $(this).closest('tr');
      $("div[datafld=unit_name]").text();
  });
  */
  //combo_widget();   
} // end Prihod_group

function open_sklad_group() {
    $("#sklad_group ~input").attr('value','');  
     $("#sklad_group ~input").autocomplete("option","source", N_data);  
     $("#sklad_group ~input").autocomplete("option","minLength", 0 ); 
     $("#sklad_group ~input").autocomplete("search","");  
     $("#sklad_group ~button").attr("title","Список групп Поставщика");  
}
function open_prihod_tovar() {
    $("select.prihod_tovar ~input").attr('value','');  
     $("select.prihod_tovar ~input").autocomplete("option","source", Tovar_data);  
     $("select.prihod_tovar ~input").autocomplete("option","minLength", 0 ); 
     $("select.prihod_tovar ~input").autocomplete("search","");  
     $("select.prihod_tovar ~button").attr("title","Список товаров Группы");  
}
//function show_save_suppl() {}
//-------------------- еще один вариант combobox c autoselect   
//-----------------
function change_group_name(item)
{ // меняет Список групп товаров для каждого конкретного 
    
}
// ---------------------------------
function AC_combo_widget(){
$.widget("ui.combobox", {
    _create: function() {
        var self = this,
            select = this.element.hide(),
            selected = select.children(":selected"),
            value = selected.val() ? selected.text() : "";
        var input = this.input = $("<input>").insertAfter(select).val(value).autocomplete({
            delay: 300,
            minLength: 2,
            source1: [],
            select1: function(event, ui) { var id=ui.item.value_id;  $('#supplayer_id2').text(id);  },

        }).addClass("ui-widget ui-widget-content ui-corner-left");

        input.data("autocomplete")._renderItem = function(ul, item) {
            return $("<li></li>").data("item.autocomplete", item).append("<a>" + item.label + "</a>").appendTo(ul);
        };

        this.button = $("<button type='button'>&nbsp;</button>").attr("tabIndex", -1).attr("title", "Весь список").insertAfter(input).button({
            icons: {
                primary: "ui-icon-triangle-1-s"
            },
            text: false
        }).removeClass("ui-corner-all").addClass("ui-corner-right ui-button-icon").click(function() {
            // close if already visible
            if (input.autocomplete("widget").is(":visible")) {
                input.autocomplete("close");
                return;
            }
            // work around a bug (likely same cause as #5265)
            $(this).blur();
            ws='all';
            if (input.autocomplete('option','minLength')==0 ) ws='';
            // pass empty string as value to search for, displaying all results
            input.autocomplete("search", ws);
            input.focus();
        });
    },

    destroy: function() {
        this.input.remove();
        this.button.remove();
        this.element.show();
        $.Widget.prototype.destroy.call(this);
    }
});
}
function Save_Prihod_Start(form) {
   // Сохранняет введенные в форме данные в таблицу
   //
   // сначала собираем данные
   // здесь form - это форм
   var w_arr=[], w_arr1=[], send_param=[];
    //w_arr1=$('input, textarea, select',form).filter(function(index){ return this.value.length > 0}).serializeArray(); // берем только не пустые поля
    var ii=0;
    $('input[name], textarea[name]',form).each(function(index){ 
    //    if(this.value.length > 0){
            ii=1;
            w_arr[$(this).attr('name')]=$(this).val();
      //  }
    }) // берем только не пустые поля
    
    if (ii == 0) {alert ('Не введено ни одного поля!'); return; } 
    w_arr['name']=$("select.prihod_tovar ~input").val();
     w_arr['id']=0 ;
     w_arr['method']='update' ;
    w_arr['tab_name']='sklad_prihod';
    w_arr['place_id']='3';
    //var text2=$('input, textarea, select',form).serializeArray(); // берем только не пустые поля
    var req_new=true;
    var table_doc=$("[setdata='sklad_prihod']:visible"); //ищем таблицу на странице
     if(table_doc.length > 0) {req_new = false;} 
    send_param[0]=w_arr; 
    //return;
//    send2server('update', false, send_param) ;
    var reply=''; var DS_ret='';
    var req = new JsHttpRequest();
 // Code automatically called on load finishing.
     req.onreadystatechange = function()
     {
        if (req.readyState == 4) {
     /*   var wstr=''; var error='';
        Reply=req.responseJS.reply;
        error= req.responseText.error;
        wstr=req.responseText;
        if(wstr) {error+=wstr};
        wstr=req.responseJS.data_set;if(wstr) {DS_ret=wstr;} else {DS_ret='<rowset />';}
        //Save_Row_finish(DS_ret,Reply,error);
       //    Save_Prihod_Finish(DS_ret,Reply,error);
       */
         is_new=true; //                
         // эти все схемы можно передавать в javascript код при загруке страницы в php программе.
         wstr=req.responseJS.table_templ;if(wstr) {table_templ=wstr;} else {is_new=false;}
         wstr=req.responseJS.edit_shema; if(wstr) edit_shema=wstr; else {edit_shema=null;}
         wstr=req.responseJS.data_set;if(wstr) {data_set=wstr;} else {data_set='<rowset/>';}
         wstr=req.responseJS.view_shema;if(wstr) view_shema=wstr;
         wstr=req.responseJS.table_place;if(wstr) {table_place=wstr} else {table_place='table_place';}
         document.getElementById('debug').innerHTML = req.responseText;
          if (edit_shema) {edit_shema_html=edit_shema;}
           //    debugger
          //     hide_search_form(); 
               if(is_new){
                show_data_set2(); //выполняется, только когда  table_templ и edit_shema и view_shema передаются первый раз
               } else {
              update_data_set(); //при повторном запросе передается только data_set
               }
       
        }
     }
     req.open('POST', URLto, true);
    req.send( { 'is_sql': false,'req_new':req_new, 'param':send_param} );
}
function Save_Prihod_Finish(DS_ret,Reply,error)
{
   if (error) {  alert ('Данные сохранить не удалось! <br/>'+error);  return;  }
     str_DSO='<?xml version="1.0" encoding="windows-1251"?>';
      str_DSO+='<setdata>'+DS_ret+'</setdata>';
   // 2.1 Еще вариант, для работы с jQuery загрузим эти данные в DOM объект
   //   debugger
      var DS_html=$(DS_ret);
       var UpDS_DOM= XMLstr2DOM(str_DSO);  
       ds_name='sklad_prihod';
       var table_doc=$("[setdata='"+ds_name+"']:visible"); //ищем таблицу на странице
       
 if(table_doc.length > 0) // если она есть - записываем в "конец" новую строку
 {
     var trdat=$("table[setdata="+ds_name+"] tbody tr"); 
     var trdat_new=$(trdat).clone(); //делаем ее копию   
     $(trdat_new).find('[datafld]').each(function(i,n){this.innerHTML='';})//очищаем содержимое  
     
     // переписываем нумерацию строк
     var table=$(trdat).closest('table'); $("div[datafld='numrec']",table).each( function(i,n) { this.innerHTML=i+1;})
     Nrow_click=i-1;
     var ds_row=$(ds_name, DataSet_DOM).children().get(Nrow_click);
     $(UpDS_DOM).insertAfter(ds_row);
      var divdat =$(trdat).find("div[datafld], span[datafld]");
       for (i=0;i <divdat.length; i++)
       {  datafld=$(divdat[i]).attr('datafld');
          value=$(datafld,UpDS_DOM).html();
          $('datafld='+datafld+']',trdat_new).append(value);           
       }
     $(trdat_new).insertAfter(trdat) //вставляем после текущей со всем содержимым
 } else 
 {
     
 }
}
//------