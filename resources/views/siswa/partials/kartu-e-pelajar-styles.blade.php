/* ISO/IEC 7810 ID-1: 85.60 × 53.98 mm = 242.65 × 152.98 pt */
body {
    font-family: DejaVu Sans, sans-serif;
    color: #020617;
    font-size: 7pt;
    margin: 0;
    padding: 0;
}
.sheet { width: 100%; border-collapse: collapse; }
.sheet > tbody > tr > td { vertical-align: top; width: 48%; }
.sheet > tbody > tr > td.gap { width: 4%; }
.person-row { page-break-inside: avoid; margin-bottom: 8pt; }
.person-row .sheet { margin-bottom: 0; }

.card {
    width: 242.65pt;
    height: 152.98pt;
    overflow: hidden;
    border: 1.1pt solid #022C22;
    background: #ffffff;
}
table.inner {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}
table.inner > tbody > tr > td { padding: 0; margin: 0; }

/* ===== DEPAN: hdr 34 + bdy 93.5 + cap 10.5 + ftr 14.98 = 152.98 ===== */
td.hdr {
    background: #022C22;
    height: 34pt;
    padding: 0 !important;
}
table.kop { width: 100%; border-collapse: collapse; }
td.kop-logo {
    width: 32pt;
    background: #022C22;
    text-align: center;
    vertical-align: middle !important;
    padding: 0.5pt 0.6pt !important;
}
td.kop-logo img { width: 28pt; height: 28pt; }
td.kop-text {
    background: #022C22;
    text-align: center;
    vertical-align: middle !important;
    padding: 1pt 0.5pt !important;
    line-height: 1.08;
}
.kop-l1, .kop-l2 {
    color: #FBBF24;
    font-size: 4.0pt;
    font-weight: bold;
    white-space: nowrap;
}
.kop-l3 {
    color: #FBBF24;
    font-size: 4.8pt;
    font-weight: bold;
    margin-top: 0.25pt;
    white-space: nowrap;
}
.kop-l4, .kop-l5 {
    color: #ffffff;
    font-size: 3.15pt;
    margin-top: 0.2pt;
    white-space: nowrap;
}

td.bdy {
    background: #ffffff;
    height: 93.5pt;
    padding: 4.5pt 1.5pt 1pt 3pt !important;
    vertical-align: top !important;
}

table.top-row { width: 100%; border-collapse: collapse; }
table.top-row > tbody > tr > td { vertical-align: top; padding: 0; }
td.top-left { padding-right: 2pt !important; }
td.top-qr {
    width: 34pt;
    text-align: right;
    vertical-align: top !important;
    padding: 0 !important;
}
td.top-qr img {
    width: 32pt;
    height: 32pt;
    border: 0.45pt solid #D1FAE5;
    padding: 0.7pt;
    background: #ffffff;
    display: block;
}

table.ribbon { border-collapse: collapse; width: auto; }
td.ribbon-main {
    background: #065F46;
    color: #ffffff;
    font-size: 5.5pt;
    font-weight: bold;
    letter-spacing: 1.05pt;
    padding: 2.2pt 10pt !important;
    white-space: nowrap;
    vertical-align: middle !important;
    border: none !important;
    text-align: center;
}
td.ribbon-gold-row {
    background: #F59E0B;
    height: 1.5pt;
    font-size: 1pt;
    line-height: 1pt;
    padding: 0 !important;
    border: none !important;
}
.ribbon-gap { height: 5.8pt; font-size: 1pt; line-height: 1pt; }

table.main { width: 100%; border-collapse: collapse; }
table.main > tbody > tr > td { vertical-align: top; padding: 0; }
td.foto-col { width: 46pt; padding-right: 3pt !important; }
img.foto, .foto-box {
    width: 42pt;
    height: 56pt;
    border: 0.8pt solid #065F46;
    display: block;
}
.foto-box { background: #f8fafc; text-align: center; }
.foto-box img { width: 16pt; margin-top: 16pt; }

table.data { width: 100%; border-collapse: collapse; }
table.data td {
    padding: 0.35pt 0;
    font-size: 4.5pt;
    line-height: 1.28;
    letter-spacing: 0.1pt;
    vertical-align: top;
    font-weight: bold;
    color: #020617;
}
table.data tr:not(:last-child) td { white-space: nowrap; }
td.lbl {
    width: 24pt;
    text-transform: uppercase;
    padding-right: 2.5pt !important;
}
td.col {
    width: 8pt;
    text-align: center;
    padding: 0 2.5pt !important;
}
td.val {
    padding-left: 2pt !important;
}
td.val-alamat {
    font-size: 4.15pt;
    line-height: 1.28;
    letter-spacing: 0.1pt;
    white-space: normal;
    padding-left: 2pt !important;
}

td.cap-row {
    background: #ffffff;
    height: 10.5pt;
    padding: 0 4pt 0.5pt 3pt !important;
    vertical-align: middle !important;
}
table.cap { width: 100%; border-collapse: collapse; }
table.cap td { vertical-align: middle !important; padding: 0 !important; }
td.cap-txt {
    font-size: 3.25pt;
    font-weight: bold;
    color: #020617;
    white-space: nowrap;
}
td.cap-logo { width: 50pt; text-align: right; padding-right: 3.5pt !important; }
td.cap-logo img { height: 9pt; width: auto; display: block; margin-left: auto; }

td.ftr {
    background: #022C22;
    height: 14.98pt;
    padding: 0 !important;
    vertical-align: top !important;
    text-align: center;
}
table.ftr-tbl { width: 100%; border-collapse: collapse; }
td.ftr-cell {
    background: #022C22;
    height: 14.98pt;
    width: 100%;
    padding: 2.5pt 2.5pt 0 2.5pt !important;
    vertical-align: top !important;
    text-align: center;
    color: #ffffff;
    font-size: 2.9pt;
    font-weight: bold;
    line-height: 2.9pt;
    white-space: nowrap;
    letter-spacing: -0.02pt;
}

/* ===== BELAKANG ===== */
td.bh {
    background: #022C22;
    color: #FBBF24;
    text-align: center;
    height: 18.5pt;
    font-size: 7.2pt;
    font-weight: bold;
    letter-spacing: 0.85pt;
    padding: 0 4pt !important;
    vertical-align: middle !important;
}
td.bgold { height: 1.8pt; background: #F59E0B; font-size: 1pt; line-height: 1pt; }
td.bc {
    height: 112.2pt;
    text-align: center;
    vertical-align: middle !important;
    padding: 3pt 7pt !important;
    background-color: #f3f4f6;
}
.bc-lead {
    color: #022C22;
    font-size: 5.9pt;
    font-weight: bold;
    margin-bottom: 2.8pt;
}
.bc-item {
    color: #020617;
    font-size: 6.2pt;
    font-weight: bold;
    margin: 1.8pt 0;
    white-space: nowrap;
}
td.bf {
    background: #022C22;
    height: 20.48pt;
    padding: 0 !important;
    vertical-align: top !important;
}
table.bf-tbl { width: 100%; border-collapse: collapse; }
td.bf-cell {
    background: #022C22;
    height: 20.48pt;
    padding: 4.0pt 4pt 0 4pt !important;
    vertical-align: top !important;
    text-align: center;
    color: #ffffff;
    font-size: 5.6pt;
    font-weight: bold;
    letter-spacing: 0.35pt;
    white-space: nowrap;
    line-height: 5.6pt;
}
