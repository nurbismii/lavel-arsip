<script>
document.addEventListener('DOMContentLoaded', function () {
    const stageMap = window.VOpsKnowledgeStages || {};

    document.querySelectorAll('[data-knowledge-form]').forEach(function (form) {
        const workflowSelect = form.querySelector('[data-knowledge-workflow-select]');
        const stageSelect = form.querySelector('[data-knowledge-stage-select]');
        const stageHelp = form.querySelector('[data-knowledge-stage-help]');

        if (!workflowSelect || !stageSelect) {
            return;
        }

        function renderStages() {
            const workflowId = workflowSelect.value;
            const selectedStage = stageSelect.dataset.selectedStage || '';
            const stages = workflowId && stageMap[workflowId] ? stageMap[workflowId] : [];

            stageSelect.innerHTML = '';

            const emptyOption = document.createElement('option');
            emptyOption.value = '';
            emptyOption.textContent = workflowId ? '-- Tanpa tahap khusus --' : '-- Pilih alur kerja terlebih dahulu --';
            stageSelect.appendChild(emptyOption);

            stages.forEach(function (stage) {
                const option = document.createElement('option');
                option.value = stage.id;
                option.textContent = stage.label;
                option.selected = String(stage.id) === String(selectedStage);
                stageSelect.appendChild(option);
            });

            stageSelect.disabled = !workflowId || stages.length === 0;

            if (stageHelp) {
                if (!workflowId) {
                    stageHelp.textContent = 'Tahap bersifat opsional. Pilih alur kerja jika SOP perlu dipetakan ke proses tertentu.';
                } else if (!stages.length) {
                    stageHelp.textContent = 'Alur kerja ini belum memiliki tahap. SOP tetap bisa disimpan pada level alur kerja.';
                } else {
                    stageHelp.textContent = 'Pilih tahap jika SOP hanya berlaku pada langkah tertentu.';
                }
            }
        }

        workflowSelect.addEventListener('change', function () {
            stageSelect.dataset.selectedStage = '';
            renderStages();
        });

        stageSelect.addEventListener('change', function () {
            stageSelect.dataset.selectedStage = stageSelect.value;
        });

        renderStages();
    });
});
</script>
