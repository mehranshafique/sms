<style>
    :root {
        --primary-blue: #002b80;
        --header-orange: #d87b22;
        --fail-red: #d32f2f;
        --stamp-blue: #2585c9;
    }

    body {
        font-family: 'Arial Narrow', Arial, sans-serif;
        background-color: #555;
        margin: 0;
        padding: 0;
    }

    /* Print Controls */
    .print-controls {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 1000;
    }
    
    .print-btn {
        padding: 10px 20px;
        background: var(--header-orange);
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: bold;
        box-shadow: 0 4px 6px rgba(0,0,0,0.3);
        transition: 0.2s;
    }

    .print-btn:hover { background: #b86518; }

    /* Bulk A4 Landscape Container */
    .a4-landscape {
        width: 297mm;
        height: 210mm;
        background-color: #fff;
        display: flex;
        flex-direction: row;
        box-shadow: 0 5px 20px rgba(0,0,0,0.3);
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        overflow: hidden;
        page-break-after: always;
    }

    /* Base Student Column Properties */
    .student-column {
        box-sizing: border-box;
        padding: 10mm 6mm;
        position: relative;
        background-color: #fdfbf5;
        background-image: 
            url("data:image/svg+xml,%3Csvg width='120' height='120' viewBox='0 0 120 120' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' stroke='%23ebd5a1' stroke-width='0.5' opacity='0.7'%3E%3Cpath d='M0,60 C30,10 90,110 120,60 C150,10 210,110 240,60' /%3E%3Cpath d='M0,65 C30,15 90,115 120,65 C150,15 210,115 240,65' /%3E%3Cpath d='M0,70 C30,20 90,120 120,70 C150,20 210,120 240,70' /%3E%3Cpath d='M0,60 C30,110 90,10 120,60 C150,110 210,10 240,60' /%3E%3Cpath d='M0,65 C30,115 90,15 120,65 C150,115 210,15 240,65' /%3E%3Cpath d='M0,70 C30,120 90,20 120,70 C150,120 210,20 240,70' /%3E%3C/g%3E%3C/svg%3E"),
            url("data:image/svg+xml,%3Csvg viewBox='0 0 100 30' preserveAspectRatio='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M0,15 Q25,0 50,15 T100,15 V30 H0 Z' fill='%23ebd5a1' opacity='0.4'/%3E%3Cpath d='M0,20 Q25,5 50,20 T100,20 V30 H0 Z' fill='%23d6bd7e' opacity='0.5'/%3E%3C/svg%3E");
        background-position: top left, bottom left;
        background-repeat: repeat, no-repeat;
        background-size: 120px 120px, 100% 30mm;
        -webkit-print-color-adjust: exact !important;
        color-adjust: exact !important;
    }

    /* Single Card Wrapper (Prevents stretching on screen) */
    .single-card-view {
        width: 100mm;
        max-width: 100%;
        min-height: 210mm;
        height: auto !important; /* Expands to fit content securely */
        margin: 40px auto;
        box-shadow: 0 10px 30px rgba(0,0,0,0.4);
        border: 1px solid #ccc;
        border-radius: 6px;
    }

    .single-card-view-period {
        width: 74mm;
    }

    .header-content { text-align: center; margin-bottom: 2px; }
    .logo-box {
        width: 35px; height: 40px; border: 1px solid var(--primary-blue);
        border-radius: 5px 5px 20px 20px; margin: 0 auto 6px auto;
        display: flex; align-items: center; justify-content: center;
        font-size: 4.5px; color: var(--primary-blue); font-weight: bold;
        background: rgba(255, 255, 255, 0.7); overflow: hidden;
    }
    .school-name { font-size: 12px; font-weight: bold; color: #000; margin-bottom: 2px; }
    .student-name { font-size: 10px; font-weight: bold; margin: 4px 0 2px 0; }
    .class-name { font-size: 10px; font-weight: bold; margin-bottom: 4px; }
    
    .barcode {
        height: 20px; width: 90px; margin: 4px auto;
        background-image: linear-gradient(to right, #000 0, #000 2px, transparent 2px, transparent 4px, #000 4px, #000 7px, transparent 7px, transparent 9px, #000 9px, #000 10px, transparent 10px, transparent 14px, #000 14px, #000 16px, transparent 16px, transparent 18px, #000 18px, #000 22px, transparent 22px, transparent 23px, #000 23px, #000 26px, transparent 26px, transparent 28px, #000 28px, #000 29px, transparent 29px, transparent 32px, #000 32px, #000 36px, transparent 36px, transparent 38px, #000 38px, #000 40px, transparent 40px, transparent 42px, #000 42px, #000 45px, transparent 45px, transparent 46px, #000 46px, #000 48px, transparent 48px, transparent 52px, #000 52px, #000 53px, transparent 53px, transparent 56px, #000 56px, #000 59px, transparent 59px, transparent 60px, #000 60px, #000 62px, transparent 62px, transparent 65px, #000 65px, #000 68px, transparent 68px, transparent 70px, #000 70px, #000 72px, transparent 72px, transparent 76px, #000 76px, #000 77px, transparent 77px, transparent 80px, #000 80px, #000 84px, transparent 84px, transparent 86px, #000 86px, #000 88px, transparent 88px, transparent 90px, #000 90px, #000 94px, transparent 94px, transparent 96px, #000 96px, #000 98px, transparent 98px, transparent 100px);
    }
    .term-title { font-size: 9px; font-weight: bold; margin-top: 4px; text-transform: uppercase; }

    .divider-thick { height: 2px; background-color: var(--primary-blue); margin-top: 4px; }
    .divider-thin { height: 1px; background-color: var(--primary-blue); margin-top: 1px; margin-bottom: 4px; }
    .divider-bottom { height: 1px; background-color: var(--primary-blue); margin: 3px 0; }

    table { width: 100%; border-collapse: collapse; font-size: 9px; }
    th { color: var(--header-orange); font-weight: bold; text-align: center; padding-bottom: 3px; }
    th.left-align, td.left-align { text-align: left; }
    td { padding: 1.5px 0; text-align: center; font-weight: bold; color: #1a1a1a; }
    .fail-grade { color: var(--fail-red) !important; }

    .summary-container { margin-top: 5px; padding-top: 5px; border-top: 1.5px solid #000; }
    .summary-row { display: flex; justify-content: space-between; align-items: center; font-size: 9.5px; margin-bottom: 2.5px; }
    .summary-row .label { flex: 2; text-align: left; }
    .summary-row .val { flex: 0.5; text-align: center; font-weight: bold; font-size: 11px;}

    .footer-wrapper { position: relative; width: 100%; height: 75px; margin-top: 15px; clear: both; }

    .qr-code {
        position: absolute; left: 0; bottom: 0;
        width: 32px; height: 32px; background-color: white;
        padding: 2px; box-sizing: border-box; background-size: cover;
    }

    .stamp-overlay {
        position: absolute;
        left: 50%;
        bottom: 0;
        transform: translateX(-50%);
        width: 72px;
        height: 72px;
        pointer-events: none;
        opacity: 0.95;
        z-index: 5;
    }

    .signature-block {
        position: absolute; right: 0; bottom: 0;
        font-size: 8px; text-align: center; line-height: 1.5;
        font-weight: bold; color: #000;
    }

    /* Print Logic overrides single view styling automatically */
    @media print {
        @page { size: A4 landscape; margin: 0; }
        body { background: none; padding: 0; margin: 0; }
        .print-controls { display: none !important; }
        
        .single-card-view {
            margin: 0 !important;
            box-shadow: none !important;
            border: none !important;
            border-right: 1px dashed #ccc !important;
            height: 210mm !important;
            border-radius: 0;
        }
        /* Lock widths into exact column fractions when printing single view */
        .single-card-view-period { width: 25% !important; }
        .single-card-view:not(.single-card-view-period) { width: 33.33% !important; }
    }
</style>