<?php
require_once 'config.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Ambil parameter dari URL
$laporan = isset($_GET['laporan']) ? sanitize_input($_GET['laporan']) : '';
$user_id = $_SESSION['user_id'];

// Ambil filter
$filter_start_date = isset($_GET['start_date']) ? sanitize_input($_GET['start_date']) : '';
$filter_end_date = isset($_GET['end_date']) ? sanitize_input($_GET['end_date']) : '';
$filter_pengirim = isset($_GET['pengirim']) ? sanitize_input($_GET['pengirim']) : '';
$filter_penerima = isset($_GET['penerima']) ? sanitize_input($_GET['penerima']) : '';


$data = [];
$title = "Laporan Tidak Ditemukan";
$columns = [];

// Logika Query berdasarkan jenis laporan
switch ($laporan) {
    case 'inbox_internal':
        $title = "Laporan Surat Masuk";
        $columns = ["No. Surat", "Perihal", "Pengirim", "Tgl. Surat", "Status", "aksi"];
        
        $query_langsung = "
            SELECT s.nomor_surat, s.perihal, u_pengirim.nama_lengkap as nama_pengirim, s.tanggal_surat, s.status, 'Langsung' as tipe_masuk
            FROM surat s JOIN users u_pengirim ON s.id_pengirim = u_pengirim.id
            WHERE s.tujuan = ? AND s.id_pengirim_eksternal IS NULL";
        
        $query_diteruskan = "
            SELECT s.nomor_surat, s.perihal, CONCAT('Diteruskan oleh ', u_penerus.nama_lengkap) as nama_pengirim, s.tanggal_surat, 'diterima' as status, 'Diteruskan' as tipe_masuk
            FROM surat s JOIN surat_diteruskan sd ON s.id_surat = sd.id_surat
            JOIN users u_penerus ON sd.id_penerus = u_penerus.id
            WHERE sd.id_penerima = ?";

        $params_langsung = [$user_id]; $types_langsung = "i";
        $params_diteruskan = [$user_id]; $types_diteruskan = "i";

        if (!empty($filter_start_date)) {
            $query_langsung .= " AND s.tanggal_surat >= ?"; $params_langsung[] = $filter_start_date; $types_langsung .= "s";
            $query_diteruskan .= " AND s.tanggal_surat >= ?"; $params_diteruskan[] = $filter_start_date; $types_diteruskan .= "s";
        }
        if (!empty($filter_end_date)) {
            $query_langsung .= " AND s.tanggal_surat <= ?"; $params_langsung[] = $filter_end_date; $types_langsung .= "s";
            $query_diteruskan .= " AND s.tanggal_surat <= ?"; $params_diteruskan[] = $filter_end_date; $types_diteruskan .= "s";
        }
        if (!empty($filter_pengirim) && intval($filter_pengirim) > 0) {
            $query_langsung .= " AND s.id_pengirim = ?"; $params_langsung[] = intval($filter_pengirim); $types_langsung .= "i";
            $query_diteruskan .= " AND sd.id_penerus = ?"; $params_diteruskan[] = intval($filter_pengirim); $types_diteruskan .= "i";
        }
        
        $final_query = "($query_langsung) UNION ($query_diteruskan) ORDER BY tanggal_surat DESC";
        $stmt_final = $conn->prepare($final_query);
        $stmt_final->bind_param($types_langsung . $types_diteruskan, ...array_merge($params_langsung, $params_diteruskan));
        $stmt_final->execute();
        $result = $stmt_final->get_result();
        while($row = $result->fetch_assoc()) { $data[] = $row; }
        break;

    case 'inbox_eksternal':
        if ($_SESSION['role'] === 'fakultas') {
            $title = "Laporan Arsip Surat Eksternal";
            $columns = ["No. Surat", "Perihal", "Pengirim", "Tgl. Surat", "Diteruskan Kepada"];

            $where_clauses = ["s.tujuan = ?"];
            $bind_params_types = "i";
            $bind_params_values = [$user_id];

            if (!empty($filter_start_date)) { $where_clauses[] = "s.tanggal_surat >= ?"; $bind_params_types .= "s"; $bind_params_values[] = $filter_start_date; }
            if (!empty($filter_end_date)) { $where_clauses[] = "s.tanggal_surat <= ?"; $bind_params_types .= "s"; $bind_params_values[] = $filter_end_date; }
            if (!empty($filter_pengirim) && intval($filter_pengirim) > 0) { $where_clauses[] = "s.id_pengirim_eksternal = ?"; $bind_params_types .= "i"; $bind_params_values[] = intval($filter_pengirim); }
            $where_sql = implode(" AND ", $where_clauses);

            $query = "SELECT s.nomor_surat, s.perihal, k.nama_instansi as nama_pengirim, s.tanggal_surat, GROUP_CONCAT(u.nama_lengkap SEPARATOR ', ') as diteruskan_kepada
                      FROM surat s JOIN kontak_luar k ON s.id_pengirim_eksternal = k.id_kontak
                      LEFT JOIN surat_diteruskan sd ON s.id_surat = sd.id_surat
                      LEFT JOIN users u ON sd.id_penerima = u.id
                      WHERE $where_sql GROUP BY s.id_surat ORDER BY s.tanggal_surat DESC";
            $stmt = $conn->prepare($query);
            $stmt->bind_param($bind_params_types, ...$bind_params_values);
            $stmt->execute();
            $result = $stmt->get_result();
            while($row = $result->fetch_assoc()) { $data[] = $row; }
        }
        break;

    case 'outbox':
        $title = "Laporan Riwayat Surat Keluar";
        $columns = ["No. Surat", "Perihal", "Penerima", "Tgl. Kirim", "Status"];

        $where_clauses = ["s.id_pengirim = ?", "s.id_pengirim_eksternal IS NULL"];
        $bind_params_types = "i";
        $bind_params_values = [$user_id];
        
        if (!empty($filter_start_date)) { $where_clauses[] = "s.tanggal_surat >= ?"; $bind_params_types .= "s"; $bind_params_values[] = $filter_start_date; }
        if (!empty($filter_end_date)) { $where_clauses[] = "s.tanggal_surat <= ?"; $bind_params_types .= "s"; $bind_params_values[] = $filter_end_date; }
        if (!empty($filter_penerima)) {
            list($tipe, $id) = explode('-', $filter_penerima);
            if (($tipe === 'internal' || $tipe === 'eksternal') && is_numeric($id)) {
                $where_clauses[] = "s.tipe_tujuan = ? AND s.tujuan = ?";
                $bind_params_types .= "si"; $bind_params_values[] = $tipe; $bind_params_values[] = $id;
            }
        }
        $where_sql = implode(" AND ", $where_clauses);

        $query = "SELECT s.nomor_surat, s.perihal, 
                  CASE WHEN s.tipe_tujuan = 'internal' THEN u.nama_lengkap WHEN s.tipe_tujuan = 'eksternal' THEN k.nama_instansi END as nama_penerima,
                  s.tanggal_dibuat as tanggal_kirim, s.status
                  FROM surat s 
                  LEFT JOIN users u ON s.tujuan = u.id AND s.tipe_tujuan = 'internal'
                  LEFT JOIN kontak_luar k ON s.tujuan = k.id_kontak AND s.tipe_tujuan = 'eksternal'
                  WHERE $where_sql ORDER BY s.tanggal_dibuat DESC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param($bind_params_types, ...$bind_params_values);
        $stmt->execute();
        $result = $stmt->get_result();
        while($row = $result->fetch_assoc()) { $data[] = $row; }
        break;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Laporan - <?php echo htmlspecialchars($title); ?></title>
    <style>
        body { font-family: 'Times New Roman', Times, serif; font-size: 12pt; line-height: 1.5; color: #000; }
        @page { size: A4; margin: 2cm; }
        .kop-surat {
            display: flex;
            align-items: center;
            gap: 20px;
            border-bottom: 3px solid #000;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .kop-surat img {
            width: 85px;
            height: auto;
        }
        .kop-surat-text {
            text-align: center;
            flex-grow: 1;
        }
        .kop-surat-text h1, .kop-surat-text h2, .kop-surat-text p {
            margin: 0;
            padding: 0;
        }
        .kop-surat-text h1 { font-size: 16pt; }
        .kop-surat-text h2 { font-size: 18pt; font-weight: bold; }
        .kop-surat-text p { font-size: 10pt; }
        .report-title { text-align: center; margin-bottom: 20px; }
        .report-title h3 { margin: 0; font-size: 14pt; text-decoration: underline; }
        .filter-info { margin-bottom: 15px; font-size: 11pt; }
        .report-table { width: 100%; border-collapse: collapse; }
        .report-table th, .report-table td { border: 1px solid #000; padding: 8px; text-align: left; }
        .report-table th { background-color: #e9ecef; font-weight: bold; }
        .text-center { text-align: center; }
        .no-data { text-align: center; padding: 20px; font-style: italic; }
        @media print {
            body { -webkit-print-color-adjust: exact; }
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="kop-surat">
        <img src="img/logo.png" alt="Logo">
        <div class="kop-surat-text">
            <h1>UNIVERSITAS PERJUANGAN TASIKMALAYA</h1>
            <h2>FAKULTAS TEKNIK</h2>
            <p>Jl. Peta No. 177, Kahuripan, Tawang, Tasikmalaya 46115</p>
            <p>Email: ft@unper.ac.id, Website: ft.unper.ac.id</p>
        </div>
    </div>

    <div class="report-title">
        <h3><?php echo htmlspecialchars($title); ?></h3>
    </div>
    
    <div class="filter-info">
        <strong>Filter Aktif:</strong><br>
        - Periode Tanggal: <?php echo !empty($filter_start_date) ? date('d M Y', strtotime($filter_start_date)) : 'Semua'; ?> s/d <?php echo !empty($filter_end_date) ? date('d M Y', strtotime($filter_end_date)) : 'Semua'; ?><br>
        - <?php echo ($laporan === 'outbox' ? 'Penerima' : 'Pengirim'); ?>: 
        <?php 
            if(!empty($filter_pengirim) || !empty($filter_penerima)){
                $filter_id = !empty($filter_pengirim) ? $filter_pengirim : $filter_penerima;
                // Anda mungkin perlu query tambahan di sini untuk mendapatkan nama berdasarkan ID
                echo "ID " . htmlspecialchars($filter_id); 
            } else {
                echo "Semua";
            }
        ?>
        <br>
        Dicetak oleh: <?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?> pada <?php echo date('d M Y H:i:s'); ?>
    </div>

    <table class="report-table">
        <thead>
            <tr>
                <th>No.</th>
                <?php foreach ($columns as $col): ?>
                    <th><?php echo htmlspecialchars($col); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (count($data) > 0): ?>
                <?php $no = 1; foreach ($data as $row): ?>
                    <tr>
                        <td class="text-center"><?php echo $no++; ?></td>
                        <?php foreach ($row as $key => $value): ?>
                        <td>
                            <?php 
                                if (in_array($key, ['tanggal_surat', 'tanggal_kirim'])) {
                                    echo date('d M Y', strtotime($value));
                                } else {
                                    echo htmlspecialchars($value); 
                                }
                            ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="<?php echo count($columns) + 1; ?>" class="no-data">Tidak ada data yang sesuai dengan filter yang diterapkan.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>

