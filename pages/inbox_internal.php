<?php
$user_id = $_SESSION["user_id"];

// Ambil daftar pengirim (users) untuk filter
$users_res = $conn->query("SELECT id, nama_lengkap FROM users WHERE id != $user_id ORDER BY nama_lengkap");
$users_list = [];
while($user = $users_res->fetch_assoc()){ $users_list[] = $user; }

// Logika filter server-side
$filter_start_date = isset($_GET['start_date']) ? sanitize_input($_GET['start_date']) : '';
$filter_end_date = isset($_GET['end_date']) ? sanitize_input($_GET['end_date']) : '';
$filter_pengirim = isset($_GET['pengirim']) ? intval($_GET['pengirim']) : 0;
$query_params = "laporan=inbox_internal&start_date=$filter_start_date&end_date=$filter_end_date&pengirim=$filter_pengirim";


// Base queries
$query_langsung = "
    SELECT 
        s.id_surat, s.nomor_surat, s.perihal, s.isi_ringkas, s.tanggal_surat, 
        s.tanggal_dibuat, s.file_path, s.status, 
        u_pengirim.nama_lengkap as nama_pengirim, 
        'Langsung' as tipe_masuk,
        NULL as nama_penerus,
        NULL as nama_pengirim_asli
    FROM surat s
    JOIN users u_pengirim ON s.id_pengirim = u_pengirim.id
    WHERE s.tujuan = ? AND s.id_pengirim_eksternal IS NULL";

$query_diteruskan = "
    SELECT 
        s.id_surat, s.nomor_surat, s.perihal, s.isi_ringkas, s.tanggal_surat, 
        sd.tanggal_diteruskan as tanggal_dibuat, s.file_path, 'diterima' as status,
        u_penerus.nama_lengkap as nama_pengirim,
        'Diteruskan' as tipe_masuk,
        u_penerus.nama_lengkap as nama_penerus,
        k.nama_instansi as nama_pengirim_asli
    FROM surat s
    JOIN surat_diteruskan sd ON s.id_surat = sd.id_surat
    JOIN users u_penerus ON sd.id_penerus = u_penerus.id
    JOIN kontak_luar k ON s.id_pengirim_eksternal = k.id_kontak
    WHERE sd.id_penerima = ?";

// Apply filters
$params_langsung = [$user_id];
$types_langsung = "i";
$params_diteruskan = [$user_id];
$types_diteruskan = "i";

if (!empty($filter_start_date)) {
    $query_langsung .= " AND s.tanggal_surat >= ?";
    $query_diteruskan .= " AND s.tanggal_surat >= ?";
    $params_langsung[] = $filter_start_date;
    $params_diteruskan[] = $filter_start_date;
    $types_langsung .= "s";
    $types_diteruskan .= "s";
}
if (!empty($filter_end_date)) {
    $query_langsung .= " AND s.tanggal_surat <= ?";
    $query_diteruskan .= " AND s.tanggal_surat <= ?";
    $params_langsung[] = $filter_end_date;
    $params_diteruskan[] = $filter_end_date;
    $types_langsung .= "s";
    $types_diteruskan .= "s";
}
if ($filter_pengirim > 0) {
    $query_langsung .= " AND s.id_pengirim = ?";
    $query_diteruskan .= " AND sd.id_penerus = ?"; // Pengirim untuk surat diteruskan adalah si penerus
    $params_langsung[] = $filter_pengirim;
    $params_diteruskan[] = $filter_pengirim;
    $types_langsung .= "i";
    $types_diteruskan .= "i";
}

$final_query = "($query_langsung) UNION ($query_diteruskan) ORDER BY tanggal_dibuat DESC";

$stmt_final = $conn->prepare($final_query);
$stmt_final->bind_param($types_langsung . $types_diteruskan, ...array_merge($params_langsung, $params_diteruskan));
$stmt_final->execute();
$result = $stmt_final->get_result();
?>
<div class="content-header">
    <h1>Surat Masuk</h1>
    <p>Daftar surat yang diterima dari unit internal maupun yang diteruskan oleh fakultas.</p>
