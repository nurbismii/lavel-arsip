<script>
document.addEventListener('DOMContentLoaded', function () {
    function formatSize(bytes) {
        if (!bytes) {
            return '-';
        }

        const units = ['B', 'KB', 'MB', 'GB'];
        let size = bytes;
        let index = 0;

        while (size >= 1024 && index < units.length - 1) {
            size = size / 1024;
            index++;
        }

        return `${index === 0 ? size : size.toFixed(2)} ${units[index]}`;
    }

    function setFiles(input, files) {
        const transfer = new DataTransfer();

        files.forEach(function (file) {
            transfer.items.add(file);
        });

        input.files = transfer.files;
    }

    function renderSelectedFiles(input) {
        const wrapper = input.closest('[data-file-picker]');
        const list = wrapper ? wrapper.querySelector('[data-selected-file-list]') : null;

        if (!list) {
            return;
        }

        const files = Array.from(input.files || []);
        list.innerHTML = '';

        if (!files.length) {
            list.classList.add('d-none');
            list.classList.remove('selected-file-list');
            return;
        }

        list.classList.remove('d-none');
        list.classList.add('selected-file-list');
        files.forEach(function (file, index) {
            const item = document.createElement('div');
            item.className = 'selected-file-item d-flex justify-content-between align-items-center gap-2 mb-1';

            const name = document.createElement('span');
            name.className = 'text-truncate';
            name.appendChild(document.createTextNode(file.name + ' '));

            const size = document.createElement('small');
            size.className = 'text-muted';
            size.textContent = '(' + formatSize(file.size) + ')';
            name.appendChild(size);

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'btn btn-sm btn-outline-danger';
            remove.dataset.removeSelectedFile = index;
            remove.textContent = 'Hapus';

            item.appendChild(name);
            item.appendChild(remove);
            list.appendChild(item);
        });
    }

    document.addEventListener('change', function (event) {
        if (!event.target.matches('[data-file-input-preview]')) {
            return;
        }

        renderSelectedFiles(event.target);
    });

    document.addEventListener('click', function (event) {
        const button = event.target.closest('[data-remove-selected-file]');

        if (!button) {
            return;
        }

        const wrapper = button.closest('[data-file-picker]');
        const input = wrapper ? wrapper.querySelector('[data-file-input-preview]') : null;

        if (!input) {
            return;
        }

        const removeIndex = parseInt(button.dataset.removeSelectedFile, 10);
        const files = Array.from(input.files || []).filter(function (_file, index) {
            return index !== removeIndex;
        });

        setFiles(input, files);
        renderSelectedFiles(input);
    });
});
</script>
