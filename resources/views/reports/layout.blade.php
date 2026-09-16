<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">

    <title>@yield('title')</title>

    <style>
        body {
            font-family: dejavusans, sans-serif;
            direction: rtl;
            color: #1f2937;
            font-size: 11px;
        }

        .header {
            border-bottom: 2px solid #111827;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }

        .brand {
            font-size: 24px;
            font-weight: bold;
            color: #111827;
        }

        .report-title {
            font-size: 18px;
            font-weight: bold;
            margin-top: 5px;
        }

        .meta {
            font-size: 10px;
            color: #6b7280;
            margin-top: 5px;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 8px;
            padding-bottom: 5px;
            border-bottom: 1px solid #d1d5db;
        }

        .info-table,
        .summary-table,
        .data-table,
        .verification {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            border: 1px solid #e5e7eb;
            padding: 8px;
        }

        .info-label {
            font-weight: bold;
            background: #f3f4f6;
            width: 18%;
        }

        .summary-table {
            margin-bottom: 15px;
        }

        .summary-table td {
            border: 1px solid #e5e7eb;
            padding: 10px 6px;
            text-align: center;
            vertical-align: top;
        }

        .summary-label {
            font-size: 9px;
            color: #6b7280;
            margin-bottom: 5px;
        }

        .summary-value {
            font-size: 14px;
            font-weight: bold;
        }

        .data-table th {
            background: #111827;
            color: #ffffff;
            padding: 8px 5px;
            border: 1px solid #111827;
            font-size: 9px;
        }

        .data-table td {
            border: 1px solid #d1d5db;
            padding: 7px 5px;
            font-size: 9px;
            vertical-align: middle;
        }

        .data-table tr {
            page-break-inside: avoid;
        }

        .center {
            text-align: center;
        }

        .amount-debt {
            color: #b91c1c;
            font-weight: bold;
        }

        .amount-payment {
            color: #047857;
            font-weight: bold;
        }

        .muted {
            color: #6b7280;
        }

        .empty {
            text-align: center;
            padding: 20px;
            color: #6b7280;
        }

        .verification {
            margin-top: 25px;
            border: 1px solid #d1d5db;
        }

        .verification td {
            padding: 12px;
            vertical-align: middle;
        }

        .verification-info {
            width: 75%;
        }

        .verification-title {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .verification-text {
            font-size: 9px;
            color: #6b7280;
            line-height: 1.7;
        }

        .verification-id {
            font-size: 9px;
            margin-top: 8px;
        }

        .qr-cell {
            width: 25%;
            text-align: center;
        }

        .qr-label {
            font-size: 8px;
            margin-top: 4px;
            color: #6b7280;
        }

        .footer {
            margin-top: 18px;
            font-size: 8px;
            color: #9ca3af;
            text-align: center;
        }
    </style>
</head>

<body>

    <div class="header">
        <div class="brand">
            سند
        </div>

        <div class="report-title">
            @yield('title')
        </div>

        <div class="meta">
            تاريخ إنشاء التقرير:
            {{ now()->format('Y-m-d H:i') }}
        </div>
    </div>

    @yield('content')

    @include('reports.verification')

    <div class="footer">
        تم إنشاء هذا التقرير إلكترونيًا من نظام سند.
    </div>

</body>
</html>