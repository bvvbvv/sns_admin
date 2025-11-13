<?php
include './utility_radius_pay.php';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Платежи - просмотр</title>
    <link rel="stylesheet" type="text/css" href="./css/datatables.css">
    <link rel="stylesheet" type="text/css" href="./css/sns_pay.css">
    <!-- <link rel="stylesheet" type="text/css" href="./css/list_pay.css"> -->
    <script type="text/javascript" src="./js/jquery-3.7.1.min.js"></script>
    <script type="text/javascript" src="./js/datatables.min.js"></script>
    <!-- Библиотеки для TableExport -->
  <script src="https://cdn.jsdelivr.net/npm/file-saver@2.0.5/dist/FileSaver.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
  <!-- <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.core.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/tableexport@5.2.0/dist/js/tableexport.min.js"></script> -->

    <style type="text/css">
        .dataTables_wrapper { margin-top: 20px; margin-bottom: 20px; }
        table.dataTable { width: 100%; margin: 15px 0; }
    </style>
</head>
<body>

<div class="container-div">
    <table style="min-width: 300px;">
        <tr>
            <td style="text-align: right" width="50%"> Дата с: 
            </td>
            <td style="text-align: left">
              <input type="date" id="date_begin" value="2025-11-01"></label>
            </td>
        </tr>
        <tr>
            <td style="text-align: right" width="50%"> по: 
            </td>
            <td style="text-align: left">
              <input type="date" id="date_end" value="2025-11-13" ></label>
            </td>   
        </tr>
        <!--
         <tr>
            <td style="text-align: right" width="50%">    
                Режим: 
            </td>
            <td style="text-align: left">
            <select id="parentid">
                <option value="total">Суммарно (total)</option>
                <option value="bytrans">По транзакциям</option>
            </select>
            </td>
        </tr>
        -->
        <tr><td  style="text-align:center; min-width: 120px;" colspan="2">
            <button class="btn" id="loadBtn">Получить отчет</button>
        </td>
    </tr>
    </table>
</div>

<div id="list_cmnt" style="margin-top:10px;font-size:small;color:blue; text-align: center;"></div>


<div id="export2xls" class="container-div"> 
    <button id="exportBtn" value="1111.xls" filename="fffname">Экспорт в Excel</button>
     </div>
    
    <!-- @@@ Контейнер где будет вставлена таблица -->
<div id="tableContainer" style="margin-top: 20px;"></div>

<script defer>
// $(function() {
//   $('#exportBtn').on('click', function() {
//     //debugger
//     // Создаём экземпляр TableExport
//       var table = TableExport(document.getElementById("paymentsTable"), {
//         formats: ["xlsx"],          // какие форматы разрешены
//         filename: "222users_data",     // имя файла
//         sheetname: "Sheet1",        // имя листа
//         exportButtons: false        // не добавлять автоматические кнопки
//       });

//       // Получаем экспортный объект (первый формат — xlsx)
//       var exportData = table.getExportData()['paymentsTable']['xlsx'];

//       // Экспортируем
//       table.export2file(
//         exportData.data,
//         exportData.mimeType,
//         exportData.filename,
//         exportData.fileExtension
//       );
//   });
// });

document.getElementById('exportBtn').addEventListener('click', function() {
    // Получаем таблицу
    var table = document.getElementById('paymentsTable');

    // Преобразуем HTML таблицу в SheetJS workbook
    var wb = XLSX.utils.table_to_book(table, { sheet: "Sheet1" });

    // Сохраняем как файл .xlsx
    XLSX.writeFile(wb, "33333.xlsx");
  });
</script>

<script type="text/javascript">
    $(function(){
        var table = null;
        var excel_file_name=$('#date_begin').val() + "-" +  $('date_end').val()+'.xls';
        function loadData() {
            var payload = {
                action: 'test_select',
                date_begin: $('#date_begin').val(),
                date_end: $('#date_end').val(),
                parentid: $('#parentid').val()
            };

            $.ajax({
                url: './ajax_get_radius_pay.php', // new dedicated AJAX endpoint
                method: 'POST',
                data: payload,
                dataType: 'json'
            }).done(function(resp){
                console.log('AJAX response:', resp);
                
                if (!resp || !resp.rows) {
                    $('#list_cmnt').text('Ошибка ответа от сервера: rows не найдены');
                    console.error('resp.rows undefined', resp);
                    return;
                }

                $('#list_cmnt').html(resp.list_cmnt || '');
                console.log('Data rows:', resp.rows.length);

                var data = resp.rows.map(function(r){ return r.cell; });
                console.log('Mapped data:', data);

                // Если таблица уже существует, очищаем контейнер
                if ($.fn.DataTable.isDataTable('#paymentsTable')) {
                    table.destroy();
                }
                $('#tableContainer').empty();

                // Создаём таблицу динамически
                var tableHtml = `
                    
                    <table id="paymentsTable" class="display" style="width:100%">
                        <thead>
                            <tr>
                                <th>№</th>
                                <th>Реселлер / Абонент</th>
                                <th>Банк (шт)</th>
                                <th>Банк (сумма)</th>
                                <th>Наличка (шт)</th>
                                <th>Наличка (сумма)</th>
                                <th>Эквайринг (шт)</th>
                                <th>Эквайринг (сумма)</th>
                                <th>Всего (шт)</th>
                                <th>Всего (сумма)</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                    
                `;
                
                // Вставляем таблицу в контейнер
                $('#tableContainer').html(tableHtml);
               // exportHTML='<button id="exportBtn" filename='+excel_file_name+'">Экспорт в Excel</button>';
                //$('#export2xls').html(exportHTML);
                // Инициализируем DataTables с данными
                table = $('#paymentsTable').DataTable({
                    data: data,
                    columns: [
                        { title: '№' },
                        { title: 'Реселлер / Абонент' },
                        { title: 'Банк (шт)' },
                        { title: 'Банк (сумма)' },
                        { title: 'Наличка (шт)' },
                        { title: 'Наличка (сумма)' },
                        { title: 'Эквайринг (шт)' },
                        { title: 'Эквайринг (сумма)' },
                        { title: 'Всего (шт)' },
                        { title: 'Всего (сумма)' }
                    ],
                    pageLength: 25,
                    order: [[0,'asc']],
                    responsive: true,
                    language: { url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Russian.json' }
                });

            }).fail(function(jqXHR, status, err){
                $('#list_cmnt').text('AJAX error: ' + status + ' ' + err);
                console.error('AJAX fail:', jqXHR, status, err);
            });
        }

        $('#loadBtn').on('click', function(e){ e.preventDefault(); loadData(); });
    });
</script>

</body>
</html>