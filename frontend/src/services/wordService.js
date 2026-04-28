// src/services/wordService.js

import PizZip from 'pizzip';
import docxtemplater from 'docxtemplater';
import { saveAs } from 'file-saver';

const Docxtemplater = docxtemplater;

// ฟังก์ชันช่วยจัดรูปแบบเวลา
const formatVenTime = (timeStr) => {
    if (!timeStr) return "";
    // ถ้าเวลาเริ่มที่ 16.30 หรือช่วงบ่าย/ค่ำ มักจะเป็นเวรข้ามคืน
    if (timeStr.includes("16:30:00")) {
        return "16.30 - 08.30 น. ของวันรุ่งขึ้น";
    }
    // ถ้าเป็นเวรเช้า
    if (timeStr.includes("08.30")) {
        return "08.30 – 16.30 น.";
    }
    return timeStr; // คืนค่าเดิมถ้าไม่เข้าเงื่อนไข
};

// ฟังก์ชันแปลงวันที่ yyyy-mm-dd เป็นภาษาไทย
const formatThaiDate = (dateString) => {
    if (!dateString || dateString === "-") return "-";
    const date = new Date(dateString);
    if (isNaN(date)) return dateString;
    return date.toLocaleDateString('th-TH', { 
        year: 'numeric', month: 'long', day: 'numeric' 
    });
};

export const exportShiftChangeToWord = async (changeData, venDetail) => {
    try {
        const response = await fetch('/templates/shift_change_form.docx');
        if (!response.ok) throw new Error('ไม่พบไฟล์ Template');
        
        const content = await response.arrayBuffer();
        const zip = new PizZip(content);
        const doc = new Docxtemplater(zip, { paragraphLoop: true, linebreaks: true });

        // เตรียมข้อมูลส่งออก
        doc.render({
            change_no: changeData.change_no || changeData.change_id,
            
            // ข้อมูลคำสั่ง (เพิ่มใหม่)
            order_no: venDetail.command_num || "-", 
            order_date: venDetail.command_date || "-",
            
            // ข้อมูลชื่อเวรและเวลา (ปรับปรุงใหม่)
            ven_name_full: venDetail.ven_name || venDetail.duty_role, // ใช้ชื่อเต็มจากฐานข้อมูล
            ven_date: formatThaiDate(venDetail.ven_date),
            command_date: formatThaiDate(venDetail.command_date),
            ven_time: formatVenTime(venDetail.ven_time), // เรียกใช้ตัวจัดเวลาด้านบน
            command_num : venDetail.command_num,
            duty_main : venDetail.duty_main,
            duty_main_full : venDetail.duty_main_full,
            
            change_date: formatThaiDate(changeData.change_date),
            // ข้อมูลผู้เปลี่ยน
            user1_name: changeData.user1_name,
            user2_name: changeData.user2_name,

            user1_dep: changeData.user1_dep,
            user2_dep: changeData.user2_dep,
            
            export_date: new Date().toLocaleDateString('th-TH', { 
                year: 'numeric', month: 'long', day: 'numeric' 
            })
        });

        const out = doc.getZip().generate({
            type: 'blob',
            mimeType: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        });
        
        saveAs(out, `ใบเปลี่ยนเวร_${changeData.change_no || changeData.change_id}.docx`);
        return true;
    } catch (error) {
        console.error('Error:', error);
        throw error;
    }
};

// export const exportShiftChangeToWord = async (changeData, venDetail) => {
//   try {
//     // 1. โหลดไฟล์ Template จากโฟลเดอร์ public
//     const response = await fetch('/templates/shift_change_form.docx');
//     if (!response.ok) throw new Error('ไม่พบไฟล์ Template ฟอร์มใบเปลี่ยนเวร');
    
//     const content = await response.arrayBuffer();
//     const zip = new PizZip(content);
    
//     // 2. สร้าง instance ของ docxtemplater
//     const doc = new Docxtemplater(zip, {
//       paragraphLoop: true,
//       linebreaks: true,
//     });

//     // 3. เตรียมข้อมูลที่จะเอาไปหยอดในฟอร์ม (ปรับชื่อตัวแปรให้ตรงกับในไฟล์ Word)
//     doc.render({
//       change_no: changeData.change_no || changeData.change_id,
//       command_num : venDetail.command_num,
//       user1_name: changeData.user1_name,
//       user2_name: changeData.user2_name,
//       ven_date: venDetail.ven_date,
//       duty_role: venDetail.duty_role,
//       change_date: venDetail.change_date,
//       export_date: new Date().toLocaleDateString('th-TH', { 
//         year: 'numeric', month: 'long', day: 'numeric' 
//       })
//     });
//     // ข้อมูลสมมติที่ได้จากฐานข้อมูลหรือตัวแปรใน Vue

//     // 4. สร้างไฟล์และดาวน์โหลด
//     const out = doc.getZip().generate({
//       type: 'blob',
//       mimeType: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
//     });
    
//     // บันทึกไฟล์โดยตั้งชื่อตามเลขที่ใบเปลี่ยน
//     saveAs(out, `ใบเปลี่ยนเวร_${changeData.change_no || changeData.change_id}.docx`);
    
//     return true;
//   } catch (error) {
//     console.error('Error generating word document:', error);
//     throw error;
//   }
// };