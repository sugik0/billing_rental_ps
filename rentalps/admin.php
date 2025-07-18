<?php
session_start();
// Keamanan: Hanya admin yang boleh mengakses halaman ini
if (!isset($_SESSION['kasir_id']) || $_SESSION['kasir_role'] !== 'admin') {
    die("Akses ditolak. Anda bukan admin.");
}
include 'config.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Panel Admin - Rental PS</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="admin_style.css">
</head>
<body>
    <div class="header">
        <h1>Panel Pengaturan Admin</h1>
        <div class="info-kasir">
            Admin: <strong><?php echo htmlspecialchars($_SESSION['kasir_nama']); ?></strong>
            <a href="index.php" class="tombol-kembali">Kembali ke Dashboard</a>
            <a href="logout.php" class="tombol-logout">Logout</a>
        </div>
    </div>

    <div class="admin-container">
        <nav class="admin-nav">
            <a href="#tab-konsol" class="admin-nav-link active">Pengaturan Konsol</a>
            <a href="#tab-kasir" class="admin-nav-link">Pengaturan Kasir</a>
            <a href="#tab-laporan" class="admin-nav-link">Laporan Omset</a>
        </nav>

        <div id="tab-konsol" class="admin-tab-content active">
            <h2>Manajemen Konsol</h2>
            <p>Atur perangkat, harga, dan status aktif konsol di sini.</p>
            <table class="admin-table" id="tabel-konsol">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama</th>
                        <th>IP Address</th>
                        <th>Harga/Menit</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    </tbody>
            </table>
            <form id="form-konsol" class="admin-form">
                <h3 id="form-konsol-title">Tambah Konsol Baru</h3>
                <input type="hidden" name="id" id="konsol-id">
                <input type="text" name="nama" id="konsol-nama" placeholder="Nama Konsol (e.g., PS 5 - 01)" required>
                <input type="text" name="tipe" id="konsol-tipe" placeholder="Tipe (e.g., PS 5)" required>
                <input type="text" name="ip_address" id="konsol-ip" placeholder="IP Address" required>
                <input type="number" name="harga_per_menit" id="konsol-harga" placeholder="Harga/Menit" required>
                <select name="is_active" id="konsol-status">
                    <option value="1">Aktif</option>
                    <option value="0">Non-Aktif (Rusak)</option>
                </select>
                <div class="form-actions">
                    <button type="submit">Simpan Konsol</button>
                    <button type="button" id="btn-clear-konsol">Batal</button>
                </div>
            </form>
        </div>

        <div id="tab-kasir" class="admin-tab-content">
            <h2>Manajemen Kasir</h2>
            <p>Tambah, edit, atau hapus akun untuk kasir Anda.</p>
             <table class="admin-table" id="tabel-kasir">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Nama Lengkap</th>
                        <th>Peran</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    </tbody>
            </table>
            <form id="form-kasir" class="admin-form">
                <h3 id="form-kasir-title">Tambah Kasir Baru</h3>
                <input type="hidden" name="id" id="kasir-id">
                <input type="text" name="username" id="kasir-username" placeholder="Username (untuk login)" required>
                <input type="text" name="nama_lengkap" id="kasir-nama" placeholder="Nama Lengkap" required>
                <select name="role" id="kasir-role">
                    <option value="kasir">Kasir</option>
                    <option value="admin">Admin</option>
                </select>
                <input type="password" name="password" id="kasir-password" placeholder="Password Baru (kosongkan jika tidak diubah)">
                <div class="form-actions">
                    <button type="submit">Simpan Kasir</button>
                    <button type="button" id="btn-clear-kasir">Batal</button>
                </div>
            </form>
        </div>

        <div id="tab-laporan" class="admin-tab-content">
            <h2>Laporan Omset</h2>
            <p>Lihat rekapitulasi pendapatan berdasarkan rentang tanggal.</p>
            <form id="form-laporan" class="admin-form-inline">
                <label for="start_date">Dari Tanggal:</label>
                <input type="date" id="start_date" name="start_date" required>
                <label for="end_date">Sampai Tanggal:</label>
                <input type="date" id="end_date" name="end_date" required>
                <button type="submit">Tampilkan Laporan</button>
                <button type="button" id="btn-export-xlsx" style="margin-left: auto;">Export to XLSX</button>
            </form>
            <hr>
            <div id="area-laporan">
                <h3>Hasil Laporan</h3>
                <table class="admin-table" id="tabel-laporan">
                     <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Nama Kasir</th>
                            <th>Jml. Transaksi</th>
                            <th>Total Omset</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td colspan="4" style="text-align:center;">Silakan pilih rentang tanggal dan klik "Tampilkan Laporan".</td></tr>
                    </tbody>
                </table>
                <div class="laporan-total">
                    <strong>Grand Total Omset:</strong> <span id="grand-total">Rp 0</span>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="admin.js"></script>
</body>
</html>