<?php
require_once 'config.php';

// Cek apakah user sudah login dan memiliki role 'fakultas'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'fakultas') {
    header("Location: index.php");
    exit();
}

$id_kontak = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id_kontak <= 0) {
    header("Location: dashboard.php?page=contacts");
    exit();
}

// Proses update data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_kontak'])) {
    $nama_instansi = sanitize_input($_POST['nama_instansi']);
    $alamat = sanitize_input($_POST['alamat']);
    $email = sanitize_input($_POST['email']);
    $telepon = sanitize_input($_POST['telepon']);

    $stmt = $conn->prepare("UPDATE kontak_luar SET nama_instansi = ?, alamat = ?, email = ?, telepon = ? WHERE id_kontak = ?");
    $stmt->bind_param("ssssi", $nama_instansi, $alamat, $email, $telepon, $id_kontak);
    $stmt->execute();
    $stmt->close();
    header("Location: dashboard.php?page=contacts&status=updated");
    exit();
}

// Ambil data kontak yang akan diedit
$stmt = $conn->prepare("SELECT * FROM kontak_luar WHERE id_kontak = ?");
$stmt->bind_param("i", $id_kontak);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows !== 1) {
    header("Location: dashboard.php?page=contacts");
    exit();
}
$kontak = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Kontak - Sistem Manajemen Surat</title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
</head>
<body class="dashboard-body">

    <div class="app-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h3>E-Office</h3>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php?page=inbox"><i data-feather="inbox"></i> Surat Masuk</a>
                <a href="dashboard.php?page=outbox"><i data-feather="send"></i> Riwayat Keluar</a>
                <a href="dashboard.php?page=compose"><i data-feather="edit-3"></i> Buat Surat</a>
                <a href="dashboard.php?page=contacts" class="active"><i data-feather="book"></i> Kontak Eksternal</a>
            </nav>
            <div class="sidebar-footer">
                <div class="user-profile">
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?></span>
                    <span class="user-role"><?php echo str_replace('_', ' ', $_SESSION['role']); ?></span>
                </div>
                <a href="dashboard.php?page=logout" class="logout-btn">
                    <i data-feather="log-out"></i> Logout
                </a>
            </div>
        </aside>
        <main class="main-content">
            <div class="content-header">
                <h1>Edit Kontak Eksternal</h1>
                <p>Ubah informasi kontak instansi di bawah ini.</p>
            </div>
            <div class="content-body">
                <div class="card">
                    <div class="card-body">
                         <form action="edit_contact.php?id=<?php echo $id_kontak; ?>" method="post" class="form-compose">
                            <input type="hidden" name="id_kontak" value="<?php echo $kontak['id_kontak']; ?>">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="nama_instansi">Nama Instansi</label>
                                    <input type="text" name="nama_instansi" required value="<?php echo htmlspecialchars($kontak['nama_instansi']); ?>">
                                </div>
                                 <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($kontak['email']); ?>">
                                </div>
                            </div>
                             <div class="form-row">
                                 <div class="form-group">
                                    <label for="alamat">Alamat</label>
                                    <input type="text" name="alamat" value="<?php echo htmlspecialchars($kontak['alamat']); ?>">
                                </div>
                                 <div class="form-group">
                                    <label for="telepon">Telepon</label>
                                    <input type="text" name="telepon" value="<?php echo htmlspecialchars($kontak['telepon']); ?>">
                                </div>
                            </div>
                            <div class="form-actions">
                                <a href="dashboard.php?page=contacts" class="btn btn-secondary">Batal</a>
                                <button type="submit" name="update_kontak" class="btn btn-primary">Simpan Perubahan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
<script>
    feather.replace();
</script>
</body>
</html>
