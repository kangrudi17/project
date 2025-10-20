<?php
// Get list of internal users
$internal_users = $conn->query("SELECT id, nama_lengkap, role FROM users WHERE id != ".$_SESSION["user_id"]);
// Get list of external contacts
$external_contacts = $conn->query("SELECT id_kontak, nama_instansi FROM kontak_luar");
?>
<div class="content-header">
    <h1>Buat Surat Baru</h1>
    <p>Isi formulir di bawah ini untuk mengirim surat.</p>
</div>
<div class="content-body">
    <div class="card">
        <div class="card-body">
            <form action="dashboard.php" method="post" enctype="multipart/form-data" class="form-compose">
                <div class="form-row">
                    <div class="form-group">
                        <label for="nomor_surat">Nomor Surat</label>
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
                    <label for="tujuan">Tujuan Surat</label>
                    <select id="tujuan" name="tujuan" required>
                        <option value="">-- Pilih Tujuan --</option>
                        <optgroup label="Internal">
                            <?php while($user = $internal_users->fetch_assoc()): ?>
                                <option value="internal-<?php echo $user["id"]; ?>"><?php echo $user["nama_lengkap"]; ?></option>
                            <?php endwhile; ?>
                        </optgroup>
                         <?php if ($_SESSION["role"] == "fakultas" || $_SESSION["role"] == "keuangan"): ?>
                        <optgroup label="Eksternal">
                            <?php while($kontak = $external_contacts->fetch_assoc()): ?>
                                <option value="eksternal-<?php echo $kontak["id_kontak"]; ?>"><?php echo $kontak["nama_instansi"]; ?></option>
                            <?php endwhile; ?>
                        </optgroup>
                        <?php endif; ?>
                    </select>
                </div>
                 <div class="form-group">
                    <label for="isi_ringkas">Isi Ringkas</label>
                    <textarea id="isi_ringkas" name="isi_ringkas" rows="4"></textarea>
                </div>
                 <div class="form-group">
                    <label for="file_surat">Lampiran (Opsional, PDF/DOCX/JPG)</label>
                    <input type="file" id="file_surat" name="file_surat" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                </div>
                <div class="form-actions">
                    <button type="submit" name="tambah_surat" class="btn btn-primary">Kirim Surat</button>
                    <button type="reset" class="btn btn-secondary">Reset</button>
                </div>
            </form>
        </div>
    </div>
</div>
