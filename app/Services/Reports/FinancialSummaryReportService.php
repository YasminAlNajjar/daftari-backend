<?php

namespace App\Services\Reports;

class FinancialSummaryReportService extends BaseReportService
{
    public function __construct(
        private ReportQueryService $queryService
    ) {
    }

    public function get(int $userId,string $date): array 
    {

        [$startDate, $endDate] =
            $this->getDayRange($date);

        $balanceTotals =
            $this->queryService
                ->currentBalanceTotals(
                    $userId
                );

        $dailyTotals =
            $this->queryService
                ->periodSummary(
                    userId: $userId,
                    startDate: $startDate,
                    endDate: $endDate
                );

        $todayDebts = (float) $dailyTotals->total_debts;

        $todayPayments = (float) $dailyTotals->total_payments;

        return [
            'date' => $date,

            'total_outstanding_debt' =>
                $this->money(
                    $balanceTotals
                        ->total_outstanding_debt
                ),

            'total_customer_credit' =>
                $this->money(
                    $balanceTotals
                        ->total_customer_credit
                ),

            'today_debts' =>
                $this->money(
                    $todayDebts
                ),

            'today_payments' =>
                $this->money(
                    $todayPayments
                ),

            'today_net_movement' =>
                $this->money(
                    $todayPayments
                    - $todayDebts
                ),
        ];
    }
}