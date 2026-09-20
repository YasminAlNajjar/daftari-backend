<table class="verification">
    <tr>

        <td class="verification-info">

            <div class="verification-title">
                التحقق من صحة التقرير
            </div>

            <div class="verification-text">
                امسح رمز QR للتحقق من أن هذا التقرير
                صادر عن نظام سند ولمعرفة بيانات الشهادة.
            </div>

            <div class="verification-id">
                رقم الشهادة:
                {{ $certificate->id }}
            </div>

        </td>

        <td class="qr-cell">

            <barcode
                code="{{ $verificationUrl }}"
                type="QR"
                size="0.8"
                error="M"
                disableborder="1"
            />

            <div class="qr-label">
                امسح للتحقق
            </div>

        </td>

    </tr>
</table>