<style>
    :root {
        --primary-blue: #002b80;
        --header-orange: #d87b22;
        --fail-red: #d32f2f;
        --stamp-blue: #2585c9;
        --card-max-height: 210mm;
        --card-width: 74.25mm;
    }

    body {
        font-family: 'Arial Narrow', Arial, sans-serif;
        background-color: #555;
        margin: 0;
        padding: 0;
    }

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
    }

    .a4-landscape {
        width: 297mm;
        min-height: 0;
        height: auto;
        max-height: var(--card-max-height);
        background-color: #fff;
        display: flex;
        flex-direction: row;
        align-items: flex-start;
        box-shadow: 0 5px 20px rgba(0,0,0,0.3);
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        overflow: hidden;
        page-break-after: always;
        margin: 0 auto 20px;
    }

    .student-column {
        box-sizing: border-box;
        flex: 0 0 25%;
        max-width: 25%;
        width: 25%;
        height: auto;
        max-height: var(--card-max-height);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        padding: 4mm 3mm;
        position: relative;
        border-right: 1px dashed #ccc;
        background-color: #fdfbf5;
        background-image:
            url("data:image/svg+xml,%3Csvg width='120' height='120' viewBox='0 0 120 120' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' stroke='%23ebd5a1' stroke-width='0.5' opacity='0.7'%3E%3Cpath d='M0,60 C30,10 90,110 120,60 C150,10 210,110 240,60' /%3E%3Cpath d='M0,65 C30,15 90,115 120,65 C150,15 210,115 240,65' /%3E%3Cpath d='M0,70 C30,20 90,120 120,70 C150,20 210,120 240,70' /%3E%3Cpath d='M0,60 C30,110 90,10 120,60 C150,110 210,10 240,60' /%3E%3Cpath d='M0,65 C30,115 90,15 120,65 C150,115 210,15 240,65' /%3E%3Cpath d='M0,70 C30,120 90,20 120,70 C150,120 210,20 240,70' /%3E%3C/g%3E%3C/svg%3E"),
            url("data:image/svg+xml,%3Csvg viewBox='0 0 100 30' preserveAspectRatio='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M0,15 Q25,0 50,15 T100,15 V30 H0 Z' fill='%23ebd5a1' opacity='0.4'/%3E%3Cpath d='M0,20 Q25,5 50,20 T100,20 V30 H0 Z' fill='%23d6bd7e' opacity='0.5'/%3E%3C/svg%3E");
        background-position: top left, bottom left;
        background-repeat: repeat, no-repeat;
        background-size: 120px 120px, 100% 18mm;
        -webkit-print-color-adjust: exact !important;
        color-adjust: exact !important;
    }

    .student-column:last-child {
        border-right: none;
    }

    .card-inner {
        display: flex;
        flex-direction: column;
        height: auto;
        max-height: 100%;
        min-height: 0;
        flex: 1 1 auto;
    }

    .single-card-page {
        display: flex;
        justify-content: center;
        align-items: flex-start;
        width: 100%;
        min-height: 100vh;
        padding: 24px 0;
        box-sizing: border-box;
    }

    /* Single student preview: shrink to content, never taller than one A4 landscape quarter */
    .single-card-view {
        width: var(--card-width);
        max-width: var(--card-width);
        height: auto !important;
        max-height: var(--card-max-height);
        min-height: 0;
        margin: 0 auto;
        box-shadow: 0 10px 30px rgba(0,0,0,0.4);
        border: 1px solid #ccc;
        border-radius: 0;
        flex: none;
    }

    .single-card-view .card-inner {
        height: auto;
        max-height: calc(var(--card-max-height) - 8mm);
    }

    .header-content {
        text-align: center;
        margin-bottom: 2px;
        flex-shrink: 0;
    }

    .epst-header {
        font-size: 6.5px !important;
        line-height: 1.25 !important;
        margin-bottom: 4px !important;
    }

    .epst-header div {
        font-size: inherit !important;
    }

    .logo-box {
        width: 32px;
        height: 36px;
        border: 1px solid var(--primary-blue);
        border-radius: 5px 5px 16px 16px;
        margin: 0 auto 4px auto;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 4px;
        color: var(--primary-blue);
        font-weight: bold;
        background: rgba(255, 255, 255, 0.7);
        overflow: hidden;
    }

    .school-name { font-size: 9px; font-weight: bold; color: #000; margin-bottom: 1px; }
    .student-name { font-size: 8.5px; font-weight: bold; margin: 2px 0 1px 0; }
    .class-name { font-size: 8px; font-weight: bold; margin-bottom: 3px; }

    .barcode {
        height: 16px;
        width: 80px;
        margin: 2px auto;
        background-image: linear-gradient(to right, #000 0, #000 2px, transparent 2px, transparent 4px, #000 4px, #000 7px, transparent 7px, transparent 9px, #000 9px, #000 10px, transparent 10px, transparent 14px, #000 14px, #000 16px, transparent 16px, transparent 18px, #000 18px, #000 22px, transparent 22px, transparent 23px, #000 23px, #000 26px, transparent 26px, transparent 28px, #000 28px, #000 29px, transparent 29px, transparent 32px, #000 32px, #000 36px, transparent 36px, transparent 38px, #000 38px, #000 40px, transparent 40px, transparent 42px, #000 42px, #000 45px, transparent 45px, transparent 46px, #000 46px, #000 48px, transparent 48px, transparent 52px, #000 52px, #000 53px, transparent 53px, transparent 56px, #000 56px, #000 59px, transparent 59px, transparent 60px, #000 60px, #000 62px, transparent 62px, transparent 65px, #000 65px, #000 68px, transparent 68px, transparent 70px, #000 70px, #000 72px, transparent 72px, transparent 76px, #000 76px, #000 77px, transparent 77px, transparent 80px, #000 80px);
    }

    .term-title-bar {
        background: var(--primary-blue);
        color: #fff;
        font-size: 7px;
        font-weight: bold;
        text-transform: uppercase;
        padding: 3px 4px;
        margin-top: 3px;
        line-height: 1.2;
    }

    .divider-thick { height: 2px; background-color: var(--primary-blue); margin-top: 3px; flex-shrink: 0; }
    .divider-thin { height: 1px; background-color: var(--primary-blue); margin-top: 1px; margin-bottom: 2px; flex-shrink: 0; }
    .divider-bottom { height: 1px; background-color: var(--primary-blue); margin: 2px 0; flex-shrink: 0; }

    /* Do not stretch empty space when few subjects */
    .subjects-table-wrap {
        flex: 0 1 auto;
        min-height: 0;
        overflow: hidden;
    }

    .subjects-table-wrap.density-high table { font-size: 6.5px; }
    .subjects-table-wrap.density-high td { padding: 0.5px 0; }
    .subjects-table-wrap.density-medium table { font-size: 7.5px; }
    .subjects-table-wrap.density-low table { font-size: 8.5px; }

    table { width: 100%; border-collapse: collapse; font-size: 7.5px; table-layout: fixed; }
    th { color: var(--header-orange); font-weight: bold; text-align: center; padding-bottom: 2px; font-size: 7px; }
    th.left-align, td.left-align { text-align: left; }
    td {
        padding: 1px 0;
        text-align: center;
        font-weight: bold;
        color: #1a1a1a;
        word-wrap: break-word;
        overflow: hidden;
    }
    td.subject-name {
        font-size: 6.5px;
        line-height: 1.15;
        max-height: 2.4em;
        overflow: hidden;
    }
    .fail-grade { color: var(--fail-red) !important; }

    .summary-container {
        margin-top: 3px;
        padding-top: 3px;
        border-top: 1.5px solid #000;
        flex-shrink: 0;
    }
    .summary-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 7px;
        margin-bottom: 1.5px;
    }
    .summary-row .label { flex: 2; text-align: left; }
    .summary-row .val { flex: 0.5; text-align: center; font-weight: bold; font-size: 8px; }

    .footer-wrapper {
        position: relative;
        width: 100%;
        height: 52px;
        margin-top: 6px;
        flex-shrink: 0;
    }

    .qr-code {
        position: absolute;
        left: 0;
        bottom: 0;
        width: 28px;
        height: 28px;
        background-color: white;
        padding: 1px;
        box-sizing: border-box;
        background-size: cover;
    }

    .stamp-overlay {
        position: absolute;
        left: 50%;
        bottom: 0;
        transform: translateX(-50%);
        width: 58px;
        height: 58px;
        pointer-events: none;
        opacity: 0.95;
        z-index: 5;
    }

    .signature-block {
        position: absolute;
        right: 0;
        bottom: 0;
        font-size: 6.5px;
        text-align: center;
        line-height: 1.3;
        font-weight: bold;
        color: #000;
    }

    @media print {
        @page { size: A4 landscape; margin: 0; }
        body { background: none; padding: 0; margin: 0; }
        .print-controls { display: none !important; }

        .a4-landscape {
            margin: 0;
            box-shadow: none;
            width: 297mm;
            max-height: var(--card-max-height);
            height: auto;
            page-break-after: always;
            align-items: flex-start;
        }

        .student-column {
            height: auto !important;
            max-height: var(--card-max-height) !important;
            overflow: hidden !important;
        }

        .single-card-page {
            min-height: 0 !important;
            padding: 0 !important;
            display: block !important;
        }

        .single-card-view {
            margin: 0 auto !important;
            box-shadow: none !important;
            border: none !important;
            width: var(--card-width) !important;
            max-width: var(--card-width) !important;
            height: auto !important;
            max-height: var(--card-max-height) !important;
            page-break-inside: avoid;
        }
    }
</style>
