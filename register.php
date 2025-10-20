<?php
require_once 'config.php';

// Jika sudah login, redirect ke dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error_message = '';
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_lengkap = sanitize_input($_POST['nama_lengkap']);
    $username = sanitize_input($_POST['username']);
    $password = sanitize_input($_POST['password']);
    $role = sanitize_input($_POST['role']);

    // Validasi sederhana
    if (empty($nama_lengkap) || empty($username) || empty($password) || empty($role)) {
        $error_message = "Semua field wajib diisi.";
    } elseif (!in_array($role, ['prodi_ti', 'prodi_ts', 'fakultas', 'keuangan'])) {
        $error_message = "Role tidak valid.";
    } else {
        // Cek jika username sudah ada
        $stmt_check = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt_check->bind_param("s", $username);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $error_message = "Username sudah digunakan, silakan pilih yang lain.";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert user baru
            $stmt_insert = $conn->prepare("INSERT INTO users (username, password, nama_lengkap, role) VALUES (?, ?, ?, ?)");
            $stmt_insert->bind_param("ssss", $username, $hashed_password, $nama_lengkap, $role);

            if ($stmt_insert->execute()) {
                // Redirect ke halaman login dengan pesan sukses
                header("Location: index.php?status=registered");
                exit();
            } else {
                $error_message = "Terjadi kesalahan. Gagal mendaftar.";
            }
            $stmt_insert->close();
        }
        $stmt_check->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - Sistem Manajemen Surat</title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="login-body">

    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h2>Buat Akun Baru</h2>
                <p>Isi data diri Anda untuk mendaftar</p>
            </div>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="login-form">
                <div class="form-group">
                    <label for="nama_lengkap">Nama Lengkap</label>
                    <input type="text" id="nama_lengkap" name="nama_lengkap" required>
                </div>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="role">Role</label>
                    <select id="role" name="role" required>
                        <option value="">-- Pilih Role --</option>
                        <option value="prodi_ti">Prodi Teknik Informatika</option>
                        <option value="prodi_ts">Prodi Teknik Sipil</option>
                        <option value="fakultas">Admin Fakultas</option>
                        <option value="keuangan">Keuangan</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Daftar</button>
            </form>
            <div class="login-register-link">
                <p>Sudah punya akun? <a href="index.php">Login di sini</a></p>
            </div>
        </div>
    </div>

</body>
</html>
