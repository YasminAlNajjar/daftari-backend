@extends('reports.layout')

@section('title', 'تقرير أرصدة الزبائن')

@section('content')

@php
    $filterLabels = [
        'all' => 'جميع الزبائن',
        'debtors' => 'المدينون',
        'credit' => 'أصحاب الرصيد الدائن',
        'settled' => 'المسددون',
    ];

    $sortLabels = [
        'most_indebted' => 'الأكثر مديونية',
        'least_indebted' => 'الأقل مديونية',
        'name' => 'الاسم',
    ];
@endphp


<table class="info-table">

    <tr>

        <td class="info-label">
            الفلتر
        </td>

        <td>
            {{
                $filterLabels[$report['filter']]
                    ?? $report['filter']
            }}
        </td>

        <td class="info-label">
            الترتيب
        </td>

        <td>
            {{
                $sortLabels[$report['sort']]
                    ?? $report['sort']
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
                الديون المستحقة
            </div>

            <div class="summary-value amount-debt">
                {{ $report['summary']['total_outstanding_debt'] }}
            </div>
        </td>

        <td>
            <div class="summary-label">
                أرصدة الزبائن
            </div>

            <div class="summary-value amount-payment">
                {{ $report['summary']['total_customer_credit'] }}
            </div>
        </td>

        <td>
            <div class="summary-label">
                عدد المدينين
            </div>

            <div class="summary-value">
                {{ $report['summary']['debtors_count'] }}
            </div>
        </td>

    </tr>

    <tr>

        <td>
            <div class="summary-label">
               زبائن لديهم رصيد
            </div>

            <div class="summary-value">
                {{ $report['summary']['credit_customers_count'] }}
            </div>
        </td>

        <td>
            <div class="summary-label">
                المسددون
            </div>

            <div class="summary-value">
                {{ $report['summary']['settled_customers_count'] }}
            </div>
        </td>

        <td>
            <div class="summary-label">
                إجمالي الزبائن
            </div>

            <div class="summary-value">
                {{ $report['summary']['total_customers_count'] }}
            </div>
        </td>

    </tr>

</table>


<div class="section-title">
    أرصدة الزبائن
</div>

<table class="data-table">

    <thead>
        <tr>

            <th width="28%">
                الزبون
            </th>

            <th width="22%">
                الهاتف
            </th>

            <th width="18%">
                الرصيد
            </th>

            <th width="17%">
                الحالة
            </th>

            <th width="15%">
                حد الائتمان
            </th>

        </tr>
    </thead>

    <tbody>

        @forelse($report['customers'] as $customer)

            @php
                $balance =
                    (float) $customer['balance'];
            @endphp

            <tr>

                <td>
                    {{ $customer['name'] }}
                </td>

                <td class="center">
                    {{ $customer['phone'] }}
                </td>

                <td
                    class="center {{
                        $balance < 0
                            ? 'amount-debt'
                            : ($balance > 0
                                ? 'amount-payment'
                                : '')
                    }}"
                >
                    {{ $customer['balance'] }}
                </td>

                <td class="center">

                    @if($balance < 0)

                        مدين

                    @elseif($balance > 0)

                        له رصيد

                    @else

                        مسدد

                    @endif

                </td>

                <td class="center">
                    {{
                        $customer['credit_limit']
                            ?? '—'
                    }}
                </td>

            </tr>

        @empty

            <tr>
                <td colspan="5" class="empty">
                    لا يوجد زبائن مطابقون للفلتر.
                </td>
            </tr>

        @endforelse

    </tbody>

</table>

@endsection