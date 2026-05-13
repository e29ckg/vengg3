<?php
// backend/src/Controllers/FinanceController.php

class FinanceController {
    private $financeModel;

    public function __construct($financeModel) {
        $this->financeModel = $financeModel;
    }

    // ฟังก์ชันรับ Request ดึงรายการคำสั่งตามเดือน (ดัดแปลงจาก getCommonReport)
    public function getCommands($monthYear) {
        $stmt = $this->financeModel->getCommandsByMonth($monthYear);
        $rowCount = $stmt->rowCount();
        
        if ($rowCount > 0) {
            $commands = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $commands = [];
        }
        
        http_response_code(200);
        // หมายเหตุ: ส่งคืนในรูปแบบ { status, data } เพื่อให้ตรงกับฝั่ง Vue.js ที่คุณเขียนไว้
        echo json_encode([
            "status" => "success",
            "data" => $commands
        ]);
    }

    public function getFinanceReport($monthYear, $commandId = null) {
        // 1. หาจำนวนวันทั้งหมดของเดือนนั้น (ด้วย PHP พื้นฐาน)
        $totalDaysInMonth = (int) date('t', strtotime($monthYear . '-01'));

        // 2. ดึงรายการ "วันหยุดราชการ" (จากเวรกลางวัน)
        $holidays = $this->financeModel->getHolidaysFromDayShift($monthYear, $commandId);

        // 3. ดึงข้อมูลรายงานบุคคล
        $stmt = $this->financeModel->getFinanceReportByMonth($monthYear, $commandId);
        $reportList = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ส่งข้อมูลทั้งก้อนกลับไปให้ Vue.js
        http_response_code(200);
        echo json_encode([
            "status" => "success",
            "data" => [
                "month_info" => [
                    "total_days" => $totalDaysInMonth,
                    "holidays" => $holidays
                ],
                "report_list" => $reportList
            ]
        ]);
    }
}
?>