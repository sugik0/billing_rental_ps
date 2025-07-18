<?php
session_start();
header('Content-Type: application/json');
include 'config.php';

// Fungsi isTvOn() tidak ada perubahan
function isTvOn($ip) {
    if (!defined('ADB_PATH') || empty($ip)) return false; 
    $command_power = ADB_PATH . " -s " . escapeshellarg($ip) . " shell dumpsys power";
    $output_power = shell_exec($command_power);
    if (empty($output_power)) return false;
    if (strpos($output_power, 'mWakefulness=Awake') !== false) return true;
    if (strpos($output_power, 'Display Power: state=ON') !== false) return true;
    $command_input = ADB_PATH . " -s " . escapeshellarg($ip) . " shell dumpsys input_method";
    $output_input = shell_exec($command_input);
    if (!empty($output_input) && strpos($output_input, 'mInteractive=true') !== false) return true;
    return false;
}

$action = $_POST['action'] ?? '';
$response = ['status' => 'error', 'message' => 'Aksi tidak dikenal.'];

// Keamanan: Periksa apakah user sudah login untuk aksi yang memerlukan otentikasi
$protected_actions = ['start_rental', 'add_time', 'finish_session', 'force_stop_session'];
if (in_array($action, $protected_actions) && !isset($_SESSION['kasir_id'])) {
    die(json_encode(['status' => 'error', 'message' => 'Sesi Anda telah berakhir. Silakan login kembali.']));
}

if ($action == 'start_rental') {
    $id_konsol = intval($_POST['id_konsol']);
    $durasi = intval($_POST['durasi']);
    $konsol_res = $conn->query("SELECT ip_address FROM konsol WHERE id = $id_konsol AND is_active = 1");
    $ip_tv = $konsol_res->fetch_assoc()['ip_address'] ?? null;
    if ($id_konsol > 0 && $durasi > 0 && $ip_tv) {
        if (!isTvOn($ip_tv)) {
            shell_exec(ADB_PATH . " -s " . escapeshellarg($ip_tv) . " shell input keyevent 26");
            sleep(4);
        }
        if (isTvOn($ip_tv)) {
            $waktu_mulai = date('Y-m-d H:i:s');
            $waktu_selesai = date('Y-m-d H:i:s', strtotime("+$durasi minutes"));
            $stmt = $conn->prepare("INSERT INTO log_sewa (id_konsol, waktu_mulai, waktu_selesai, durasi_menit, status) VALUES (?, ?, ?, ?, 'berjalan')");
            $stmt->bind_param("isss", $id_konsol, $waktu_mulai, $waktu_selesai, $durasi);
            if ($stmt->execute()) { $response = ['status' => 'success']; } 
            else { $response['message'] = 'Gagal menyimpan ke database: ' . $stmt->error; }
        } else { $response['message'] = 'Gagal menyalakan TV.'; }
    } else { $response['message'] = 'Input tidak valid atau konsol tidak aktif.'; }
}

elseif ($action == 'add_time') {
    $log_id = intval($_POST['log_id']);
    $durasi_tambahan = intval($_POST['durasi']);
    $id_konsol = intval($_POST['id_konsol']);
    $konsol_res = $conn->query("SELECT ip_address FROM konsol WHERE id = $id_konsol");
    $ip_tv = $konsol_res->fetch_assoc()['ip_address'] ?? null;
    if ($log_id > 0 && $durasi_tambahan > 0 && $ip_tv) {
        if (!isTvOn($ip_tv)) {
            shell_exec(ADB_PATH . " -s " . escapeshellarg($ip_tv) . " shell input keyevent 26");
            sleep(4);
        }
        $result = $conn->query("SELECT * FROM log_sewa WHERE id = $log_id");
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $waktu_selesai_lama = new DateTime($row['waktu_selesai']);
            $sekarang = new DateTime();
            $base_time = ($sekarang > $waktu_selesai_lama) ? $sekarang : $waktu_selesai_lama;
            $waktu_selesai_baru = $base_time->add(new DateInterval("PT{$durasi_tambahan}M"));
            $durasi_total_baru = $row['durasi_menit'] + $durasi_tambahan;
            $stmt = $conn->prepare("UPDATE log_sewa SET waktu_selesai = ?, durasi_menit = ?, status = 'berjalan' WHERE id = ?");
            $waktu_selesai_baru_str = $waktu_selesai_baru->format('Y-m-d H:i:s');
            $stmt->bind_param("sii", $waktu_selesai_baru_str, $durasi_total_baru, $log_id);
            if($stmt->execute()){ $response = ['status' => 'success']; } 
            else { $response['message'] = 'Gagal update database: ' . $stmt->error; }
        } else { $response['message'] = 'Log sewa tidak ditemukan.'; }
    } else { $response['message'] = 'Input tidak valid atau ID Konsol tidak ditemukan.'; }
}

elseif ($action == 'finish_session') {
    $log_id = intval($_POST['log_id']);
    $id_kasir_login = $_SESSION['kasir_id'];
    $nama_kasir_login = $_SESSION['kasir_nama'];
    if ($log_id > 0 && $id_kasir_login > 0) {
        $result = $conn->query("SELECT * FROM log_sewa WHERE id = $log_id");
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $id_konsol = $row['id_konsol'];
            $durasi_final = $row['durasi_menit'];
            $konsol_res = $conn->query("SELECT harga_per_menit FROM konsol WHERE id = $id_konsol");
            $harga_per_menit = $konsol_res->fetch_assoc()['harga_per_menit'] ?? 0;
            $total_biaya_final = $durasi_final * $harga_per_menit;
            $stmt = $conn->prepare("UPDATE log_sewa SET status = 'arsip', total_biaya = ?, id_kasir_selesai = ?, nama_kasir_selesai = ? WHERE id = ?");
            $stmt->bind_param("iisi", $total_biaya_final, $id_kasir_login, $nama_kasir_login, $log_id);
            if ($stmt->execute()) { $response = ['status' => 'success']; } 
            else { $response['message'] = 'Gagal mengarsipkan sesi: ' . $stmt->error; }
        } else { $response['message'] = 'Log sewa tidak ditemukan untuk diarsipkan.'; }
    }
}

