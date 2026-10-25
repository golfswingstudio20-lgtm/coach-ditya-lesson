<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Create Booking - {{ \App\Support\Brand::name() }}</title>

    <style>
        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            min-height: 100%;
        }

        body {
            font-family: Inter, Arial, Helvetica, sans-serif;

            color: var(--d-text, #17261d);

            background: var(--d-bg, #f3f1ea);

            overflow-x: hidden;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        button,
        input,
        select,
        textarea {
            font: inherit;
        }


        /* =====================================================
           PAGE
        ====================================================== */

        .page {
            min-height: 100vh;

            background:
                radial-gradient(
                    circle at 70% 10%,
                    rgba(var(--d-green-rgb, 31, 77, 51), 0.08),
                    transparent 30%
                );

            padding-bottom: 40px;
        }


        /* =====================================================
           NAVBAR
        ====================================================== */

        .navbar {
            height: 76px;

            display: flex;
            align-items: center;
            justify-content: space-between;

            padding: 0 38px;

            background:
                var(--d-surface, #ffffff);

            border-bottom:
                1px solid rgba(var(--d-green-rgb, 31, 77, 51), 0.13);

            backdrop-filter: blur(12px);

            position: sticky;
            top: 0;
            z-index: 100;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;

            color: var(--d-text, #17261d);

            font-size: 21px;
            font-weight: 900;

            white-space: nowrap;
        }

        .brand-icon {
            width: 38px;
            height: 38px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 10px;

            background:
                linear-gradient(
                    145deg,
                    var(--d-surface, #ffffff),
                    #55a900
                );

            color: var(--d-text, #17261d);

            font-size: 20px;
            font-weight: 900;
        }

        .brand span {
            color: var(--d-text, #17261d);
        }

        .brand .accent {
            color: var(--d-ink-green-2, #3d7d57);
        }

        .brand .bl3d {
            font-size: 26px;
        }

        .top-right {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .admin-user {
            display: flex;
            align-items: center;
            gap: 9px;
        }

        .admin-avatar {
            width: 36px;
            height: 36px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 50%;

            background: var(--d-btn, #1f4d33);
            color: #ffffff;

            font-size: 13px;
            font-weight: 900;
        }

        .admin-name strong {
            display: block;

            color: var(--d-text, #17261d);

            font-size: 11px;
        }

        .admin-name span {
            display: block;

            margin-top: 2px;

            color: var(--d-muted, #77837b);

            font-size: 9px;
        }


        /* =====================================================
           LAYOUT
        ====================================================== */

        .layout {
            width: min(1420px, 94%);

            margin: 0 auto;

            padding-top: 30px;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;

            margin-bottom: 13px;

            color: var(--d-muted, #77837b);

            font-size: 9px;
        }

        .breadcrumb a {
            color: var(--d-ink-green, #1f4d33);
        }

        .heading {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;

            gap: 20px;

            margin-bottom: 22px;
        }

        .heading-left {
            min-width: 0;
        }

        .eyebrow {
            color: var(--d-ink-green, #1f4d33);

            font-size: 10px;
            font-weight: 900;

            letter-spacing: 2.5px;
            text-transform: uppercase;

            margin-bottom: 6px;
        }

        h1 {
            margin: 0;

            color: var(--d-text, #17261d);
            font-family: 'Playfair Display', Georgia, serif;

            font-size: clamp(27px, 3vw, 40px);

            line-height: 1;

            letter-spacing: -1px;

            font-weight: 900;
        }

        .heading-description {
            max-width: 760px;

            margin-top: 8px;

            color: var(--d-muted, #77837b);

            font-size: 11px;

            line-height: 1.55;
        }

        .back-button {
            min-height: 36px;

            display: inline-flex;
            align-items: center;
            justify-content: center;

            padding: 0 14px;

            border:
                1px solid rgba(var(--d-ink-rgb, 23, 46, 33), 0.09);

            border-radius: 8px;

            color: var(--d-text-2, #3c4a42);

            background:
                rgba(var(--d-ink-rgb, 23, 46, 33), 0.015);

            font-size: 9px;
            font-weight: 700;

            white-space: nowrap;

            transition: .2s ease;
        }

        .back-button:hover {
            color: var(--d-text, #17261d);

            border-color:
                rgba(var(--d-green-rgb, 31, 77, 51), 0.3);
        }


        /* =====================================================
           ALERT
        ====================================================== */

        .alert {
            margin-bottom: 15px;

            padding: 12px 14px;

            border-radius: 10px;

            font-size: 10px;
            font-weight: 700;
        }

        .alert-success {
            border:
                1px solid rgba(130, 215, 148, .30);

            background:
                rgba(75, 157, 94, .10);

            color: var(--d-text-2, #3c4a42);
        }

        .alert-error {
            border:
                1px solid rgba(255, 91, 91, .25);

            background:
                rgba(180, 55, 55, .08);

            color: var(--d-text-2, #3c4a42);
        }

        .alert-error ul {
            margin: 0;
            padding-left: 17px;
        }


        /* =====================================================
           MAIN GRID
        ====================================================== */

        .main-grid {
            display: grid;

            grid-template-columns:
                minmax(0, 1.55fr)
                minmax(280px, .65fr);

            gap: 15px;

            align-items: start;
        }


        /* =====================================================
           CARD
        ====================================================== */

        .card {
            min-width: 0;

            border:
                1px solid rgba(var(--d-ink-rgb, 23, 46, 33), 0.09);

            border-radius: 15px;

            background:
                var(--d-surface, #ffffff);

            box-shadow:
                0 20px 55px rgba(var(--d-shadow-rgb, 23, 46, 33), .12);

            overflow: hidden;

            backdrop-filter: blur(10px);
        }

        .card-header {
            min-height: 60px;

            display: flex;
            align-items: center;
            gap: 11px;

            padding: 0 17px;

            border-bottom:
                1px solid rgba(var(--d-ink-rgb, 23, 46, 33), 0.09);
        }

        .card-icon {
            width: 31px;
            height: 31px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 9px;

            background:
                rgba(145, 240, 0, .11);

            color: var(--d-ink-green, #1f4d33);

            font-size: 14px;
        }

        .card-title strong {
            display: block;

            color: var(--d-text, #17261d);

            font-size: 12px;
        }

        .card-title span {
            display: block;

            margin-top: 3px;

            color: var(--d-muted, #77837b);

            font-size: 8px;
        }

        .card-body {
            padding: 17px;
        }


        /* =====================================================
           CUSTOMER TYPE
        ====================================================== */

        .section-title {
            margin-bottom: 8px;

            color: var(--d-text, #17261d);

            font-size: 10px;
            font-weight: 800;
        }

        .customer-type-grid {
            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 9px;

            margin-bottom: 16px;
        }

        .customer-type-option {
            position: relative;
        }

        .customer-type-option input {
            position: absolute;

            opacity: 0;

            pointer-events: none;
        }

        .customer-type-label {
            min-height: 67px;

            display: flex;
            align-items: center;

            gap: 10px;

            padding: 11px;

            border:
                1px solid rgba(var(--d-ink-rgb, 23, 46, 33), 0.09);

            border-radius: 10px;

            background:
                rgba(var(--d-ink-rgb, 23, 46, 33), 0.011);

            cursor: pointer;

            transition: .18s ease;
        }

        .customer-type-label:hover {
            border-color:
                rgba(var(--d-green-rgb, 31, 77, 51), 0.25);

            background:
                rgba(var(--d-green-rgb, 31, 77, 51), 0.035);
        }

        .customer-type-option input:checked + .customer-type-label {
            border-color:
                rgba(var(--d-green-rgb, 31, 77, 51), 0.42);

            background:
                rgba(var(--d-green-rgb, 31, 77, 51), 0.08);

            box-shadow:
                inset 0 0 0 1px rgba(var(--d-green-rgb, 31, 77, 51), 0.08);
        }

        .type-icon {
            width: 34px;
            height: 34px;

            flex: 0 0 34px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 9px;

            background:
                rgba(var(--d-green-rgb, 31, 77, 51), 0.08);

            color: var(--d-ink-green, #1f4d33);

            font-size: 13px;
            font-weight: 900;
        }

        .type-text {
            min-width: 0;
        }

        .type-text strong {
            display: block;

            color: var(--d-text, #17261d);

            font-size: 10px;
        }

        .type-text span {
            display: block;

            margin-top: 4px;

            color: var(--d-muted, #77837b);

            font-size: 8px;

            line-height: 1.4;
        }


        /* =====================================================
           MEMBER AREA
        ====================================================== */

        .customer-area {
            display: none;
        }

        .customer-area.active {
            display: block;
        }

        .customer-search {
            position: relative;

            margin-bottom: 9px;
        }

        .customer-search input {
            width: 100%;
            height: 39px;

            padding:
                0 12px
                0 35px;

            border:
                1px solid rgba(var(--d-ink-rgb, 23, 46, 33), 0.09);

            border-radius: 9px;

            outline: none;

            background:
                rgba(var(--d-ink-rgb, 23, 46, 33), 0.015);

            color: var(--d-text, #17261d);

            font-size: 10px;
        }

        .customer-search input:focus {
            border-color:
                rgba(var(--d-green-rgb, 31, 77, 51), 0.4);
        }

        .search-icon {
            position: absolute;

            left: 12px;
            top: 50%;

            transform: translateY(-50%);

            color: var(--d-muted, #77837b);

            font-size: 12px;

            pointer-events: none;
        }

        .customer-list {
            max-height: 218px;

            overflow-y: auto;

            border:
                1px solid rgba(var(--d-ink-rgb, 23, 46, 33), 0.09);

            border-radius: 10px;
        }

        .customer-item {
            width: 100%;

            min-height: 54px;

            display: flex;
            align-items: center;

            gap: 9px;

            padding: 8px 10px;

            border: 0;

            border-bottom:
                1px solid rgba(var(--d-ink-rgb, 23, 46, 33), 0.09);

            background:
                rgba(var(--d-ink-rgb, 23, 46, 33), 0.007);

            color: var(--d-text, #17261d);

            text-align: left;

            cursor: pointer;

            transition: .16s ease;
        }

        .customer-item:last-child {
            border-bottom: 0;
        }

        .customer-item:hover {
            background:
                rgba(var(--d-green-rgb, 31, 77, 51), 0.045);
        }

        .customer-item.selected {
            background:
                rgba(var(--d-green-rgb, 31, 77, 51), 0.09);
        }

        .customer-avatar {
            width: 31px;
            height: 31px;

            flex: 0 0 31px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 50%;

            background:
                var(--d-surface, #ffffff);

            color: var(--d-text-2, #3c4a42);

            font-size: 9px;
            font-weight: 900;
        }

        .customer-info {
            min-width: 0;
            flex: 1;
        }

        .customer-info strong {
            display: block;

            overflow: hidden;

            text-overflow: ellipsis;

            white-space: nowrap;

            color: var(--d-text, #17261d);

            font-size: 10px;
        }

        .customer-info span {
            display: block;

            margin-top: 3px;

            overflow: hidden;

            text-overflow: ellipsis;

            white-space: nowrap;

            color: var(--d-muted, #77837b);

            font-size: 8px;
        }

        .customer-check {
            width: 20px;
            height: 20px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 50%;

            color: transparent;

            background:
                rgba(var(--d-ink-rgb, 23, 46, 33), 0.021);

            font-size: 9px;
        }

        .customer-item.selected .customer-check {
            color: var(--d-text, #17261d);

            background:
                var(--d-surface, #ffffff);
        }

        .selected-customer {
            display: none;

            margin-top: 10px;

            padding: 10px 11px;

            border:
                1px solid rgba(var(--d-green-rgb, 31, 77, 51), 0.18);

            border-radius: 10px;

            background:
                rgba(var(--d-green-rgb, 31, 77, 51), 0.055);
        }

        .selected-customer.show {
            display: flex;

            align-items: center;

            gap: 9px;
        }

        .selected-customer-info {
            flex: 1;
            min-width: 0;
        }

        .selected-customer-info strong {
            display: block;

            color: var(--d-text, #17261d);

            font-size: 10px;
        }

        .selected-customer-info span {
            display: block;

            margin-top: 3px;

            color: var(--d-muted, #77837b);

            font-size: 8px;
        }


        /* =====================================================
           OFFLINE CUSTOMER AREA
        ====================================================== */

        .offline-area {
            display: none;
        }

        .offline-area.active {
            display: block;
        }

        .offline-notice {
            margin-bottom: 13px;

            padding: 11px 12px;

            border:
                1px solid rgba(var(--d-green-rgb, 31, 77, 51), 0.14);

            border-radius: 9px;

            background:
                rgba(var(--d-green-rgb, 31, 77, 51), 0.045);

            color: var(--d-muted, #77837b);

            font-size: 8px;

            line-height: 1.55;
        }

        .offline-notice strong {
            color: var(--d-ink-green, #1f4d33);
        }


        /* =====================================================
           FORM
        ====================================================== */

        .booking-section {
            margin-top: 17px;

            padding-top: 17px;

            border-top:
                1px solid rgba(var(--d-ink-rgb, 23, 46, 33), 0.09);
        }

        .form-grid {
            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 12px;
        }

        .form-group {
            min-width: 0;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;

            margin-bottom: 6px;

            color: var(--d-muted, #77837b);

            font-size: 9px;
            font-weight: 750;
        }

        .required {
            color: var(--d-ink-green, #1f4d33);
        }

        .form-control {
            width: 100%;
            height: 39px;

            padding: 0 11px;

            border:
                1px solid rgba(var(--d-ink-rgb, 23, 46, 33), 0.09);

            border-radius: 9px;

            outline: none;

            background:
                rgba(var(--d-ink-rgb, 23, 46, 33), 0.015);

            color: var(--d-text, #17261d);

            font-size: 10px;
        }

        textarea.form-control {
            height: 78px;

            padding-top: 10px;

            resize: vertical;
        }

        .form-control:focus {
            border-color:
                rgba(var(--d-green-rgb, 31, 77, 51), 0.42);
        }

        select.form-control {
            cursor: pointer;
        }

        select.form-control option {
            color: var(--d-text, #111);
            background: white;
        }

        .status-box {
            width: 100%;
            height: 39px;

            display: flex;
            align-items: center;

            gap: 7px;

            padding: 0 11px;

            border:
                1px solid rgba(var(--d-green-rgb, 31, 77, 51), 0.25);

            border-radius: 9px;

            background:
                rgba(var(--d-green-rgb, 31, 77, 51), 0.07);

            color: var(--d-ink-green, #1f4d33);

            font-size: 10px;
            font-weight: 850;
        }

        .status-dot {
            width: 7px;
            height: 7px;

            border-radius: 50%;

            background: var(--d-surface, #ffffff);

            box-shadow:
                0 0 10px rgba(var(--d-green-rgb, 31, 77, 51), 0.45);
        }

        .help-text {
            margin-top: 5px;

            color: var(--d-muted, #77837b);

            font-size: 8px;

            line-height: 1.45;
        }


        /* =====================================================
           ACTIONS
        ====================================================== */

        .actions {
            display: flex;

            justify-content: flex-end;

            gap: 8px;

            margin-top: 16px;

            padding-top: 15px;

            border-top:
                1px solid rgba(var(--d-ink-rgb, 23, 46, 33), 0.09);
        }

        .button {
            min-height: 37px;

            display: inline-flex;
            align-items: center;
            justify-content: center;

            gap: 7px;

            padding: 0 14px;

            border-radius: 8px;

            font-size: 9px;
            font-weight: 850;

            cursor: pointer;

            transition: .18s ease;
        }

        .button-secondary {
            border:
                1px solid rgba(var(--d-ink-rgb, 23, 46, 33), 0.09);

            background:
                rgba(var(--d-ink-rgb, 23, 46, 33), 0.015);

            color: var(--d-muted, #77837b);
        }

        .button-secondary:hover {
            color: var(--d-text, #17261d);

            background:
                rgba(var(--d-ink-rgb, 23, 46, 33), 0.033);
        }

        .button-primary {
            border: 0;

            background:
                var(--d-btn, #1f4d33);

            color: #ffffff;

            box-shadow:
                0 8px 22px rgba(var(--d-green-rgb, 31, 77, 51), 0.12);
        }

        .button-primary:hover {
            background:
                var(--d-btn-hover, #2a6444);

            transform:
                translateY(-1px);
        }

        .button-primary:disabled {
            opacity: .45;

            cursor: not-allowed;

            transform: none;
        }


        /* =====================================================
           RIGHT INFO
        ====================================================== */

        .info-column {
            min-width: 0;
        }

        .info-card {
            padding: 15px;
        }

        .info-title {
            display: flex;
            align-items: center;
            gap: 8px;

            margin-bottom: 14px;

            color: var(--d-text, #17261d);

            font-size: 12px;
            font-weight: 800;
        }

        .info-icon {
            width: 28px;
            height: 28px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 8px;

            background:
                rgba(var(--d-green-rgb, 31, 77, 51), 0.1);

            color: var(--d-ink-green, #1f4d33);
        }

        .info-item {
            display: flex;

            gap: 10px;

            padding: 11px 0;

            border-bottom:
                1px solid rgba(var(--d-ink-rgb, 23, 46, 33), 0.09);
        }

        .info-item:last-child {
            border-bottom: 0;
        }

        .info-item-icon {
            width: 28px;
            height: 28px;

            flex: 0 0 28px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 50%;

            background:
                rgba(var(--d-green-rgb, 31, 77, 51), 0.07);

            color: var(--d-ink-green, #1f4d33);

            font-size: 10px;
        }

        .info-item-text strong {
            display: block;

            color: var(--d-text, #17261d);

            font-size: 9px;
        }

        .info-item-text span {
            display: block;

            margin-top: 4px;

            color: var(--d-muted, #77837b);

            font-size: 8px;

            line-height: 1.5;
        }


        /* =====================================================
           PREVIEW
        ====================================================== */

        .preview-card {
            margin-top: 14px;

            padding: 15px;
        }

        .preview-title {
            color: var(--d-muted, #77837b);

            font-size: 9px;
            font-weight: 800;

            letter-spacing: .8px;
            text-transform: uppercase;

            margin-bottom: 11px;
        }

        .preview-status {
            display: flex;
            align-items: center;
            justify-content: space-between;

            padding: 10px;

            border-radius: 9px;

            background:
                rgba(var(--d-green-rgb, 31, 77, 51), 0.05);

            border:
                1px solid rgba(var(--d-green-rgb, 31, 77, 51), 0.12);
        }

        .preview-status span:first-child {
            color: var(--d-muted, #77837b);

            font-size: 9px;
        }

        .preview-status strong {
            color: var(--d-ink-green, #1f4d33);

            font-size: 9px;
        }

        .preview-row {
            display: flex;
            align-items: center;
            justify-content: space-between;

            min-height: 36px;

            gap: 10px;

            border-bottom:
                1px solid rgba(var(--d-ink-rgb, 23, 46, 33), 0.09);
        }

        .preview-row:last-child {
            border-bottom: 0;
        }

        .preview-row span {
            color: var(--d-muted, #77837b);

            font-size: 8px;
        }

        .preview-row strong {
            color: var(--d-text, #17261d);

            font-size: 9px;

            text-align: right;

            max-width: 65%;

            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }


        /* =====================================================
           RESPONSIVE
        ====================================================== */

        @media (max-width: 900px) {

            .main-grid {
                grid-template-columns: 1fr;
            }

            .info-column {
                display: grid;

                grid-template-columns:
                    repeat(2, minmax(0, 1fr));

                gap: 14px;

                align-items: start;
            }

            .preview-card {
                margin-top: 0;
            }
        }

        @media (max-width: 650px) {

            .navbar {
                height: auto;

                min-height: 68px;

                padding: 12px 15px;
            }

            .admin-name {
                display: none;
            }

            .layout {
                width: 94%;

                padding-top: 22px;
            }

            .heading {
                align-items: flex-start;

                flex-direction: column;
            }

            .customer-type-grid {
                grid-template-columns: 1fr;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full {
                grid-column: auto;
            }

            .info-column {
                grid-template-columns: 1fr;
            }

            .actions {
                flex-direction: column-reverse;
            }

            .button {
                width: 100%;
            }
        }
    </style>
    @include('partials.brand-head')
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap">
    <link rel="stylesheet" href="{{ asset('css/fw.css') }}?v={{ substr(md5((string) @filemtime(public_path('css/fw.css'))), 0, 8) }}">
    @include('partials.theme-script')
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600;700&family=Lilita+One&display=swap">
    <link rel="stylesheet" href="{{ asset('css/cdl-admin.css') }}?v={{ substr(md5((string) @filemtime(public_path('css/cdl-admin.css'))), 0, 8) }}">
</head>


<body class="fw-admin cdl cdl-offline">

<div class="page">


    {{-- =====================================================
         NAVBAR
    ====================================================== --}}

    <nav class="navbar">

        <a
            href="{{ route('admin.dashboard') }}"
            class="brand"
        >

            @include('partials.brand-logo', ['accentClass' => 'accent'])

        </a>


        <div class="top-right">
            <button type="button" class="fw-theme-btn" data-theme-toggle aria-label="Ganti mode terang/gelap" title="Mode terang/gelap">{!! \App\Support\Icons::svg('moon', 'i-moon') !!}{!! \App\Support\Icons::svg('sun', 'i-sun') !!}</button>

            <div class="admin-user">

                <div class="admin-avatar">
                    {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                </div>

                <div class="admin-name">

                    <strong>
                        {{ auth()->user()->name ?? 'Administrator' }}
                    </strong>

                    <span>
                        Administrator
                    </span>

                </div>

            </div>

        </div>

    </nav>


    <div class="layout">


        {{-- =================================================
             BREADCRUMB
        ================================================== --}}

        <div class="breadcrumb">

            <a href="{{ route('admin.dashboard') }}">
                Dashboard
            </a>

            <span>›</span>

            <span>
                Create Booking
            </span>

        </div>


        {{-- =================================================
             HEADING
        ================================================== --}}

        <div class="heading">

            <div class="heading-left">

                <div class="eyebrow">
                    Booking Admin
                </div>

                <h1>
                    Create Booking
                </h1>

                <div class="heading-description">
                    Admin dapat membuat booking untuk
                    <strong style="color:var(--d-ink-green, #1f4d33);">Member</strong>
                    yang sudah memiliki akun atau
                    <strong style="color:var(--d-ink-green, #1f4d33);">Customer Offline</strong>
                    yang belum memiliki akun.
                    Booking Admin langsung berstatus
                    <strong style="color:var(--d-ink-green, #1f4d33);">BOOKED</strong>.
                </div>

            </div>


            <a
                href="{{ route('admin.dashboard') }}"
                class="back-button"
            >
                ← Kembali ke Dashboard
            </a>

        </div>


        {{-- =================================================
             SUCCESS
        ================================================== --}}

        @if (session('success'))

            <div class="alert alert-success">
                ✓ {{ session('success') }}
            </div>

        @endif


        {{-- =================================================
             ERRORS
        ================================================== --}}

        @if ($errors->any())

            <div class="alert alert-error">

                <ul>

                    @foreach ($errors->all() as $error)

                        <li>
                            {{ $error }}
                        </li>

                    @endforeach

                </ul>

            </div>

        @endif


        {{-- =================================================
             MAIN GRID
        ================================================== --}}

        <div class="main-grid">


            {{-- =================================================
                 FORM CARD
            ================================================== --}}

            <div class="card">

                <div class="card-header">

                    <div class="card-icon">
                        ＋
                    </div>

                    <div class="card-title">

                        <strong>
                            Booking Baru
                        </strong>

                        <span>
                            Pilih jenis customer lalu isi detail booking
                        </span>

                    </div>

                </div>


                <div class="card-body">


                    {{-- =================================================
                         FORM
                    ================================================== --}}

                    <form
                        method="POST"
                        action="{{ route('admin.offline-booking.store') }}"
                        id="offlineBookingForm"
                    >

                        @csrf


                        {{-- =================================================
                             CUSTOMER TYPE
                        ================================================== --}}

                        <div class="section-title">
                            Jenis Customer
                        </div>


                        <div class="customer-type-grid">

                            <div class="customer-type-option">

                                <input
                                    type="radio"
                                    name="customer_type"
                                    id="customerTypeMember"
                                    value="member"
                                    {{ old('customer_type', 'member') === 'member' ? 'checked' : '' }}
                                >

                                <label
                                    for="customerTypeMember"
                                    class="customer-type-label"
                                >

                                    <div class="type-icon">
                                        ◉
                                    </div>

                                    <div class="type-text">

                                        <strong>
                                            Member Terdaftar
                                        </strong>

                                        <span>
                                            Customer sudah memiliki akun.
                                        </span>

                                    </div>

                                </label>

                            </div>


                            <div class="customer-type-option">

                                <input
                                    type="radio"
                                    name="customer_type"
                                    id="customerTypeOffline"
                                    value="offline"
                                    {{ old('customer_type') === 'offline' ? 'checked' : '' }}
                                >

                                <label
                                    for="customerTypeOffline"
                                    class="customer-type-label"
                                >

                                    <div class="type-icon">
                                        +
                                    </div>

                                    <div class="type-text">

                                        <strong>
                                            Customer Offline
                                        </strong>

                                        <span>
                                            Belum memiliki akun/member.
                                        </span>

                                    </div>

                                </label>

                            </div>

                        </div>


                        {{-- =================================================
                             MEMBER CUSTOMER
                        ================================================== --}}

                        <div
                            class="customer-area"
                            id="memberCustomerArea"
                        >

                            <div class="section-title">
                                Pilih Member
                            </div>


                            <div class="customer-search">

                                <span class="search-icon">
                                    ⌕
                                </span>

                                <input
                                    type="text"
                                    id="customerSearch"
                                    placeholder="Cari nama atau email member..."
                                    autocomplete="off"
                                >

                            </div>


                            <div
                                class="customer-list"
                                id="customerList"
                            >

                                @forelse ($customers as $customer)

                                    @php
                                        $customerName = trim($customer->name ?? 'Customer');

                                        $customerInitial = strtoupper(
                                            substr($customerName, 0, 1)
                                        );
                                    @endphp

                                    <button
                                        type="button"
                                        class="customer-item"
                                        data-id="{{ $customer->id }}"
                                        data-name="{{ strtolower($customerName) }}"
                                        data-email="{{ strtolower($customer->email ?? '') }}"
                                        data-display-name="{{ $customerName }}"
                                        data-display-email="{{ $customer->email ?? '' }}"
                                    >

                                        <div class="customer-avatar">
                                            {{ $customerInitial }}
                                        </div>

                                        <div class="customer-info">

                                            <strong>
                                                {{ $customerName }}
                                            </strong>

                                            <span>
                                                {{ $customer->email ?: 'Email belum tersedia' }}
                                            </span>

                                        </div>

                                        <div class="customer-check">
                                            ✓
                                        </div>

                                    </button>

                                @empty

                                    <div
                                        style="
                                            padding:20px;
                                            color:#6f7f76;
                                            text-align:center;
                                            font-size:9px;
                                        "
                                    >
                                        Belum ada member terdaftar.
                                    </div>

                                @endforelse

                            </div>


                            <input
                                type="hidden"
                                name="user_id"
                                id="userId"
                                value="{{ old('user_id') }}"
                            >


                            <div
                                class="selected-customer"
                                id="selectedCustomerBox"
                            >

                                <div
                                    class="customer-avatar"
                                    id="selectedCustomerInitial"
                                >
                                    ?
                                </div>

                                <div class="selected-customer-info">

                                    <strong id="selectedCustomerName">
                                        Member
                                    </strong>

                                    <span id="selectedCustomerEmail">
                                        -
                                    </span>

                                </div>

                            </div>

                        </div>


                        {{-- =================================================
                             OFFLINE CUSTOMER
                        ================================================== --}}

                        <div
                            class="offline-area"
                            id="offlineCustomerArea"
                        >

                            <div class="section-title">
                                Data Customer Offline
                            </div>


                            <div class="offline-notice">

                                <strong>Customer offline tidak dibuatkan akun.</strong>
                                Data hanya disimpan pada booking ini.
                                Jika customer ingin menjadi member nanti,
                                akun dapat dibuat melalui proses registrasi terpisah.

                            </div>


                            <div class="form-grid">

                                <div class="form-group">

                                    <label for="offline_customer_name">

                                        Nama Customer

                                        <span class="required">
                                            *
                                        </span>

                                    </label>

                                    <input
                                        type="text"
                                        id="offline_customer_name"
                                        name="offline_customer_name"
                                        class="form-control"
                                        value="{{ old('offline_customer_name') }}"
                                        maxlength="255"
                                        placeholder="Nama customer"
                                    >

                                </div>


                                <div class="form-group">

                                    <label for="offline_customer_phone">

                                        Nomor HP

                                        <span class="required">
                                            *
                                        </span>

                                    </label>

                                    <input
                                        type="text"
                                        id="offline_customer_phone"
                                        name="offline_customer_phone"
                                        class="form-control"
                                        value="{{ old('offline_customer_phone') }}"
                                        maxlength="30"
                                        placeholder="08xxxxxxxxxx"
                                    >

                                </div>


                                <div class="form-group full">

                                    <label for="offline_customer_email">

                                        Email

                                        <span style="color:var(--d-muted, #586960);">
                                            (opsional)
                                        </span>

                                    </label>

                                    <input
                                        type="email"
                                        id="offline_customer_email"
                                        name="offline_customer_email"
                                        class="form-control"
                                        value="{{ old('offline_customer_email') }}"
                                        maxlength="255"
                                        placeholder="email@example.com"
                                    >

                                </div>

                            </div>

                        </div>


                        {{-- =================================================
                             BOOKING DETAIL
                        ================================================== --}}

                        <div class="booking-section">

                            <div class="section-title">
                                Detail Booking
                            </div>


                            <div class="form-grid">


                                {{-- JENIS LESSON & LAPANGAN (konsep sama dengan halaman booking customer) --}}

                                @php
                                    $offlineLocations   = collect($offlineLocations ?? []);
                                    $offlineLessonTypes = \App\Support\BookingRules::lessonTypes();
                                    $offlineLesson      = old('lesson_type', 'driving');
                                    $offlineLocation    = (string) old('location_id', $offlineLocations->first()?->id);
                                    $obRp               = fn ($n) => 'Rp' . number_format((int) $n, 0, ',', '.');
                                    $obCourseStart      = \App\Support\BookingRules::courseStart();
                                    $obCourseEnd        = \App\Support\BookingRules::courseEnd();
                                @endphp

                                <style>
                                    .ob-options { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 10px; }
                                    .ob-option input { position: absolute; opacity: 0; pointer-events: none; }
                                    .ob-card {
                                        height: 100%; display: flex; flex-direction: column; gap: 5px; padding: 13px 14px;
                                        border: 1px solid rgba(var(--d-ink-rgb, 23, 46, 33), 0.12); border-radius: 12px; background: rgba(var(--d-ink-rgb, 23, 46, 33), 0.018); cursor: pointer;
                                    }
                                    .ob-card strong { font-size: 14px; }
                                    .ob-price { color: var(--d-ink-green, #1f4d33); font-size: 18px; font-weight: 900; }
                                    .ob-price small, .ob-card small { color: var(--d-text-2, #3c4a42); font-size: 11px; font-weight: 600; }
                                    .ob-note { color: var(--d-muted, #77837b) !important; font-weight: 700 !important; }
                                    .ob-option:hover .ob-card { border-color: rgba(var(--d-green-rgb, 31, 77, 51), 0.4); }
                                    .ob-option input:checked + .ob-card { border-color: rgba(var(--d-ink-rgb, 23, 46, 33),.12); background: rgba(var(--d-green-rgb, 31, 77, 51), 0.08); box-shadow: inset 0 0 0 1px #e3e6df; }
                                    .ob-option input:focus-visible + .ob-card { outline: 2px solid var(--d-line-solid, #e3e6df); outline-offset: 2px; }
                                    .ob-place { flex-direction: row; align-items: center; gap: 10px; }
                                    .ob-pin { color: var(--d-ink-green, #1f4d33); font-size: 16px; }
                                    .ob-total {
                                        display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap;
                                        padding: 13px 15px; border-radius: 12px; border: 1px solid rgba(var(--d-green-rgb, 31, 77, 51), 0.25); background: rgba(var(--d-green-rgb, 31, 77, 51), 0.06);
                                    }
                                    .ob-total strong { color: var(--d-ink-green, #1f4d33); font-size: 18px; }
                                    .ob-total small { color: var(--d-text-2, #3c4a42); font-size: 11px; }
                                </style>

                                <div class="form-group full">
                                    <label>Jenis Lesson <span class="required">*</span></label>

                                    <div class="ob-options">
                                        @foreach ($offlineLessonTypes as $typeKey => $typeLabel)
                                            <label class="ob-option">
                                                <input type="radio" name="lesson_type" value="{{ $typeKey }}" @checked($offlineLesson === $typeKey)>
                                                <span class="ob-card">
                                                    <strong>{{ $typeLabel }}</strong>
                                                    <span class="ob-price">
                                                        {{ $typeKey === 'course' ? $obRp(\App\Support\BookingRules::coursePrice()) : $obRp(\App\Models\Payment::pricePerHour()) }}
                                                        <small>{{ $typeKey === 'course' ? '/ sesi' : '/ jam' }}</small>
                                                    </span>
                                                    @if ($typeKey === 'course')
                                                        <small>Jam otomatis {{ $obCourseStart }} – {{ $obCourseEnd }}, lapangan golf pilihan customer</small>
                                                        @if (\App\Support\BookingRules::courseNote())
                                                            <small class="ob-note">* {{ \App\Support\BookingRules::courseNote() }}</small>
                                                        @endif
                                                    @else
                                                        <small>Latihan di driving range, per jam bersama coach</small>
                                                    @endif
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>

                                @if ($offlineLocations->isNotEmpty())
                                    <div class="form-group full" id="locationGroup">
                                        <label>Lapangan Driving Range <span class="required">*</span></label>

                                        <div class="ob-options">
                                            @foreach ($offlineLocations as $location)
                                                <label class="ob-option">
                                                    <input type="radio" name="location_id" value="{{ $location->id }}" @checked($offlineLocation === (string) $location->id)>
                                                    <span class="ob-card ob-place">
                                                        <span class="ob-pin">◉</span>
                                                        <span>
                                                            <strong>{{ $location->name }}</strong>
                                                            @if ($location->area)
                                                                <small style="display: block">{{ $location->area }}</small>
                                                            @endif
                                                        </span>
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <div class="form-group full" id="venueGroup">
                                    <label for="course_venue">Lapangan Golf (pilihan customer) <span class="required">*</span></label>

                                    <input type="text" id="course_venue" name="course_venue" class="form-control" maxlength="150"
                                           value="{{ old('course_venue') }}" placeholder="Contoh: Padang Golf Pondok Indah">

                                    <div class="help-text">Tulis lapangan golf yang diminta customer untuk Course Lesson.</div>
                                </div>

                                <div class="form-group full">
                                    <div class="ob-total">
                                        <span>Estimasi harga: <strong id="obTotal">-</strong></span>
                                        <small>Booking offline dibayar langsung di tempat.</small>
                                    </div>
                                </div>

                                <script>
                                    document.addEventListener('DOMContentLoaded', function () {
                                        var start = document.getElementById('start_time');
                                        var end   = document.getElementById('end_time');
                                        var place = document.getElementById('locationGroup');
                                        var venue = document.getElementById('venueGroup');
                                        var venueInput = document.getElementById('course_venue');
                                        var total = document.getElementById('obTotal');
                                        var courseStart = @json($obCourseStart);
                                        var courseEnd   = @json($obCourseEnd);
                                        var pricePerHour = {{ (int) \App\Models\Payment::pricePerHour() }};
                                        var coursePrice  = {{ (int) \App\Support\BookingRules::coursePrice() }};

                                        if (!start || !end) return;

                                        function lesson() {
                                            var checked = document.querySelector('input[name="lesson_type"]:checked');
                                            return checked ? checked.value : 'driving';
                                        }

                                        function minutes(value) {
                                            var p = (value || '').split(':').map(Number);
                                            return p.length === 2 ? p[0] * 60 + p[1] : NaN;
                                        }

                                        function rupiah(n) {
                                            return 'Rp' + Number(n).toLocaleString('id-ID');
                                        }

                                        function sync() {
                                            var course = lesson() === 'course';

                                            if (course) {
                                                start.value = courseStart;
                                                end.value = courseEnd;
                                            }

                                            start.readOnly = end.readOnly = course;
                                            start.style.opacity = end.style.opacity = course ? '.6' : '';
                                            if (place) place.style.display = course ? 'none' : '';
                                            venue.style.display = course ? '' : 'none';
                                            venueInput.required = course;

                                            updateTotal();
                                        }

                                        function updateTotal() {
                                            if (lesson() === 'course') {
                                                total.textContent = rupiah(coursePrice) + ' / sesi';
                                                return;
                                            }

                                            var diff = minutes(end.value) - minutes(start.value);
                                            total.textContent = diff > 0 ? rupiah(Math.round(diff / 60 * pricePerHour)) : '-';
                                        }

                                        document.querySelectorAll('input[name="lesson_type"]').forEach(function (radio) {
                                            radio.addEventListener('change', sync);
                                        });

                                        start.addEventListener('change', updateTotal);
                                        end.addEventListener('change', updateTotal);
                                        sync();
                                    });
                                </script>


                                {{-- TANGGAL --}}

                                <div class="form-group">

                                    <label for="booking_date">

                                        Tanggal

                                        <span class="required">
                                            *
                                        </span>

                                    </label>

                                    <input
                                        type="date"
                                        id="booking_date"
                                        name="booking_date"
                                        class="form-control"
                                        value="{{ old('booking_date') }}"
                                        min="{{ now()->format('Y-m-d') }}"
                                        required
                                    >

                                </div>


                                {{-- START --}}

                                <div class="form-group">

                                    <label for="start_time">

                                        Jam Mulai

                                        <span class="required">
                                            *
                                        </span>

                                    </label>

                                    <input
                                        type="time"
                                        id="start_time"
                                        name="start_time"
                                        class="form-control"
                                        value="{{ old('start_time', '08:00') }}"
                                        min="07:00"
                                        max="19:59"
                                        required
                                    >

                                    <div class="help-text">
                                        Jam operasional mulai 07:00.
                                    </div>

                                </div>


                                {{-- END --}}

                                <div class="form-group">

                                    <label for="end_time">

                                        Jam Selesai

                                        <span class="required">
                                            *
                                        </span>

                                    </label>

                                    <input
                                        type="time"
                                        id="end_time"
                                        name="end_time"
                                        class="form-control"
                                        value="{{ old('end_time', '09:00') }}"
                                        min="07:01"
                                        max="20:00"
                                        required
                                    >

                                    <div class="help-text">
                                        Durasi booking minimal 30 menit.
                                    </div>

                                </div>


                                {{-- STATUS --}}

                                <div class="form-group">

                                    <label>
                                        Status
                                    </label>

                                    <div class="status-box">

                                        <span class="status-dot"></span>

                                        <span>
                                            BOOKED
                                        </span>

                                    </div>

                                    <div class="help-text">
                                        Booking Admin langsung dikonfirmasi.
                                    </div>

                                </div>


                                {{-- SOURCE --}}

                                <div class="form-group">

                                    <label>
                                        Source
                                    </label>

                                    <div class="status-box">

                                        <span class="status-dot"></span>

                                        <span>
                                            OFFLINE
                                        </span>

                                    </div>

                                    <div class="help-text">
                                        Booking dibuat langsung oleh Admin.
                                    </div>

                                </div>


                                {{-- ADMIN NOTES --}}

                                <div class="form-group full">

                                    <label for="admin_notes">
                                        Catatan Admin
                                    </label>

                                    <textarea
                                        id="admin_notes"
                                        name="admin_notes"
                                        class="form-control"
                                        maxlength="1000"
                                        placeholder="Tambahkan catatan booking jika diperlukan..."
                                    >{{ old('admin_notes') }}</textarea>

                                    <div class="help-text">
                                        Maksimal 1000 karakter.
                                    </div>

                                </div>

                            </div>


                            {{-- =================================================
                                 ACTIONS
                            ================================================== --}}

                            <div class="actions">

                                <a
                                    href="{{ route('admin.dashboard') }}"
                                    class="button button-secondary"
                                >
                                    Batal
                                </a>


                                <button
                                    type="submit"
                                    class="button button-primary"
                                    id="submitButton"
                                >
                                    ✓
                                    Simpan Booking
                                </button>

                            </div>

                        </div>


                    </form>

                </div>

            </div>


            {{-- =================================================
                 RIGHT COLUMN
            ================================================== --}}

            <div class="info-column">


                {{-- INFO --}}

                <div class="card info-card">

                    <div class="info-title">

                        <div class="info-icon">
                            i
                        </div>

                        Informasi Booking

                    </div>


                    <div class="info-item">

                        <div class="info-item-icon">
                            ◉
                        </div>

                        <div class="info-item-text">

                            <strong>
                                Member Terdaftar
                            </strong>

                            <span>
                                Pilih member yang sudah memiliki akun.
                                Booking akan tersimpan menggunakan
                                user_id member tersebut.
                            </span>

                        </div>

                    </div>


                    <div class="info-item">

                        <div class="info-item-icon">
                            +
                        </div>

                        <div class="info-item-text">

                            <strong>
                                Customer Offline
                            </strong>

                            <span>
                                Tidak perlu membuat akun.
                                Nama, nomor HP, dan email disimpan langsung
                                pada data booking.
                            </span>

                        </div>

                    </div>


                    <div class="info-item">

                        <div class="info-item-icon">
                            ✓
                        </div>

                        <div class="info-item-text">

                            <strong>
                                Status langsung BOOKED
                            </strong>

                            <span>
                                Booking yang dibuat Admin tidak masuk Pending.
                                Status langsung menjadi Booked.
                            </span>

                        </div>

                    </div>


                    <div class="info-item">

                        <div class="info-item-icon">
                            ◷
                        </div>

                        <div class="info-item-text">

                            <strong>
                                Jadwal tetap dicek
                            </strong>

                            <span>
                                Sistem akan menolak booking jika jadwal
                                bentrok dengan booking aktif lainnya.
                            </span>

                        </div>

                    </div>

                </div>


                {{-- PREVIEW --}}

                <div class="card preview-card">

                    <div class="preview-title">
                        Preview Booking
                    </div>


                    <div class="preview-status">

                        <span>
                            Status
                        </span>

                        <strong>
                            ● BOOKED
                        </strong>

                    </div>


                    <div class="preview-row">

                        <span>
                            Customer
                        </span>

                        <strong id="previewCustomer">
                            Belum dipilih
                        </strong>

                    </div>


                    <div class="preview-row">

                        <span>
                            Tanggal
                        </span>

                        <strong id="previewDate">
                            -
                        </strong>

                    </div>


                    <div class="preview-row">

                        <span>
                            Jam
                        </span>

                        <strong id="previewTime">
                            08:00 - 09:00
                        </strong>

                    </div>


                    <div class="preview-row">

                        <span>
                            Source
                        </span>

                        <strong>
                            Offline
                        </strong>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


<script>
    (function () {

        'use strict';


        /* =====================================================
           ELEMENTS
        ====================================================== */

        const form =
            document.getElementById('offlineBookingForm');

        const submitButton =
            document.getElementById('submitButton');


        const memberRadio =
            document.getElementById('customerTypeMember');

        const offlineRadio =
            document.getElementById('customerTypeOffline');


        const memberArea =
            document.getElementById('memberCustomerArea');

        const offlineArea =
            document.getElementById('offlineCustomerArea');


        const customerSearch =
            document.getElementById('customerSearch');

        const customerItems =
            Array.from(
                document.querySelectorAll('.customer-item')
            );


        const userId =
            document.getElementById('userId');


        const selectedCustomerBox =
            document.getElementById('selectedCustomerBox');

        const selectedCustomerInitial =
            document.getElementById('selectedCustomerInitial');

        const selectedCustomerName =
            document.getElementById('selectedCustomerName');

        const selectedCustomerEmail =
            document.getElementById('selectedCustomerEmail');


        const offlineName =
            document.getElementById('offline_customer_name');

        const offlinePhone =
            document.getElementById('offline_customer_phone');

        const offlineEmail =
            document.getElementById('offline_customer_email');


        const bookingDate =
            document.getElementById('booking_date');

        const startTime =
            document.getElementById('start_time');

        const endTime =
            document.getElementById('end_time');


        const adminNotes =
            document.getElementById('admin_notes');


        const previewCustomer =
            document.getElementById('previewCustomer');

        const previewDate =
            document.getElementById('previewDate');

        const previewTime =
            document.getElementById('previewTime');


        /* =====================================================
           CUSTOMER TYPE
        ====================================================== */

        function updateCustomerType() {

            const selectedType =
                document.querySelector(
                    'input[name="customer_type"]:checked'
                )?.value || 'member';


            if (selectedType === 'member') {

                memberArea.classList.add('active');

                offlineArea.classList.remove('active');

                userId.disabled = false;

            } else {

                memberArea.classList.remove('active');

                offlineArea.classList.add('active');

                userId.value = '';

                customerItems.forEach(function (item) {
                    item.classList.remove('selected');
                });

                selectedCustomerBox.classList.remove('show');

            }


            updateCustomerPreview();

        }


        if (memberRadio) {

            memberRadio.addEventListener(
                'change',
                updateCustomerType
            );

        }


        if (offlineRadio) {

            offlineRadio.addEventListener(
                'change',
                updateCustomerType
            );

        }


        /* =====================================================
           CUSTOMER SEARCH
        ====================================================== */

        function filterCustomers() {

            if (!customerSearch) {
                return;
            }

            const keyword =
                customerSearch.value
                    .trim()
                    .toLowerCase();


            customerItems.forEach(function (item) {

                const name =
                    item.dataset.name || '';

                const email =
                    item.dataset.email || '';


                const matched =
                    name.includes(keyword) ||
                    email.includes(keyword);


                item.style.display =
                    matched ? 'flex' : 'none';

            });

        }


        if (customerSearch) {

            customerSearch.addEventListener(
                'input',
                filterCustomers
            );

        }


        /* =====================================================
           MEMBER SELECT
        ====================================================== */

        function selectMember(item) {

            if (!item) {
                return;
            }


            customerItems.forEach(function (other) {

                other.classList.remove('selected');

            });


            item.classList.add('selected');


            const id =
                item.dataset.id || '';

            const name =
                item.dataset.displayName || 'Member';

            const email =
                item.dataset.displayEmail || '-';


            userId.value =
                id;


            selectedCustomerInitial.textContent =
                name
                    .trim()
                    .charAt(0)
                    .toUpperCase();


            selectedCustomerName.textContent =
                name;


            selectedCustomerEmail.textContent =
                email || 'Email belum tersedia';


            selectedCustomerBox.classList.add('show');


            updateCustomerPreview();

        }


        customerItems.forEach(function (item) {

            item.addEventListener(
                'click',
                function () {

                    selectMember(item);

                }
            );

        });


        /* =====================================================
           CUSTOMER PREVIEW
        ====================================================== */

        function updateCustomerPreview() {

            const selectedType =
                document.querySelector(
                    'input[name="customer_type"]:checked'
                )?.value || 'member';


            if (selectedType === 'member') {

                const selectedItem =
                    customerItems.find(function (item) {

                        return (
                            item.dataset.id ===
                            userId.value
                        );

                    });


                if (selectedItem) {

                    previewCustomer.textContent =
                        selectedItem.dataset.displayName ||
                        'Member';

                } else {

                    previewCustomer.textContent =
                        'Belum dipilih';

                }

                return;
            }


            const name =
                offlineName?.value.trim() || '';


            if (name) {

                previewCustomer.textContent =
                    name;

            } else {

                previewCustomer.textContent =
                    'Customer Offline';

            }

        }


        if (offlineName) {

            offlineName.addEventListener(
                'input',
                updateCustomerPreview
            );

        }


        if (offlinePhone) {

            offlinePhone.addEventListener(
                'input',
                updateCustomerPreview
            );

        }


        if (offlineEmail) {

            offlineEmail.addEventListener(
                'input',
                updateCustomerPreview
            );

        }


        /* =====================================================
           DATE PREVIEW
        ====================================================== */

        function updateDatePreview() {

            if (!bookingDate || !previewDate) {
                return;
            }


            if (!bookingDate.value) {

                previewDate.textContent =
                    '-';

                return;
            }


            const parts =
                bookingDate.value.split('-');


            if (parts.length !== 3) {

                previewDate.textContent =
                    bookingDate.value;

                return;
            }


            previewDate.textContent =
                `${parts[2]}/${parts[1]}/${parts[0]}`;

        }


        if (bookingDate) {

            bookingDate.addEventListener(
                'change',
                updateDatePreview
            );

        }


        /* =====================================================
           TIME PREVIEW
        ====================================================== */

        function updateTimePreview() {

            if (!previewTime) {
                return;
            }


            const start =
                startTime?.value || '--:--';

            const end =
                endTime?.value || '--:--';


            previewTime.textContent =
                `${start} - ${end}`;

        }


        if (startTime) {

            startTime.addEventListener(
                'change',
                updateTimePreview
            );

        }


        if (endTime) {

            endTime.addEventListener(
                'change',
                updateTimePreview
            );

        }


        /* =====================================================
           OLD INPUT
        ====================================================== */

        const oldUserId =
            userId?.value || '';


        if (oldUserId) {

            const oldCustomer =
                customerItems.find(function (item) {

                    return (
                        item.dataset.id ===
                        oldUserId
                    );

                });


            if (oldCustomer) {

                selectMember(oldCustomer);

            }

        }


        /* =====================================================
           FORM VALIDATION
        ====================================================== */

        if (form) {

            form.addEventListener(
                'submit',
                function (event) {

                    const selectedType =
                        document.querySelector(
                            'input[name="customer_type"]:checked'
                        )?.value;


                    if (!selectedType) {

                        event.preventDefault();

                        alert(
                            'Silakan pilih jenis customer.'
                        );

                        return;

                    }


                    /* =========================================
                       MEMBER
                    ========================================== */

                    if (
                        selectedType === 'member' &&
                        !userId.value
                    ) {

                        event.preventDefault();

                        alert(
                            'Silakan pilih member terlebih dahulu.'
                        );

                        return;

                    }


                    /* =========================================
                       OFFLINE
                    ========================================== */

                    if (
                        selectedType === 'offline'
                    ) {

                        if (
                            !offlineName.value.trim()
                        ) {

                            event.preventDefault();

                            alert(
                                'Nama customer wajib diisi.'
                            );

                            offlineName.focus();

                            return;

                        }


                        if (
                            !offlinePhone.value.trim()
                        ) {

                            event.preventDefault();

                            alert(
                                'Nomor HP customer wajib diisi.'
                            );

                            offlinePhone.focus();

                            return;

                        }

                    }


                    /* =========================================
                       DATE
                    ========================================== */

                    if (
                        !bookingDate.value
                    ) {

                        event.preventDefault();

                        alert(
                            'Silakan pilih tanggal booking.'
                        );

                        bookingDate.focus();

                        return;

                    }


                    /* =========================================
                       TIME
                    ========================================== */

                    if (
                        !startTime.value ||
                        !endTime.value
                    ) {

                        event.preventDefault();

                        alert(
                            'Silakan isi jam mulai dan jam selesai.'
                        );

                        return;

                    }


                    /* =========================================
                       TIME CALCULATION
                    ========================================== */

                    const startParts =
                        startTime.value.split(':');

                    const endParts =
                        endTime.value.split(':');


                    const startMinutes =
                        (
                            parseInt(startParts[0], 10) * 60
                        ) +
                        parseInt(startParts[1], 10);


                    const endMinutes =
                        (
                            parseInt(endParts[0], 10) * 60
                        ) +
                        parseInt(endParts[1], 10);


                    if (
                        endMinutes <=
                        startMinutes
                    ) {

                        event.preventDefault();

                        alert(
                            'Jam selesai harus lebih besar dari jam mulai.'
                        );

                        endTime.focus();

                        return;

                    }


                    if (
                        (endMinutes - startMinutes) < 30
                    ) {

                        event.preventDefault();

                        alert(
                            'Durasi booking minimal 30 menit.'
                        );

                        endTime.focus();

                        return;

                    }


                    /* =========================================
                       SUBMIT
                    ========================================== */

                    if (submitButton) {

                        submitButton.disabled =
                            true;

                        submitButton.innerHTML =
                            'Menyimpan...';

                    }

                }
            );

        }


        /* =====================================================
           INITIALIZE
        ====================================================== */

        updateCustomerType();

        updateDatePreview();

        updateTimePreview();

        updateCustomerPreview();

    })();
</script>

</body>
</html>
