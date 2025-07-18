// Ganti isi file auth.php
<?php
session_start();
include 'config.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $stmt = $conn->prepare("SELECT id, password, nama_lengkap, role FROM kasir WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $kasir = $result->fetch_assoc();
        if (password_verify($password, $kasir['password'])) {
            $_SESSION['kasir_id'] = $kasir['id'];
            $_SESSION['kasir_nama'] = $kasir['nama_lengkap'];
            $_SESSION['kasir_role'] = $kasir['role']; // <<< BARU: Simpan peran/role
            header("Location: index.php");
            exit();
        }
    }
    header("Location: login.php?error=Username atau password salah!");
    exit();
}
?>