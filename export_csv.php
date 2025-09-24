<?php
session_start();

// Authentication check
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$log_base_path = '/var/log/remotelogs/hotspotRT_parsed';
$selected_nas = $_GET['nas'] ?? '';
$selected_date = $_GET['date'] ?? date('Y-m-d');
$log_file = "$log_base_path/$selected_nas/$selected_date.log";

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="mikrotik_logs_' . $selected_date . '.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Time', 'NAS Name', 'User', 'MAC Address', 'Source IP', 'Source Port', 'Destination IP', 'Destination Port', 'Protocol']);

if (file_exists($log_file)) {
    $handle = fopen($log_file, 'r');
    if ($handle) {
        while (($line = fgetcsv($handle)) !== false) {
            if (count($line) >= 9) {
                fputcsv($output, $line);
            }
        }
        fclose($handle);
    }
}

fclose($output);
?>