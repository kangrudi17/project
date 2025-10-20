<?php
$result = $conn->query("SELECT * FROM kontak_luar ORDER BY nama_instansi");
?>
<div class="content-header">
    <h1>Kelola Kontak Eksternal</h1>
    <p>Tambah dan lihat daftar kontak instansi luar.</p>
</div>
<div class="content-body">
    <div class="card">
         <div class="card-header">
            <h4>Tambah Kontak Baru</h4>
        </div>
        <div class="card-body">
             <form action="dashboard.php" method="post" class="form-compose">
                <div class="form-row">
                    <div class="form-group">
                        <label for="nama_instansi">Nama Instansi</label>
                        <input type="text" name="nama_instansi" required>
                    </div>
                     <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" name="email">
                    </div>
                </div>
                 <div class="form-row">
                     <div class="form-group">
                        <label for="alamat">Alamat</label>
                        <input type="text" name="alamat">
                    </div>
                     <div class="form-group">
                        <label for="telepon">Telepon</label>
                        <input type="text" name="telepon">
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" name="tambah_kontak" class="btn btn-primary">Simpan Kontak</button>
                </div>
            </form>
        </div>
    </div>
    <div class="card" style="margin-top: 2rem;">
        <div class="card-header">
            <h4>Daftar Kontak</h4>
        </div>
        <div class="card-body">
            <div class="filter-container">
                <input type="search" id="searchContacts" class="search-box" placeholder="Cari kontak...">
            </div>
            <div class="table-responsive">
                <table class="table" id="tableContacts">
                    <thead>
                        <tr>
                            <th>Nama Instansi</th>
                            <th>Email</th>
                            <th>Telepon</th>
                            <th>Alamat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row["nama_instansi"]); ?></td>
                                <td><?php echo htmlspecialchars($row["email"]); ?></td>
                                <td><?php echo htmlspecialchars($row["telepon"]); ?></td>
                                <td><?php echo htmlspecialchars($row["alamat"]); ?></td>
                                <td>
                                    <div class="table-actions">
                                        <a href="edit_contact.php?id=<?php echo $row['id_kontak']; ?>" class="btn btn-sm btn-secondary">Edit</a>
                                        <form method="POST" action="dashboard.php" onsubmit="return confirm('Anda yakin ingin menghapus kontak ini?');">
                                            <input type="hidden" name="id_kontak" value="<?php echo $row['id_kontak']; ?>">
                                            <button type="submit" name="hapus_kontak" class="btn btn-sm btn-danger">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                             <tr><td colspan="5" class="text-center">Belum ada kontak eksternal.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
