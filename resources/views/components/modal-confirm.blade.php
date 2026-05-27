{{-- Confirm Delete Modal --}}
<div class="modal-overlay" id="modal-confirm-delete">
    <div class="modal-box">
        <h3>
            <i class="bi bi-exclamation-triangle-fill text-danger" style="margin-right:6px"></i>
            Konfirmasi Hapus
        </h3>
        <p>Apakah Anda yakin ingin menghapus <strong id="delete-item-name">item ini</strong>? Data yang dihapus tidak dapat dikembalikan.</p>
        <div class="modal-actions">
            <button class="btn btn-outline" type="button" onclick="closeModal('modal-confirm-delete')">Batal</button>
            <button class="btn btn-danger" type="button" id="btn-confirm-delete">Hapus</button>
        </div>
    </div>
</div>
