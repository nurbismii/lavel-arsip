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
        --app-border: #d8e3ee;
        --app-bg: #edf3f8;
        --app-card: #f4f8fb;
        --app-radius: 16px;
        --app-shadow: 12px 12px 28px rgba(148, 163, 184, .26), -12px -12px 28px rgba(255, 255, 255, .9);
        --neu-surface: #f4f8fb;
        --neu-surface-soft: #eef4f8;
        --neu-surface-strong: #fbfdff;
        --neu-shadow: 12px 12px 28px rgba(148, 163, 184, .26), -12px -12px 28px rgba(255, 255, 255, .9);
        --neu-shadow-sm: 6px 6px 14px rgba(148, 163, 184, .22), -6px -6px 14px rgba(255, 255, 255, .86);
        --neu-shadow-lg: 18px 18px 42px rgba(148, 163, 184, .28), -18px -18px 42px rgba(255, 255, 255, .92);
        --neu-shadow-inset: inset 6px 6px 12px rgba(148, 163, 184, .22), inset -6px -6px 12px rgba(255, 255, 255, .9);
        --neu-shadow-pressed: inset 5px 5px 11px rgba(30, 41, 59, .16), inset -5px -5px 11px rgba(255, 255, 255, .9);
        --bg-curve-x: 0px;
        --bg-curve-y: 0px;
        --bg-curve-x-reverse: 0px;
        --bg-curve-y-reverse: 0px;
        --bg-curve-scroll-y: 0px;
    }

    html {
        min-height: 100%;
    }

    body {
        background: var(--app-bg);
        color: var(--app-dark);
    }

    body.app-shell {
        min-height: 100vh;
        overflow-x: hidden;
        background:
            linear-gradient(180deg, rgba(237, 243, 248, .98) 0%, rgba(230, 240, 246, .92) 42%, rgba(239, 245, 249, .98) 100%),
            var(--app-bg);
    }

    body.app-shell #app,
    body.app-shell .app-guest-main {
        position: relative;
        z-index: 1;
        min-height: 100vh;
    }

    body.app-shell main {
        position: relative;
        z-index: 1;
    }

    body.app-shell .navbar {
        position: relative;
        z-index: 1050;
    }

    body.app-shell .navbar .dropdown-menu {
        z-index: 1060;
    }

    .app-bg-curve {
        position: fixed;
        inset: 0;
        z-index: 0;
        pointer-events: none;
        overflow: hidden;
        background:
            linear-gradient(135deg, rgba(37, 99, 235, .08), transparent 34%),
            linear-gradient(225deg, rgba(20, 184, 166, .08), transparent 38%);
    }

    .app-bg-curve::after {
        content: "";
        position: absolute;
        inset: 0;
        opacity: .42;
        background-image:
            linear-gradient(rgba(37, 99, 235, .08) 1px, transparent 1px),
            linear-gradient(90deg, rgba(15, 118, 110, .07) 1px, transparent 1px);
        background-size: 56px 56px;
        -webkit-mask-image: linear-gradient(180deg, rgba(0, 0, 0, .8), rgba(0, 0, 0, .08) 62%, transparent 100%);
        mask-image: linear-gradient(180deg, rgba(0, 0, 0, .8), rgba(0, 0, 0, .08) 62%, transparent 100%);
        transform: translate3d(var(--bg-curve-x-reverse), var(--bg-curve-y-reverse), 0);
        transition: transform .22s ease;
    }

    .app-bg-curve__svg {
        position: absolute;
        left: 50%;
        width: 116vw;
        min-width: 980px;
        pointer-events: none;
        will-change: transform;
    }

    .app-bg-curve__svg--top {
        top: -2px;
        height: clamp(320px, 44vh, 430px);
        transform: translate3d(calc(-50% + var(--bg-curve-x)), calc(var(--bg-curve-y) + var(--bg-curve-scroll-y)), 0);
    }

    .app-bg-curve__svg--bottom {
        right: auto;
        bottom: -96px;
        height: clamp(260px, 36vh, 370px);
        opacity: .76;
        transform: translate3d(calc(-50% + var(--bg-curve-x-reverse)), var(--bg-curve-y-reverse), 0);
    }

    .app-bg-curve__shape {
        transform-box: fill-box;
        transform-origin: center;
    }

    .app-bg-curve__shape--primary {
        filter: drop-shadow(0 26px 48px rgba(37, 99, 235, .1));
    }

    .app-bg-curve__shape--accent {
        animation: app-curve-breathe 9s ease-in-out infinite alternate;
    }

    .app-bg-curve__shape--bottom {
        fill: rgba(37, 99, 235, .08);
    }

    .app-bg-curve__line {
        fill: none;
        stroke: rgba(37, 99, 235, .2);
        stroke-width: 2;
        stroke-linecap: round;
        stroke-dasharray: 10 16;
        animation: app-curve-drift 14s linear infinite;
    }

    .app-bg-curve__line--soft {
        stroke: rgba(15, 118, 110, .16);
        stroke-width: 1.5;
        stroke-dasharray: 6 18;
        animation-duration: 18s;
        animation-direction: reverse;
    }

    .app-bg-curve__line--bottom {
        stroke: rgba(245, 158, 11, .17);
        animation-duration: 16s;
    }

    .navbar.bg-white {
        background-color: rgba(244, 248, 251, .92) !important;
        border-color: rgba(255, 255, 255, .72) !important;
        box-shadow: var(--neu-shadow-sm) !important;
    }

    .auth-page-surface {
        background: transparent;
        padding: 2rem 1rem;
    }

    body.app-shell .card {
        background-color: rgba(244, 248, 251, .94);
    }

    @supports ((backdrop-filter: blur(16px)) or (-webkit-backdrop-filter: blur(16px))) {
        .navbar.bg-white,
        .auth-page-surface .card {
            -webkit-backdrop-filter: blur(16px);
            backdrop-filter: blur(16px);
        }
    }

    @keyframes app-curve-breathe {
        from {
            opacity: .76;
            transform: translateY(0);
        }

        to {
            opacity: .96;
            transform: translateY(16px);
        }
    }

    @keyframes app-curve-drift {
        to {
            stroke-dashoffset: -120;
        }
    }

    body.app-shell .card,
    .app-card,
    .filter-panel,
    .workflow-step-card,
    .dropdown-menu {
        border: 1px solid rgba(255, 255, 255, .72) !important;
        background: var(--neu-surface);
        box-shadow: var(--neu-shadow);
    }

    body.app-shell .card,
    .app-card,
    .filter-panel {
        border-radius: var(--app-radius);
    }

    .card.border-0 {
        border: 1px solid rgba(255, 255, 255, .72) !important;
    }

    .shadow-sm {
        box-shadow: var(--neu-shadow-sm) !important;
    }

    .rounded-4 {
        border-radius: var(--app-radius) !important;
    }

    .dropdown-menu {
        background: rgba(244, 248, 251, .98);
        border-radius: 16px !important;
        padding: .55rem;
    }

    .dropdown-item {
        border-radius: 12px;
        color: var(--app-dark);
        font-weight: 600;
    }

    .dropdown-item:hover,
    .dropdown-item:focus {
        background: var(--neu-surface-soft);
        color: var(--app-primary);
        box-shadow: var(--neu-shadow-inset);
    }

    .navbar .nav-link {
        transition: color .18s ease, background-color .18s ease, box-shadow .18s ease, transform .18s ease;
    }

    .navbar .nav-link.bg-light,
    .navbar .nav-link.bg-white {
        background: var(--neu-surface) !important;
        border: 1px solid rgba(255, 255, 255, .72) !important;
        box-shadow: var(--neu-shadow-sm);
    }

    .navbar .nav-link.bg-light:hover,
    .navbar .nav-link.bg-white:hover {
        transform: translateY(-1px);
        box-shadow: var(--neu-shadow);
    }

    .navbar .nav-link.active,
    .navbar .nav-link.bg-primary {
        background: linear-gradient(135deg, #2563eb 0%, #0f766e 100%) !important;
        border-color: rgba(255, 255, 255, .42) !important;
        color: #fff !important;
        box-shadow: 8px 8px 18px rgba(37, 99, 235, .24), -8px -8px 18px rgba(255, 255, 255, .72);
    }

    .form-control,
    .form-select,
    select.form-control,
    textarea.form-control {
        border: 1px solid rgba(255, 255, 255, .78);
        border-radius: 14px;
        background-color: var(--neu-surface-soft);
        color: var(--app-dark);
        box-shadow: var(--neu-shadow-inset);
        transition: border-color .18s ease, box-shadow .18s ease, background-color .18s ease;
    }

    .form-control:focus,
    .form-select:focus,
    select.form-control:focus,
    textarea.form-control:focus {
        border-color: rgba(37, 99, 235, .46);
        background-color: var(--neu-surface-strong);
        box-shadow: var(--neu-shadow-inset), 0 0 0 .22rem rgba(37, 99, 235, .16);
    }

    .form-floating > .form-control,
    .form-floating > .form-select {
        border-radius: 16px;
    }

    .form-check-input {
        border-color: rgba(148, 163, 184, .35);
        background-color: var(--neu-surface-soft);
        box-shadow: var(--neu-shadow-inset);
    }

    .form-check-input:checked {
        background-color: var(--app-primary);
        border-color: var(--app-primary);
        box-shadow: var(--neu-shadow-sm);
    }

    .btn {
        border-radius: 14px;
        border-width: 1px;
        font-weight: 700;
        box-shadow: var(--neu-shadow-sm);
        transition: transform .16s ease, box-shadow .16s ease, background-color .16s ease, border-color .16s ease;
    }

    .btn:hover {
        transform: translateY(-1px);
        box-shadow: var(--neu-shadow);
    }

    .btn:active,
    .btn.active {
        transform: translateY(0);
        box-shadow: var(--neu-shadow-pressed) !important;
    }

    .btn:disabled,
    .btn.disabled {
        box-shadow: var(--neu-shadow-inset);
        opacity: .72;
    }

    .btn-primary {
        background: linear-gradient(135deg, #2563eb 0%, #0f766e 100%);
        border-color: rgba(37, 99, 235, .26);
        color: #fff;
    }

    .btn-secondary,
    .btn-light,
    .btn-outline-secondary,
    .btn-outline-primary,
    .btn-outline-warning,
    .btn-outline-danger,
    .btn-outline-dark {
        background: var(--neu-surface);
        border-color: rgba(255, 255, 255, .72);
    }

    .btn-secondary,
    .btn-light,
    .btn-outline-secondary,
    .btn-outline-dark {
        color: #334155;
    }

    .btn-secondary:hover,
    .btn-light:hover,
    .btn-outline-secondary:hover,
    .btn-outline-dark:hover {
        background: var(--neu-surface-strong);
        border-color: rgba(255, 255, 255, .88);
        color: var(--app-dark);
    }

    .btn-outline-primary {
        color: var(--app-primary);
    }

    .btn-outline-primary:hover {
        background: linear-gradient(135deg, #2563eb 0%, #0f766e 100%);
        color: #fff;
    }

    .btn-outline-danger {
        color: var(--app-danger);
    }

    .btn-outline-warning {
        color: #b45309;
    }

    .btn-danger {
        background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
        border-color: rgba(220, 38, 38, .28);
    }

    .btn-warning {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        border-color: rgba(245, 158, 11, .28);
        color: #111827;
    }

    .alert {
        border: 1px solid rgba(255, 255, 255, .72);
        border-radius: var(--app-radius);
        box-shadow: var(--neu-shadow-sm);
    }

    .table {
        --bs-table-bg: transparent;
        color: var(--app-dark);
    }

    .table > :not(caption) > * > * {
        border-bottom-color: rgba(148, 163, 184, .22);
    }

    .table-hover > tbody > tr:hover > * {
        background-color: rgba(255, 255, 255, .42);
        box-shadow: inset 4px 4px 10px rgba(148, 163, 184, .12), inset -4px -4px 10px rgba(255, 255, 255, .82);
    }

    .badge {
        border: 1px solid rgba(255, 255, 255, .42);
        box-shadow: 3px 3px 8px rgba(148, 163, 184, .16), -3px -3px 8px rgba(255, 255, 255, .62);
    }

    .list-group-item {
        border-color: rgba(148, 163, 184, .22);
        background: var(--neu-surface);
    }

    .border {
        border-color: rgba(148, 163, 184, .24) !important;
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

        .app-bg-curve__shape--accent,
        .app-bg-curve__line {
            animation: none;
        }
    }

    @media (max-width: 768px) {
        .app-bg-curve__svg {
            width: 162vw;
            min-width: 720px;
        }

        .app-bg-curve__svg--top {
            height: 330px;
        }

        .app-bg-curve__svg--bottom {
            height: 260px;
            bottom: -118px;
            opacity: .6;
        }

        .app-bg-curve::after {
            background-size: 44px 44px;
            opacity: .32;
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
        border: 1px solid rgba(255, 255, 255, .72);
        border-radius: var(--app-radius);
        box-shadow: var(--neu-shadow);
    }

    .app-card-hover {
        transition: transform .18s ease, box-shadow .18s ease;
    }

    .app-card-hover:hover {
        transform: translateY(-2px);
        box-shadow: var(--neu-shadow-lg);
    }

    .filter-panel {
        background: var(--app-card);
        border: 1px solid rgba(255, 255, 255, .72);
        border-radius: var(--app-radius);
        padding: 1rem;
        margin-bottom: 1rem;
        box-shadow: var(--neu-shadow);
    }

    .empty-state {
        text-align: center;
        padding: 42px 24px;
        border: 1px dashed rgba(148, 163, 184, .42);
        border-radius: var(--app-radius);
        background: var(--neu-surface-soft);
        box-shadow: var(--neu-shadow-inset);
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
        box-shadow: var(--neu-shadow-sm);
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
        border: 1px solid rgba(255, 255, 255, .72);
        border-radius: var(--app-radius);
        background: var(--neu-surface);
        padding: 1rem;
        box-shadow: var(--neu-shadow-sm);
    }

    .workflow-optional-section {
        position: relative;
        border: 1px solid rgba(255, 255, 255, .72);
        border-radius: 12px;
        background: var(--neu-surface-soft);
        padding: .85rem;
        transition: border-color .18s ease, background .18s ease;
        box-shadow: var(--neu-shadow-inset);
    }

    .workflow-optional-section.is-on {
        border-color: #bfdbfe;
        background: var(--neu-surface);
        box-shadow: var(--neu-shadow-sm);
    }

    .workflow-toggle {
        border: 1px solid rgba(255, 255, 255, .72);
        border-radius: 999px;
        background: var(--neu-surface);
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
        box-shadow: var(--neu-shadow-sm);
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
        background: linear-gradient(135deg, #2563eb 0%, #0f766e 100%);
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
        box-shadow: 8px 8px 18px rgba(37, 99, 235, .2), -8px -8px 18px rgba(255, 255, 255, .72);
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
        border: 1px solid rgba(255, 255, 255, .72);
        border-radius: 10px;
        background: var(--neu-surface);
        padding: .45rem .55rem;
        box-shadow: var(--neu-shadow-sm);
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
        box-shadow: var(--neu-shadow-lg);
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
