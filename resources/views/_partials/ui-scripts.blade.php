<script>
document.addEventListener('DOMContentLoaded', function () {
    function setButtonLoading(button, text) {
        if (!button || button.disabled) {
            return;
        }

        button.dataset.originalText = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>' + (text || 'Memproses...');
    }

    document.addEventListener('submit', function (event) {
        const form = event.target;

        if (!form.matches('[data-loading-form]')) {
            return;
        }

        const confirmTitle = form.dataset.confirmTitle;

        if (confirmTitle && form.dataset.confirmed !== 'true') {
            event.preventDefault();

            Swal.fire({
                title: confirmTitle,
                text: form.dataset.confirmText || 'Apakah Anda yakin ingin melanjutkan proses ini?',
                icon: form.dataset.confirmIcon || 'question',
                showCancelButton: true,
                confirmButtonText: form.dataset.confirmButton || 'Ya, lanjutkan',
                cancelButtonText: form.dataset.cancelButton || 'Batal',
                reverseButtons: true
            }).then(function (result) {
                if (!result.isConfirmed) {
                    return;
                }

                form.dataset.confirmed = 'true';
                const submitter = form.querySelector('[type="submit"]');
                setButtonLoading(submitter, submitter ? submitter.dataset.loadingText : null);
                form.submit();
            });

            return;
        }

        const submitter = form.querySelector('[type="submit"]');
        setButtonLoading(submitter, submitter ? submitter.dataset.loadingText : null);
    });

    if (window.appFlash && window.appFlash.success) {
        Swal.fire({
            icon: 'success',
            title: 'Berhasil',
            text: window.appFlash.success,
            timer: 1800,
            showConfirmButton: false
        });
    }

    if (window.appFlash && window.appFlash.error) {
        Swal.fire({
            icon: 'error',
            title: 'Gagal',
            text: window.appFlash.error
        });
    }
});
</script>
