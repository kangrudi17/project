
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
