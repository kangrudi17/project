<?php
$user_id = $_SESSION["user_id"];

// Jumlah surat masuk (internal dan diteruskan)
if ($_SESSION["role"] === "fakultas") {
    $query_masuk = "SELECT COUNT(*) as total FROM surat WHERE tujuan = $user_id AND id_pengirim_eksternal IS NULL";
    $result_masuk = $conn->query($query_masuk);
    $jumlah_masuk = $result_masuk->fetch_assoc()["total"];
} else {
    $q_langsung = "SELECT COUNT(*) as total FROM surat WHERE tujuan = $user_id AND id_pengirim_eksternal IS NULL";
    $res_langsung = $conn->query($q_langsung)->fetch_assoc()["total"];

    $q_diteruskan = "SELECT COUNT(DISTINCT id_surat) as total FROM surat_diteruskan WHERE id_penerima = $user_id";
    $res_diteruskan = $conn->query($q_diteruskan)->fetch_assoc()["total"];
    
    $jumlah_masuk = $res_langsung + $res_diteruskan;
}

// Jumlah surat keluar
$result_keluar = $conn->query("SELECT COUNT(*) as total FROM surat WHERE id_pengirim = $user_id AND id_pengirim_eksternal IS NULL");
$jumlah_keluar = $result_keluar->fetch_assoc()["total"];

// Jumlah surat sudah divalidasi (diterima/ditolak)
$result_validasi = $conn->query("SELECT COUNT(*) as total FROM surat WHERE tujuan = $user_id AND id_pengirim_eksternal IS NULL AND status != 'pending'");
$jumlah_validasi = $result_validasi->fetch_assoc()["total"];
?>
<div class="content-header">
    <h1>Dashboard</h1>
    <p>Selamat datang, <?php echo htmlspecialchars($_SESSION["nama_lengkap"]); ?>! Berikut ringkasan aktivitas surat Anda.</p>
</div>
<div class="content-body">
    <div class="stat-cards-container">
        <div class="stat-card">
            <div class="icon-wrapper bg-blue">
                <i data-feather="inbox"></i>
            </div>
            <div class="stat-info">
                <h2><?php echo $jumlah_masuk; ?></h2>
                <p>Total Surat Masuk</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="icon-wrapper bg-green">
                <i data-feather="send"></i>
            </div>
            <div class="stat-info">
                <h2><?php echo $jumlah_keluar; ?></h2>
                <p>Total Surat Keluar</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="icon-wrapper bg-yellow">
                <i data-feather="check-square"></i>
            </div>
            <div class="stat-info">
                <h2><?php echo $jumlah_validasi; ?></h2>
                <p>Surat Tervalidasi</p>
            </div>
        </div>
    </div>
</div>
