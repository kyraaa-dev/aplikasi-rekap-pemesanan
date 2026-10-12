<?php
require_once 'config.php';
header('Content-Type: text/plain');

echo "DB Host: " . ($host ?? 'none') . "\n";
echo "DB Name: " . ($db ?? 'none') . "\n";
echo "Connected: " . ($connected ? 'YES' : 'NO') . "\n";

if ($conn) {
    echo "\n--- Databases in Cluster ---\n";
    $res_db = $conn->query("SHOW DATABASES");
    if ($res_db) {
        while ($row = $res_db->fetch_row()) {
            echo "- " . $row[0] . "\n";
        }
    }

    echo "\n--- Tables in current DB ($db) ---\n";
    $res = $conn->query("SHOW TABLES");
    if ($res) {
        while ($row = $res->fetch_row()) {
            echo "- " . $row[0] . "\n";
        }
    }

    echo "\n--- Check stok_mutz ---\n";
    $res_stok = $conn->query("SELECT COUNT(*) as cnt, SUM(jumlah_stok) as total FROM stok_mutz");
    if ($res_stok) {
        $row = $res_stok->fetch_assoc();
        echo "Row count: " . $row['cnt'] . "\n";
        echo "Total stok: " . ($row['total'] ?? 0) . "\n";
    } else {
        echo "Error query stok_mutz: " . $conn->error . "\n";
    }
}

