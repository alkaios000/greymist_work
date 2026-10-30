<?php
/**
 * File: views/layouts/modal_konfirmasi.php
 * Fungsi: Menyediakan dialog modal konfirmasi dan modal ubah nama proyek yang dapat digunakan ulang.
 */
$current_route_name = $active_page ?? 'projects';
?>
<!-- Modal Ubah Nama Proyek -->
<div class="modal-overlay" id="renameProjectModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fas fa-pen" style="color: var(--btn-blue); margin-right: 6px;"></i> Ubah Nama Proyek</h3>
            <button type="button" class="modal-close" data-modal-close="renameProjectModal" aria-label="Tutup">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" action="<?= e(action_url('rename_project')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="project_id" id="renameProjectId" value="">
            <input type="hidden" name="return_page" value="<?= e($current_route_name) ?>">
            <div class="modal-body">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" for="renameProjectName">Nama Proyek Baru</label>
                    <input type="text" class="form-control" name="project_name" id="renameProjectName" required maxlength="100" placeholder="Masukkan nama proyek...">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-modal-close="renameProjectModal">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Konfirmasi Aksi Global -->
<div class="modal-overlay" id="globalConfirmModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="modal-title" id="globalConfirmTitle">Konfirmasi Tindakan</h3>
            <button type="button" class="modal-close" data-modal-close="globalConfirmModal" aria-label="Tutup">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" id="globalConfirmForm" action="">
            <?= csrf_field() ?>
            <input type="hidden" name="project_id" id="globalConfirmProjectId" value="">
            <input type="hidden" name="return_page" value="<?= e($current_route_name) ?>">
            <div class="modal-body">
                <p id="globalConfirmMessage">Apakah Anda yakin ingin melanjutkan tindakan ini?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-modal-close="globalConfirmModal">Batal</button>
                <button type="submit" class="btn btn-danger btn-sm" id="globalConfirmSubmitBtn">Lanjutkan</button>
            </div>
        </form>
    </div>
</div>

