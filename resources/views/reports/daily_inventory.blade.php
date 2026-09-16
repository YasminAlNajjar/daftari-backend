@extends('reports.layout')

@section('title', 'الجرد اليومي')

@section('content')

<table class="info-table">

    <tr>

        <td class="info-label">
            تاريخ التقرير
        </td>

        <td>
            {{ $report['date'] }}
        </td>

    </tr>

</table>


<div class="section-title">
    ملخص اليوم
</div>

<table class="summary-table">

    <tr>

        <td>
            <div class="summary-label">
                إجمالي الديون
            </div>

            <div class="summary-value amount-debt">
                {{ $report['summary']['total_debts'] }}
            </div>
        </td>

        <td>
            <div class="summary-label">
                إجمالي الدفعات
            </div>

            <div class="summary-value amount-payment">
                {{ $report['summary']['total_payments'] }}
            </div>
        </td>

        <td>
            <div class="summary-label">
                صافي الحركة
            </div>

            <div class="summary-value">
                {{ $report['summary']['net_movement'] }}
            </div>
        </td>

        <td>
            <div class="summary-label">
                عدد المعاملات
            </div>

            <div class="summary-value">
                {{ $report['summary']['transactions_count'] }}
            </div>
        </td>

    </tr>

    <tr>

        <td>
            <div class="summary-label">
                عدد معاملات الدين
            </div>

            <div class="summary-value">
                {{ $report['summary']['debt_transactions_count'] }}
            </div>
        </td>

        <td>
            <div class="summary-label">
                عدد معاملات الدفع
            </div>

            <div class="summary-value">
                {{ $report['summary']['payment_transactions_count'] }}
            </div>
        </td>

        <td>
            <div class="summary-label">
                الزبائن النشطون
            </div>

            <div class="summary-value">
                {{ $report['summary']['active_customers_count'] }}
            </div>
        </td>

        <td>
            <div class="summary-label">
                تاريخ الجرد
            </div>

            <div class="summary-value">
                {{ $report['date'] }}
            </div>
        </td>

    </tr>

</table>


<div class="section-title">
    معاملات اليوم
</div>

<table class="data-table">

    <thead>

        <tr>

            <th width="18%">
                التاريخ
            </th>

            <th width="22%">
                الزبون
            </th>

            <th width="12%">
                النوع
            </th>

            <th width="16%">
                المبلغ
            </th>

            <th width="32%">
                الوصف
            </th>

        </tr>

    </thead>

    <tbody>

        @forelse($report['transactions'] as $transaction)

            <tr>

                <td class="center">
                    {{ $transaction['transaction_date'] }}
                </td>

                <td>
                    {{ $transaction['customer_name'] }}
                </td>

                <td class="center">

                    {{
                        $transaction['type'] === 'debt'
                            ? 'دين'
                            : 'دفعة'
                    }}

                </td>

                <td
                    class="center {{
                        $transaction['type'] === 'debt'
                            ? 'amount-debt'
                            : 'amount-payment'
                    }}"
                >

                    {{ $transaction['amount'] }}

                </td>

                <td>
                    {{ $transaction['description'] ?: '—' }}
                </td>

            </tr>

        @empty

            <tr>

                <td
                    colspan="5"
                    class="empty"
                >
                    لا توجد معاملات في هذا اليوم.
                </td>

            </tr>

        @endforelse

    </tbody>

</table>

@endsection