<?php
// AJAX endpoint for get_radius_pay - returns JSON
include './utility_radius_pay.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Only POST allowed']);
    exit;
}

$action = $_POST['action'] ?? '';
if ($action !== 'test_select') {
    echo json_encode(['error' => 'Invalid action']);
    exit;
}

// Basic validation
$date_begin = $_POST['date_begin'] ?? '';
$date_end = $_POST['date_end'] ?? '';
$parentid = $_POST['parentid'] ?? 'total';

// Validate date format YYYY-MM-DD or empty
$datePattern = '/^\d{4}-\d{2}-\d{2}$/';
if ($date_begin !== '' && !preg_match($datePattern, $date_begin)) {
    echo json_encode(['error' => 'Invalid date_begin format']);
    exit;
}
if ($date_end !== '' && !preg_match($datePattern, $date_end)) {
    echo json_encode(['error' => 'Invalid date_end format']);
    exit;
}
/*
$allowedParents = ['total', 'bytrans'];
if (!in_array($parentid, $allowedParents)) {
    // default to total
    $parentid = 'total';
}
*/
// Connect to DB
$mysqli = new mysqli("localhost", "pr_perl", "vasya151", "radius");
if ($mysqli->connect_error) {
    echo json_encode(['error' => 'DB connection error: ' . $mysqli->connect_error]);
    exit;
}
$mysqli->set_charset("utf8mb4");

