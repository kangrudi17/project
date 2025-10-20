<?php
$user_id = $_SESSION["user_id"];
$query = "SELECT s.*, u.nama_lengkap as nama_pengirim FROM surat s JOIN users u ON s.id_pengirim = u.id WHERE s.tujuan = ? AND s.tipe_tujuan = 'internal' ORDER BY s.tanggal_dibuat DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<div class="content-header">
    <h1>Surat Masuk</h1>
    <p>Daftar surat yang ditujukan kepada Anda.</p>
</div>
<div class="content-body">
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
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
                                <td><?php echo htmlspecialchars($row["nama_pengirim"]); ?></td>
                                <td><?php echo date("d M Y", strtotime($row["tanggal_surat"])); ?></td>
                                <td><span class="badge status-<?php echo $row["status"]; ?>"><?php echo ucfirst($row["status"]); ?></span></td>
                                <td>
                                    <?php if ($row["status"] == "pending"): ?>
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
                                <td colspan="7" class="text-center">Tidak ada surat masuk.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
