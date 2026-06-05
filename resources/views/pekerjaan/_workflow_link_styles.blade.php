<style>
    .workflow-link-card {
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        background: #f8fafc;
        padding: 1rem;
        transition: border-color .18s ease, box-shadow .18s ease, background-color .18s ease;
    }

    .workflow-link-card.is-active {
        border-color: #bfdbfe;
        background: #ffffff;
        box-shadow: 0 12px 30px rgba(37, 99, 235, .08);
    }

    .workflow-link-card.is-inherited {
        border-color: #cbd5e1;
        background: #f8fafc;
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
        border-top: 1px solid #e2e8f0;
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
