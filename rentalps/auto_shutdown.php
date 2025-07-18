<?php
// Skrip ini harus dijalankan terus-menerus di latar belakang via Command Prompt.
// Fungsinya HANYA untuk mematikan TV dan mengubah status saat waktu habis.

// Sertakan file konfigurasi untuk koneksi database dan path ADB
include 'config.php';

echo date('[Y-m-d H:i:s]') . " - Memulai pengecekan...\n";

// Ambil semua sesi yang statusnya 'berjalan' dan waktunya sudah habis
$now = date('Y-m-d H:i:s');
$result = $conn->query("SELECT * FROM log_sewa WHERE status = 'berjalan' AND waktu_selesai <= '$now'");

if ($result && $result->num_rows > 0) {
    while ($sewa = $result->fetch_assoc()) {
        $id_sewa = $sewa['id'];
        $id_konsol = $sewa['id_konsol'];

        // Ambil data IP address dari tabel 'konsol' berdasarkan id_konsol
        $konsol_res = $conn->query("SELECT ip_address FROM konsol WHERE id = $id_konsol");
        $ip_tv = $konsol_res->fetch_assoc()['ip_address'] ?? null;

        if ($ip_tv) {
            echo "-> Waktu habis untuk Konsol #$id_konsol. Mematikan TV di IP $ip_tv...\n";
            
            // 1. Kirim perintah untuk mematikan TV
            // escapeshellarg() digunakan untuk keamanan
            $command = ADB_PATH . " -s " . escapeshellarg($ip_tv) . " shell input keyevent 26";
            shell_exec($command);

            // 2. Update status di database menjadi 'selesai'
            $update_stmt = $conn->prepare("UPDATE log_sewa SET status = 'selesai' WHERE id = ?");
            $update_stmt->bind_param("i", $id_sewa);
            $update_stmt->execute();
            $update_stmt->close();
            
            echo "   Status sewa #$id_sewa di database telah diupdate menjadi 'selesai'.\n";
        } else {
            echo "-> Peringatan: Tidak ditemukan IP untuk konsol #$id_konsol.\n";
        }
    }
} else {
    // Pesan ini akan muncul di CMD jika tidak ada sewa yang habis
    echo "   Tidak ada sewa yang perlu dimatikan saat ini.\n";
}

$conn->close();
?>
