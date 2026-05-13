<?php
class BackupController {
    private $backupModel;

    public function __construct($backupModel) {
        $this->backupModel = $backupModel;
    }

    public function downloadSql() {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '300');

        $date = date('Ymd_His');
        $sqlFileName = "database_{$date}.sql";

        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $sqlFileName . '"');

        echo $this->backupModel->getDatabaseBackupSql();
    }

    // 🌟 ฟังก์ชัน: สำหรับดาวน์โหลดไฟล์ ZIP
    public function downloadImages($baseDir) {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '300');

        // กำหนดโฟลเดอร์ต้นทาง และปลายทาง
        $backupDir = $baseDir . '/uploads/backups/';
        $imagesDir = $baseDir . '/uploads/avatars/';

        // สั่งให้ Model สร้างไฟล์ ZIP ให้
        $zipInfo = $this->backupModel->createImagesZipBackup($imagesDir, $backupDir);

        if ($zipInfo && file_exists($zipInfo['path'])) {
            // สำคัญที่สุด: ล้าง Buffer ก่อนส่งไฟล์
            if (ob_get_length()) {
                ob_end_clean();
            }

            // ส่ง Header ให้ Browser เริ่มดาวน์โหลด
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $zipInfo['name'] . '"');
            header('Content-Length: ' . filesize($zipInfo['path']));
            header('Cache-Control: max-age=0'); 
            
            // อ่านไฟล์ส่งให้ Browser
            readfile($zipInfo['path']);

            // โหลดเสร็จแล้ว ลบไฟล์ ZIP ทิ้ง
            unlink($zipInfo['path']);
            exit;
        } else {
            // กรณีเกิดข้อผิดพลาดในการสร้างไฟล์ ZIP
            http_response_code(500);
            echo "Error: ไม่สามารถสร้างหรือหาไฟล์อัปโหลดได้";
        }
    }
}