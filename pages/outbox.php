<?php
$user_id = $_SESSION["user_id"];

// Ambil daftar penerima (internal & eksternal) untuk filter
$internal_recipients_res = $conn->query("SELECT id, nama_lengkap FROM users WHERE id != $user_id ORDER BY nama_lengkap");
$external_recipients_res = $conn->query("SELECT id_kontak, nama_instansi FROM kontak_luar ORDER BY nama_instansi");

// Logika filter server-side
$filter_start_date = isset($_GET['start_date']) ? sanitize_input($_GET['start_date']) : '';
$filter_end_date = isset($_GET['end_date']) ? sanitize_input($_GET['end_date']) : '';
$filter_penerima = isset($_GET['penerima']) ? sanitize_input($_GET['penerima']) : '';
$query_params = "laporan=outbox&start_date=$filter_start_date&end_date=$filter_end_date&penerima=$filter_penerima";


$where_clauses = ["s.id_pengirim = ?", "s.id_pengirim_eksternal IS NULL"];
$bind_params_types = "i";
$bind_params_values = [$user_id];

if (!empty($filter_start_date)) {
    $where_clauses[] = "s.tanggal_surat >= ?";
    $bind_params_types .= "s";
    $bind_params_values[] = $filter_start_date;
}
if (!empty($filter_end_date)) {
    $where_clauses[] = "s.tanggal_surat <= ?";
    $bind_params_types .= "s";
    $bind_params_values[] = $filter_end_date;
}
if (!empty($filter_penerima)) {
    list($tipe, $id) = explode('-', $filter_penerima);
    if (($tipe === 'internal' || $tipe === 'eksternal') && is_numeric($id)) {
        $where_clauses[] = "s.tipe_tujuan = ? AND s.tujuan = ?";
        $bind_params_types .= "si";
        $bind_params_values[] = $tipe;
        $bind_params_values[] = $id;
    }
}

$where_sql = implode(" AND ", $where_clauses);

$query = "SELECT s.*, 
          CASE 
            WHEN s.tipe_tujuan = 'internal' THEN u.nama_lengkap 
            WHEN s.tipe_tujuan = 'eksternal' THEN k.nama_instansi 
          END as nama_penerima
          FROM surat s 
          LEFT JOIN users u ON s.tujuan = u.id AND s.tipe_tujuan = 'internal'
          LEFT JOIN kontak_luar k ON s.tujuan = k.id_kontak AND s.tipe_tujuan = 'eksternal'
          WHERE $where_sql ORDER BY s.tanggal_dibuat DESC"; 
          
$stmt = $conn->prepare($query);
$stmt->bind_param($bind_params_types, ...$bind_params_values);
$stmt->execute();
$result = $stmt->get_result();
?>
<div class="content-header">
    <h1>Riwayat Surat Keluar</h1>
    <p>Daftar surat yang telah Anda kirimkan.</p>
</div>
<div class="content-body">
    <div class="card">
        <div class="card-body">
            <!-- Filter Form -->
            <form method="GET" action="dashboard.php" class="filter-form">
                <input type="hidden" name="page" value="outbox">
                <div class="filter-group">
                    <label for="start_date_outbox">Dari Tanggal</label>
                    <input type="date" name="start_date" id="start_date_outbox" value="<?php echo htmlspecialchars($filter_start_date); ?>">
                </div>
                <div class="filter-group">
                    <label for="end_date_outbox">Sampai Tanggal</label>
                    <input type="date" name="end_date" id="end_date_outbox" value="<?php echo htmlspecialchars($filter_end_date); ?>">
                </div>
                <div class="filter-group">
                    <label for="penerima_outbox">Penerima</label>
                    <select name="penerima" id="penerima_outbox">
                        <option value="">Semua Penerima</option>
                        <optgroup label="Internal">
                        <?php while($recipient = $internal_recipients_res->fetch_assoc()): ?>
                            <option value="internal-<?php echo $recipient['id']; ?>" <?php echo ($filter_penerima == "internal-".$recipient['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($recipient['nama_lengkap']); ?>
                            </option>
                        <?php endwhile; ?>
                        </optgroup>
                         <?php if ($_SESSION['role'] === 'fakultas' || $_SESSION['role'] === 'keuangan'): ?>
                         <optgroup label="Eksternal">
                         <?php while($recipient = $external_recipients_res->fetch_assoc()): ?>
                            <option value="eksternal-<?php echo $recipient['id_kontak']; ?>" <?php echo ($filter_penerima == "eksternal-".$recipient['id_kontak']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($recipient['nama_instansi']); ?>
                            </option>
                         <?php endwhile; ?>
                         </optgroup>
                         <?php endif; ?>
                    </select>
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    <a href="dashboard.php?page=outbox" class="btn btn-secondary btn-sm">Reset</a>
                    <a href="cetak_laporan.php?<?php echo $query_params; ?>" target="_blank" class="btn btn-success btn-sm"><i data-feather="printer"></i> Cetak</a>
                </div>
            </form>

            <div class="filter-container" style="border-top: 1px solid var(--border-color); margin-top: 1.5rem; padding-top: 1.5rem;">
                <input type="search" id="searchOutbox" class="search-box" placeholder="Cari dalam hasil filter...">
            </div>
            <div class="table-responsive">
                 <table class="table" id="tableOutbox">
                    <thead>
                        <tr>
                            <th>No. Surat</th>
                            <th>Perihal</th>
                            <th>Penerima</th>
                            <th>Tgl. Kirim</th>
                            <?php if ($_SESSION['role'] !== 'fakultas'): ?>
                            <th>Status Penerimaan</th>
                            <?php endif; ?>
                            <th>Lampiran</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row["nomor_surat"]); ?></td>
                                <td><?php echo htmlspecialchars($row["perihal"]); ?></td>
                                <td><?php echo htmlspecialchars($row["nama_penerima"]); ?></td>
                                <td><?php echo date("d M Y", strtotime($row["tanggal_dibuat"])); ?></td>
                                <?php if ($_SESSION['role'] !== 'fakultas'): ?>
                                <td><span class="badge status-<?php echo $row["status"]; ?>"><?php echo ucfirst($row["status"]); ?></span></td>
                                <?php endif; ?>
                                <td>
                                    <?php if (!empty($row["file_path"])): ?>
                                        <a href="<?php echo htmlspecialchars($row["file_path"]); ?>" download class="btn btn-sm btn-secondary">
                                           <i data-feather="download"></i> Unduh
                                        </a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                             <tr>
                                <td colspan="<?php echo ($_SESSION['role'] !== 'fakultas') ? '6' : '5'; ?>" class="text-center">Tidak ada surat keluar sesuai filter.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
