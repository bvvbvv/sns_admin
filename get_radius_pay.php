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
    <link rel="stylesheet" type="text/css" href="./css/list_pay.css">
    <script type="text/javascript" src="./js/jquery-3.7.1.min.js"></script>
    <script type="text/javascript" src="./js/datatables.min.js"></script>
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
              <input type="date" id="date_begin" ></label>
            </td>
        </tr>
        <tr>
            <td style="text-align: right" width="50%"> по: 
            </td>
            <td style="text-align: left">
              <input type="date" id="date_end" ></label>
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
            <button class="btn" id="loadBtn">Загрузить</button>
        </td>
    </tr>
    </table>
</div>

<div id="list_cmnt" style="margin-top:10px;font-size:small;color:blue; text-align: center;"></div>

<!-- !!!!!  ица -->
<!-- Контейнер где будет вставлена таблица -->
<!-- Контейнер где будет вставлена таблица -->
<div id="tableContainer" style="margin-top: 20px;"></div>

<script type="text/javascript">
    $(function(){
        var table = null;

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
                    order: [[0,'desc']],
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