<?php
$skip_db_select = true;
require 'config.php';

$db_name = isset($db) ? $db : (getenv('DB_NAME') ?: "test");

// Create database if permitted, then select
@$conn->query("CREATE DATABASE IF NOT EXISTS `$db_name`");
if (!$conn->select_db($db_name)) {
    die("<div style='color: red;'>❌ Gagal memilih database '$db_name': " . $conn->error . "</div>");
}
echo "<div style='color: green;'>✅ Database '$db_name' siap digunakan.</div>";

// Create SKPD table
$sql_skpd = "CREATE TABLE IF NOT EXISTS skpd (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_skpd VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sql_skpd) === TRUE) {
    echo "<div style='color: green;'>✅ Table 'skpd' created successfully.</div>";
} else {
    echo "<div style='color: red;'>❌ Error creating table skpd: " . $conn->error . "</div>";
}

// Create Pesanan table
$sql_pesanan = "CREATE TABLE IF NOT EXISTS pesanan (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    skpd_id INT(6) UNSIGNED NOT NULL,
    nama_pemesan VARCHAR(255) NULL,
    jenis_kelamin ENUM('Laki-laki', 'Perempuan') NOT NULL,
    jenis_mutz ENUM('Biasa', 'Kepala SKPD') DEFAULT 'Biasa',
    ukuran INT(3) NOT NULL,
    jumlah INT(5) NOT NULL DEFAULT 1,
    status_bayar ENUM('Belum Lunas', 'Lunas') DEFAULT 'Belum Lunas',
    status_pengambilan ENUM('Menunggu Diproses', 'Sedang Dibuat', 'Siap Diambil', 'Sudah Diambil') DEFAULT 'Menunggu Diproses',
    catatan VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (skpd_id) REFERENCES skpd(id) ON DELETE CASCADE
)";
if ($conn->query($sql_pesanan) === TRUE) {
    echo "<div style='color: green;'>✅ Table 'pesanan' created successfully.</div>";
} else {
    echo "<div style='color: red;'>❌ Error creating table pesanan: " . $conn->error . "</div>";
}

// Auto-migrate: Pastikan kolom 'catatan' ada jika tabel sudah dibuat sebelumnya
$check_catatan = $conn->query("SHOW COLUMNS FROM pesanan LIKE 'catatan'");
if ($check_catatan && $check_catatan->num_rows == 0) {
    $conn->query("ALTER TABLE pesanan ADD COLUMN catatan VARCHAR(255) NULL AFTER status_pengambilan");
    echo "<div style='color: green;'>✅ Kolom 'catatan' berhasil ditambahkan ke tabel pesanan.</div>";
}

// Create Retur table
$sql_retur = "CREATE TABLE IF NOT EXISTS retur_pesanan (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pesanan_id INT(6) UNSIGNED NOT NULL,
    alasan VARCHAR(255) NOT NULL,
    ukuran_lama INT(3) NOT NULL,
    ukuran_baru INT(3) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pesanan_id) REFERENCES pesanan(id) ON DELETE CASCADE
)";
if ($conn->query($sql_retur) === TRUE) {
    echo "<div style='color: green;'>✅ Table 'retur_pesanan' created successfully.</div>";
} else {
    echo "<div style='color: red;'>❌ Error creating table retur_pesanan: " . $conn->error . "</div>";
}

// Create Stok table
$sql_stok = "CREATE TABLE IF NOT EXISTS stok_mutz (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    jenis_mutz ENUM('Biasa', 'Kepala SKPD') NOT NULL,
    jenis_kelamin ENUM('Laki-laki', 'Perempuan') NOT NULL,
    ukuran INT(3) NOT NULL,
    jumlah_stok INT(5) NOT NULL DEFAULT 0,
    UNIQUE KEY mutz_combo (jenis_mutz, jenis_kelamin, ukuran)
)";
if ($conn->query($sql_stok) === TRUE) {
    echo "<div style='color: green;'>✅ Table 'stok_mutz' created successfully.</div>";
    
    // Seed data
    $jenis_m = ['Biasa', 'Kepala SKPD'];
    $jenis_k = ['Laki-laki', 'Perempuan'];
    foreach($jenis_m as $jm) {
        foreach($jenis_k as $jk) {
            $sizes = ($jk == 'Laki-laki') ? [55,56,57,58,59,60] : [58,59,60];
            foreach($sizes as $size) {
                $conn->query("INSERT IGNORE INTO stok_mutz (jenis_mutz, jenis_kelamin, ukuran, jumlah_stok) VALUES ('$jm', '$jk', $size, 0)");
            }
        }
    }
} else {
    echo "<div style='color: red;'>❌ Error creating table stok_mutz: " . $conn->error . "</div>";
}

// Create Settings table
$sql_settings = "CREATE TABLE IF NOT EXISTS settings (
    id INT(1) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_username VARCHAR(50) NOT NULL,
    admin_password VARCHAR(255) NOT NULL,
    harga_biasa INT(10) NOT NULL,
    harga_kepala INT(10) NOT NULL
)";
if ($conn->query($sql_settings) === TRUE) {
    echo "<div style='color: green;'>✅ Table 'settings' created successfully.</div>";
    
    // Insert default settings if empty
    $check_settings = $conn->query("SELECT * FROM settings");
    if ($check_settings->num_rows == 0) {
        $conn->query("INSERT INTO settings (admin_username, admin_password, harga_biasa, harga_kepala) VALUES ('admin', 'admin123', 55000, 150000)");
        echo "<div style='color: green;'>✅ Default settings inserted.</div>";
    }
} else {
    echo "<div style='color: red;'>❌ Error creating table settings: " . $conn->error . "</div>";
}

echo "<br><a href='index.php' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; display: inline-block; font-family: sans-serif;'>Lanjut ke Aplikasi</a>";

$conn->close();
?>
