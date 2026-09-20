<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>التحقق من التقرير - سند</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f3f4f6;
            color: #111827;
            margin: 0;
            padding: 30px 15px;
        }

        .container {
            max-width: 650px;
            margin: auto;
            background: white;
            border-radius: 12px;
            padding: 30px;
        }

        h1 {
            margin-top: 0;
        }

        .success {
            background: #ecfdf5;
            padding: 15px;
            border-radius: 8px;
        }

        .error {
            background: #fef2f2;
            padding: 15px;
            border-radius: 8px;
        }

        .warning {
            background: #fffbeb;
            padding: 15px;
            border-radius: 8px;
        }

        .info {
            margin-top: 20px;
        }

        .info div {
            margin-bottom: 10px;
        }

        form {
            margin-top: 25px;
        }

        button {
            margin-top: 10px;
            padding: 10px 18px;
            cursor: pointer;
        }
    </style>
</head>

<body>

<div class="container">

    <h1>سند</h1>

    <h2>التحقق من صحة التقرير</h2>

    @if(!$validCertificate)

        <div class="error">
            هذه الشهادة غير موجودة أو أن رابط التحقق غير صالح.
        </div>

    @else

        <div class="success">
            الشهادة صالحة وصادرة عن نظام سند.
        </div>

        <div class="info">

            <div>
                <strong>رقم الشهادة:</strong>
                {{ $certificate->id }}
            </div>

            @if($certificate->reportExport)

                <div>
                    <strong>نوع التقرير:</strong>
                    {{ $certificate->reportExport->report_type }}
                </div>

            @endif

            <div>
                <strong>تاريخ إصدار الشهادة:</strong>
                {{ $certificate->created_at }}
            </div>

        </div>

        @if($fileVerification === 'valid')

            <div class="success">
                ملف PDF المرفوع مطابق للنسخة الأصلية ولم يتم تعديله.
            </div>

        @elseif($fileVerification === 'modified')

            <div class="warning">
                ملف PDF المرفوع لا يطابق النسخة الأصلية أو تم تعديله.
            </div>

        @endif

        <form
            method="POST"
            action="{{ route('reports.verify.file', ['token' => $token]) }}"
            enctype="multipart/form-data"
        >

            @csrf

            <label>
                ارفع ملف PDF للتحقق من سلامته:
            </label>

            <br><br>

            <input
                type="file"
                name="report_file"
                accept="application/pdf"
                required
            >

            @error('report_file')
                <div class="error">
                    {{ $message }}
                </div>
            @enderror

            <br>

            <button type="submit">
                التحقق من الملف
            </button>

        </form>

    @endif

</div>

</body>
</html>