// Call test_select implementation (re-implemented here to be self-contained)
function test_select_ajax($w_arr, $mysqli)
{
    $procent['rs_damansk']=0.15;
    $procent['rs_pshichenko']=0.2;
    $procent['rs_isaoptik']=0.15;
    $procent['rs_isaenko']=0.20;

    $SQL_Select = "call C42('" . ($w_arr['date_begin'] ?? '') . "','" . ($w_arr['date_end'] ?? '') . "')";
    $total = 0; $sum = 0; $num = 0; $cnt_tot = 0; $tot_sum = 0;
    $tot_cnt_bank = 0; $tot_cnt_local = 0; $tot_cnt_ekvar = 0;
    $tot_proc_bank = 0; $tot_proc_local = 0; $tot_proc_ekvar = 0;
    $tot_sum_bank = 0; $tot_sum_local = 0; $tot_sum_ekvar = 0;
    $response = new stdClass();
    $response->rows = [];

    $row = $mysqli->query($SQL_Select);
    if ($row) {
        if ($row instanceof mysqli_result && $row->num_rows > 0) {
            while ($r = $row->fetch_assoc()) {
                $row_tot = isset($r['tot_sum']) ? (float)$r['tot_sum'] : 0.0;
                $row_cnt = isset($r['cnt_tot']) ? (int)$r['cnt_tot'] : 0;

                $sum += $row_tot;
                $cells = [];
                $cells[] = $total + 1;
                $cells[] = isset($r['res_name']) ? $r['res_name'] : (isset($r['longname']) ? $r['longname'] : '');
                $sys_name = isset($r['sys_name']) ? $r['sys_name'] : '';
                $proc=round(((float)$procent[$sys_name])*100,2);
                $sys_name_proc=$sys_name.':'.$proc.'%';
                //$cells[] = isset($r['sys_name']) ? $r['sys_name'] : '';
                $cells[] = $sys_name_proc;
                $cells[] = isset($r['cnt_bank']) ? $r['cnt_bank'] : '';
                $cells[] = isset($r['sum_bank']) ? $r['sum_bank'] : '';
                $sum_bank= isset($r['sum_bank']) ? $r['sum_bank'] : 0.0;
                $proc_bank=round(((float)$sum_bank)*$procent[$sys_name],2);
                $cells[]=$proc_bank;

                $cells[] = isset($r['cnt_local']) ? $r['cnt_local'] : '';
                $cells[] = isset($r['sum_local']) ? $r['sum_local'] : '';
                $sum_local= isset($r['sum_local']) ? $r['sum_local'] : 0.0;
                $proc_local=round(((float)$sum_local)*$procent[$sys_name],2);
                $cells[]=$proc_local;

                $cells[] = isset($r['cnt_ekvar']) ? $r['cnt_ekvar'] : '';
                $cells[] = isset($r['sum_ekvar']) ? $r['sum_ekvar'] : '';
                $sum_ekvar= isset($r['sum_ekvar']) ? $r['sum_ekvar'] : 0.0;
                $proc_ekvar=round(((float)$sum_ekvar)*$procent[$sys_name],2);
                $cells[]=$proc_ekvar;

                $cells[] = isset($r['cnt_tot']) ? $r['cnt_tot'] : $row_cnt;
                $cells[] = isset($r['tot_sum']) ? $r['tot_sum'] : $row_tot;
                $cells[]=$proc_bank+$proc_ekvar;  // Итоговая сумма для реселлера только по безналу

                $tot_cnt_bank += isset($r['cnt_bank']) ? (int)$r['cnt_bank'] : 0;
                $tot_sum_bank += isset($r['sum_bank']) ? (float)$r['sum_bank'] : 0.0;

                $tot_proc_bank += $proc_bank;
                $tot_proc_ekvar += $proc_ekvar;
                $tot_proc_local += $proc_local;

                $tot_cnt_local += isset($r['cnt_local']) ? (int)$r['cnt_local'] : 0;
                $tot_sum_local += isset($r['sum_local']) ? (float)$r['sum_local'] : 0.0;
                $tot_cnt_ekvar += isset($r['cnt_ekvar']) ? (int)$r['cnt_ekvar'] : 0;
                $tot_sum_ekvar += isset($r['sum_ekvar']) ? (float)$r['sum_ekvar'] : 0.0;

                $response->rows[$num] = ['id' => (isset($r['rid']) ? $r['rid'] : (isset($r['id']) ? $r['id'] : $num)), 'cell' => $cells];

                $num++; $cnt_tot += $row_cnt; $tot_sum += $row_tot; 
                $total++;
            }
            $cells = [];
            $cells[] = $total+1;
            $cells[] = 'Сумма по всем реселлерам';
            $cells[] = '-';
            $cells[] = $tot_cnt_bank;
            $cells[] = $tot_sum_bank;
            $cells[] = round($tot_proc_bank,2);
            $cells[] = $tot_cnt_local; 
            $cells[] = $tot_sum_local;
            $cells[] = round($tot_proc_local,2);
            $cells[] = $tot_cnt_ekvar;  
            $cells[] = $tot_sum_ekvar;
            $cells[] = round($tot_proc_ekvar,2);
            $cells[] = $cnt_tot;
            $cells[] = $tot_sum;
            $cells[] = round($tot_proc_bank + $tot_proc_ekvar,2);
            $response->rows[$num] = ['id' => (isset($r['rid']) ? $r['rid'] : (isset($r['id']) ? $r['id'] : $num)), 'cell' => $cells];
        }

        while ($mysqli->more_results() && $mysqli->next_result()) {
            $extra = $mysqli->use_result();
            if ($extra instanceof mysqli_result) $extra->free();
        }
    }


    $cnt_tot = $total; $tot_sum = $sum;
    //$list_cmnt = "Поиск с " . ($w_arr['date_begin'] ?? '') . " по: " . ($w_arr['date_end'] ?? '') ;
    $list_cmnt = "Поиск с <b>" . ($w_arr['date_begin'] ?? '') . "</b> по: <b>" . ($w_arr['date_end'] ?? '') . "</b><br/>";
    if ($total == 0) { $list_cmnt .= 'Платежей не найдено'; }
    //else { $list_cmnt .= "Найдено платежей $cnt_tot на сумму $tot_sum грн."; }
     else { $list_cmnt .= "Найдено реселлеров <b>$cnt_tot</b> Общая сумма <b>$tot_sum</b> грн."; }

    $response->list_cmnt = $list_cmnt;
    return $response;
}

// Build input array
$input = [
    'date_begin' => $date_begin,
    'date_end' => $date_end,
    'parentid' => $parentid
];

$result = test_select_ajax($input, $mysqli);
$i=0;
echo json_encode($result);

?>