</div>
<div class="content-body">
    <div class="card">
        <div class="card-body">
             <!-- Filter Form -->
            <form method="GET" action="dashboard.php" class="filter-form">
                <input type="hidden" name="page" value="inbox_internal">
                <div class="filter-group">
                    <label for="start_date_inbox">Dari Tanggal</label>
                    <input type="date" name="start_date" id="start_date_inbox" value="<?php echo htmlspecialchars($filter_start_date); ?>">
                </div>
                <div class="filter-group">
                    <label for="end_date_inbox">Sampai Tanggal</label>
                    <input type="date" name="end_date" id="end_date_inbox" value="<?php echo htmlspecialchars($filter_end_date); ?>">
                </div>
                <div class="filter-group">
                    <label for="pengirim_inbox">Pengirim</label>
                    <select name="pengirim" id="pengirim_inbox">
                        <option value="">Semua Pengirim</option>
                        <?php foreach($users_list as $user): ?>
                        <option value="<?php echo $user['id']; ?>" <?php echo ($filter_pengirim == $user['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['nama_lengkap']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    <a href="dashboard.php?page=inbox_internal" class="btn btn-secondary btn-sm">Reset</a>
                    <a href="cetak_laporan.php?<?php echo $query_params; ?>" target="_blank" class="btn btn-success btn-sm"><i data-feather="printer"></i> Cetak</a>
                </div>
            </form>
            
            <div class="filter-container" style="border-top: 1px solid var(--border-color); margin-top: 1.5rem; padding-top: 1.5rem;">
                <input type="search" id="searchInboxInternal" class="search-box" placeholder="Cari dalam hasil filter...">
            </div>
            <div class="table-responsive">
                <table class="table" id="tableInboxInternal">
                    <thead>
                        <tr>
                            <th>No. Surat</th>
                            <th>Perihal</th>
                            <th>Pengirim</th>
                            <th>Tgl. Surat</th>
                            <th>Status</th>
                            <th>Aksi</th>
                            <th>Lampiran</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row["nomor_surat"]); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row["perihal"]); ?></strong>
                                    <p class="text-muted"><?php echo nl2br(substr(htmlspecialchars($row["isi_ringkas"]), 0, 100)); ?>...</p>
                                </td>
                                <td>
                                    <?php if ($row["tipe_masuk"] == "Diteruskan"): ?>
                                        <strong><?php echo htmlspecialchars($row["nama_penerus"]); ?></strong>
                                        <p class="text-muted" style="font-size: 0.8em;">(Meneruskan dari <?php echo htmlspecialchars($row["nama_pengirim_asli"]); ?>)</p>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($row["nama_pengirim"]); ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date("d M Y", strtotime($row["tanggal_surat"])); ?></td>
                                <td>
                                    <?php if ($row["tipe_masuk"] == "Diteruskan"): ?>
                                        <span class="badge status-diterima">Diteruskan</span>
                                    <?php else: ?>
                                        <span class="badge status-<?php echo $row["status"]; ?>"><?php echo ucfirst($row["status"]); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($row["status"] == "pending" && $row["tipe_masuk"] == "Langsung"): ?>
                                    <form method="POST" action="dashboard.php" style="display:inline-block;">
                                        <input type="hidden" name="id_surat" value="<?php echo $row["id_surat"]; ?>">
                                        <input type="hidden" name="status" value="diterima">
                                        <input type="hidden" name="catatan_status" value="Surat diterima dan akan diproses.">
                                        <button type="submit" name="aksi_surat" class="btn btn-sm btn-success">Terima</button>
                                    </form>
                                    <form method="POST" action="dashboard.php" style="display:inline-block;">
                                        <input type="hidden" name="id_surat" value="<?php echo $row["id_surat"]; ?>">
                                        <input type="hidden" name="status" value="ditolak">
                                        <input type="hidden" name="catatan_status" value="Surat ditolak karena..." onclick="this.value=prompt('Alasan penolakan:','') || ''">
                                        <button type="submit" name="aksi_surat" class="btn btn-sm btn-danger">Tolak</button>
                                    </form>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
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
                                <td colspan="7" class="text-center">Tidak ada surat masuk sesuai filter.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
