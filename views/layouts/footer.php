<?php
/**
 * File: views/layouts/footer.php
 * Fungsi: Menutup kontainer layout utama, menyertakan modal konfirmasi global, dan memuat file JS modular.
 */
?>
        </main>
    </div>
</div>

<?php require ROOT_PATH . '/views/layouts/modal_konfirmasi.php'; ?>

<script src="<?= e(asset_url('js/main.js')) ?>"></script>
<script src="<?= e(asset_url('js/components/modal.js')) ?>"></script>
<script src="<?= e(asset_url('js/components/notification.js')) ?>"></script>
<script src="<?= e(asset_url('js/modules/dashboard.js')) ?>"></script>
<script src="<?= e(asset_url('js/modules/admin.js')) ?>"></script>
</body>
</html>

