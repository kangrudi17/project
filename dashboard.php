<?php
require_once 'config.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Ambil data user dari session
$user_id_session = $_SESSION['user_id'];
$role_session = $_SESSION['role'];
$nama_lengkap_session = $_SESSION['nama_lengkap'];

// --- HANDLER UNTUK SEMUA FORM SUBMISSION ---
$notification = '';
$notif_type = 'success';

// Ambil notifikasi dari session jika ada (setelah redirect)
if (isset($_SESSION['notification'])) {
    $notification = $_SESSION['notification'];
    $notif_type = isset($_SESSION['notif_type']) ? $_SESSION['notif_type'] : 'success';
    unset($_SESSION['notification'], $_SESSION['notif_type']);
}

// Proses Ganti Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ganti_password'])) {
    $password_lama = $_POST['password_lama'];
    $password_baru = $_POST['password_baru'];
    $konfirmasi_password = $_POST['konfirmasi_password'];
    
    $stmt_check = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt_check->bind_param("i", $user_id_session);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    $user = $result->fetch_assoc();
    $stmt_check->close();

    if ($user && password_verify($password_lama, $user['password'])) {
        if (strlen($password_baru) >= 6) {
            if ($password_baru === $konfirmasi_password) {
                $password_hash_baru = password_hash($password_baru, PASSWORD_DEFAULT);
                $stmt_update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt_update->bind_param("si", $password_hash_baru, $user_id_session);
                if ($stmt_update->execute()) {
                    $_SESSION['notification'] = "Password Anda berhasil diperbarui.";
                    $_SESSION['notif_type'] = "success";
                } else {
                    $_SESSION['notification'] = "Terjadi kesalahan pada database.";
                    $_SESSION['notif_type'] = "error";
                }
                $stmt_update->close();
            } else {
                $_SESSION['notification'] = "Konfirmasi password baru tidak cocok.";
                $_SESSION['notif_type'] = "error";
            }
        } else {
            $_SESSION['notification'] = "Password baru minimal harus 6 karakter.";
            $_SESSION['notif_type'] = "error";
        }
    } else {
        $_SESSION['notification'] = "Password lama yang Anda masukkan salah.";
        $_SESSION['notif_type'] = "error";
    }
    header("Location: dashboard.php?page=ganti_password");
    exit();
}


// Proses Aksi (terima/tolak surat)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi_surat'])) {
    $id_surat = intval($_POST['id_surat']);
    $status = $_POST['status'] === 'diterima' ? 'diterima' : 'ditolak';
    $catatan = sanitize_input($_POST['catatan_status']);

    $stmt = $conn->prepare("UPDATE surat SET status = ?, catatan_status = ? WHERE id_surat = ? AND tujuan = ?");
    $stmt->bind_param("ssii", $status, $catatan, $id_surat, $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();
    header("Location: dashboard.php?page=inbox_internal&status=updated");
    exit();
}

// Proses Teruskan Surat (MULTI-FORWARD)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forward_surat'])) {
    if ($_SESSION['role'] === 'fakultas') {
        $id_surat = intval($_POST['id_surat']);
        $id_penerus = $_SESSION['user_id'];

        if (isset($_POST['tujuan_penerusan']) && is_array($_POST['tujuan_penerusan'])) {
            $tujuan_array = $_POST['tujuan_penerusan'];
            
            // Hapus data penerusan lama jika ada (untuk menghindari duplikat jika diteruskan ulang)
            $conn->query("DELETE FROM surat_diteruskan WHERE id_surat = $id_surat");

            $stmt = $conn->prepare("INSERT INTO surat_diteruskan (id_surat, id_penerima, id_penerus, tanggal_diteruskan) VALUES (?, ?, ?, NOW())");
            
            foreach ($tujuan_array as $tujuan_id) {
                $id_penerima = intval($tujuan_id);
                if ($id_penerima > 0) {
                    $stmt->bind_param("iii", $id_surat, $id_penerima, $id_penerus);
                    $stmt->execute();
                }
            }
            $stmt->close();
            header("Location: dashboard.php?page=inbox_eksternal&status=forwarded");
        } else {
            header("Location: dashboard.php?page=inbox_eksternal&status=forward_failed_no_selection");
        }
        exit();
    }
}