elseif ($action == 'force_stop_session') {
    $log_id = intval($_POST['log_id']);
    $id_kasir_login = $_SESSION['kasir_id'];
    $nama_kasir_login = $_SESSION['kasir_nama'];
    if ($log_id > 0 && $id_kasir_login > 0) {
        $result = $conn->query("SELECT * FROM log_sewa WHERE id = $log_id AND status = 'berjalan'");
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $id_konsol = $row['id_konsol'];
            $konsol_res = $conn->query("SELECT ip_address, harga_per_menit FROM konsol WHERE id = $id_konsol");
            $konsol_data = $konsol_res->fetch_assoc();
            if ($konsol_data && isTvOn($konsol_data['ip_address'])) {
                shell_exec(ADB_PATH . " -s " . escapeshellarg($konsol_data['ip_address']) . " shell input keyevent 26");
            }
            $waktu_mulai = new DateTime($row['waktu_mulai']);
            $waktu_berhenti = new DateTime();
            $durasi_aktual_detik = $waktu_berhenti->getTimestamp() - $waktu_mulai->getTimestamp();
            $durasi_aktual_menit = ceil($durasi_aktual_detik / 60);
            $harga_per_menit = $konsol_data['harga_per_menit'] ?? 0;
            $total_biaya_final = $durasi_aktual_menit * $harga_per_menit;
            $stmt = $conn->prepare("UPDATE log_sewa SET status = 'selesai', total_biaya = ?, id_kasir_selesai = ?, nama_kasir_selesai = ?, durasi_menit = ? WHERE id = ?");
            $stmt->bind_param("iisii", $total_biaya_final, $id_kasir_login, $nama_kasir_login, $durasi_aktual_menit, $log_id);
            if ($stmt->execute()) { $response = ['status' => 'success']; } 
            else { $response['message'] = 'Gagal menghentikan sesi: ' . $stmt->error; }
        } else { $response['message'] = 'Sesi tidak ditemukan atau sudah berhenti.'; }
    }
}

elseif ($action == 'get_daily_report') {
    $today = date('Y-m-d');
    $stmt = $conn->prepare("SELECT nama_kasir_selesai, COUNT(id) as jumlah_transaksi, SUM(total_biaya) as total_omset FROM log_sewa WHERE status IN ('selesai', 'arsip') AND total_biaya IS NOT NULL AND DATE(waktu_selesai) = ? GROUP BY nama_kasir_selesai ORDER BY total_omset DESC");
    $stmt->bind_param("s", $today);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_total = $conn->prepare("SELECT SUM(total_biaya) as grand_total FROM log_sewa WHERE status IN ('selesai', 'arsip') AND total_biaya IS NOT NULL AND DATE(waktu_selesai) = ?");
    $stmt_total->bind_param("s", $today);
    $stmt_total->execute();
    $grand_total = $stmt_total->get_result()->fetch_assoc()['grand_total'] ?? 0;
    $response = ['status' => 'success', 'data' => $data, 'grand_total' => $grand_total];
}

elseif ($action == 'get_status') {
    $data_semua_konsol = [];
    $now_timestamp = time();
    $result_konsol = $conn->query("SELECT * FROM konsol ORDER BY id ASC");
    while($config = $result_konsol->fetch_assoc()) {
        $id = $config['id'];
        $konsol_info = [
            'status_display' => $config['is_active'] ? 'mati' : 'nonaktif',
            'sisa_waktu' => 0, 'durasi_total' => 0, 'log_id' => 0,
            'harga_per_menit' => $config['harga_per_menit']
        ];
        if ($config['is_active'] == 1) {
            $status_fisik_on = isTvOn($config['ip_address']);
            $res_log = $conn->query("SELECT * FROM log_sewa WHERE id_konsol = $id AND status != 'arsip' ORDER BY id DESC LIMIT 1");
            if ($res_log && $res_log->num_rows > 0) {
                $row = $res_log->fetch_assoc();
                $konsol_info['log_id'] = $row['id'];
                $konsol_info['durasi_total'] = $row['durasi_menit'];
                if ($row['status'] == 'berjalan') {
                    $waktu_selesai_ts = strtotime($row['waktu_selesai']);
                    if ($waktu_selesai_ts > $now_timestamp) {
                        $konsol_info['status_display'] = 'menyala_berjalan';
                        $konsol_info['sisa_waktu'] = $waktu_selesai_ts - $now_timestamp;
                    } else {
                        $konsol_info['status_display'] = 'waktu_habis';
                    }
                } elseif ($row['status'] == 'selesai') {
                    $konsol_info['status_display'] = 'waktu_habis';
                }
            }
            if ($status_fisik_on && $konsol_info['status_display'] == 'mati') {
                $konsol_info['status_display'] = 'menyala_standby';
            } elseif (!$status_fisik_on && $konsol_info['status_display'] == 'menyala_berjalan') {
                $konsol_info['status_display'] = 'mati';
            }
        }
        $data_semua_konsol[$id] = $konsol_info;
    }
    $response = ['status' => 'success', 'data' => $data_semua_konsol];
}

$conn->close();
echo json_encode($response);
?>