@php
    $alurKerjaTahapOptions = $alurKerjas->mapWithKeys(function ($alurKerja) {
        return [
            (string) $alurKerja->id => $alurKerja->tahaps->map(function ($tahap) {
                return [
                    'id' => (string) $tahap->id,
                    'label' => 'Tahap ' . $tahap->urutan . ' - ' . $tahap->nama,
                ];
            })->values(),
        ];
    });
@endphp

<script>
    (function() {
        const form = document.querySelector('[data-pekerjaan-form]');

        if (!form) {
            return;
        }

        const tahapOptions = @json($alurKerjaTahapOptions);
        const parentSelect = form.querySelector('[data-parent-select]');
        const teamSelect = form.querySelector('[data-team-select]');
        const workflowToggle = form.querySelector('[data-workflow-toggle]');
        const workflowToggleLabel = form.querySelector('[data-workflow-toggle-label]');
        const workflowToggleHelp = form.querySelector('[data-workflow-toggle-help]');
        const workflowCard = form.querySelector('[data-workflow-card]');
        const workflowFields = form.querySelector('[data-workflow-fields]');
        const alurKerjaSelect = form.querySelector('[data-alur-kerja-select]');
        const tahapSelect = form.querySelector('[data-tahap-select]');
        const tahapHelp = form.querySelector('[data-tahap-help]');
        const initialAlurKerjaId = alurKerjaSelect ? alurKerjaSelect.value : '';
        const initialTahapId = tahapSelect ? tahapSelect.dataset.selectedTahap : '';

        function setSelectValue(select, value) {
            if (!select) {
                return;
            }

            select.value = value || '';

            if (value && select.value !== String(value)) {
                select.value = '';
            }
        }

        function appendOption(select, value, label, selected) {
            const option = document.createElement('option');
            option.value = value;
            option.textContent = label;
            option.selected = selected;
            select.appendChild(option);
        }

        function updateToggleCopy(isEnabled, message) {
            if (workflowToggleLabel) {
                workflowToggleLabel.textContent = isEnabled ? 'Aktif' : 'Nonaktif';
            }

            if (workflowToggleHelp) {
                workflowToggleHelp.textContent = message || (isEnabled
                    ? 'Pilih alur kerja dan tahapan proses jika dokumen perlu dipetakan.'
                    : 'Dokumen akan disimpan tanpa tautan alur kerja. Upload tetap bisa dilakukan.');
            }
        }

        function setWorkflowFieldsVisible(isVisible) {
            if (workflowFields) {
                workflowFields.classList.toggle('is-hidden', !isVisible);
            }

            if (workflowCard) {
                workflowCard.classList.toggle('is-active', isVisible);
            }
        }

        function setWorkflowEnabled(isEnabled, options) {
            options = options || {};

            if (workflowToggle) {
                workflowToggle.checked = isEnabled;
            }

            setWorkflowFieldsVisible(isEnabled);

            if (!isEnabled && options.clear) {
                setSelectValue(alurKerjaSelect, '');
                setSelectValue(tahapSelect, '');
            }

            if (alurKerjaSelect) {
                alurKerjaSelect.disabled = !isEnabled;
            }

            renderTahaps(isEnabled ? (tahapSelect ? tahapSelect.value : initialTahapId) : '');

            if (!isEnabled && tahapSelect) {
                tahapSelect.disabled = true;
            }

            updateToggleCopy(
                isEnabled,
                isEnabled
                    ? 'Pilihan alur kerja aktif. Pilih tahapan jika dokumen perlu masuk ke proses tertentu.'
                    : 'Dokumen akan disimpan tanpa tautan alur kerja. Upload tetap bisa dilakukan.'
            );
        }

        function renderTahaps(selectedTahapId) {
            if (!tahapSelect || !alurKerjaSelect) {
                return;
            }

            const alurKerjaId = alurKerjaSelect.value;
            const tahaps = tahapOptions[alurKerjaId] || [];

            tahapSelect.innerHTML = '';

            if (!alurKerjaId) {
                appendOption(tahapSelect, '', '-- Tidak dikaitkan ke tahapan --', true);
                tahapSelect.disabled = true;

                if (tahapHelp) {
                    tahapHelp.textContent = 'Boleh dikosongkan. Pilih alur kerja terlebih dahulu jika dokumen perlu dikaitkan ke tahapan proses.';
                }

                return;
            }

            if (!tahaps.length) {
                appendOption(tahapSelect, '', '-- Belum ada tahapan pada alur ini --', true);
                tahapSelect.disabled = true;

                if (tahapHelp) {
                    tahapHelp.textContent = 'Alur kerja ini belum memiliki tahapan proses. Dokumen tetap bisa disimpan.';
                }

                return;
            }

            appendOption(tahapSelect, '', '-- Tidak dikaitkan ke tahapan --', !selectedTahapId);

            tahaps.forEach(function(tahap) {
                appendOption(tahapSelect, tahap.id, tahap.label, String(selectedTahapId || '') === String(tahap.id));
            });

            tahapSelect.disabled = false;

            if (tahapHelp) {
                tahapHelp.textContent = 'Boleh dikosongkan. Pilih tahapan hanya jika dokumen sudah perlu dipetakan ke proses tertentu.';
            }
        }

        function applyParentInheritance() {
            if (!parentSelect) {
                renderTahaps(initialTahapId);
                return;
            }

            const selectedParent = parentSelect.options[parentSelect.selectedIndex];
            const hasParent = selectedParent && selectedParent.value !== '';
            const inheritedTeamId = hasParent ? selectedParent.dataset.teamId : '';
            const inheritedAlurKerjaId = hasParent ? selectedParent.dataset.alurKerjaId : '';
            const inheritedTahapId = hasParent ? selectedParent.dataset.alurKerjaTahapId : '';

            if (hasParent) {
                const hasInheritedWorkflow = inheritedAlurKerjaId !== '';

                setSelectValue(teamSelect, inheritedTeamId);
                setSelectValue(alurKerjaSelect, inheritedAlurKerjaId);
                renderTahaps(inheritedTahapId);
                setSelectValue(tahapSelect, inheritedTahapId);

                if (workflowToggle) {
                    workflowToggle.checked = hasInheritedWorkflow;
                    workflowToggle.disabled = true;
                }

                setWorkflowFieldsVisible(hasInheritedWorkflow);

                if (workflowCard) {
                    workflowCard.classList.add('is-inherited');
                    workflowCard.classList.toggle('is-active', hasInheritedWorkflow);
                }

                if (teamSelect) {
                    teamSelect.disabled = true;
                }

                if (alurKerjaSelect) {
                    alurKerjaSelect.disabled = true;
                }

                if (tahapSelect) {
                    tahapSelect.disabled = true;
                }

                if (tahapHelp) {
                    tahapHelp.textContent = hasInheritedWorkflow
                        ? 'Tahapan proses mengikuti dokumen induk yang dipilih.'
                        : 'Dokumen induk belum dikaitkan ke tahapan proses.';
                }

                updateToggleCopy(
                    hasInheritedWorkflow,
                    hasInheritedWorkflow
                        ? 'Mengikuti dokumen induk. Pilihan alur kerja dan tahapan dikunci agar tetap konsisten.'
                        : 'Mengikuti dokumen induk. Dokumen induk belum dikaitkan ke alur kerja.'
                );

                return;
            }

            if (workflowCard) {
                workflowCard.classList.remove('is-inherited');
            }

            if (workflowToggle) {
                workflowToggle.disabled = false;
            }

            if (teamSelect) {
                teamSelect.disabled = false;
            }

            const shouldEnableWorkflow = workflowToggle
                ? workflowToggle.checked
                : Boolean(initialAlurKerjaId || initialTahapId);

            setWorkflowEnabled(shouldEnableWorkflow, { clear: !shouldEnableWorkflow });
        }

        if (parentSelect) {
            parentSelect.addEventListener('change', applyParentInheritance);
        }

        if (alurKerjaSelect) {
            alurKerjaSelect.addEventListener('change', function() {
                renderTahaps('');
            });
        }

        if (workflowToggle) {
            workflowToggle.addEventListener('change', function() {
                setWorkflowEnabled(workflowToggle.checked, {
                    clear: !workflowToggle.checked,
                });
            });
        }

        form.addEventListener('submit', function() {
            [teamSelect, alurKerjaSelect, tahapSelect].forEach(function(select) {
                if (select) {
                    select.disabled = false;
                }
            });
        });

        applyParentInheritance();
    })();
</script>
