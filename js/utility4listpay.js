// здесь будут функции и переменные, перенесенные из main_lib.js
var    search_div = null; // div с формой для поиска
var    view_search = false;
var     view_new_firm=false;
var Cur_Tabs_div = null; //Текущий выбранный tabs, объект jquery
var Tabs_List=new Object();
var Cur_Tabs_id = '';// id текущего Tabs
var cache_AC = {} ; // кеширование для autocomplete
var AC_Change = false ;// флаг изменения поля ввода autoselect
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
//------------------ Меню редактирования строки -----------------------
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