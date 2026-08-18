<?php
require 'config.php';

// Pastikan user sudah login
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$format = $_GET['format'] ?? 'sql';
$timestamp = date('Y-m-d_H-i-s');
$tables = ['settings', 'skpd', 'stok_mutz', 'pesanan', 'retur_pesanan'];

if ($format === 'json') {
    // Export Data JSON
    $backup_data = [
        'app' => 'E-MutZ KORPRI',
        'exported_at' => date('Y-m-d H:i:s'),
        'tables' => []
    ];

    foreach ($tables as $table) {
        $result = $conn->query("SELECT * FROM `$table`");
        $rows = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }
        $backup_data['tables'][$table] = $rows;
    }

    $json_content = json_encode($backup_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="backup_emutz_korpri_' . $timestamp . '.json"');
    header('Content-Length: ' . strlen($json_content));
    echo $json_content;
    exit;
} else {
    // Export Data SQL
    $sql_dump = "-- ========================================================\n";
    $sql_dump .= "-- E-MutZ KORPRI - Database Backup Dump\n";
    $sql_dump .= "-- Exported on: " . date('Y-m-d H:i:s') . "\n";
    $sql_dump .= "-- Host: " . htmlspecialchars($host ?? 'TiDB Cloud') . "\n";
    $sql_dump .= "-- Database: " . htmlspecialchars($db ?? 'test') . "\n";
    $sql_dump .= "-- ========================================================\n\n";
    $sql_dump .= "SET FOREIGN_KEY_CHECKS=0;\n";
    $sql_dump .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
    $sql_dump .= "SET time_zone = \"+07:00\";\n\n";

    foreach ($tables as $table) {
        // Table structure
        $create_res = $conn->query("SHOW CREATE TABLE `$table`");
        if ($create_res && $create_row = $create_res->fetch_row()) {
            $sql_dump .= "-- --------------------------------------------------------\n";
            $sql_dump .= "-- Table structure for table `$table`\n";
            $sql_dump .= "-- --------------------------------------------------------\n";
            $sql_dump .= "DROP TABLE IF EXISTS `$table`;\n";
            $sql_dump .= $create_row[1] . ";\n\n";
        }

        // Table data
        $data_res = $conn->query("SELECT * FROM `$table`");
        if ($data_res && $data_res->num_rows > 0) {
            $sql_dump .= "-- Dumping data for table `$table`\n";
            while ($row = $data_res->fetch_assoc()) {
                $keys = array_map(function($k) { return "`" . addslashes($k) . "`"; }, array_keys($row));
                $values = array_map(function($v) use ($conn) {
                    if ($v === null) return "NULL";
                    return "'" . $conn->real_escape_string($v) . "'";
                }, array_values($row));

                $sql_dump .= "INSERT INTO `$table` (" . implode(', ', $keys) . ") VALUES (" . implode(', ', $values) . ");\n";
            }
            $sql_dump .= "\n";
        }
    }

    $sql_dump .= "SET FOREIGN_KEY_CHECKS=1;\n";
    $sql_dump .= "-- Dump completed on " . date('Y-m-d H:i:s') . "\n";

    header('Content-Type: application/sql; charset=utf-8');
    header('Content-Disposition: attachment; filename="backup_emutz_korpri_' . $timestamp . '.sql"');
    header('Content-Length: ' . strlen($sql_dump));
    echo $sql_dump;
    exit;
}