// Proses Tambah Surat Baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_surat'])) {
    $nomor_surat = sanitize_input($_POST['nomor_surat']);
    $perihal = sanitize_input($_POST['perihal']);
    $tanggal_surat = sanitize_input($_POST['tanggal_surat']);
    $tujuan = sanitize_input($_POST['tujuan']);
    $isi_ringkas = sanitize_input($_POST['isi_ringkas']);
    $id_pengirim = $_SESSION['user_id'];

    list($tipe_tujuan, $id_tujuan) = explode('-', $tujuan);

    // File handling
    $file_path = NULL;
    if (isset($_FILES['file_surat']) && $_FILES['file_surat']['error'] == 0) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        $file_name = time() . '_' . basename($_FILES["file_surat"]["name"]);
        $target_file = $target_dir . $file_name;
        if (move_uploaded_file($_FILES["file_surat"]["tmp_name"], $target_file)) {
            $file_path = $target_file;
        }
    }
    
    $stmt = $conn->prepare("INSERT INTO surat (nomor_surat, perihal, tanggal_surat, id_pengirim, tujuan, tipe_tujuan, isi_ringkas, file_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssissss", $nomor_surat, $perihal, $tanggal_surat, $id_pengirim, $id_tujuan, $tipe_tujuan, $isi_ringkas, $file_path);
    $stmt->execute();
    $stmt->close();
    header("Location: dashboard.php?page=outbox&status=sent");
    exit();
}

// Proses Input Manual Surat Masuk
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['input_manual_surat'])) {
    if ($_SESSION['role'] === 'fakultas') {
        $nomor_surat = sanitize_input($_POST['nomor_surat']);
        $perihal = sanitize_input($_POST['perihal']);
        $tanggal_surat = sanitize_input($_POST['tanggal_surat']);
        $id_pengirim_eksternal = intval($_POST['id_pengirim_eksternal']);
        $isi_ringkas = sanitize_input($_POST['isi_ringkas']);
        
        $id_penginput = $_SESSION['user_id']; // User yg melakukan input
        $tujuan = $_SESSION['user_id']; // Surat ini ditujukan untuk fakultas

        // File handling
        $file_path = NULL;
        if (isset($_FILES['file_surat']) && $_FILES['file_surat']['error'] == 0) {
            $target_dir = "uploads/";
            if (!is_dir($target_dir)) { mkdir($target_dir, 0755, true); }
            $file_name = time() . '_' . basename($_FILES["file_surat"]["name"]);
            $target_file = $target_dir . $file_name;
            if (move_uploaded_file($_FILES["file_surat"]["tmp_name"], $target_file)) {
                $file_path = $target_file;
            }
        }
        
        $stmt = $conn->prepare("INSERT INTO surat (nomor_surat, perihal, tanggal_surat, id_pengirim, tujuan, id_pengirim_eksternal, isi_ringkas, file_path, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'diterima')");
        $stmt->bind_param("sssiisss", $nomor_surat, $perihal, $tanggal_surat, $id_penginput, $tujuan, $id_pengirim_eksternal, $isi_ringkas, $file_path);
        $stmt->execute();
        $stmt->close();
        header("Location: dashboard.php?page=inbox_eksternal&status=manual_input_success");
        exit();
    }
}


// Proses Tambah Kontak Luar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_kontak'])) {
    if ($_SESSION['role'] === 'fakultas') {
        $nama_instansi = sanitize_input($_POST['nama_instansi']);
        $alamat = sanitize_input($_POST['alamat']);
        $email = sanitize_input($_POST['email']);
        $telepon = sanitize_input($_POST['telepon']);

        $stmt = $conn->prepare("INSERT INTO kontak_luar (nama_instansi, alamat, email, telepon) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $nama_instansi, $alamat, $email, $telepon);
        $stmt->execute();
        $stmt->close();
        header("Location: dashboard.php?page=contacts&status=added");
        exit();
    }
}

