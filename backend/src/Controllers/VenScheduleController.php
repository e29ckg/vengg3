<?php
class VenScheduleController {
    private $settingModel;
    private $connection;

    public function __construct($settingModel, $connection) {
        $this->settingModel = $settingModel;
        $this->connection = $connection;
    }

    public function listMonth($month) {
        echo json_encode($this->settingModel->getSchedulesByMonth($month));
    }

    public function add($data) {
        if ($this->settingModel->addSchedule($data)) {
            echo json_encode(["success" => true, "message" => "บันทึกเวรสำเร็จ"]);
        } else {
            http_response_code(500); 
            echo json_encode(["error" => "ไม่สามารถบันทึกข้อมูลได้"]);
        }
    }

    public function remove($id) {
        if ($this->settingModel->removeSchedule($id)) {
            echo json_encode(["success" => true, "message" => "ลบเวรสำเร็จ"]);
        } else {
            http_response_code(500); 
            echo json_encode(["error" => "ไม่สามารถลบข้อมูลได้"]);
        }
    }

    public function syncGoogle($data, $baseDir) {
        $month = $data['month'] ?? ''; 
        if (!$month) {
            http_response_code(400); echo json_encode(['error' => 'ไม่พบข้อมูลเดือนที่ต้องการ']); return;
        }

        $keyFilePath = $baseDir . '/../src/Config/credentials.json';
        if (!file_exists($keyFilePath)) {
            http_response_code(400); echo json_encode(['error' => 'ไม่พบไฟล์ credentials.json']); return;
        }

        $stmtConf = $this->connection->prepare("SELECT setting_value FROM google_service_settings WHERE setting_key = 'google_calendar_id'");
        $stmtConf->execute();
        $calId = $stmtConf->fetchColumn();

        if (!$calId) {
            http_response_code(400); echo json_encode(['error' => 'กรุณาตั้งค่า Google Calendar ID ก่อน']); return;
        }

        $stmt = $this->connection->prepare("
            SELECT vs.ven_date, vn.dn AS ven_time, vns.name AS sub_name, vn.name AS main_name,
                   CONCAT_WS(' ', CONCAT(IFNULL(p.prefix_name, ''), IFNULL(p.first_name, '')), p.last_name) AS user_name
            FROM ven_schedule vs
            LEFT JOIN ven_name_sub vns ON vs.ven_name_sub_id = vns.id
            LEFT JOIN ven_com vc ON vs.ven_com_id = vc.id
            LEFT JOIN ven_name vn ON vc.ven_name_id = vn.id
            LEFT JOIN profile p ON vs.user_id = p.user_id 
            WHERE DATE_FORMAT(vs.ven_date, '%Y-%m') = ?
            ORDER BY vn.srt ASC, vns.srt ASC, vs.id ASC;
        ");
        $stmt->execute([$month]);
        $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($schedules)) {
            echo json_encode(['success' => true, 'message' => 'ไม่มีข้อมูลเวรในเดือนนี้']); return;
        }

        $groupedByDate = [];
        foreach ($schedules as $sch) {
            $date = $sch['ven_date'];
            if (!isset($groupedByDate[$date])) { $groupedByDate[$date] = []; }
            $groupedByDate[$date][] = $sch;
        }

        require_once $baseDir . '/../vendor/autoload.php';
        $client = new Google_Client();
        $client->setAuthConfig($keyFilePath);
        $client->addScope(Google_Service_Calendar::CALENDAR_EVENTS);
        $service = new Google_Service_Calendar($client);

        try {
            // ล้าง Event เก่า
            $timeMin = date('c', strtotime($month . '-01 00:00:00'));
            $timeMax = date('c', strtotime($month . '-01 +1 month 00:00:00'));
            $optParams = ['timeMin' => $timeMin, 'timeMax' => $timeMax, 'singleEvents' => true, 'maxResults' => 2500];
            $events = $service->events->listEvents($calId, $optParams)->getItems();

            if (!empty($events)) {
                foreach ($events as $oldEvent) {
                    if (strpos($oldEvent->getSummary(), 'เวรประจำวันที่') !== false) {
                        try { $service->events->delete($calId, $oldEvent->getId()); } catch (Exception $e) {}
                    }
                }
            }

            // สร้าง Event ใหม่
            $updateStmt = $this->connection->prepare("UPDATE ven_schedule SET google_event_id = ? WHERE ven_date = ?");
            
            foreach ($groupedByDate as $date => $shifts) {
                $groupedByDuty = [];
                foreach ($shifts as $sch) {
                    $dutyName = $sch['main_name'];
                    if (!isset($groupedByDuty[$dutyName])) { $groupedByDuty[$dutyName] = []; }
                    $groupedByDuty[$dutyName][] = $sch;
                }

                $description = "เวรประจำวันที่ " . date('d/m/Y', strtotime($date)) . "\n";
                foreach ($groupedByDuty as $dutyName => $dutyShifts) {
                    $description .= "---------------------\n📌" . $dutyName . "\n";
                    foreach ($dutyShifts as $sch) {
                        $uName = trim($sch['user_name']) ?: "(ยังไม่มีผู้ลงเวร)";
                        $description .= "👨‍💼 " . $uName . "\n";
                    }
                    $description .= "\n";
                }

                $event = new Google_Service_Calendar_Event([
                    'summary' => "เวรประจำวันที่ " . date('d/m/Y', strtotime($date)),
                    'description' => $description,
                    'start' => ['date' => $date, 'timeZone' => 'Asia/Bangkok'],
                    'end' => ['date' => date('Y-m-d', strtotime($date . ' +1 day')), 'timeZone' => 'Asia/Bangkok'],
                ]);

                $createdEvent = $service->events->insert($calId, $event);
                $updateStmt->execute([$createdEvent->getId(), $date]);
            }

            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(500); echo json_encode(['error' => 'Google API Error: ' . $e->getMessage()]);
        }
    }

    // 🌟 ดึงรายชื่อผู้อยู่เวรตามหน้าที่ย่อย (สำหรับลากวางจัดเวร)
    public function getUserListBySub($sub_id) {
        if (!$sub_id) {
            http_response_code(400);
            echo json_encode(["error" => "Missing sub_id parameter"]);
            return;
        }
        
        // ส่งผลลัพธ์เป็น Array กลับไปเลย
        echo json_encode($this->settingModel->getUsersBySubId($sub_id));
    }
}