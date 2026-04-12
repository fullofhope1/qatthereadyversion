<?php
// includes/classes/ReportService.php

class ReportService extends BaseService
{
    private $reportRepo;

    public function __construct(ReportRepository $reportRepo)
    {
        $this->reportRepo = $reportRepo;
    }

    public function getOverviewData($reportType, $date, $month, $year, $userId = null)
    {
        $totals = $this->reportRepo->getTotals($reportType, $date, $month, $year, $userId);
        $debtStats = $this->reportRepo->getDebtStats();
        $refunds = $this->reportRepo->getRefunds($reportType, $date, $month, $year);

        return array_merge($totals, $debtStats, ['refunds' => $refunds]);
    }

    public function getDetailedViewData($view, $reportType, $date, $month, $year, $providerId = null, $userId = null)
    {
        switch ($view) {
            case 'Sales':
                return $this->reportRepo->getSalesList($reportType, $date, $month, $year, $providerId);
            case 'Receiving':
                return $this->reportRepo->getPurchasesList($reportType, $date, $month, $year);
            case 'Expenses':
                return $this->reportRepo->getExpensesList($reportType, $date, $month, $year, $userId);
            case 'Waste':
                return $this->reportRepo->getWasteList($reportType, $date, $month, $year);
            case 'unknown_transfers':
                return $this->reportRepo->getUnknownTransfersList($reportType, $date, $month, $year);
            case 'Printable':
                return [
                    'sales' => $this->reportRepo->getSalesList($reportType, $date, $month, $year),
                    'purchases' => $this->reportRepo->getPurchasesList($reportType, $date, $month, $year),
                    'expenses' => $this->reportRepo->getExpensesList($reportType, $date, $month, $year, $userId),
                    'waste' => $this->reportRepo->getWasteList($reportType, $date, $month, $year)
                ];
            default:
                return [];
        }
    }

    public function getCashSummary($reportType, $date, $month, $year, $userId = null)
    {
        $summary = $this->reportRepo->getCashSummary($reportType, $date, $month, $year, $userId);
        $summary['remaining_cash'] = ($summary['cash_sales'] + $summary['collected_payments'] + $summary['total_unknown_transfers']) - ($summary['total_expenses'] + $summary['cash_refunds'] + $summary['deposits_yer']);
        return $summary;
    }

    public function getSummaryBreakdowns($reportType, $date, $month, $year)
    {
        return [
            'sales' => $this->reportRepo->getSalesBreakdown($reportType, $date, $month, $year),
            'leftovers' => $this->reportRepo->getLeftoversBreakdown($reportType, $date, $month, $year),
            'deposits' => $this->reportRepo->getDepositsByCurrency($reportType, $date, $month, $year),
            'waste_stats' => $this->reportRepo->getWasteStats($reportType, $date, $month, $year)
        ];
    }

    public function getDashboardStats()
    {
        return [
            'total_receivables' => $this->reportRepo->getTotalReceivables(),
            'inventory_value' => $this->reportRepo->getInventoryValue(),
            'electronic_balance' => $this->reportRepo->getElectronicBalance()
        ];
    }
}