// Proses Hapus Kontak Luar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_kontak'])) {
    if ($_SESSION['role'] === 'fakultas') {
        $id_kontak = intval($_POST['id_kontak']);
        $stmt = $conn->prepare("DELETE FROM kontak_luar WHERE id_kontak = ?");
        $stmt->bind_param("i", $id_kontak);
        $stmt->execute();
        $stmt->close();
        header("Location: dashboard.php?page=contacts&status=deleted");
        exit();
    }
}

// Proses Tambah User Baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_user'])) {
    if ($_SESSION['role'] === 'fakultas') {
        $username = sanitize_input($_POST['username']);
        $nama_lengkap = sanitize_input($_POST['nama_lengkap']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = sanitize_input($_POST['role']);

        $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        $check->store_result();

        if ($check->num_rows === 0) {
            $stmt = $conn->prepare("INSERT INTO users (username, nama_lengkap, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $nama_lengkap, $password, $role);
            $stmt->execute();
            $stmt->close();
            header("Location: dashboard.php?page=users&status=added");
        } else {
            header("Location: dashboard.php?page=users&status=exists");
        }
        $check->close();
        exit();
    }
}

// Proses Hapus User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_user'])) {
    if ($_SESSION['role'] === 'fakultas') {
        $id_user = intval($_POST['id_user']);
        if ($id_user !== $_SESSION['user_id']) {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $id_user);
            $stmt->execute();
            $stmt->close();
            header("Location: dashboard.php?page=users&status=deleted");
        } else {
             header("Location: dashboard.php?page=users&status=self_delete_error");
        }
        exit();
    }
}


// Ambil halaman saat ini
$page = isset($_GET['page']) ? $_GET['page'] : 'home';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SIMS-FT</title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
</head>
<body class="dashboard-body">
    <button class="menu-toggle" id="menu-toggle" aria-label="Buka Menu">
        <i data-feather="menu"></i>
    </button>
    <div class="overlay" id="overlay"></div>

    <div class="app-container">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h3>SIMS-FT</h3>
            </div>
            <nav class="sidebar-nav">
                 <a href="?page=home" class="<?php echo $page == 'home' ? 'active' : ''; ?>">
                    <i data-feather="home"></i> <span>Dashboard</span>
                </a>
                <?php if ($_SESSION['role'] === 'fakultas'): ?>
                    <a href="?page=inbox_internal" class="<?php echo $page == 'inbox_internal' ? 'active' : ''; ?>">
                        <i data-feather="arrow-down-left"></i> <span>Surat Masuk Internal</span>
                    </a>
                    <a href="?page=inbox_eksternal" class="<?php echo $page == 'inbox_eksternal' ? 'active' : ''; ?>">
                        <i data-feather="hard-drive"></i> <span>Arsip Surat Eksternal</span>
                    </a>
                <?php else: ?>
                    <a href="?page=inbox_internal" class="<?php echo $page == 'inbox_internal' ? 'active' : ''; ?>">
                        <i data-feather="inbox"></i> <span>Surat Masuk</span>
                    </a>
                <?php endif; ?>

                <a href="?page=outbox" class="<?php echo $page == 'outbox' ? 'active' : ''; ?>">
                    <i data-feather="send"></i> <span>Riwayat Keluar</span>
                </a>
                <a href="?page=compose" class="<?php echo $page == 'compose' ? 'active' : ''; ?>">
                    <i data-feather="edit-3"></i> <span>Buat Surat</span>
                </a>

                <?php if ($_SESSION['role'] === 'fakultas'): ?>
                <a href="?page=manual_input" class="<?php echo $page == 'manual_input' ? 'active' : ''; ?>">
                    <i data-feather="file-plus"></i> <span>Input Surat Eksternal</span>
                </a>
                <a href="?page=contacts" class="<?php echo $page == 'contacts' ? 'active' : ''; ?>">
                    <i data-feather="book"></i> <span>Kontak Eksternal</span>
                </a>
                <a href="?page=users" class="<?php echo $page == 'users' ? 'active' : ''; ?>">
                    <i data-feather="users"></i> <span>Kelola Pengguna</span>
                </a>
                <?php endif; ?>
            </nav>
            <div class="sidebar-footer">
                <div class="user-profile">
                    <span class="user-name"><?php echo htmlspecialchars($nama_lengkap_session); ?></span>
                    <span class="user-role"><?php echo str_replace('_', ' ', $role_session); ?></span>
                </div>
                <a href="?page=ganti_password" class="sidebar-link <?php echo $page == 'ganti_password' ? 'active' : ''; ?>">
                    <i data-feather="key"></i> <span>Ganti Password</span>
                </a>
                <a href="dashboard.php?page=logout" class="logout-btn">
                    <i data-feather="log-out"></i> <span>Logout</span>
                </a>
            </div>
        </aside>

        <main class="main-content">
            <?php
            // Tampilkan notifikasi jika ada
            if (!empty($notification)) {
                $alert_class = $notif_type === 'error' ? 'alert-danger' : 'alert-success';
                echo '<div class="alert ' . $alert_class . '">' . htmlspecialchars($notification) . '</div>';
            }

            // Logout logic
            if ($page === 'logout') {
                session_destroy();
                header("Location: index.php");
                exit();
            }

            // Konten dinamis berdasarkan halaman
            $allowed_pages = ['home', 'inbox_internal', 'inbox_eksternal', 'outbox', 'compose', 'manual_input', 'contacts', 'users', 'ganti_password'];
            if (in_array($page, $allowed_pages)) {
                $fakultas_only_pages = ['inbox_eksternal', 'manual_input', 'contacts', 'users'];
                if (in_array($page, $fakultas_only_pages) && $_SESSION['role'] !== 'fakultas') {
                     include 'pages/home.php'; // Default to home if access denied
                } else if (file_exists("pages/{$page}.php")) {
                    include "pages/{$page}.php";
                } else {
                    // Fallback untuk halaman yang belum ada filenya, seperti ganti_password
                    include 'pages/home.php';
                }
            } else {
                include 'pages/home.php';
            }
            ?>
             <footer class="page-footer">
                Copyright &copy; Rudihartono 2025
            </footer>
        </main>
    </div>

