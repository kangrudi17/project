<?php
$external_contacts = $conn->query("SELECT id_kontak, nama_instansi FROM kontak_luar ORDER BY nama_instansi");
?>
<div class="content-header">
    <h1>Input Manual Surat Eksternal</h1>
    <p>Gunakan formulir ini untuk mengarsipkan surat fisik/email yang diterima dari pihak eksternal.</p>
</div>
<div class="content-body">
    <div class="card">
        <div class="card-body">
            <form action="dashboard.php" method="post" enctype="multipart/form-data" class="form-compose">
                <div class="form-row">
                    <div class="form-group">
                        <label for="nomor_surat">Nomor Surat (dari pengirim)</label>
                        <input type="text" id="nomor_surat" name="nomor_surat" required>
                    </div>
                    <div class="form-group">
                        <label for="tanggal_surat">Tanggal Surat</label>
                        <input type="date" id="tanggal_surat" name="tanggal_surat" required value="<?php echo date("Y-m-d"); ?>">
                    </div>
                </div>
                 <div class="form-group">
                    <label for="perihal">Perihal</label>
                    <input type="text" id="perihal" name="perihal" required>
                </div>
                 <div class="form-group">
                    <label for="id_pengirim_eksternal">Pengirim (Eksternal)</label>
                    <select id="id_pengirim_eksternal" name="id_pengirim_eksternal" required>
                        <option value="">-- Pilih Kontak Eksternal --</option>
                        <?php while($kontak = $external_contacts->fetch_assoc()): ?>
                            <option value="<?php echo $kontak["id_kontak"]; ?>"><?php echo htmlspecialchars($kontak["nama_instansi"]); ?></option>
                        <?php endwhile; ?>
                    </select>
                    <small class="text-muted">Jika pengirim belum ada, tambahkan terlebih dahulu di menu Kontak Eksternal.</small>
                </div>
                 <div class="form-group">
                    <label for="isi_ringkas">Isi Ringkas</label>
                    <textarea id="isi_ringkas" name="isi_ringkas" rows="4"></textarea>
                </div>
                 <div class="form-group">
                    <label for="file_surat">Pindai/Lampiran Surat (Opsional)</label>
                    <input type="file" id="file_surat" name="file_surat" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                </div>
                <div class="form-actions">
                    <button type="submit" name="input_manual_surat" class="btn btn-primary">Simpan ke Arsip</button>
                </div>
            </form>
        </div>
    </div>
</div>
