<?php
$user_id = $_SESSION["user_id"];

// Ambil daftar prodi untuk form forward
$prodi_users_res = $conn->query("SELECT id, nama_lengkap FROM users WHERE role LIKE 'prodi_%'");
$prodi_users = [];
while($prodi = $prodi_users_res->fetch_assoc()){
    $prodi_users[] = $prodi;
}

// Ambil daftar pengirim eksternal untuk filter
$kontak_res = $conn->query("SELECT id_kontak, nama_instansi FROM kontak_luar ORDER BY nama_instansi");
$kontak_list = [];
while($kontak = $kontak_res->fetch_assoc()){
    $kontak_list[] = $kontak;
}

// Logika filter server-side
$filter_start_date = isset($_GET['start_date']) ? sanitize_input($_GET['start_date']) : '';
$filter_end_date = isset($_GET['end_date']) ? sanitize_input($_GET['end_date']) : '';
$filter_pengirim = isset($_GET['pengirim']) ? intval($_GET['pengirim']) : 0;
$query_params = "laporan=inbox_eksternal&start_date=$filter_start_date&end_date=$filter_end_date&pengirim=$filter_pengirim";


$where_clauses = ["s.tujuan = ?"];
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
if ($filter_pengirim > 0) {
    $where_clauses[] = "s.id_pengirim_eksternal = ?";
    $bind_params_types .= "i";
    $bind_params_values[] = $filter_pengirim;
}

$where_sql = implode(" AND ", $where_clauses);

$query = "SELECT 
            s.*, 
            k.nama_instansi as nama_pengirim, 
            GROUP_CONCAT(u.nama_lengkap SEPARATOR ', ') as diteruskan_kepada
          FROM surat s 
          JOIN kontak_luar k ON s.id_pengirim_eksternal = k.id_kontak 
          LEFT JOIN surat_diteruskan sd ON s.id_surat = sd.id_surat
          LEFT JOIN users u ON sd.id_penerima = u.id
          WHERE $where_sql
          GROUP BY s.id_surat
          ORDER BY s.tanggal_dibuat DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param($bind_params_types, ...$bind_params_values);
$stmt->execute();
$result = $stmt->get_result();
?>
<div class="content-header">
    <h1>Arsip Surat Masuk Eksternal</h1>
    <p>Daftar surat dari pihak eksternal yang telah diinput dan diarsipkan secara manual.</p>
</div>
<div class="content-body">
    <div class="card">
        <div class="card-body">
            <!-- Filter Form -->
            <form method="GET" action="dashboard.php" class="filter-form">
                <input type="hidden" name="page" value="inbox_eksternal">
                <div class="filter-group">
                    <label for="start_date_eksternal">Dari Tanggal</label>
                    <input type="date" name="start_date" id="start_date_eksternal" value="<?php echo htmlspecialchars($filter_start_date); ?>">
                </div>
                <div class="filter-group">
                    <label for="end_date_eksternal">Sampai Tanggal</label>
                    <input type="date" name="end_date" id="end_date_eksternal" value="<?php echo htmlspecialchars($filter_end_date); ?>">
                </div>
                <div class="filter-group">
                    <label for="pengirim_eksternal">Pengirim</label>
                    <select name="pengirim" id="pengirim_eksternal">
                        <option value="">Semua Pengirim</option>
                        <?php foreach($kontak_list as $kontak): ?>
                        <option value="<?php echo $kontak['id_kontak']; ?>" <?php echo ($filter_pengirim == $kontak['id_kontak']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($kontak['nama_instansi']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    <a href="dashboard.php?page=inbox_eksternal" class="btn btn-secondary btn-sm">Reset</a>
                     <a href="cetak_laporan.php?<?php echo $query_params; ?>" target="_blank" class="btn btn-success btn-sm"><i data-feather="printer"></i> Cetak</a>
                </div>
            </form>
            
            <div class="filter-container" style="border-top: 1px solid var(--border-color); margin-top: 1.5rem; padding-top: 1.5rem;">
                <input type="search" id="searchInboxEksternal" class="search-box" placeholder="Cari dalam hasil filter...">
            </div>
            
            <div class="table-responsive">
                <table class="table" id="tableInboxEksternal">
                    <thead>
                        <tr>
                            <th>No. Surat</th>
                            <th>Perihal</th>
                            <th>Pengirim Eksternal</th>
                            <th>Tgl. Surat</th>
                            <th>Lampiran</th>
                            <th>Aksi / Status Penerusan</th>
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
                                <td><?php echo htmlspecialchars($row["nama_pengirim"]); ?></td>
                                <td><?php echo date("d M Y", strtotime($row["tanggal_surat"])); ?></td>
                                <td>
                                    <?php if (!empty($row["file_path"])): ?>
                                        <a href="<?php echo htmlspecialchars($row["file_path"]); ?>" download class="btn btn-sm btn-secondary">
                                            <i data-feather="download"></i> Unduh
                                        </a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                     <?php if (empty($row["diteruskan_kepada"])): ?>
                                        <form method="POST" action="dashboard.php">
                                            <input type="hidden" name="id_surat" value="<?php echo $row["id_surat"]; ?>">
                                            <div class="forward-checkbox-group">
                                                <?php foreach ($prodi_users as $prodi): ?>
                                                    <label class="forward-checkbox-label">
                                                        <input type="checkbox" name="tujuan_penerusan[]" value="<?php echo $prodi['id']; ?>">
                                                        <span><?php echo htmlspecialchars($prodi['nama_lengkap']); ?></span>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                            <button type="submit" name="forward_surat" class="btn btn-sm btn-primary" style="margin-top: 0.5rem;">
                                                <i data-feather="send" style="width:14px; height: 14px;"></i> Teruskan
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <div class="forward-info">
                                            <i data-feather="check-circle"></i>
                                            <span>Telah diteruskan ke:<br><strong><?php echo htmlspecialchars($row["diteruskan_kepada"]); ?></strong></span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">Data tidak ditemukan sesuai filter.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
