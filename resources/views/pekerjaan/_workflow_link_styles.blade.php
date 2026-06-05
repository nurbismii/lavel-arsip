<style>
    .workflow-link-card {
        border: 1px solid rgba(255, 255, 255, .72);
        border-radius: 16px;
        background: var(--neu-surface-soft);
        padding: 1rem;
        transition: border-color .18s ease, box-shadow .18s ease, background-color .18s ease;
        box-shadow: var(--neu-shadow-inset);
    }

    .workflow-link-card.is-active {
        border-color: #bfdbfe;
        background: var(--neu-surface);
        box-shadow: var(--neu-shadow-sm);
    }

    .workflow-link-card.is-inherited {
        border-color: #cbd5e1;
        background: var(--neu-surface-soft);
    }

    .workflow-link-header {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        align-items: flex-start;
    }

    .workflow-link-title {
        color: #0f172a;
        font-weight: 800;
        margin-bottom: .2rem;
    }

    .workflow-link-subtitle {
        color: #64748b;
        font-size: .875rem;
        margin-bottom: 0;
    }

    .workflow-link-fields {
        border-top: 1px solid rgba(148, 163, 184, .22);
        margin-top: 1rem;
        padding-top: 1rem;
    }

    .workflow-link-fields.is-hidden {
        display: none;
    }

    .workflow-switch {
        min-width: 116px;
        padding-left: 3.4rem;
        text-align: right;
    }

    .workflow-switch .form-check-input {
        width: 3rem;
        height: 1.5rem;
        margin-left: -3.4rem;
        cursor: pointer;
    }

    .workflow-switch .form-check-input:disabled {
        cursor: not-allowed;
    }

    .workflow-switch .form-check-label {
        color: #334155;
        font-weight: 700;
        cursor: pointer;
    }

    @media (max-width: 768px) {
        .workflow-link-header {
            flex-direction: column;
        }

        .workflow-switch {
            align-self: flex-start;
            text-align: left;
        }
    }
</style>
