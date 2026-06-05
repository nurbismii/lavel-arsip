<style>
    :root {
        --app-primary: #2563eb;
        --app-primary-soft: #eef4ff;
        --app-success: #16a34a;
        --app-warning: #f59e0b;
        --app-danger: #dc2626;
        --app-info: #0284c7;
        --app-dark: #0f172a;
        --app-muted: #64748b;
        --app-border: #e2e8f0;
        --app-bg: #f8fafc;
        --app-card: #ffffff;
        --app-radius: 16px;
        --app-shadow: 0 12px 30px rgba(15, 23, 42, .06);
    }

    body {
        background: var(--app-bg);
        color: var(--app-dark);
    }

    .brand-logo {
        display: inline-flex;
        align-items: center;
        gap: .75rem;
        text-decoration: none;
    }

    .brand-logo--center {
        flex-direction: column;
        gap: .65rem;
        text-align: center;
    }

    .brand-logo-mark {
        width: var(--brand-logo-size, 46px);
        height: var(--brand-logo-size, 46px);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        border-radius: 16px;
        background:
            radial-gradient(circle at 72% 18%, rgba(34, 197, 94, .32), transparent 28%),
            linear-gradient(135deg, #1d4ed8 0%, #2563eb 58%, #0f766e 100%);
        color: #fff;
        box-shadow: 0 14px 30px rgba(37, 99, 235, .24);
        overflow: hidden;
    }

    .brand-logo-mark--sm {
        --brand-logo-size: 38px;
        border-radius: 13px;
    }

    .brand-logo-mark--md {
        --brand-logo-size: 46px;
    }

    .brand-logo-mark--lg {
        --brand-logo-size: 66px;
        border-radius: 20px;
    }

    .brand-logo-svg {
        width: 100%;
        height: 100%;
        display: block;
    }

    .brand-logo-gear {
        transform-box: view-box;
        animation: brand-gear-spin 8s linear infinite;
    }

    .brand-logo-gear--main {
        transform-origin: 30px 37px;
    }

    .brand-logo-gear--upper {
        transform-origin: 50px 21px;
        animation-duration: 5.8s;
        animation-direction: reverse;
    }

    .brand-logo-gear--lower {
        transform-origin: 50px 50px;
        animation-duration: 9.5s;
    }

    .brand-logo-v {
        filter: drop-shadow(0 2px 2px rgba(15, 23, 42, .18));
    }

    .brand-logo-copy {
        display: flex;
        flex-direction: column;
        line-height: 1.08;
    }

    .brand-logo-title {
        color: var(--app-dark);
        font-size: 1.15rem;
        font-weight: 800;
        letter-spacing: 0;
    }

    .brand-logo-subtitle {
        margin-top: .18rem;
        color: var(--app-muted);
        font-size: .82rem;
        font-weight: 500;
        line-height: 1.35;
    }

    @keyframes brand-gear-spin {
        to {
            transform: rotate(360deg);
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .brand-logo-gear {
            animation: none;
        }
    }

    .app-page-header {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        align-items: flex-start;
        margin-bottom: 1rem;
    }

    .app-page-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        font-size: .75rem;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
        color: var(--app-primary);
        background: var(--app-primary-soft);
        padding: .35rem .65rem;
        border-radius: 999px;
        margin-bottom: .5rem;
    }

    .app-page-title {
        font-weight: 800;
        color: var(--app-dark);
        margin-bottom: .25rem;
    }

    .app-page-subtitle {
        color: var(--app-muted);
        margin-bottom: 0;
    }

    .app-page-actions {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
    }

    .app-card {
        background: var(--app-card);
        border: 1px solid var(--app-border);
        border-radius: var(--app-radius);
        box-shadow: var(--app-shadow);
    }

    .app-card-hover {
        transition: transform .18s ease, box-shadow .18s ease;
    }

    .app-card-hover:hover {
        transform: translateY(-2px);
        box-shadow: 0 14px 32px rgba(15, 23, 42, .08);
    }

    .filter-panel {
        background: var(--app-card);
        border: 1px solid var(--app-border);
        border-radius: var(--app-radius);
        padding: 1rem;
        margin-bottom: 1rem;
        box-shadow: var(--app-shadow);
    }

    .empty-state {
        text-align: center;
        padding: 42px 24px;
        border: 1px dashed #d8dee9;
        border-radius: var(--app-radius);
        background: #f8fafc;
    }

    .empty-state-icon {
        width: 56px;
        height: 56px;
        margin: 0 auto 16px;
        display: grid;
        place-items: center;
        border-radius: 16px;
        background: var(--app-primary-soft);
        color: var(--app-primary);
        font-size: 24px;
        font-weight: 800;
    }

    .empty-state h5 {
        font-weight: 700;
        margin-bottom: 6px;
    }

    .empty-state p {
        color: var(--app-muted);
        margin-bottom: 0;
    }

    .workflow-step-card {
        border: 1px solid var(--app-border);
        border-radius: var(--app-radius);
        background: #fff;
        padding: 1rem;
    }

    .workflow-optional-section {
        position: relative;
        border: 1px solid var(--app-border);
        border-radius: 12px;
        background: #f8fafc;
        padding: .85rem;
        transition: border-color .18s ease, background .18s ease;
    }

    .workflow-optional-section.is-on {
        border-color: #bfdbfe;
        background: #fff;
    }

    .workflow-toggle {
        border: 1px solid var(--app-border);
        border-radius: 999px;
        background: #fff;
        color: var(--app-muted);
        min-width: 76px;
        height: 34px;
        padding: 0 .55rem;
        display: inline-flex;
        align-items: center;
        justify-content: space-between;
        gap: .45rem;
        font-size: .75rem;
        font-weight: 800;
        line-height: 1;
        transition: border-color .18s ease, background .18s ease, color .18s ease;
    }

    .workflow-toggle-dot {
        width: 16px;
        height: 16px;
        border-radius: 999px;
        background: #cbd5e1;
        transition: background .18s ease, transform .18s ease;
    }

    .workflow-toggle.is-on {
        border-color: var(--app-primary);
        background: var(--app-primary);
        color: #fff;
    }

    .workflow-toggle.is-on .workflow-toggle-dot {
        background: #fff;
        transform: translateX(4px);
    }

    .workflow-floating-actions {
        position: sticky;
        right: 1rem;
        bottom: 1rem;
        z-index: 8;
        justify-content: flex-end;
        pointer-events: none;
        margin-top: .75rem;
    }

    .workflow-floating-add {
        border-radius: 999px;
        box-shadow: 0 14px 28px rgba(37, 99, 235, .2);
        pointer-events: auto;
        font-weight: 700;
    }

    .workflow-floating-add:disabled {
        border-color: #cbd5e1;
        background: #e2e8f0;
        color: #64748b;
        box-shadow: none;
        cursor: not-allowed;
        opacity: .75;
    }

    .workflow-optional-section.is-off .workflow-floating-actions {
        display: none !important;
    }

    .selected-file-list .selected-file-item {
        border: 1px solid var(--app-border);
        border-radius: 10px;
        background: #fff;
        padding: .45rem .55rem;
    }

    .draggable-structured-row,
    .draggable-tahap-row,
    .pic-row {
        cursor: grab;
        transition: border-color .18s ease, box-shadow .18s ease, opacity .18s ease;
    }

    .draggable-structured-row.is-dragging,
    .draggable-tahap-row.is-dragging,
    .pic-row.is-dragging {
        cursor: grabbing;
        opacity: .62;
        border-color: var(--app-primary);
        box-shadow: 0 12px 26px rgba(37, 99, 235, .18);
    }

    .structured-drag-handle,
    .tahap-drag-handle,
    .pic-drag-handle {
        cursor: grab;
        font-weight: 700;
        color: var(--app-muted);
    }

    .form-label .required-mark {
        color: var(--app-danger);
    }

    @media (max-width: 768px) {
        .app-page-header {
            flex-direction: column;
        }

        .app-page-actions {
            width: 100%;
            display: grid;
        }

        .app-page-actions .btn,
        .filter-panel .btn {
            width: 100%;
        }
    }

    @media (min-width: 992px) {
        .workflow-optional-section.is-on {
            padding-bottom: 3.25rem;
        }

        .workflow-optional-section [data-optional-body] > .d-flex.justify-content-end.mb-2,
        .workflow-optional-section [data-optional-body] > .d-flex.justify-content-end.mt-2 {
            display: none !important;
        }
    }
</style>
