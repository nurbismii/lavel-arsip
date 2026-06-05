<script>
document.addEventListener('DOMContentLoaded', function () {
    (function initInteractiveBackground() {
        const background = document.querySelector('.app-bg-curve');

        if (!background || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }

        const root = document.documentElement;
        let frame = null;
        let targetX = 0;
        let targetY = 0;
        let targetScrollY = 0;
        let currentX = 0;
        let currentY = 0;
        let currentScrollY = 0;

        function render() {
            currentX += (targetX - currentX) * 0.14;
            currentY += (targetY - currentY) * 0.14;
            currentScrollY += (targetScrollY - currentScrollY) * 0.14;

            root.style.setProperty('--bg-curve-x', currentX.toFixed(2) + 'px');
            root.style.setProperty('--bg-curve-y', currentY.toFixed(2) + 'px');
            root.style.setProperty('--bg-curve-x-reverse', (-currentX).toFixed(2) + 'px');
            root.style.setProperty('--bg-curve-y-reverse', (-currentY).toFixed(2) + 'px');
            root.style.setProperty('--bg-curve-scroll-y', currentScrollY.toFixed(2) + 'px');

            if (
                Math.abs(targetX - currentX) > 0.1
                || Math.abs(targetY - currentY) > 0.1
                || Math.abs(targetScrollY - currentScrollY) > 0.1
            ) {
                frame = requestAnimationFrame(render);
                return;
            }

            frame = null;
        }

        function requestRender() {
            if (!frame) {
                frame = requestAnimationFrame(render);
            }
        }

        function updateScrollOffset() {
            targetScrollY = Math.min(window.scrollY, 640) * -0.05;
            requestRender();
        }

        window.addEventListener('pointermove', function (event) {
            if (event.pointerType === 'touch') {
                return;
            }

            targetX = ((event.clientX / window.innerWidth) - 0.5) * 34;
            targetY = ((event.clientY / window.innerHeight) - 0.5) * 24;
            requestRender();
        }, { passive: true });

        window.addEventListener('scroll', updateScrollOffset, { passive: true });
        updateScrollOffset();
    })();

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
