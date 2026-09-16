@extends('reports.layout')

@section('title', 'تقرير المعاملات')

@section('content')

@php
    $typeLabels = [
        'all' => 'جميع المعاملات',
        'debt' => 'الديون فقط',
        'payment' => 'الدفعات فقط',
    ];
@endphp


<table class="info-table">

    <tr>

        <td class="info-label">
            من تاريخ
        </td>

        <td>
            {{ $report['period']['from_date'] }}
        </td>

        <td class="info-label">
            إلى تاريخ
        </td>

        <td>
            {{ $report['period']['to_date'] }}
        </td>

    </tr>

    <tr>

        <td class="info-label">
            نوع المعاملات
        </td>

        <td colspan="3">
            {{
                $typeLabels[
                    $report['filter']['type']
                ] ?? $report['filter']['type']
            }}
        </td>

    </tr>

</table>


<div class="section-title">
    ملخص التقرير
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

</table>


<div class="section-title">
    تفاصيل المعاملات
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
                <td colspan="5" class="empty">
                    لا توجد معاملات ضمن الفترة المحددة.
                </td>
            </tr>

        @endforelse

    </tbody>

</table>

@endsection