<?php
class BackupModel {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getDatabaseBackupSql() {
        $sql = "-- Backup Date: " . date('Y-m-d H:i:s') . "\n\n";
        
        $tables = [];
        $stmt = $this->conn->query('SHOW TABLES');
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }

        foreach ($tables as $table) {
            $stmt = $this->conn->query("SHOW CREATE TABLE `$table`");
            $row = $stmt->fetch(PDO::FETCH_NUM);
            $sql .= "\nDROP TABLE IF EXISTS `$table`;\n";
            $sql .= $row[1] . ";\n\n";

            $stmt = $this->conn->query("SELECT * FROM `$table`");
            $numCols = $stmt->columnCount();

            while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                $sql .= "INSERT INTO `$table` VALUES(";
                for ($j = 0; $j < $numCols; $j++) {
                    if ($row[$j] === null) {
                        $sql .= "NULL";
                    } else {
                        $sql .= $this->conn->quote($row[$j]);
                    }
                    
                    if ($j < ($numCols - 1)) {
                        $sql .= ",";
                    }
                }
                $sql .= ");\n";
            }
            $sql .= "\n";
        }
        
        return $sql;
    }

    // 🌟 สำหรับสร้างไฟล์ ZIP รูปภาพ
    public function createImagesZipBackup($sourceDir, $backupDir) {
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0777, true);
        }

        $date = date('Ymd_His');
        $zipFileName = "images_backup_{$date}.zip";
        
        // ตรวจสอบและเติม / ให้ path
        $zipFilePath = rtrim($backupDir, '/') . '/' . $zipFileName; 
        
        $zip = new ZipArchive();
        
        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            $hasFiles = false;

            if (is_dir($sourceDir)) {
                $files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($sourceDir), 
                    RecursiveIteratorIterator::LEAVES_ONLY
                );

                foreach ($files as $name => $file) {
                    if (!$file->isDir()) {
                        $filePath = $file->getRealPath();
                        // ตัด Path ให้เหลือแค่โฟลเดอร์ avatars/... ข้างใน ZIP
                        $relativePath = 'avatars/' . substr($filePath, strlen($sourceDir));
                        $zip->addFile($filePath, $relativePath);
                        $hasFiles = true;
                    }
                }
            }

            // ถ้าไม่มีไฟล์รูปภาพเลย ให้สร้างไฟล์ text แจ้งเตือน
            if (!$hasFiles) {
                $zip->addFromString('empty_note.txt', 'ยังไม่มีรูปภาพในระบบ');
            }

            $zip->close();
            
            // ส่งคืนข้อมูล Path และชื่อไฟล์กลับไปให้ Controller
            return [
                'path' => $zipFilePath,
                'name' => $zipFileName
            ];
        }

        return false; // ถ้าเปิด ZipArchive ไม่สำเร็จ
    }
}