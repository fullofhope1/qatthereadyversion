<?php
// includes/classes/CommunicationService.php

class CommunicationService extends BaseService
{
    protected $commRepo;

    public function __construct(CommunicationRepository $commRepo)
    {
        $this->commRepo = $commRepo;
    }

    protected function getArabicDay($date)
    {
        $days = [
            'Sunday' => 'الأحد',
            'Monday' => 'الاثنين',
            'Tuesday' => 'الثلاثاء',
            'Wednesday' => 'الأربعاء',
            'Thursday' => 'الخميس',
            'Friday' => 'الجمعة',
            'Saturday' => 'السبت'
        ];
        return $days[date('l', strtotime($date))] ?? '';
    }

    public function getWhatsAppStatementsData()
    {
        $customers = $this->commRepo->getDebtorsWithActivity();
        $dateStr = date('j / n / Y');
        $dayName = $this->getArabicDay(date('Y-m-d'));

        foreach ($customers as &$c) {
            // Header
            $msg = "إشعار من القادري لأجود انواع القات : {$dayName} {$dateStr}م\n";
            
            // Latest Transaction Details
            if ($c['last_sale_amount']) {
                $lastAmt = number_format($c['last_sale_amount']);
                $msg .= "عليكم  {$lastAmt} محلي\n";
                
                $qatType = $c['last_qat_type'] ?? 'قات';
                $weightInfo = "";
                if ($c['last_unit_type'] === 'weight') {
                    $weightInfo = ($c['last_weight'] < 1000) ? $c['last_weight'] . " جم" : ($c['last_weight']/1000) . " كجم";
                } else {
                    $weightInfo = $c['last_units'] . " حبة/ربطة";
                }
                $msg .= "{$qatType} / {$weightInfo} ؛\n";
            } else {
                $msg .= "لا يوجد مبيعات مسجلة مؤخراً\n";
            }

            // Total Debt
            $totalDebt = number_format($c['total_debt']);
            $msg .= "الإجمالي - عليكم  {$totalDebt} محلي\n\n";

            // Bank Accounts
            $msg .= "أرقام حساباتنا :\n";
            $msg .= "جيب   ⬅️ 774456261\n";
            $msg .= "جوالي  ⬅️ 774456261\n";
            $msg .= "كريمي  ⬅️ 121940835";

            $c['encoded_msg'] = rawurlencode($msg);
            $c['formatted_phone'] = $this->formatPhone($c['phone']);
        }

        return $customers;
    }

    protected function formatPhone($phone)
    {
        if (strlen($phone) >= 9) {
            $last9 = substr($phone, -9);
            if (substr($last9, 0, 1) == '7') {
                return '967' . $last9;
            }
        }
        return $phone;
    }

    public function getUnknownTransfersData($limit = 100)
    {
        return $this->commRepo->getUnknownTransfers($limit);
    }

    public function processUnknownTransfer($action, $data)
    {
        if ($action === 'add') {
            return $this->commRepo->createUnknownTransfer($data);
        } elseif ($action === 'update') {
            $id = $data['id'];
            unset($data['id']);
            return $this->commRepo->updateUnknownTransfer($id, $data);
        }
        return false;
    }

    public function linkTransferToCustomer($transferId, $customerId)
    {
        return $this->commRepo->resolveTransfer($transferId, $customerId);
    }
}
