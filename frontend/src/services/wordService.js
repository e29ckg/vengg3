// src/services/wordService.js

import PizZip from 'pizzip';
import docxtemplater from 'docxtemplater';
import { saveAs } from 'file-saver';

const Docxtemplater = docxtemplater;

// ฟังก์ชันช่วยจัดรูปแบบเวลา
// ฟังก์ชันช่วยจัดรูปแบบเวลา
const formatVenTime = (venDetail) => {
    // 1. ถ้า Backend มีการส่ง ven_time_text (เช่น 16.30 - 20.00 น.) มาให้แล้ว ให้ใช้เลย (แม่นยำที่สุด)
    if (venDetail.ven_time_text) {
        if(venDetail.ven_time_text === '16.30 - 08.30'){return `${venDetail.ven_time_text} นาฬิกา ของวันรุ่งขึ้น`}
        return `${venDetail.ven_time_text} นาฬิกา` ;
    }

    const timeStr = venDetail.ven_time_text || "";
    const dutyName = venDetail.ven_name || venDetail.duty_role || "";

    // 2. ถ้าเป็นเวร nightCourt
    if (dutyName.toLowerCase().includes('nightCourt')) {
        return "16.30 - 20.00 น.";
    }

    // 3. ถ้าเป็นเวรกลางคืนปกติข้ามวัน
    if (timeStr.includes("16:30") || timeStr.includes("16.30")) {
        return "16.30 - 08.30 น. ของวันรุ่งขึ้น";
    }

    // 4. ถ้าเป็นเวรเช้า
    if (timeStr.includes("08:30") || timeStr.includes("08.30")) {
        return "08.30 – 16.30 น.";
    }

    return `${timeStr} น.`; // คืนค่าเดิมถ้าไม่เข้าเงื่อนไข
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

        let swal_comment = '';

        if (changeData.is_swap == 1) {
            swal_comment = `และข้าพเจ้าจะมาปฏิบัติหน้าที่แทนในวันที่ ${formatThaiDate(changeData.s2_date)}`;
        }

        // เตรียมข้อมูลส่งออก
        doc.render({
            change_no: changeData.change_no || changeData.change_id,
            ref_change_no: changeData.ref_change_no  ? `และบันทึกใบเปลี่ยนเวรเลขที่ ${changeData.ref_change_no})` : "",

            // ข้อมูลหน่วยงานและผู้บริหาร
            agency_name: venDetail.agency_name || "-",
            director_name: venDetail.director_name || ".......................................",
            director_position: venDetail.director_position || ".......................................",
            
            // ข้อมูลคำสั่ง
            order_no: venDetail.command_num || "-", 
            order_date: venDetail.command_date || "-",
            
            // ข้อมูลชื่อเวรและเวลา
            ven_name_full: venDetail.ven_name || venDetail.duty_role, 
            ven_date: formatThaiDate(venDetail.ven_date),
            command_date: formatThaiDate(venDetail.command_date),
            ven_time: formatVenTime(venDetail),
            command_num: venDetail.command_num,
            duty_main: venDetail.duty_main,
            duty_main_full: venDetail.duty_main_full,
            swal_comment: swal_comment,
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