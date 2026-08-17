<link rel="stylesheet" href="{{ asset('vendor/quill/quill.snow.css') }}">

<style>
    .pekerjaan-rich-text {
        border-radius: 0.5rem;
        background: #ffffff;
        transition: box-shadow 0.18s ease;
    }

    .pekerjaan-rich-text .ql-toolbar.ql-snow {
        display: flex;
        flex-wrap: wrap;
        gap: 0.15rem;
        border-color: #ced4da;
        border-radius: 0.5rem 0.5rem 0 0;
        background: #f8fafc;
    }

    .pekerjaan-rich-text .ql-container.ql-snow {
        min-height: 8rem;
        border-color: #ced4da;
        border-radius: 0 0 0.5rem 0.5rem;
        font-family: inherit;
        font-size: 0.95rem;
    }

    .pekerjaan-rich-text.is-compact .ql-container.ql-snow {
        min-height: 6rem;
    }

    .pekerjaan-rich-text .ql-editor {
        min-height: inherit;
        line-height: 1.55;
    }

    .pekerjaan-rich-text:focus-within {
        box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.16);
    }

    .pekerjaan-rich-text:focus-within .ql-toolbar.ql-snow,
    .pekerjaan-rich-text:focus-within .ql-container.ql-snow {
        border-color: #86a8f7;
    }

    .pekerjaan-rich-text.is-invalid .ql-toolbar.ql-snow,
    .pekerjaan-rich-text.is-invalid .ql-container.ql-snow {
        border-color: #dc3545;
    }

    .pekerjaan-rich-text-error {
        color: #dc3545;
        font-size: 0.82rem;
        margin-top: 0.35rem;
    }

    .rich-text-content {
        overflow-wrap: anywhere;
        white-space: normal;
    }

    .rich-text-content > :last-child {
        margin-bottom: 0;
    }

    .rich-text-content p,
    .rich-text-content blockquote,
    .rich-text-content ul,
    .rich-text-content ol {
        margin-bottom: 0.5rem;
    }

    .rich-text-content h2,
    .rich-text-content h3 {
        color: #334155;
        font-size: 1rem;
        font-weight: 700;
        margin: 0 0 0.45rem;
    }

    .rich-text-content blockquote {
        border-left: 3px solid #cbd5e1;
        color: #475569;
        padding-left: 0.75rem;
    }

    @media (max-width: 575.98px) {
        .pekerjaan-rich-text .ql-toolbar.ql-snow {
            padding: 0.4rem;
        }

        .pekerjaan-rich-text .ql-formats {
            margin-right: 0.35rem;
        }
    }
</style>
