<?php
$result = $conn->query("SELECT * FROM users ORDER BY nama_lengkap");
?>
<div class="content-header">
    <h1>Kelola Pengguna</h1>
    <p>Tambah dan lihat daftar pengguna sistem.</p>
</div>
<div class="content-body">
    <div class="card">
         <div class="card-header">
            <h4>Tambah Pengguna Baru</h4>
        </div>
        <div class="card-body">
             <form action="dashboard.php" method="post" class="form-compose">
                <div class="form-row">
                    <div class="form-group">
                        <label for="nama_lengkap">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" required>
                    </div>
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" name="username" required>
                    </div>
                </div>
                 <div class="form-row">
                     <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" name="password" required>
                    </div>
                     <div class="form-group">
                        <label for="role">Peran (Role)</label>
                        <select name="role" required>
                            <option value="">-- Pilih Peran --</option>
                            <option value="fakultas">Fakultas</option>
                            <option value="prodi_ti">Prodi TI</option>
                            <option value="prodi_sipil">Prodi Sipil</option>
                            <option value="keuangan">Keuangan</option>
                        </select>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" name="tambah_user" class="btn btn-primary">Simpan Pengguna</button>
                </div>
            </form>
        </div>
    </div>
    <div class="card" style="margin-top: 2rem;">
        <div class="card-header">
            <h4>Daftar Pengguna</h4>
        </div>
        <div class="card-body">
            <div class="filter-container">
                <input type="search" id="searchUsers" class="search-box" placeholder="Cari pengguna...">
            </div>
            <div class="table-responsive">
                <table class="table" id="tableUsers">
                    <thead>
                        <tr>
                            <th>Nama Lengkap</th>
                            <th>Username</th>
                            <th>Peran</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row["nama_lengkap"]); ?></td>
                                <td><?php echo htmlspecialchars($row["username"]); ?></td>
                                <td><span class="user-role-badge"><?php echo str_replace('_', ' ', $row["role"]); ?></span></td>
                                <td>
                                    <?php if ($row['id'] != $_SESSION['user_id']): ?>
                                    <div class="table-actions">
                                        <form method="POST" action="dashboard.php" onsubmit="return confirm('Anda yakin ingin menghapus pengguna ini?');">
                                            <input type="hidden" name="id_user" value="<?php echo $row['id']; ?>">
                                            <button type="submit" name="hapus_user" class="btn btn-sm btn-danger">Hapus</button>
                                        </form>
                                    </div>
                                    <?php else: ?>
                                    -
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                             <tr><td colspan="4" class="text-center">Belum ada pengguna lain.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
