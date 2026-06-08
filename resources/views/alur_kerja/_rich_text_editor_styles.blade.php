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

        .rich-text-field--document .ql-editor {
            min-height: 520px;
            padding: 1.25rem;
            background: #fff;
            font-size: 1rem;
            line-height: 1.68;
        }

        .rich-text-field--document .ql-editor h1,
        .rich-text-content h1 {
            font-size: 1.42rem;
            font-weight: 800;
            line-height: 1.25;
            margin-bottom: .75rem;
            text-transform: uppercase;
        }

        .rich-text-field--document .ql-editor h2,
        .rich-text-content h2 {
            font-size: 1.12rem;
            font-weight: 800;
            margin-top: 1rem;
            margin-bottom: .45rem;
        }

        .rich-text-field--document .ql-editor h3,
        .rich-text-content h3 {
            font-size: 1rem;
            font-weight: 800;
            margin-top: .85rem;
            margin-bottom: .35rem;
        }

        .rich-text-field--document .ql-editor table,
        .sop-document-preview table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin: .25rem 0 1rem;
            color: var(--app-dark);
        }

        .rich-text-field--document .ql-editor th,
        .rich-text-field--document .ql-editor td,
        .sop-document-preview th,
        .sop-document-preview td {
            border: 1px solid #111827;
            padding: .38rem .48rem;
            vertical-align: middle;
        }

        .rich-text-field--document .ql-editor table p,
        .sop-document-preview table p {
            margin: .08rem 0;
            line-height: 1.35;
        }

        .rich-text-field--document .ql-editor table:first-child,
        .sop-document-preview table:first-child {
            font-size: .86rem;
        }

        .rich-text-field--document .ql-editor table:first-child tr:first-child td:first-child,
        .sop-document-preview table:first-child tr:first-child td:first-child {
            width: 46%;
        }

        .rich-text-field--document .ql-editor table:first-child tr:first-child td:nth-child(2),
        .sop-document-preview table:first-child tr:first-child td:nth-child(2) {
            width: 18%;
        }

        .rich-text-field--document .ql-editor table:first-child tr:first-child td:nth-child(3),
        .sop-document-preview table:first-child tr:first-child td:nth-child(3) {
            width: 4%;
            text-align: center;
        }

        .rich-text-field--document .ql-editor table:first-child tr:first-child td:nth-child(4),
        .sop-document-preview table:first-child tr:first-child td:nth-child(4) {
            width: 32%;
        }

        .rich-text-field--document .ql-editor table:first-child tr:nth-child(2) td,
        .sop-document-preview table:first-child tr:nth-child(2) td {
            text-align: center;
            font-size: .96rem;
            letter-spacing: 0;
        }

        .rich-text-field--document .ql-editor .sop-kop-block,
        .sop-document-preview .sop-kop-block {
            width: 100%;
            margin: 0 0 1rem;
            border: 1.5px solid #111827;
            background: #fff;
            color: #111827;
            font-size: .84rem;
            line-height: 1.25;
            overflow: hidden;
        }

        .rich-text-field--document .ql-editor .sop-kop-top,
        .sop-document-preview .sop-kop-top {
            display: grid;
            grid-template-columns: minmax(260px, 1.08fr) minmax(290px, .92fr);
            border-bottom: 1.5px solid #111827;
        }

        .rich-text-field--document .ql-editor .sop-kop-company,
        .sop-document-preview .sop-kop-company {
            min-height: 86px;
            padding: .7rem .85rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            text-align: center;
            border-right: 1.5px solid #111827;
        }

        .rich-text-field--document .ql-editor .sop-kop-name,
        .sop-document-preview .sop-kop-name {
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0;
            margin-bottom: .2rem;
        }

        .rich-text-field--document .ql-editor .sop-kop-address,
        .sop-document-preview .sop-kop-address {
            font-size: .8rem;
        }

        .rich-text-field--document .ql-editor .sop-kop-meta,
        .sop-document-preview .sop-kop-meta {
            display: grid;
            grid-template-columns: minmax(96px, .9fr) 18px minmax(130px, 1.2fr);
        }

        .rich-text-field--document .ql-editor .sop-kop-meta-label,
        .rich-text-field--document .ql-editor .sop-kop-meta-separator,
        .rich-text-field--document .ql-editor .sop-kop-meta-value,
        .sop-document-preview .sop-kop-meta-label,
        .sop-document-preview .sop-kop-meta-separator,
        .sop-document-preview .sop-kop-meta-value {
            min-height: 21.5px;
            padding: .18rem .35rem;
            display: flex;
            align-items: center;
            border-bottom: 1px solid #111827;
            overflow-wrap: anywhere;
        }

        .rich-text-field--document .ql-editor .sop-kop-meta-label,
        .sop-document-preview .sop-kop-meta-label {
            font-weight: 700;
            border-right: 1px solid #111827;
        }

        .rich-text-field--document .ql-editor .sop-kop-meta-separator,
        .sop-document-preview .sop-kop-meta-separator {
            justify-content: center;
            padding-left: 0;
            padding-right: 0;
            border-right: 1px solid #111827;
        }

        .rich-text-field--document .ql-editor .sop-kop-meta-label:nth-last-child(-n+3),
        .rich-text-field--document .ql-editor .sop-kop-meta-separator:nth-last-child(-n+2),
        .rich-text-field--document .ql-editor .sop-kop-meta-value:last-child,
        .sop-document-preview .sop-kop-meta-label:nth-last-child(-n+3),
        .sop-document-preview .sop-kop-meta-separator:nth-last-child(-n+2),
        .sop-document-preview .sop-kop-meta-value:last-child {
            border-bottom: 0;
        }

        .rich-text-field--document .ql-editor .sop-kop-title,
        .sop-document-preview .sop-kop-title {
            padding: .48rem .75rem;
            text-align: center;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0;
        }

        .rich-text-field--document .ql-editor .sop-kop-title > div:first-child,
        .sop-document-preview .sop-kop-title > div:first-child {
            margin-bottom: .15rem;
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

        .rich-text-field--document .ql-editor img,
        .sop-document-preview img {
            display: block;
            max-width: 100%;
            height: auto;
            margin: .85rem auto;
            border: 1px solid var(--app-border);
            border-radius: 10px;
            background: #fff;
        }
    </style>
@endonce
