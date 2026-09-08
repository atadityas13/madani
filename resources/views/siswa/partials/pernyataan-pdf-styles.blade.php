<style>
    @page { margin: 40px 54px 48px; }
    body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 11px;
        color: #111;
        line-height: 1.4;
    }
    .kop { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
    .kop td { vertical-align: middle; }
    .kop-logo { width: 84px; }
    .kop-logo img { width: 78px; height: auto; }
    .kop-text { text-align: center; padding: 0 8px; }
    .kop-text .line1 { font-size: 13px; font-weight: bold; letter-spacing: 0.2px; }
    .kop-text .line2 { font-size: 12px; font-weight: bold; }
    .kop-text .line3 { font-size: 12px; font-weight: bold; margin-top: 1px; }
    .kop-text .line4 { font-size: 9.5px; margin-top: 2px; white-space: nowrap; }
    .kop-text .line5 { font-size: 9.5px; margin-top: 1px; white-space: nowrap; }
    .kop-qr { width: 82px; text-align: right; }
    .kop-qr img { width: 74px; height: 74px; }
    .kop-line {
        border: 0;
        border-top: 3px solid #111;
        border-bottom: 1px solid #111;
        margin: 6px 0 14px;
    }
    .doc-title {
        text-align: center;
        font-size: 14px;
        font-weight: bold;
        text-decoration: underline;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        margin: 0 0 12px;
    }
    .foto-float {
        float: right;
        width: 90px;
        margin: 0 0 10px 14px;
        text-align: right;
    }
    .foto-float img {
        width: 86px;
        height: 108px;
        object-fit: cover;
        border: 1px solid #999;
    }
    .foto-placeholder {
        width: 86px;
        height: 108px;
        border-collapse: collapse;
        border: 1px solid #999;
    }
    .foto-placeholder td {
        width: 86px;
        height: 108px;
        text-align: center;
        vertical-align: middle;
        color: #888;
        font-size: 10px;
    }
    .clear { clear: both; height: 0; }
    .section { font-size: 12px; font-weight: bold; margin: 10px 0 3px; text-transform: uppercase; }
    .section-first { margin-top: 0; margin-bottom: 3px; }
    .page-break { page-break-before: always; }
    .page-break .section:first-child { margin-top: 0; }
    .ortu-block { page-break-inside: avoid; }
    .rows { width: 100%; border-collapse: collapse; }
    .rows td { padding: 2px 0; vertical-align: top; font-size: 11px; }
    .rows .label { width: 38%; }
    .rows .colon { width: 14px; text-align: left; }
    .pernyataan-box {
        margin-top: 0;
        text-align: justify;
        font-size: 11px;
        line-height: 1.45;
    }
    .pernyataan-box p { margin: 0 0 6px; }
    .pernyataan-box .penutup { margin: 0; }
    .surat-wrap { padding: 28px 32px 0; }
    .surat-title {
        text-align: center;
        font-size: 14px;
        font-weight: bold;
        text-decoration: underline;
        text-transform: uppercase;
        margin: 0 0 28px;
        letter-spacing: 0.5px;
    }
    .surat-body { text-align: justify; font-size: 11px; line-height: 1.5; }
    .surat-body p { margin: 0 0 6px; }
    .surat-body ol { margin: 6px 0 6px 18px; padding: 0; }
    .surat-body li { margin-bottom: 3px; }
    .identitas-surat { width: 100%; border-collapse: collapse; margin: 8px 0 10px; }
    .identitas-surat td { padding: 1.5px 0; vertical-align: top; }
    .identitas-surat .label { width: 38%; }
    .identitas-surat .colon { width: 14px; }
    .ttd-table { width: 100%; border-collapse: collapse; margin-top: 34px; }
    .ttd-table > tbody > tr > td { vertical-align: top; width: 50%; padding: 0; }
    .ttd-inner { width: 230px; border-collapse: collapse; }
    .ttd-inner td {
        text-align: left;
        font-size: 11px;
        line-height: 1.15;
        padding: 0;
    }
    .ttd-date { white-space: nowrap; }
    .ttd-img {
        height: 64px;
        width: auto;
        max-width: 160px;
        margin: 8px 0 4px;
        display: block;
    }
    .ttd-spacer { width: 120px; height: 64px; margin: 8px 0 4px; }
    .footer {
        position: fixed;
        left: 0;
        right: 0;
        bottom: -18px;
        font-size: 9px;
        color: #444;
        text-align: center;
        border-top: 1px solid #bbb;
        padding-top: 4px;
    }
</style>
