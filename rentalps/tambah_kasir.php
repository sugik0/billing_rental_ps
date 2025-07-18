<?php
/**
 * Utilitas untuk menambah user baru (Admin/Kasir) via Command Line.
 * * Cara menjalankan:
 * 1. Buka Command Prompt (CMD).
 * 2. Ketik perintah berikut dan tekan Enter:
 * C:\xampp\php\php.exe C:\xampp\htdocs\rentalps\tambah_kasir.php
 * (Sesuaikan path jika instalasi XAMPP atau folder proyek Anda berbeda)
 * 3. Ikuti instruksi yang muncul di layar.
 */

// Memuat koneksi database dan pengaturan dasar
include 'config.php';

// Cek apakah skrip dijalankan dari command line, bukan dari browser
if (php_sapi_name() !== 'cli') {
    die("Akses ditolak. Skrip ini hanya boleh dijalankan dari Command Prompt (CMD).");
}

echo "========================================\n";
echo "   Utilitas Tambah User (Admin/Kasir)   \n";
echo "========================================\n";

// Minta input dari pengguna di command line
$username = readline("Masukkan username baru: ");
$nama_lengkap = readline("Masukkan nama lengkap: ");
$password = readline("Masukkan password: ");
$role = strtolower(readline("Masukkan peran ('admin' atau 'kasir'): ")); // <-- BARU: Minta input peran

// --- Validasi Input ---
if (empty($username) || empty($nama_lengkap) || empty($password)) {
    die("GAGAL: Username, nama lengkap, dan password tidak boleh kosong.\n");
}

// Validasi peran, jika input salah, default ke 'kasir' demi keamanan
if ($role !== 'admin' && $role !== 'kasir') {
    echo "Peringatan: Peran tidak valid. Diatur sebagai 'kasir' (default).\n";
    $role = 'kasir';
}

// --- Proses Data ---

// Hashing password dengan BCRYPT, standar keamanan yang kuat
$password_hash = password_hash($password, PASSWORD_BCRYPT);

// Siapkan query SQL untuk memasukkan data baru
$stmt = $conn->prepare("INSERT INTO kasir (username, password, nama_lengkap, role) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $username, $password_hash, $nama_lengkap, $role);

// Eksekusi query dan berikan feedback
if ($stmt->execute()) {
    echo "----------------------------------------\n";
    echo "BERHASIL! User baru telah ditambahkan.\n";
    echo "  Username: $username\n";
    echo "  Nama    : $nama_lengkap\n";
    echo "  Peran   : $role\n";
    echo "----------------------------------------\n";
} else {
    echo "----------------------------------------\n";
    echo "GAGAL: Tidak dapat menambahkan user.\n";
    // Tampilkan pesan error spesifik jika ada (misal: username sudah ada)
    echo "Pesan Error MySQL: " . $stmt->error . "\n";
    echo "----------------------------------------\n";
}

// Tutup koneksi
$stmt->close();
$conn->close();
?>