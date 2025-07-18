<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['kasir_id']) || $_SESSION['kasir_role'] !== 'admin') {
    die(json_encode(['status' => 'error', 'message' => 'Akses ditolak.']));
}

include 'config.php';
$action = $_POST['action'] ?? '';
$response = ['status' => 'error', 'message' => 'Aksi tidak valid.'];

// === MANAJEMEN DATA (READ) ===
if ($action == 'get_all_data') {
    $konsol = $conn->query("SELECT * FROM konsol ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);
    $kasir = $conn->query("SELECT id, username, nama_lengkap, role FROM kasir ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);
    $response = ['status' => 'success', 'data' => ['konsol' => $konsol, 'kasir' => $kasir]];
}
elseif ($action == 'get_console') {
    $id = intval($_POST['id']);
    $stmt = $conn->prepare("SELECT * FROM konsol WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $konsol = $stmt->get_result()->fetch_assoc();
    $response = ['status' => 'success', 'data' => $konsol];
}
elseif ($action == 'get_user') {
    $id = intval($_POST['id']);
    $stmt = $conn->prepare("SELECT id, username, nama_lengkap, role FROM kasir WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $kasir = $stmt->get_result()->fetch_assoc();
    $response = ['status' => 'success', 'data' => $kasir];
}

// === MANAJEMEN KONSOL (CREATE/UPDATE/DELETE) ===
elseif ($action == 'add_console') {
    $stmt = $conn->prepare("INSERT INTO konsol (nama, tipe, ip_address, harga_per_menit, is_active) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssii", $_POST['nama'], $_POST['tipe'], $_POST['ip_address'], $_POST['harga_per_menit'], $_POST['is_active']);
    if($stmt->execute()) $response = ['status' => 'success'];
    else $response['message'] = $stmt->error;
}
elseif ($action == 'update_console') {
    $stmt = $conn->prepare("UPDATE konsol SET nama=?, tipe=?, ip_address=?, harga_per_menit=?, is_active=? WHERE id=?");
    $stmt->bind_param("sssiii", $_POST['nama'], $_POST['tipe'], $_POST['ip_address'], $_POST['harga_per_menit'], $_POST['is_active'], $_POST['id']);
    if($stmt->execute()) $response = ['status' => 'success'];
    else $response['message'] = $stmt->error;
}
elseif ($action == 'delete_console') {
    $id = intval($_POST['id']);
    $check_stmt = $conn->prepare("SELECT id FROM log_sewa WHERE id_konsol = ? AND status IN ('berjalan', 'selesai')");
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    if ($result->num_rows > 0) {
        $response['message'] = 'Gagal: Masih ada sesi aktif untuk konsol ini.';
    } else {
        $delete_stmt = $conn->prepare("DELETE FROM konsol WHERE id = ?");
        $delete_stmt->bind_param("i", $id);
        if ($delete_stmt->execute()) $response = ['status' => 'success'];
        else $response['message'] = 'Gagal menghapus konsol.';
    }
}

// === MANAJEMEN KASIR (CREATE/UPDATE/DELETE) ===
elseif ($action == 'add_user') {
    if (empty($_POST['password'])) {
         $response['message'] = 'Password tidak boleh kosong untuk user baru.';
    } else {
        $password_hash = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO kasir (username, password, nama_lengkap, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $_POST['username'], $password_hash, $_POST['nama_lengkap'], $_POST['role']);
        if($stmt->execute()) $response = ['status' => 'success'];
        else $response['message'] = 'Gagal menambah kasir: ' . $stmt->error;
    }
}
elseif ($action == 'update_user') {
    $id = intval($_POST['id']);
    $username = $_POST['username'];
    $nama_lengkap = $_POST['nama_lengkap'];
    $role = $_POST['role'];
    $password = $_POST['password'];
    if ($id == $_SESSION['kasir_id'] && $role == 'kasir') {
        $response['message'] = 'Anda tidak dapat mengubah peran Anda sendiri menjadi kasir.';
    } else {
        if (!empty($password)) {
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("UPDATE kasir SET username=?, nama_lengkap=?, role=?, password=? WHERE id=?");
            $stmt->bind_param("ssssi", $username, $nama_lengkap, $role, $password_hash, $id);
        } else {
            $stmt = $conn->prepare("UPDATE kasir SET username=?, nama_lengkap=?, role=? WHERE id=?");
            $stmt->bind_param("sssi", $username, $nama_lengkap, $role, $id);
        }
        if($stmt->execute()) {
            $response = ['status' => 'success'];
            if ($id == $_SESSION['kasir_id']) { $_SESSION['kasir_nama'] = $nama_lengkap; }
        } else { $response['message'] = 'Gagal update kasir: ' . $stmt->error; }
    }
}
elseif ($action == 'delete_user') {
    $id = intval($_POST['id']);
    if ($id === 1) {
        $response['message'] = 'Tidak dapat menghapus admin utama (ID 1).';
    } elseif ($id === $_SESSION['kasir_id']) {
        $response['message'] = 'Anda tidak dapat menghapus akun Anda sendiri.';
    } else {
        $stmt = $conn->prepare("DELETE FROM kasir WHERE id = ?");
        $stmt->bind_param("i", $id);
        if($stmt->execute()) $response = ['status' => 'success'];
        else $response['message'] = 'Gagal menghapus kasir.';
    }
}

// === LAPORAN OMSET ===
elseif ($action == 'generate_report') {
    $start_date = $_POST['start_date'] . ' 00:00:00';
    $end_date = $_POST['end_date'] . ' 23:59:59';
    // PERBAIKAN: Query sekarang membaca status 'selesai' DAN 'arsip'
    $stmt = $conn->prepare("SELECT DATE_FORMAT(waktu_selesai, '%Y-%m-%d') as tanggal, nama_kasir_selesai, COUNT(id) as jumlah_transaksi, SUM(total_biaya) as total_omset FROM log_sewa WHERE status IN ('selesai', 'arsip') AND total_biaya IS NOT NULL AND waktu_selesai BETWEEN ? AND ? GROUP BY tanggal, nama_kasir_selesai ORDER BY tanggal DESC, total_omset DESC");
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_total = $conn->prepare("SELECT SUM(total_biaya) as grand_total FROM log_sewa WHERE status IN ('selesai', 'arsip') AND total_biaya IS NOT NULL AND waktu_selesai BETWEEN ? AND ?");
    $stmt_total->bind_param("ss", $start_date, $end_date);
    $stmt_total->execute();
    $grand_total = $stmt_total->get_result()->fetch_assoc()['grand_total'] ?? 0;
    $response = ['status' => 'success', 'data' => $data, 'grand_total' => $grand_total];
}

echo json_encode($response);
$conn->close();
?>