<?php
session_start();
// Keamanan: Jika tidak ada sesi kasir, arahkan paksa ke halaman login
if (!isset($_SESSION['kasir_id'])) {
    header("Location: login.php");
    exit();
}
// Sertakan file konfigurasi untuk koneksi database
include 'config.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Rental PS</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="header">
        <h1>Dashboard Operator</h1>
        
        <div id="jam-sekarang">00:00:00</div>

        <div class="info-kasir">
            <button id="btn-laporan-harian" class="tombol-laporan">Laporan Hari Ini</button>
            Kasir: <strong><?php echo htmlspecialchars($_SESSION['kasir_nama']); ?></strong>
            <?php if ($_SESSION['kasir_role'] === 'admin'): ?>
                <a href="admin.php" class="tombol-logout" style="background-color:#5bc0de;">Pengaturan</a>
            <?php endif; ?>
            <a href="logout.php" class="tombol-logout">Logout</a>
        </div>
    </div>

    <div class="container">
        <?php 
            // Mengambil semua data konsol dari database untuk ditampilkan
            $result = $conn->query("SELECT * FROM konsol ORDER BY id ASC");
            while($config = $result->fetch_assoc()): 
        ?>
            <div class="konsol-card <?php if(!$config['is_active']) echo 'non-aktif'; ?>" id="konsol-<?php echo $config['id']; ?>" data-idkonsol="<?php echo $config['id']; ?>">
                
                <div class="konsol-nomor">#<?php echo $config['id']; ?></div>

                <h2><?php echo htmlspecialchars($config['nama']); ?></h2>

                <div class="konsol-details">
                    <span class="detail-item"><?php echo htmlspecialchars($config['tipe']); ?></span>
                    <span class="detail-separator">&middot;</span>
                    <span class="detail-item">Rp <?php echo number_format($config['harga_per_menit']); ?> / menit</span>
                </div>
                
                <div class="status" data-status="mati">
                    <div class="status-dot"></div>
                    <span class="status-text">MENUNGGU DATA...</span>
                </div>

                <div class="info-sewa" style="display: none;">
                    <div class="timer">
                        Sisa Waktu: <span class="sisa-waktu">--:--:--</span>
                    </div>
                    <div class="biaya" style="display: none;">
                        Total Biaya: <span class="total-biaya">Rp 0</span>
                    </div>
                    <button type="button" class="tombol-hentikan">HENTIKAN SEWA</button>
                </div>

                <form class="form-sewa" style="display: none;">
                    <input type="number" class="input-menit" placeholder="Jumlah Menit" min="1" required>
                    <button type="submit">START</button>
                </form>

                <form class="form-tambah-waktu" style="display: none;" data-logid="">
                    <input type="number" class="input-menit-tambahan" placeholder="Tambah Menit" min="1">
                    <button type="submit">TAMBAH WAKTU</button>
                    <button type="button" class="tombol-selesai">SELESAI</button>
                </form>

            </div>
        <?php endwhile; ?>
    </div>

    <div id="modal-laporan" class="modal-overlay">
        <div class="modal-content">
            <span class="modal-close">&times;</span>
            <h2>Laporan Omset Hari Ini</h2>
            <p>Tanggal: <strong><?php echo date('d F Y'); ?></strong></p>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Nama Kasir</th>
                        <th>Jumlah Transaksi</th>
                        <th>Total Omset</th>
                    </tr>
                </thead>
                <tbody id="laporan-harian-body">
                    </tbody>
            </table>
            <div class="laporan-total">
                <strong>Grand Total Hari Ini:</strong> <span id="grand-total-harian">Rp 0</span>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="script.js"></script>
</body>
</html>