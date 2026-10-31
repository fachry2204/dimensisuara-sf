@page { margin: {{ $header ? 106 : 48 }}pt 46pt {{ $bottomMargin }}pt; }
body { margin:0 !important; font-family:helvetica, sans-serif; font-size:9.5pt; line-height:1.48; color:#172338; }
p,ol { margin-bottom:9pt; }
li { margin-top:6pt; }
h1 { font-size:18pt; } h2 { font-size:12pt; }
p { orphans:3; widows:3; }
.contract-title-0 { font-family:times,serif; font-size:17pt; line-height:1.25; margin:0 0 4pt !important; }
.contract-title-1 { font-size:10.5pt; margin:0 0 4pt !important; }
.contract-title-2 { font-size:8.5pt; color:#475569; margin:0 0 14pt !important; }
.contract-signatures { page-break-inside:avoid; margin-top:16pt; }
.contract-signing-date { font-size:9pt; color:#172338; }
.contract-section-heading { page-break-after:avoid; }
/* Keep identity in the snapshot; the PDF canvas repeats it on every page. */
.contract-pdf-header, .contract-pdf-footer { display:none; }
