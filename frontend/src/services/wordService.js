// src/services/wordService.js

import PizZip from 'pizzip';
import docxtemplater from 'docxtemplater';
import { saveAs } from 'file-saver';

const Docxtemplater = docxtemplater;

// ฟังก์ชันช่วยจัดรูปแบบเวลา
const formatVenTime = (venDetail) => {
    // 1. ถ้า Backend มีการส่ง ven_time_text มาให้แล้ว
    if (venDetail.ven_time_text) {
        if(venDetail.ven_time_text === '16.30 - 08.30'){
            return `${venDetail.ven_time_text} นาฬิกา ของวันรุ่งขึ้น`;
        }
        return `${venDetail.ven_time_text} นาฬิกา`;
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

        const court = venDetail.agency_name || "";
        let fullAgencyName = court; 

        if (court.includes("ศาลจังหวัด")) {
            fullAgencyName = court.replace("ศาลจังหวัด", "สำนักอำนวยการประจำศาลจังหวัด");
        } else if (court.includes("ศาลแขวง")) {
            fullAgencyName = court.replace("ศาลแขวง", "สำนักงานประจำศาลแขวง");
        } else if (court.includes("ศาลเยาวชนและครอบครัวกลาง")) {
            // เช็คชื่อเต็มก่อนศาลเยาวชนทั่วไป
            fullAgencyName = court.replace("ศาลเยาวชนและครอบครัวกลาง", "สำนักอำนวยการประจำศาลเยาวชนและครอบครัวกลาง");
        } else if (court.includes("ศาลเยาวชน")) {
            fullAgencyName = court.replace("ศาลเยาวชน", "สำนักงานประจำศาลเยาวชน");
        }

        let agency_name_short = fullAgencyName.replace(/สำนักงานประจำศาล|สำนักอำนวยการประจำศาล/g, '').trim();
        let sendTo = venDetail.duty_role == 'ผู้พิพากษา' ? `ผู้พิพากษาหัวหน้าศาล${agency_name_short}` : `ผู้อำนวยการ${fullAgencyName}`;

        // 🌟 แก้ไขจุดที่ 2: ประกาศตัวแปรค่าเริ่มต้น (Default) ไว้เลย จะได้ไม่ต้องเขียน if-else ซ้ำซ้อน
        let director_name = `(${venDetail.director_name || ""})`;
        let director_position = `${venDetail.director_position || ""}`;
        let admins_name = `(${venDetail.admins_name || ""})`;
        let admins_position = `${venDetail.admins_position || ""}`;
        let finance_name = `${venDetail.finance_name || ""}`;
        let finance_position = `${venDetail.finance_position || ""}`;
        
        let director_sign = `[ ] อนุญาต    [ ] ไม่อนุญาต `;
        let admins_sign = `ขอประทานเสนอ ผู้พิพากษาหัวหน้าศาล\n - เพื่อโปรดพิจารณา`;

        // ปรับแก้เฉพาะกรณีที่เป็นผู้พิพากษา (ซ่อนช่อง ผอ.)
        if (venDetail.duty_role == 'ผู้พิพากษา') {
            admins_name = "";
            admins_position = "";
            admins_sign = "";
        }

        let swal_comment = '';
        if (changeData.is_swap == 1) {
            swal_comment = `และข้าพเจ้าจะมาปฏิบัติหน้าที่แทนในวันที่ ${formatThaiDate(changeData.s2_date)}`;
        }

        // เตรียมข้อมูลส่งออก
        doc.render({
            change_no: changeData.change_no || changeData.change_id,
            ref_change_no: changeData.ref_change_no ? `และบันทึกใบเปลี่ยนเวรเลขที่ ${changeData.ref_change_no}` : "",

            // ข้อมูลหน่วยงาน
            agency_name: court || "-",
            sendTo: sendTo,

            // ข้อมูลผู้บริหาร
            director_name: director_name,
            director_position: director_position,
            admins_name: admins_name,
            admins_position: admins_position,
            finance_name: finance_name,
            finance_position: finance_position,
            director_sign: director_sign,
            admins_sign: admins_sign,
            
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