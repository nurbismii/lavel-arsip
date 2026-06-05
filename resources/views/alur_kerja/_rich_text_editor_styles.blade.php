@once
    <link rel="stylesheet" href="{{ asset('vendor/quill/quill.snow.css') }}">

    <style>
        .rich-text-source {
            display: none !important;
        }

        .swal2-container .rich-text-field,
        .swal2-container .ql-toolbar,
        .swal2-container .ql-container {
            display: none !important;
        }

        .rich-text-field .ql-toolbar.ql-snow {
            border-color: var(--app-border);
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
            background: #f8fafc;
        }

        .rich-text-field .ql-container.ql-snow {
            border-color: var(--app-border);
            border-bottom-left-radius: 12px;
            border-bottom-right-radius: 12px;
            font-family: inherit;
        }

        .rich-text-field .ql-editor {
            min-height: 116px;
            color: var(--app-dark);
            font-size: .95rem;
            line-height: 1.55;
        }

        .rich-text-field .ql-editor.ql-blank::before {
            color: #94a3b8;
            font-style: normal;
        }

        .rich-text-field.is-invalid .ql-toolbar.ql-snow,
        .rich-text-field.is-invalid .ql-container.ql-snow {
            border-color: var(--app-danger);
        }

        .rich-text-content {
            overflow-wrap: anywhere;
            line-height: 1.6;
        }

        .rich-text-content p,
        .rich-text-content ul,
        .rich-text-content ol,
        .rich-text-content blockquote {
            margin-bottom: .5rem;
        }

        .rich-text-content > :last-child {
            margin-bottom: 0;
        }

        .rich-text-content blockquote {
            margin-left: 0;
            padding-left: .75rem;
            border-left: 3px solid var(--app-border);
            color: var(--app-muted);
        }

        .rich-text-content--compact {
            font-size: inherit;
            line-height: 1.45;
        }
    </style>
@endonce
