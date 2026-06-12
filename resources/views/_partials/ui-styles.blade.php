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

    .knowledge-content {
        font-size: .98rem;
        color: var(--app-dark);
    }

    .knowledge-content h1,
    .knowledge-content h2,
    .knowledge-content h3,
    .knowledge-content h4,
    .knowledge-content h5,
    .knowledge-content h6 {
        font-size: 1rem;
        font-weight: 800;
        margin-top: 1rem;
        margin-bottom: .5rem;
    }

    .knowledge-content ul,
    .knowledge-content ol {
        padding-left: 1.25rem;
    }

    .knowledge-content a {
        font-weight: 700;
        text-decoration: none;
    }

    .sop-form-guide {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: .75rem;
    }

    .sop-form-guide__item {
        display: flex;
        align-items: center;
        gap: .75rem;
        padding: .85rem;
        border: 1px solid rgba(255, 255, 255, .72);
        border-radius: 14px;
        background: var(--neu-surface-soft);
        box-shadow: var(--neu-shadow-sm);
    }

    .sop-form-guide__item > span,
    .sop-step-number {
        width: 34px;
        height: 34px;
        display: inline-grid;
        place-items: center;
        flex: 0 0 auto;
        border-radius: 12px;
        background: var(--app-primary);
        color: #fff;
        font-weight: 800;
        box-shadow: var(--neu-shadow-sm);
    }

    .sop-form-guide__item strong {
        display: block;
        color: var(--app-dark);
        line-height: 1.2;
    }

    .sop-form-guide__item small {
        display: block;
        color: var(--app-muted);
        line-height: 1.35;
    }

    .sop-form-section {
        padding: 1rem;
        border: 1px solid rgba(255, 255, 255, .72);
        border-radius: var(--app-radius);
        background: var(--neu-surface);
        box-shadow: var(--neu-shadow-sm);
    }

    .sop-form-section + .sop-form-section {
        margin-top: 1rem;
    }

    .sop-section-heading {
        display: flex;
        align-items: flex-start;
        gap: .75rem;
        margin-bottom: 1rem;
    }

    .sop-section-heading h6 {
        margin-bottom: .15rem;
        font-weight: 800;
    }

    .sop-section-heading p {
        margin-bottom: 0;
        color: var(--app-muted);
        font-size: .92rem;
    }

    .sop-advanced-panel,
    .sop-repeatable-row,
    .sop-procedure-row {
        border: 1px solid rgba(255, 255, 255, .72);
        border-radius: 14px;
        background: var(--neu-surface-soft);
        padding: .85rem;
        box-shadow: var(--neu-shadow-inset);
    }

    .sop-procedure-row__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        margin-bottom: .85rem;
        padding-bottom: .65rem;
        border-bottom: 1px solid rgba(148, 163, 184, .22);
    }

    .sop-procedure-row__title {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: .5rem;
        min-width: 0;
    }

    .sop-symbol-dropzone {
        padding: .85rem;
        border: 1px dashed rgba(37, 99, 235, .3);
        border-radius: 14px;
        background: rgba(248, 250, 252, .72);
        transition: border-color .18s ease, box-shadow .18s ease, background .18s ease;
    }

    .sop-symbol-dropzone.is-drag-over {
        border-color: var(--app-primary);
        background: var(--app-primary-soft);
        box-shadow: 0 0 0 4px rgba(37, 99, 235, .08);
    }

    .sop-symbol-dropzone__hint {
        margin-top: .75rem;
        padding: .65rem .75rem;
        border-radius: 12px;
        background: rgba(255, 255, 255, .74);
        color: var(--app-muted);
        font-size: .88rem;
    }

    .sop-document-workspace {
        transition: border-color .18s ease, box-shadow .18s ease, background .18s ease;
    }

    .sop-document-workspace.is-drag-over {
        border-color: var(--app-primary);
        background: linear-gradient(180deg, var(--neu-surface), var(--app-primary-soft));
        box-shadow: 0 0 0 4px rgba(37, 99, 235, .08), var(--neu-shadow-sm);
    }

    .sop-editor-commandbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        padding: .75rem;
        border: 1px solid rgba(148, 163, 184, .22);
        border-radius: 14px;
        background: var(--neu-surface-soft);
    }

    .sop-editor-commandbar__actions {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
    }

    .sop-diagram-uploader {
        padding: .9rem;
        border: 1px solid rgba(148, 163, 184, .22);
        border-radius: 14px;
        background: var(--neu-surface-soft);
    }

    .sop-diagram-uploader__drop,
    .sop-diagram-uploader__preview {
        height: 100%;
        padding: .9rem;
        border: 1px solid var(--app-border);
        border-radius: 14px;
        background: #fff;
    }

    .sop-diagram-uploader__icon {
        width: 44px;
        height: 44px;
        display: grid;
        place-items: center;
        margin-bottom: .75rem;
        border-radius: 14px;
        background: var(--app-primary-soft);
        color: var(--app-primary);
        font-size: 1.1rem;
    }

    .sop-diagram-uploader__image {
        display: block;
        width: 100%;
        max-height: 220px;
        object-fit: contain;
        border: 1px solid var(--app-border);
        border-radius: 12px;
        background: #fff;
    }

    .sop-diagram-uploader__grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
        gap: .75rem;
    }

    .sop-diagram-uploader__item {
        min-width: 0;
    }

    .sop-diagram-uploader__file {
        color: var(--app-muted);
        font-size: .88rem;
        font-weight: 700;
        overflow-wrap: anywhere;
    }

    .sop-flow-designer {
        display: grid;
        grid-template-columns: 270px minmax(0, 1fr);
        gap: .85rem;
        align-items: stretch;
    }

    .sop-flow-toolbar {
        display: flex;
        flex-direction: column;
        gap: .75rem;
        padding: .85rem;
        border: 1px solid rgba(148, 163, 184, .22);
        border-radius: 14px;
        background: var(--neu-surface-soft);
    }

    .sop-flow-toolbar__title strong,
    .sop-flow-toolbar__title small {
        display: block;
    }

    .sop-flow-toolbar__title strong {
        color: var(--app-dark);
        font-weight: 800;
    }

    .sop-flow-toolbar__title small {
        color: var(--app-muted);
    }

    .sop-flow-toolbar__group {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
    }

    .sop-flow-toolbar__group--actions {
        padding-top: .65rem;
        border-top: 1px solid rgba(148, 163, 184, .22);
    }

    .sop-flow-workspace {
        min-width: 0;
    }

    .sop-flow-help {
        padding: .65rem .75rem;
        border-radius: 12px;
        background: rgba(255, 255, 255, .74);
        color: var(--app-muted);
        font-size: .88rem;
    }

    .sop-flow-sync-status {
        margin-top: .5rem;
        padding: .55rem .75rem;
        border: 1px solid rgba(22, 163, 74, .16);
        border-radius: 12px;
        background: rgba(240, 253, 244, .78);
        color: #166534;
        font-size: .84rem;
        font-weight: 700;
    }

    .sop-flow-canvas-wrap {
        overflow: auto;
        max-height: 620px;
        border: 1px solid var(--app-border);
        border-radius: 14px;
        background:
            linear-gradient(rgba(148, 163, 184, .08) 1px, transparent 1px),
            linear-gradient(90deg, rgba(148, 163, 184, .08) 1px, transparent 1px),
            #fff;
        background-size: 24px 24px;
        box-shadow: var(--neu-shadow-inset);
    }

    .sop-flow-canvas {
        position: relative;
        width: 1800px;
        min-width: 100%;
        height: 1100px;
    }

    .sop-flow-canvas.is-drag-over {
        outline: 3px solid rgba(37, 99, 235, .28);
        outline-offset: -6px;
    }

    .sop-flow-lines {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: 1;
    }

    .sop-flow-lines marker path {
        fill: #2563eb;
    }

    .sop-flow-connector {
        fill: none;
        stroke: #2563eb;
        stroke-width: 2.4;
        pointer-events: stroke;
        cursor: pointer;
    }

    .sop-flow-connector.is-selected {
        stroke: var(--app-danger);
        stroke-width: 3.4;
    }

    .sop-flow-node {
        position: absolute;
        z-index: 2;
        width: 168px;
        min-height: 72px;
        display: grid;
        grid-template-columns: 30px 1fr;
        align-items: center;
        gap: .5rem;
        padding: .6rem;
        border: 1px solid rgba(37, 99, 235, .28);
        border-radius: 12px;
        background: #fff;
        box-shadow: 0 12px 28px rgba(15, 23, 42, .08);
        cursor: move;
        user-select: none;
    }

    .sop-flow-node.is-selected {
        border-color: var(--app-primary);
        box-shadow: 0 0 0 4px rgba(37, 99, 235, .12), 0 14px 30px rgba(15, 23, 42, .1);
    }

    .sop-flow-node.is-source {
        border-color: var(--app-success);
        box-shadow: 0 0 0 4px rgba(22, 163, 74, .14), 0 14px 30px rgba(15, 23, 42, .1);
    }

    .sop-flow-node.is-readonly {
        cursor: default;
    }

    .sop-flow-node__label {
        width: 100%;
        min-height: 46px;
        padding: .25rem .35rem;
        border: 1px solid transparent;
        border-radius: 8px;
        resize: none;
        background: transparent;
        color: var(--app-dark);
        font-size: .82rem;
        font-weight: 700;
        line-height: 1.25;
        overflow: hidden;
    }

    div.sop-flow-node__label {
        display: flex;
        align-items: center;
    }

    .sop-flow-node__label:focus {
        border-color: rgba(37, 99, 235, .32);
        background: var(--app-primary-soft);
        outline: 0;
    }

    .sop-flow-node--terminator {
        border-radius: 999px;
    }

    .sop-flow-node--decision {
        min-height: 92px;
    }

    .sop-flow-node--dokumen {
        border-bottom-left-radius: 20px;
        border-bottom-right-radius: 20px;
    }

    .sop-flow-empty {
        position: absolute;
        left: 50%;
        top: 50%;
        transform: translate(-50%, -50%);
        z-index: 0;
        padding: 1rem 1.25rem;
        border: 1px dashed var(--app-border);
        border-radius: 14px;
        background: rgba(248, 250, 252, .88);
        color: var(--app-muted);
        font-weight: 700;
        text-align: center;
    }

    .sop-symbol-palette {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
        justify-content: flex-start;
    }

    .sop-symbol-chip {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        min-height: 38px;
        max-width: 100%;
        padding: .45rem .65rem;
        border: 1px solid var(--app-border);
        border-radius: 12px;
        background: #fff;
        color: var(--app-dark);
        font-size: .84rem;
        font-weight: 700;
        line-height: 1.2;
        cursor: grab;
        transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease;
    }

    .sop-symbol-chip:hover,
    .sop-symbol-chip:focus {
        border-color: rgba(37, 99, 235, .42);
        box-shadow: var(--neu-shadow-sm);
        transform: translateY(-1px);
    }

    .sop-symbol-chip.is-dragging {
        cursor: grabbing;
        opacity: .72;
    }

    .sop-symbol-pill {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        max-width: 100%;
        padding: .28rem .5rem;
        border: 1px solid rgba(37, 99, 235, .18);
        border-radius: 999px;
        background: var(--app-primary-soft);
        color: #1d4ed8;
        font-size: .76rem;
        font-weight: 800;
        line-height: 1.2;
    }

    .sop-symbol-icon {
        position: relative;
        display: inline-block;
        width: 18px;
        height: 18px;
        flex: 0 0 auto;
        border: 2px solid currentColor;
        color: #2563eb;
        background: #fff;
    }

    .sop-symbol-icon--terminator {
        width: 24px;
        border-radius: 999px;
    }

    .sop-symbol-icon--aktivitas {
        border-radius: 4px;
    }

    .sop-symbol-icon--decision {
        width: 16px;
        height: 16px;
        margin-left: 2px;
        margin-right: 2px;
        border-radius: 3px;
        transform: rotate(45deg);
    }

    .sop-symbol-icon--dokumen {
        border-radius: 3px;
    }

    .sop-symbol-icon--dokumen::after {
        content: "";
        position: absolute;
        right: -2px;
        bottom: -2px;
        width: 10px;
        height: 7px;
        border-left: 2px solid currentColor;
        border-top: 2px solid currentColor;
        border-top-left-radius: 8px;
        background: #fff;
    }

    .sop-symbol-icon--connector_halaman {
        width: 19px;
        clip-path: polygon(50% 0, 100% 34%, 82% 100%, 18% 100%, 0 34%);
        background: currentColor;
        border: 0;
    }

    .sop-symbol-icon--connector_internal {
        border-radius: 50%;
    }

    .sop-symbol-token {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        margin: 0 .2rem .15rem 0;
        padding: .22rem .5rem;
        border: 1px solid rgba(37, 99, 235, .24);
        border-radius: 999px;
        background: var(--app-primary-soft);
        color: #1d4ed8;
        font-size: .78rem;
        font-weight: 800;
        line-height: 1.25;
        vertical-align: middle;
        white-space: nowrap;
    }

    .sop-symbol-token::before {
        content: "";
        display: inline-block;
        width: 15px;
        height: 15px;
        border: 2px solid currentColor;
        background: #fff;
        flex: 0 0 auto;
    }

    .sop-symbol-token--terminator::before {
        width: 21px;
        border-radius: 999px;
    }

    .sop-symbol-token--aktivitas::before {
        border-radius: 4px;
    }

    .sop-symbol-token--decision::before {
        width: 13px;
        height: 13px;
        margin-left: 2px;
        margin-right: 2px;
        border-radius: 3px;
        transform: rotate(45deg);
    }

    .sop-symbol-token--dokumen::before {
        border-radius: 3px 3px 7px 3px;
    }

    .sop-symbol-token--connector_halaman::before {
        border: 0;
        background: currentColor;
        clip-path: polygon(50% 0, 100% 34%, 82% 100%, 18% 100%, 0 34%);
    }

    .sop-symbol-token--connector_internal::before {
        border-radius: 50%;
    }

    .sop-document-preview {
        padding: 1rem;
        border: 1px solid var(--app-border);
        border-radius: 14px;
        background: #fff;
    }

    .sop-diagram-block {
        margin: 1rem 0;
        padding: .85rem;
        border: 1px solid var(--app-border);
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 8px 20px rgba(15, 23, 42, .05);
        overflow-x: auto;
    }

    .sop-diagram-svg {
        display: block;
        max-width: 100%;
        height: auto;
        min-width: 620px;
    }

    .repeatable-row {
        transition: border-color .18s ease, box-shadow .18s ease;
    }

    .repeatable-row:hover {
        border-color: rgba(37, 99, 235, .28) !important;
        box-shadow: var(--neu-shadow-sm);
    }

    .repeatable-row [data-repeatable-remove] {
        padding-left: .45rem;
        padding-right: .45rem;
        font-size: .82rem;
        white-space: nowrap;
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

    .cadangan-owner-select-wrapper .select2-container {
        max-width: 100%;
    }

    .cadangan-owner-select-wrapper .select2-container--default .select2-selection--multiple {
        min-height: 46px;
        max-height: 112px;
        overflow-x: hidden;
        overflow-y: auto;
        border: 1px solid rgba(255, 255, 255, .78);
        border-radius: 14px;
        background: var(--neu-surface-soft);
        box-shadow: var(--neu-shadow-inset);
    }

    .cadangan-owner-select-wrapper .select2-container--default.select2-container--focus .select2-selection--multiple {
        border-color: rgba(37, 99, 235, .46);
        background: var(--neu-surface-strong);
        box-shadow: var(--neu-shadow-inset), 0 0 0 .22rem rgba(37, 99, 235, .16);
    }

    .cadangan-owner-select-wrapper .select2-container--default .select2-selection--multiple .select2-selection__rendered {
        display: block;
        max-width: 100%;
        margin: 0;
        padding: 0 .45rem .35rem;
        overflow-x: hidden;
        white-space: normal;
    }

    .cadangan-owner-select-wrapper .select2-container--default .select2-selection--multiple .select2-selection__choice {
        max-width: calc(100% - .45rem);
        margin-top: .35rem;
        margin-right: .35rem;
        overflow: hidden;
        text-overflow: ellipsis;
        vertical-align: top;
        white-space: nowrap;
    }

    .cadangan-owner-select-wrapper .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        padding: 0 .35rem;
    }

    .cadangan-owner-select-wrapper .select2-container--default .select2-selection--multiple .select2-selection__choice__display {
        display: inline-block;
        max-width: calc(100% - 1.8rem);
        overflow: hidden;
        text-overflow: ellipsis;
        vertical-align: bottom;
    }

    .cadangan-owner-select-wrapper .select2-container--default .select2-selection--multiple .select2-search--inline {
        max-width: 100%;
    }

    .cadangan-owner-select-wrapper .select2-container--default .select2-search--inline .select2-search__field {
        max-width: calc(100% - .35rem) !important;
        min-height: 30px;
        margin-top: .35rem;
        color: var(--app-dark);
    }

    .select2-dropdown {
        border: 1px solid rgba(148, 163, 184, .28);
        border-radius: 14px;
        background: var(--neu-surface-strong);
        box-shadow: var(--neu-shadow);
        overflow: hidden;
    }

    .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
        background: var(--app-primary);
    }

    .select2-container--default .select2-results__option--disabled {
        color: #94a3b8;
    }

    @media (min-width: 769px) and (max-width: 991.98px) {
        .sop-form-guide {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
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

        .sop-form-guide {
            grid-template-columns: 1fr;
        }

        .sop-form-section {
            padding: .85rem;
        }

        .sop-section-heading {
            gap: .65rem;
        }

        .sop-procedure-row__header {
            align-items: stretch;
            flex-direction: column;
        }

        .sop-symbol-palette {
            display: grid;
            grid-template-columns: 1fr;
        }

        .sop-symbol-chip {
            width: 100%;
            justify-content: flex-start;
        }

        .sop-editor-commandbar {
            align-items: stretch;
            flex-direction: column;
        }

        .sop-editor-commandbar__actions {
            display: grid;
            width: 100%;
        }

        .sop-diagram-uploader .btn {
            width: 100%;
        }

        .sop-flow-designer {
            grid-template-columns: 1fr;
        }

        .sop-flow-toolbar__group,
        .sop-flow-toolbar__group--actions {
            display: grid;
            grid-template-columns: 1fr;
        }

        .sop-flow-canvas-wrap {
            max-height: 560px;
        }

        .sop-flow-canvas {
            width: 1800px;
            height: 1100px;
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