<script>
    feather.replace();

    // JS untuk menu responsif
    const menuToggle = document.getElementById('menu-toggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');

    if (menuToggle && sidebar && overlay) {
        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('active');
        });

        overlay.addEventListener('click', () => {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
        });
    }

    // Fungsi untuk filter tabel client-side (hanya untuk search box)
    function applySimpleTextFilter(inputId, tableId) {
        // ... (kode JS filter yang sudah ada) ...
    }

    // Terapkan filter ke semua tabel yang ada
    document.addEventListener('DOMContentLoaded', function() {
        // ... (kode JS filter yang sudah ada) ...
    });
</script>
</body>
</html>

<?php
// Buat halaman terpisah untuk kerapian
if (!is_dir('pages')) mkdir('pages');

// File: pages/home.php (tidak berubah)
// ...

// File: pages/ganti_password.php (BARU)
$ganti_password_content = '
<div class="content-header">
    <h1>Ganti Password</h1>
    <p>Ubah password Anda secara berkala untuk menjaga keamanan akun.</p>
</div>
<div class="content-body">
    <div class="card">
        <div class="card-body">
            <form action="dashboard.php" method="post" class="form-compose">
                <div class="form-group">
                    <label for="password_lama">Password Lama</label>
                    <input type="password" id="password_lama" name="password_lama" required>
                </div>
                <div class="form-group">
                    <label for="password_baru">Password Baru</label>
                    <input type="password" id="password_baru" name="password_baru" required minlength="6">
                    <small class="text-muted">Minimal 6 karakter.</small>
                </div>
                <div class="form-group">
                    <label for="konfirmasi_password">Konfirmasi Password Baru</label>
                    <input type="password" id="konfirmasi_password" name="konfirmasi_password" required>
                </div>
                <div class="form-actions">
                    <button type="submit" name="ganti_password" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
';
file_put_contents('pages/ganti_password.php', $ganti_password_content);

// File-file halaman lain (tidak berubah)
// ...

// Tutup koneksi di akhir script
$conn->close();
?>

