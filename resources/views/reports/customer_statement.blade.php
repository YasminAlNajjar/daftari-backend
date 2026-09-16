@extends('reports.layout')

@section('title', 'كشف حساب الزبون')

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
            اسم الزبون
        </td>

        <td>
            {{ $report['customer']['name'] }}
        </td>

        <td class="info-label">
            رقم الهاتف
        </td>

        <td>
            {{ $report['customer']['phone'] }}
        </td>
    </tr>

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
            فلتر المعاملات
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
    الملخص المالي
</div>

<table class="summary-table">
    <tr>

        <td>
            <div class="summary-label">
                الرصيد الافتتاحي
            </div>

            <div class="summary-value">
                {{ $report['summary']['opening_balance'] }}
            </div>
        </td>

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
                الرصيد الختامي
            </div>

            <div class="summary-value">
                {{ $report['summary']['closing_balance'] }}
            </div>
        </td>

    </tr>
</table>


<div class="section-title">
    المعاملات
</div>

<table class="data-table">

    <thead>
        <tr>
            <th width="20%">
                التاريخ
            </th>

            <th width="15%">
                النوع
            </th>

            <th width="20%">
                المبلغ
            </th>

            <th width="45%">
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
                <td colspan="4" class="empty">
                    لا توجد معاملات ضمن الفترة المحددة.
                </td>
            </tr>

        @endforelse

    </tbody>

</table>

@endsection