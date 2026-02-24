<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>CertVerify — MySQL Powered Certificate System</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;1,400&display=swap" rel="stylesheet"/>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#030810;--bg2:#060d1c;--surf:#0a1020;--surf2:#0e1630;
  --border:rgba(255,255,255,0.06);--border2:rgba(255,255,255,0.11);
  --blue:#3b82f6;--blue-d:#1d4ed8;--blue-g:linear-gradient(135deg,#3b82f6,#1d4ed8);
  --blue-glow:rgba(59,130,246,0.22);--teal:#06d6a0;--rose:#f43f5e;--amber:#f59e0b;
  --text:#dde4f5;--text2:#7c89a8;--text3:#3f4d68;
  --fh:'Syne',sans-serif;--fb:'DM Sans',sans-serif;
  --r:14px;--rs:9px;--sh:0 8px 40px rgba(0,0,0,.55);
}
html{scroll-behavior:smooth}
body{font-family:var(--fb);background:var(--bg);color:var(--text);min-height:100vh;overflow-x:hidden}

/* BG */
.bg-mesh{position:fixed;inset:0;z-index:0;pointer-events:none}
.bg-mesh::before{content:'';position:absolute;width:900px;height:900px;top:-300px;left:-200px;background:radial-gradient(circle,rgba(59,130,246,.08) 0%,transparent 65%);animation:drift 14s ease-in-out infinite alternate}
.bg-mesh::after{content:'';position:absolute;width:700px;height:700px;bottom:-200px;right:-100px;background:radial-gradient(circle,rgba(6,214,160,.06) 0%,transparent 65%);animation:drift 10s ease-in-out infinite alternate-reverse}
.bg-grid{position:fixed;inset:0;z-index:0;pointer-events:none;background-image:linear-gradient(rgba(59,130,246,.025) 1px,transparent 1px),linear-gradient(90deg,rgba(59,130,246,.025) 1px,transparent 1px);background-size:52px 52px}
@keyframes drift{from{transform:translate(0,0) scale(1)}to{transform:translate(50px,35px) scale(1.1)}}

/* NAV */
nav{position:fixed;top:0;left:0;right:0;z-index:200;display:flex;align-items:center;justify-content:space-between;padding:15px 48px;background:rgba(3,8,16,.85);backdrop-filter:blur(24px);border-bottom:1px solid var(--border)}
.nav-logo{display:flex;align-items:center;gap:10px;font-family:var(--fh);font-weight:800;font-size:19px;cursor:pointer;background:linear-gradient(135deg,#fff,var(--blue));-webkit-background-clip:text;-webkit-text-fill-color:transparent;text-decoration:none}
.nav-tabs{display:flex;gap:4px;background:var(--surf);border-radius:50px;padding:4px}
.nav-tab{padding:8px 22px;border-radius:50px;border:none;background:transparent;color:var(--text2);font-size:13px;font-weight:600;cursor:pointer;font-family:var(--fb);transition:.2s}
.nav-tab:hover{color:var(--text)}
.nav-tab.active{background:var(--blue-g);color:#fff;box-shadow:0 4px 14px var(--blue-glow)}
.db-badge{display:flex;align-items:center;gap:7px;background:rgba(6,214,160,.1);border:1px solid rgba(6,214,160,.25);border-radius:50px;padding:6px 14px;font-size:12px;font-weight:700;color:var(--teal)}
.db-dot{width:6px;height:6px;border-radius:50%;background:var(--teal);animation:pulse 1.8s infinite}
@keyframes pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.4;transform:scale(.7)}}

/* LAYOUT */
.app{position:relative;z-index:1;min-height:100vh;display:flex;flex-direction:column}
.page{display:none;padding-top:80px;flex:1;animation:fadeUp .3s ease}
.page.active{display:flex;flex-direction:column}
@keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:none}}

/* ===== VERIFY PAGE ===== */
.verify-hero{padding:80px 40px 48px;text-align:center;max-width:700px;margin:0 auto}
.vtag{display:inline-flex;align-items:center;gap:8px;background:rgba(59,130,246,.1);border:1px solid rgba(59,130,246,.25);border-radius:50px;padding:7px 18px;font-size:12px;font-weight:700;color:var(--blue);margin-bottom:28px}
.vtag .dot{width:6px;height:6px;border-radius:50%;background:var(--blue);animation:pulse 2s infinite}
.verify-hero h1{font-family:var(--fh);font-size:clamp(38px,7vw,72px);font-weight:800;line-height:1.05;margin-bottom:18px}
.verify-hero h1 em{font-style:normal;background:linear-gradient(135deg,var(--blue),var(--teal));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.verify-hero p{font-size:17px;color:var(--text2);line-height:1.7;margin-bottom:48px}

.scard{max-width:660px;margin:0 auto 56px;background:var(--surf);border:1px solid var(--border2);border-radius:20px;padding:26px;box-shadow:var(--sh)}
.srow{display:flex;gap:10px}
.srow input{flex:1;background:var(--bg2);border:1.5px solid var(--border2);border-radius:var(--rs);padding:14px 18px;font-size:15px;color:var(--text);font-family:var(--fb);outline:none;transition:.2s}
.srow input:focus{border-color:var(--blue);box-shadow:0 0 0 3px rgba(59,130,246,.14)}
.srow input::placeholder{color:var(--text3)}
.btn-v{padding:14px 28px;background:var(--blue-g);color:#fff;border:none;border-radius:var(--rs);font-size:14px;font-weight:700;cursor:pointer;font-family:var(--fh);transition:.2s;box-shadow:0 4px 16px var(--blue-glow);white-space:nowrap}
.btn-v:hover{transform:translateY(-2px);box-shadow:0 7px 24px var(--blue-glow)}
.btn-v:disabled{opacity:.5;cursor:not-allowed;transform:none}
.qids{margin-top:14px;display:flex;flex-wrap:wrap;gap:8px;align-items:center}
.qids span{font-size:12px;color:var(--text3);font-weight:600}
.qid{background:var(--bg2);border:1px solid var(--border);border-radius:6px;padding:4px 12px;font-size:12px;color:var(--text2);cursor:pointer;transition:.2s;font-weight:600}
.qid:hover{border-color:var(--blue);color:var(--blue)}

.rarea{max-width:860px;margin:0 auto;padding:0 40px 80px;width:100%}
.sbox{text-align:center;padding:60px 40px}
.sbox-ic{font-size:54px;margin-bottom:18px}
.sbox h3{font-family:var(--fh);font-size:22px;font-weight:700;margin-bottom:10px}
.sbox p{color:var(--text2);font-size:15px;line-height:1.6}
.loader{display:flex;flex-direction:column;align-items:center;gap:14px;padding:56px}
.sp{width:38px;height:38px;border-radius:50%;border:3px solid var(--border2);border-top-color:var(--blue);animation:spin .7s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}

/* Certificate */
.vbanner{display:flex;align-items:center;gap:10px;justify-content:center;background:rgba(6,214,160,.1);border:1px solid rgba(6,214,160,.25);border-radius:50px;padding:11px 24px;width:fit-content;margin:0 auto 30px}
.ck{width:28px;height:28px;border-radius:50%;background:var(--teal);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:13px;color:#030810}
.vbanner strong{color:var(--teal);font-size:14px;font-weight:700}
.vbanner span{font-size:13px;color:var(--teal)}

.cert{background:linear-gradient(155deg,#091322 0%,#050d1c 100%);border:1px solid rgba(59,130,246,.18);border-radius:22px;overflow:hidden;box-shadow:0 20px 70px rgba(0,0,0,.6);position:relative;margin-bottom:24px}
.cert-stripe{height:4px;background:linear-gradient(90deg,var(--blue),var(--teal),var(--rose))}
.cert-wm{position:absolute;right:-20px;bottom:-20px;font-size:170px;opacity:.025;font-family:var(--fh);font-weight:800;pointer-events:none}
.cert-h{padding:34px 44px 26px;display:flex;justify-content:space-between;align-items:flex-start;border-bottom:1px solid rgba(255,255,255,0.05)}
.co-n{font-family:var(--fh);font-size:22px;font-weight:800;color:#fff;display:flex;align-items:center;gap:8px;margin-bottom:4px}
.co-s{font-size:11px;color:var(--text3);text-transform:uppercase;letter-spacing:1px}
.cid-b{text-align:right}
.cid-l{font-size:10px;color:var(--text3);font-weight:700;letter-spacing:1.5px;text-transform:uppercase;margin-bottom:5px}
.cid-v{font-family:var(--fh);font-size:17px;font-weight:800;color:var(--blue)}
.cert-b{padding:34px 44px}
.csub{font-size:10px;letter-spacing:2.5px;text-transform:uppercase;color:var(--text3);font-weight:700;margin-bottom:10px}
.cname{font-family:var(--fh);font-size:clamp(28px,4.5vw,46px);font-weight:800;color:#fff;margin-bottom:10px;line-height:1.1}
.cdesc{font-size:15px;color:var(--text2);line-height:1.85;margin-bottom:34px}
.cdesc strong{color:var(--blue);font-weight:700}
.cgrid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:34px}
.cf{background:rgba(255,255,255,.033);border:1px solid rgba(255,255,255,.06);border-radius:12px;padding:15px 17px}
.cfl{font-size:10px;color:var(--text3);font-weight:700;letter-spacing:1px;text-transform:uppercase;margin-bottom:5px}
.cfv{font-family:var(--fh);font-size:15px;font-weight:700;color:#fff}
.cert-f{padding:22px 44px;border-top:1px solid rgba(255,255,255,.05);display:flex;justify-content:space-between;align-items:center}
.seal{display:flex;align-items:center;gap:12px}
.seal-r{width:44px;height:44px;border-radius:50%;border:2px solid rgba(59,130,246,.3);background:rgba(59,130,246,.1);display:flex;align-items:center;justify-content:center;font-size:17px}
.seal-i strong{display:block;font-size:13px;color:var(--text2);font-weight:600}
.seal-i span{font-size:11px;color:var(--text3)}
.sig{text-align:right}
.sig-l{width:110px;height:1.5px;background:rgba(255,255,255,.1);margin-bottom:7px;margin-left:auto}
.sig-n{font-family:var(--fh);font-size:14px;font-weight:700;color:var(--text2)}
.sig-r{font-size:11px;color:var(--text3)}

.cacts{display:flex;gap:12px;justify-content:center}
.btn-a{display:inline-flex;align-items:center;gap:8px;padding:12px 24px;border-radius:50px;font-size:14px;font-weight:700;cursor:pointer;font-family:var(--fh);transition:.2s;border:none;text-decoration:none}
.btn-ap{background:var(--blue-g);color:#fff;box-shadow:0 4px 16px var(--blue-glow)}
.btn-ap:hover{transform:translateY(-2px);box-shadow:0 8px 26px var(--blue-glow)}
.btn-ao{background:transparent;color:var(--text2);border:1.5px solid var(--border2)!important}
.btn-ao:hover{border-color:var(--blue)!important;color:var(--blue)}

/* ===== ADMIN ===== */
.aw{max-width:1120px;margin:0 auto;padding:40px}
.ah{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:32px}
.ah h2{font-family:var(--fh);font-size:28px;font-weight:800;margin-bottom:6px}
.ah p{color:var(--text2);font-size:14px}
.ah-r{display:flex;gap:10px;align-items:center}
.btn-add{display:flex;align-items:center;gap:8px;padding:11px 22px;background:var(--blue-g);color:#fff;border:none;border-radius:50px;font-size:14px;font-weight:700;cursor:pointer;font-family:var(--fh);transition:.2s;box-shadow:0 4px 14px var(--blue-glow)}
.btn-add:hover{transform:translateY(-1px)}

.astats{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px}
.ast{background:var(--surf);border:1px solid var(--border);border-radius:var(--r);padding:20px 22px;display:flex;align-items:center;gap:14px}
.ast-i{width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:19px;flex-shrink:0}
.i-b{background:rgba(59,130,246,.14)} .i-g{background:rgba(6,214,160,.12)} .i-r{background:rgba(244,63,94,.12)} .i-y{background:rgba(245,158,11,.12)}
.ast-v{font-family:var(--fh);font-size:26px;font-weight:800;line-height:1;margin-bottom:3px}
.ast-l{font-size:11px;color:var(--text3);font-weight:600;text-transform:uppercase;letter-spacing:.5px}

.panel{background:var(--surf);border:1px solid var(--border);border-radius:var(--r);overflow:hidden;margin-bottom:24px}
.ptop{padding:18px 24px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:12px}
.ptop h3{font-family:var(--fh);font-size:16px;font-weight:700}
.tsearch{background:var(--bg2);border:1.5px solid var(--border);border-radius:var(--rs);padding:9px 14px;font-size:13px;color:var(--text);font-family:var(--fb);outline:none;width:220px;transition:.2s}
.tsearch:focus{border-color:var(--blue)}
.tsearch::placeholder{color:var(--text3)}
table{width:100%;border-collapse:collapse;font-size:13px}
th{background:var(--bg2);color:var(--text3);font-weight:700;font-size:10px;text-transform:uppercase;letter-spacing:1px;padding:11px 18px;text-align:left;border-bottom:1px solid var(--border)}
td{padding:13px 18px;border-bottom:1px solid var(--border);vertical-align:middle}
tr:last-child td{border-bottom:none}
tr:hover td{background:rgba(255,255,255,.016)}
.tid{font-family:var(--fh);font-weight:800;color:var(--blue);font-size:13px}
.chip{display:inline-flex;align-items:center;padding:3px 10px;border-radius:50px;font-size:11px;font-weight:700}
.ch-b{background:rgba(59,130,246,.14);color:var(--blue)}
.ch-g{background:rgba(6,214,160,.14);color:var(--teal)}
.tacts{display:flex;gap:6px}
.bsm{padding:5px 12px;border-radius:50px;border:1px solid var(--border2);background:transparent;font-size:11px;font-weight:700;cursor:pointer;font-family:var(--fb);transition:.2s;color:var(--text2)}
.bsm:hover{border-color:var(--blue);color:var(--blue)}
.bsm.del:hover{border-color:var(--rose);color:var(--rose)}
.empty-r{text-align:center;padding:56px;color:var(--text3)}

/* UPLOAD ZONE */
.uzone{border:2px dashed var(--border2);border-radius:var(--r);padding:44px 24px;text-align:center;cursor:pointer;transition:.25s}
.uzone:hover,.uzone.over{border-color:var(--blue);background:rgba(59,130,246,.04)}
.uzone h4{font-family:var(--fh);font-size:18px;font-weight:700;margin-bottom:8px}
.uzone p{font-size:13px;color:var(--text2);margin-bottom:16px}
.utag{display:inline-block;background:var(--surf2);color:var(--text3);font-size:11px;font-weight:700;padding:3px 10px;border-radius:6px;text-transform:uppercase;margin:0 3px;letter-spacing:.5px}
.pbar-w{background:var(--bg);border-radius:50px;height:6px;overflow:hidden;margin:14px 0 6px}
.pbar{height:100%;background:linear-gradient(90deg,var(--blue),var(--teal));border-radius:50px;transition:width .3s}

/* MODAL */
.ov{position:fixed;inset:0;z-index:500;background:rgba(0,0,0,.78);backdrop-filter:blur(12px);display:flex;align-items:center;justify-content:center;padding:20px}
.modal{background:var(--surf);border:1px solid var(--border2);border-radius:22px;width:100%;max-width:540px;box-shadow:0 24px 80px rgba(0,0,0,.7);animation:pop .25s cubic-bezier(.34,1.56,.64,1)}
@keyframes pop{from{opacity:0;transform:scale(.92) translateY(16px)}to{opacity:1;transform:none}}
.mh{padding:22px 28px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.mh h3{font-family:var(--fh);font-size:18px;font-weight:700}
.mc{background:none;border:none;color:var(--text2);font-size:20px;cursor:pointer;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;transition:.2s}
.mc:hover{background:var(--surf2);color:var(--text)}
.mb{padding:26px 28px}
.fg{margin-bottom:17px}
.fg label{display:block;font-size:11px;font-weight:700;color:var(--text2);margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px}
.fg input,.fg select{width:100%;background:var(--bg2);border:1.5px solid var(--border2);border-radius:var(--rs);padding:11px 14px;font-size:14px;color:var(--text);font-family:var(--fb);outline:none;transition:.2s}
.fg input:focus,.fg select:focus{border-color:var(--blue);box-shadow:0 0 0 3px rgba(59,130,246,.12)}
.fg input::placeholder{color:var(--text3)}
.fg select option{background:var(--surf2)}
.frow{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.mf{padding:18px 28px;border-top:1px solid var(--border);display:flex;gap:10px;justify-content:flex-end}
.bcnl{padding:10px 20px;background:transparent;border:1.5px solid var(--border2);border-radius:50px;color:var(--text2);font-size:13px;font-weight:700;cursor:pointer;font-family:var(--fb);transition:.2s}
.bcnl:hover{border-color:var(--text);color:var(--text)}
.bsv{padding:10px 24px;background:var(--blue-g);border:none;border-radius:50px;color:#fff;font-size:13px;font-weight:700;cursor:pointer;font-family:var(--fh);transition:.2s;box-shadow:0 4px 14px var(--blue-glow)}
.bsv:hover{transform:translateY(-1px)}
.bsv:disabled{opacity:.5;cursor:not-allowed;transform:none}
.mmsg{padding:10px 14px;border-radius:var(--rs);font-size:13px;font-weight:600;margin-bottom:14px}
.merr{background:rgba(244,63,94,.1);border:1px solid rgba(244,63,94,.3);color:var(--rose)}
.mok{background:rgba(6,214,160,.1);border:1px solid rgba(6,214,160,.3);color:var(--teal)}
.hidden{display:none!important}

/* Admin login */
.alog{max-width:420px;margin:80px auto 0;padding:40px}
.lcard{background:var(--surf);border:1px solid var(--border2);border-radius:22px;padding:36px;box-shadow:var(--sh)}
.lcard h2{font-family:var(--fh);font-size:24px;font-weight:800;margin-bottom:6px}
.lcard .sub{color:var(--text2);font-size:14px;margin-bottom:28px}
.lhint{background:rgba(59,130,246,.08);border:1px solid rgba(59,130,246,.2);border-radius:var(--rs);padding:12px 15px;margin-bottom:20px;font-size:13px;color:var(--text2);line-height:1.6}
.lhint strong{color:var(--blue)}

/* TOAST */
.twrap{position:fixed;bottom:24px;right:24px;z-index:999;display:flex;flex-direction:column;gap:10px;pointer-events:none}
.toast{padding:13px 20px;border-radius:var(--r);font-size:14px;font-weight:600;box-shadow:0 8px 30px rgba(0,0,0,.5);animation:slideU .3s ease;max-width:320px;pointer-events:all}
.tok{background:#091e14;border:1px solid rgba(6,214,160,.3);color:var(--teal)}
.terr{background:#1a0810;border:1px solid rgba(244,63,94,.3);color:var(--rose)}
.tinf{background:#080e24;border:1px solid rgba(59,130,246,.3);color:var(--blue)}
@keyframes slideU{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:none}}

/* Connection indicator */
.conn-status{display:flex;align-items:center;gap:6px;font-size:11px;font-weight:600;padding:5px 12px;border-radius:50px}
.conn-ok{background:rgba(6,214,160,.1);color:var(--teal);border:1px solid rgba(6,214,160,.25)}
.conn-err{background:rgba(244,63,94,.1);color:var(--rose);border:1px solid rgba(244,63,94,.25)}
.conn-dot{width:5px;height:5px;border-radius:50%;background:currentColor;animation:pulse 1.8s infinite}

@media(max-width:768px){
  nav{padding:13px 16px}.nav-tabs .nav-tab{padding:7px 14px;font-size:12px}
  .verify-hero{padding:50px 20px 28px}.scard{margin:0 16px 40px;padding:18px}
  .cgrid{grid-template-columns:1fr 1fr}.cert-h{flex-direction:column;gap:14px}
  .cacts{flex-wrap:wrap}.rarea{padding:0 16px 60px}
  .aw{padding:20px 16px}.ah{flex-direction:column;gap:14px}
  .astats{grid-template-columns:1fr 1fr}.frow{grid-template-columns:1fr}
}
@media print{
  nav,.cacts,.aw,.bg-mesh,.bg-grid,.twrap,#page-admin{display:none!important}
  .cert{border:1px solid #ddd;box-shadow:none;background:#fff;color:#000}
  .cert-stripe{background:linear-gradient(90deg,#3b82f6,#06d6a0)}
  .cname,.co-n,.cid-v,.cfv{color:#000!important}
  .cdesc,.seal-i strong,.sig-n{color:#333!important}
  .cfl,.cid-l,.co-s,.csub,.sig-r,.seal-i span,.cert-wm{color:#999!important}
  .cf{background:#f5f5f5;border-color:#e0e0e0}
  .cert-h,.cert-b,.cert-f{padding:22px 32px}
  #page-verify{display:block!important}
}
</style>
<script>
// Prevent browser autofill on all inputs
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('vid').value = '';
    document.getElementById('auser').value = '';
    document.getElementById('apass').value = '';
    
    // Extra: prevent autofill
    setTimeout(function() {
        document.getElementById('vid').value = '';
    }, 100);
});
</script>
</head>
<body>
<div class="bg-mesh"></div>
<div class="bg-grid"></div>

<div class="app">
  <nav>
    <a class="nav-logo" onclick="showPage('verify')">
      <span style="background:var(--blue-g);-webkit-background-clip:text;-webkit-text-fill-color:transparent">✦</span> CertVerify
    </a>
    <div class="nav-tabs">
      <button class="nav-tab active" onclick="showPage('verify')">🔍 Verify</button>
      <button class="nav-tab" onclick="showPage('admin')">⚙ Admin</button>
    </div>
    <div style="display:flex;align-items:center;gap:10px">
      <div id="conn-indicator" class="conn-status conn-ok"><div class="conn-dot"></div> MySQL Connected</div>
      <div class="db-badge"><div class="db-dot"></div>Live DB</div>
    </div>
  </nav>

  <!-- ===== VERIFY PAGE ===== -->
  <div class="page active" id="page-verify">
    <div class="verify-hero">
      <div class="vtag"><div class="dot"></div> Powered by MySQL + PHP</div>
      <h1>Verify Your<br/><em>Certificate</em></h1>
      <p>Enter your unique Certificate ID to instantly retrieve your verified internship certificate from the live MySQL database.</p>
    </div>

    <div class="scard" style="margin-left:auto;margin-right:auto;max-width:660px;width:calc(100% - 80px)">
      <div class="srow">
        <input type="text" id="vid" placeholder="e.g. CERT-2024-001" autocomplete="new-password" value="" onkeydown="if(event.key==='Enter')doVerify()"/>
        <button class="btn-v" id="vbtn" onclick="doVerify()">Verify →</button>
      </div>
      <div class="qids">
        <span>Try sample IDs:</span>
        <span class="qid" onclick="tryId('CERT-2024-001')">CERT-2024-001</span>
        <span class="qid" onclick="tryId('CERT-2024-002')">CERT-2024-002</span>
        <span class="qid" onclick="tryId('CERT-2024-003')">CERT-2024-003</span>
        <span class="qid" onclick="tryId('CERT-2024-004')">CERT-2024-004</span>
        <span class="qid" onclick="tryId('CERT-2024-005')">CERT-2024-005</span>
      </div>
    </div>
    <div id="vresult" class="rarea"></div>
  </div>

  <!-- ===== ADMIN PAGE ===== -->
  <div class="page" id="page-admin">
    <!-- LOGIN GATE -->
    <div id="agate" class="alog">
      <div class="lcard">
        <h2>Admin Access</h2>
        <p class="sub">Sign in to manage the MySQL certificate database</p>
        <div class="lhint">
          <strong>Demo Credentials:</strong><br/>
          Username: <code>admin</code> &nbsp;|&nbsp; Password: <code>Admin@123</code>
        </div>
        <div class="fg"><label>Username or Email</label>
          <input type="text" id="auser" placeholder="admin" onkeydown="if(event.key==='Enter')doLogin()"/>
        </div>
        <div class="fg"><label>Password</label>
          <input type="password" id="apass" placeholder="Admin@123" onkeydown="if(event.key==='Enter')doLogin()"/>
        </div>
        <div id="lerr" class="mmsg merr hidden"></div>
        <button class="bsv" style="width:100%;padding:13px;font-size:14px" onclick="doLogin()">Sign In →</button>
      </div>
    </div>

    <!-- ADMIN PANEL -->
    <div id="apanel" class="aw hidden">
      <div class="ah">
        <div>
          <h2>Certificate Database</h2>
          <p>Connected to MySQL — all changes are permanent and live.</p>
        </div>
        <div class="ah-r">
          <button class="btn-add" onclick="openUploadModal()">📤 Bulk Upload</button>
          <button class="btn-add" onclick="openAddModal()">+ Add Certificate</button>
          <button class="bsm" style="padding:10px 16px" onclick="doLogout()">Logout</button>
        </div>
      </div>

      <div class="astats">
        <div class="ast"><div class="ast-i i-b">📋</div><div><div class="ast-v" id="as-tot">—</div><div class="ast-l">Total Certificates</div></div></div>
        <div class="ast"><div class="ast-i i-g">✦</div><div><div class="ast-v" id="as-dom">—</div><div class="ast-l">Unique Domains</div></div></div>
        <div class="ast"><div class="ast-i i-r">🔍</div><div><div class="ast-v" id="as-ver">—</div><div class="ast-l">Total Verifications</div></div></div>
        <div class="ast"><div class="ast-i i-y">📅</div><div><div class="ast-v" id="as-tod">—</div><div class="ast-l">Verified Today</div></div></div>
      </div>

      <div class="panel">
        <div class="ptop">
          <h3>All Certificates</h3>
          <div style="display:flex;gap:10px">
            <input class="tsearch" id="tsearch" placeholder="Search certificates..." oninput="filterTable()"/>
            <select class="tsearch" id="tdom" onchange="filterTable()" style="width:180px">
              <option value="">All Domains</option>
            </select>
          </div>
        </div>
        <div style="overflow-x:auto"><table>
          <thead><tr>
            <th>Certificate ID</th><th>Student Name</th><th>Domain</th>
            <th>Start Date</th><th>End Date</th><th>Duration</th><th>Actions</th>
          </tr></thead>
          <tbody id="atbody"></tbody>
        </table></div>
      </div>
    </div>
  </div>
</div>

<!-- ADD/EDIT MODAL -->
<div class="ov hidden" id="cmod">
  <div class="modal">
    <div class="mh"><h3 id="mtitle">Add Certificate</h3><button class="mc" onclick="closeMod('cmod')">✕</button></div>
    <div class="mb">
      <div id="cmsg" class="mmsg hidden"></div>
      <div class="fg"><label>Certificate ID *</label><input type="text" id="m-id" placeholder="e.g. CERT-2024-011"/></div>
      <div class="fg"><label>Student Full Name *</label><input type="text" id="m-name" placeholder="e.g. Aarav Sharma"/></div>
      <div class="fg"><label>Internship Domain *</label>
        <select id="m-dom">
          <option>Web Development</option><option>Data Science</option><option>UI/UX Design</option>
          <option>Android Development</option><option>Machine Learning</option><option>Cybersecurity</option>
          <option>Cloud Computing</option><option>Blockchain</option><option>Embedded Systems</option>
          <option>Full Stack Development</option>
        </select>
      </div>
      <div class="frow">
        <div class="fg"><label>Start Date *</label><input type="date" id="m-start"/></div>
        <div class="fg"><label>End Date *</label><input type="date" id="m-end"/></div>
      </div>
    </div>
    <div class="mf">
      <button class="bcnl" onclick="closeMod('cmod')">Cancel</button>
      <button class="bsv" id="msave" onclick="saveCert()">Save to MySQL</button>
    </div>
  </div>
</div>

<!-- UPLOAD MODAL -->
<div class="ov hidden" id="umod">
  <div class="modal">
    <div class="mh"><h3>Bulk Upload (CSV)</h3><button class="mc" onclick="closeMod('umod')">✕</button></div>
    <div class="mb">
      <div id="umsg" class="mmsg hidden"></div>
      <div class="uzone" id="uzone" onclick="document.getElementById('ufile').click()">
        <div style="font-size:36px;margin-bottom:12px">📊</div>
        <h4>Drop CSV file here</h4>
        <p>or click to browse</p>
        <div><span class="utag">.csv</span><span class="utag">.txt</span></div>
        <input type="file" id="ufile" accept=".csv,.txt" style="display:none" onchange="handleFileSelect(event)"/>
      </div>
      <div id="ufile-info" class="hidden" style="margin-top:14px;background:var(--surf2);border-radius:var(--rs);padding:12px 16px;display:flex;align-items:center;gap:12px">
        <span style="font-size:22px">📄</span>
        <div style="flex:1"><div id="ufile-name" style="font-weight:600;font-size:14px"></div><div id="ufile-size" style="font-size:12px;color:var(--text3)"></div></div>
        <button onclick="clearUpload()" style="background:none;border:none;color:var(--text3);cursor:pointer;font-size:16px">✕</button>
      </div>
      <div id="uprog" class="hidden" style="margin-top:14px">
        <div class="pbar-w"><div class="pbar" id="upbar" style="width:0%"></div></div>
        <div style="font-size:12px;color:var(--text2);display:flex;justify-content:space-between"><span id="uplbl">Uploading...</span><span id="uppct">0%</span></div>
      </div>
      <div style="margin-top:16px;background:rgba(59,130,246,.07);border:1px solid rgba(59,130,246,.2);border-radius:var(--rs);padding:12px 15px;font-size:12px;color:var(--text2);line-height:1.7">
        <strong style="color:var(--blue);display:block;margin-bottom:4px">Required CSV columns (row 1 = headers):</strong>
        cert_id &nbsp;|&nbsp; student_name &nbsp;|&nbsp; domain &nbsp;|&nbsp; start_date &nbsp;|&nbsp; end_date
      </div>
      <button onclick="downloadTemplate()" style="margin-top:12px;width:100%;padding:9px;background:transparent;border:1.5px solid var(--border2);border-radius:var(--rs);color:var(--text2);font-size:12px;font-weight:700;cursor:pointer;font-family:var(--fb);transition:.2s" onmouseover="this.style.borderColor='var(--blue)'" onmouseout="this.style.borderColor='var(--border2)'">
        ⬇ Download CSV Template
      </button>
    </div>
    <div class="mf">
      <button class="bcnl" onclick="closeMod('umod')">Cancel</button>
      <button class="bsv" id="ubtn" onclick="doUpload()" disabled>Upload to MySQL</button>
    </div>
  </div>
</div>

<!-- TOAST -->
<div class="twrap" id="twrap"></div>

<script>
// ============================================================
// CONFIG — Change this to your server path
// ============================================================
const API = './api'; // relative path to api/ folder

// ============================================================
// NAVIGATION
// ============================================================
function showPage(id) {
  document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.nav-tab').forEach((t,i) => t.classList.toggle('active',(i===0&&id==='verify')||(i===1&&id==='admin')));
  document.getElementById('page-'+id).classList.add('active');
  if (id==='admin' && loggedIn) loadAdminData();
}

// ============================================================
// API HELPER
// ============================================================
async function api(endpoint, opts={}) {
  try {
    const res = await fetch(`${API}/${endpoint}`, {
      credentials: 'include',
      headers: { 'Content-Type': 'application/json', ...(opts.headers||{}) },
      ...opts
    });
    const data = await res.json();
    return { ok: res.ok, status: res.status, data };
  } catch(e) {
    setConnStatus(false);
    return { ok: false, status: 0, data: { error: 'Cannot reach PHP server. Make sure Apache/Nginx is running.' } };
  }
}

function setConnStatus(ok) {
  const el = document.getElementById('conn-indicator');
  el.className = 'conn-status ' + (ok ? 'conn-ok' : 'conn-err');
  el.innerHTML = `<div class="conn-dot"></div> ${ok ? 'MySQL Connected' : 'Server Offline'}`;
}

// ============================================================
// VERIFY
// ============================================================
function tryId(id) { document.getElementById('vid').value = id; doVerify(); }

async function doVerify() {
  const raw = document.getElementById('vid').value.trim();
  const id  = raw.toUpperCase();
  const area = document.getElementById('vresult');
  const btn  = document.getElementById('vbtn');
  if (!id) { area.innerHTML=`<div class="sbox"><div class="sbox-ic">💡</div><h3>Enter a Certificate ID</h3><p>Type your certificate ID in the box above.</p></div>`; return; }

  btn.disabled=true;
  area.innerHTML=`<div class="loader"><div class="sp"></div><p style="color:var(--text2);font-size:14px">Querying MySQL database...</p></div>`;

  const {ok, data} = await api(`verify.php?id=${encodeURIComponent(id)}`);
  btn.disabled=false;

  if (!ok || !data.success) {
    setConnStatus(data.error !== 'Cannot reach PHP server. Make sure Apache/Nginx is running.');
    area.innerHTML=`<div class="sbox"><div class="sbox-ic">❌</div><h3>Certificate Not Found</h3><p>${data.error || 'No certificate found for ID <strong style="color:var(--blue)">' + id + '</strong>.'}</p><p style="margin-top:12px;font-size:13px;color:var(--text3)">Check the ID and try again, or contact your administrator.</p></div>`;
    return;
  }

  setConnStatus(true);
  const c = data.data;
  const fmt = s => new Date(s).toLocaleDateString('en-US',{day:'numeric',month:'long',year:'numeric'});

  area.innerHTML=`
    <div class="vbanner"><div class="ck">✓</div><strong>Certificate Verified</strong><span>• Fetched from MySQL Database</span></div>
    <div class="cert" id="cp">
      <div class="cert-stripe"></div>
      <div class="cert-wm">✦</div>
      <div class="cert-h">
        <div><div class="co-n"><span style="color:var(--blue)">✦</span> CertVerify Institute</div><div class="co-s">Department of Internship Programs</div></div>
        <div class="cid-b"><div class="cid-l">Certificate ID</div><div class="cid-v">${c.cert_id}</div></div>
      </div>
      <div class="cert-b">
        <div class="csub">Certificate of Internship Completion</div>
        <div class="cname">${c.student_name}</div>
        <p class="cdesc">This is to certify that the above-named individual has successfully completed an internship in the field of <strong>${c.domain}</strong> at CertVerify Institute. The program was completed with satisfactory performance and in adherence to all professional standards.</p>
        <div class="cgrid">
          <div class="cf"><div class="cfl">Domain</div><div class="cfv">${c.domain}</div></div>
          <div class="cf"><div class="cfl">Start Date</div><div class="cfv">${fmt(c.start_date)}</div></div>
          <div class="cf"><div class="cfl">End Date</div><div class="cfv">${fmt(c.end_date)}</div></div>
        </div>
      </div>
      <div class="cert-f">
        <div class="seal"><div class="seal-r">✦</div><div class="seal-i"><strong>Digitally Verified via MySQL</strong><span>Duration: ${c.duration} • Verified: ${c.verified_at}</span></div></div>
        <div class="sig"><div class="sig-l"></div><div class="sig-n">Program Director</div><div class="sig-r">CertVerify Institute</div></div>
      </div>
    </div>
    <div class="cacts">
      <button class="btn-a btn-ap" onclick="printCert()">📥 Download / Print</button>
      <button class="btn-a btn-ao" onclick="document.getElementById('vid').value='';document.getElementById('vresult').innerHTML=''">🔍 Verify Another</button>
    </div>`;
}

function printCert() {
  const el = document.getElementById('cp');
  if(!el) return;
  const w = window.open('','_blank');
  w.document.write(`<!DOCTYPE html><html><head>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
    <style>
    *{box-sizing:border-box;margin:0;padding:0}:root{--blue:#3b82f6;--teal:#06d6a0;--rose:#f43f5e}
    body{background:#fff;padding:40px;font-family:'DM Sans',sans-serif}
    .cert{border:1px solid #e5e7eb;border-radius:16px;overflow:hidden;max-width:820px;margin:0 auto;position:relative}
    .cert-stripe{height:4px;background:linear-gradient(90deg,var(--blue),var(--teal),var(--rose))}
    .cert-wm{position:absolute;right:-20px;bottom:-20px;font-size:160px;opacity:.035;font-family:'Syne',sans-serif;font-weight:800;pointer-events:none;color:#000}
    .cert-h{padding:28px 36px 22px;display:flex;justify-content:space-between;align-items:flex-start;border-bottom:1px solid #f0f0f0}
    .co-n{font-family:'Syne',sans-serif;font-size:21px;font-weight:800;color:#111;display:flex;align-items:center;gap:8px;margin-bottom:3px}
    .co-s{font-size:11px;color:#aaa;text-transform:uppercase;letter-spacing:1px}
    .cid-b{text-align:right}.cid-l{font-size:10px;color:#aaa;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;margin-bottom:4px}
    .cid-v{font-family:'Syne',sans-serif;font-size:16px;font-weight:800;color:var(--blue)}
    .cert-b{padding:28px 36px}.csub{font-size:10px;letter-spacing:2.5px;text-transform:uppercase;color:#aaa;font-weight:700;margin-bottom:8px}
    .cname{font-family:'Syne',sans-serif;font-size:40px;font-weight:800;color:#111;margin-bottom:10px;line-height:1.1}
    .cdesc{font-size:14px;color:#555;line-height:1.85;margin-bottom:28px}.cdesc strong{color:var(--blue);font-weight:700}
    .cgrid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:28px}
    .cf{background:#f9fafb;border:1px solid #eee;border-radius:10px;padding:13px 15px}
    .cfl{font-size:10px;color:#aaa;font-weight:700;text-transform:uppercase;letter-spacing:1px;margin-bottom:4px}
    .cfv{font-family:'Syne',sans-serif;font-size:14px;font-weight:700;color:#111}
    .cert-f{padding:20px 36px;border-top:1px solid #f0f0f0;display:flex;justify-content:space-between;align-items:center}
    .seal{display:flex;align-items:center;gap:10px}
    .seal-r{width:42px;height:42px;border-radius:50%;border:2px solid rgba(59,130,246,.3);background:rgba(59,130,246,.08);display:flex;align-items:center;justify-content:center;font-size:17px}
    .seal-i strong{display:block;font-size:12px;color:#444;font-weight:600}.seal-i span{font-size:11px;color:#888}
    .sig{text-align:right}.sig-l{width:110px;height:1.5px;background:#ddd;margin-bottom:6px;margin-left:auto}
    .sig-n{font-family:'Syne',sans-serif;font-size:13px;font-weight:700;color:#444}.sig-r{font-size:11px;color:#888}
    @media print{body{padding:0}}
    </style>
  </head><body>${el.outerHTML}</body></html>`);
  w.document.close(); setTimeout(()=>w.print(),600);
}

// ============================================================
// ADMIN AUTH
// ============================================================
let loggedIn = false;

async function doLogin() {
  const user = document.getElementById('auser').value.trim();
  const pass = document.getElementById('apass').value;
  const err  = document.getElementById('lerr');
  if (!user||!pass){ err.textContent='Username and password are required.'; err.classList.remove('hidden'); return; }

  const btn = document.querySelector('#agate .bsv');
  btn.disabled=true; btn.textContent='Signing in...';

  const {ok, data} = await api('auth.php', {
    method:'POST', body: JSON.stringify({action:'login', username:user, password:pass})
  });

  btn.disabled=false; btn.textContent='Sign In →';
  if (!ok || !data.success) {
    err.textContent = data.error || 'Login failed'; err.classList.remove('hidden');
    setConnStatus(ok);
    return;
  }
  setConnStatus(true);
  loggedIn=true;
  document.getElementById('agate').classList.add('hidden');
  document.getElementById('apanel').classList.remove('hidden');
  toast('Welcome, '+data.data.username+'!','ok');
  loadAdminData();
}

async function doLogout() {
  await api('auth.php', {method:'POST', body: JSON.stringify({action:'logout'})});
  loggedIn=false;
  document.getElementById('agate').classList.remove('hidden');
  document.getElementById('apanel').classList.add('hidden');
  document.getElementById('auser').value='';
  document.getElementById('apass').value='';
  document.getElementById('lerr').classList.add('hidden');
  toast('Logged out.','inf');
}

// ============================================================
// ADMIN DATA
// ============================================================
let allCerts=[], domains=[];

async function loadAdminData() {
  const {ok, data} = await api('stats.php');
  if (!ok || !data.success) { setConnStatus(false); return; }
  setConnStatus(true);
  const s = data.data;
  document.getElementById('as-tot').textContent = s.totals.total_certs;
  document.getElementById('as-dom').textContent = s.totals.unique_domains;
  document.getElementById('as-ver').textContent = s.totals.total_verifications;
  document.getElementById('as-tod').textContent = s.totals.verifications_today;
  domains = s.domains || [];
  // populate domain filter
  const sel = document.getElementById('tdom');
  sel.innerHTML = '<option value="">All Domains</option>' + domains.map(d=>`<option>${d}</option>`).join('');
  loadCerts();
}

async function loadCerts() {
  const q = document.getElementById('tsearch').value;
  const d = document.getElementById('tdom').value;
  let qs = `certificates.php?limit=200`;
  if (q) qs += `&search=${encodeURIComponent(q)}`;
  if (d) qs += `&domain=${encodeURIComponent(d)}`;
  const {ok, data} = await api(qs);
  if (!ok || !data.success) return;
  allCerts = data.data.certificates || [];
  renderTable();
}

function filterTable() { loadCerts(); }

function renderTable() {
  const tb = document.getElementById('atbody');
  if (!allCerts.length) { tb.innerHTML=`<tr><td colspan="7"><div class="empty-r">No certificates found in MySQL database.</div></td></tr>`; return; }
  tb.innerHTML = allCerts.map(c=>`<tr>
    <td><span class="tid">${c.cert_id}</span></td>
    <td style="font-weight:600">${c.student_name}</td>
    <td><span class="chip ch-b">${c.domain}</span></td>
    <td style="font-size:12px;color:var(--text2)">${c.start_date}</td>
    <td style="font-size:12px;color:var(--text2)">${c.end_date}</td>
    <td><span class="chip ch-g">${c.duration||'—'}</span></td>
    <td><div class="tacts">
      <button class="bsm" onclick='openEditModal(${JSON.stringify(c)})'>Edit</button>
      <button class="bsm del" onclick="delCert('${c.cert_id}')">Delete</button>
    </div></td>
  </tr>`).join('');
}

// ============================================================
// MODAL — ADD / EDIT
// ============================================================
let editId=null;

function openAddModal() {
  editId=null;
  document.getElementById('mtitle').textContent='Add Certificate';
  document.getElementById('m-id').value=''; document.getElementById('m-id').readOnly=false;
  document.getElementById('m-name').value=''; document.getElementById('m-dom').value='Web Development';
  document.getElementById('m-start').value=''; document.getElementById('m-end').value='';
  setMMsg('',''); document.getElementById('cmod').classList.remove('hidden');
}

function openEditModal(c) {
  editId=c.cert_id;
  document.getElementById('mtitle').textContent='Edit Certificate';
  document.getElementById('m-id').value=c.cert_id; document.getElementById('m-id').readOnly=true;
  document.getElementById('m-name').value=c.student_name; document.getElementById('m-dom').value=c.domain;
  document.getElementById('m-start').value=c.start_date; document.getElementById('m-end').value=c.end_date;
  setMMsg('',''); document.getElementById('cmod').classList.remove('hidden');
}

function closeMod(id){ document.getElementById(id).classList.add('hidden'); }

function setMMsg(txt,type){
  const el=document.getElementById('cmsg');
  if(!txt){el.classList.add('hidden');return;}
  el.className='mmsg '+(type==='ok'?'mok':'merr'); el.textContent=txt; el.classList.remove('hidden');
}

async function saveCert() {
  const id    = document.getElementById('m-id').value.trim().toUpperCase();
  const name  = document.getElementById('m-name').value.trim();
  const dom   = document.getElementById('m-dom').value;
  const start = document.getElementById('m-start').value;
  const end   = document.getElementById('m-end').value;
  const btn   = document.getElementById('msave');

  if (!id||!name||!start||!end) { setMMsg('All fields are required.','err'); return; }
  if (end<=start) { setMMsg('End date must be after start date.','err'); return; }

  btn.disabled=true; btn.textContent='Saving to MySQL...';

  const body = {cert_id:id, student_name:name, domain:dom, start_date:start, end_date:end};
  const {ok, data} = await api(
    editId ? 'certificates.php' : 'certificates.php',
    { method: editId ? 'PUT' : 'POST', body: JSON.stringify(body) }
  );

  btn.disabled=false; btn.textContent='Save to MySQL';

  if (!ok||!data.success) { setMMsg(data.error||'Failed to save.','err'); return; }
  toast('✓ Certificate '+id+(editId?' updated':' added')+' in MySQL!','ok');
  closeMod('cmod'); loadAdminData();
}

async function delCert(id) {
  if(!confirm(`Delete certificate ${id} from MySQL?\n\nThis is permanent.`)) return;
  const {ok,data} = await api(`certificates.php?id=${encodeURIComponent(id)}`, {method:'DELETE'});
  if(!ok||!data.success){ toast(data.error||'Delete failed','err'); return; }
  toast('✓ Certificate '+id+' deleted from MySQL.','inf');
  loadAdminData();
}

// ============================================================
// BULK UPLOAD
// ============================================================
let uploadFile=null;
function openUploadModal(){ clearUpload(); document.getElementById('umod').classList.remove('hidden'); }
function handleFileSelect(e){ if(e.target.files[0]) setUpFile(e.target.files[0]); }
function setUpFile(f){
  uploadFile=f;
  document.getElementById('ufile-name').textContent=f.name;
  document.getElementById('ufile-size').textContent=(f.size/1024).toFixed(1)+' KB';
  document.getElementById('ufile-info').classList.remove('hidden');
  document.getElementById('ubtn').disabled=false;
  document.getElementById('umsg').classList.add('hidden');
}
function clearUpload(){
  uploadFile=null; document.getElementById('ufile').value='';
  document.getElementById('ufile-info').classList.add('hidden');
  document.getElementById('ubtn').disabled=true;
  document.getElementById('uprog').classList.add('hidden');
  document.getElementById('umsg').classList.add('hidden');
}

async function doUpload() {
  if(!uploadFile) return;
  const btn=document.getElementById('ubtn');
  btn.disabled=true; btn.textContent='Uploading...';
  document.getElementById('uprog').classList.remove('hidden');

  // Animate progress bar
  let p=0; const t=setInterval(()=>{ p+=8; if(p>90)p=90; setProgress(p,'Importing to MySQL...'); },180);

  const fd = new FormData();
  fd.append('file', uploadFile);

  try {
    const res = await fetch(`${API}/upload.php`, { method:'POST', credentials:'include', body:fd });
    const data = await res.json();
    clearInterval(t); setProgress(100,'Done!');
    btn.disabled=false; btn.textContent='Upload to MySQL';

    if(!res.ok||!data.success){
      showUMsg(data.error||'Upload failed','err'); return;
    }
    const d=data.data;
    showUMsg(`✅ ${d.inserted} records inserted, ${d.skipped} skipped.`+(d.errors.length?'\n\nIssues: '+d.errors.slice(0,3).join('; '):''),'ok');
    toast(`✓ ${d.inserted} certificates added to MySQL!`,'ok');
    setTimeout(()=>{ closeMod('umod'); loadAdminData(); },1800);
  } catch(e) {
    clearInterval(t); btn.disabled=false; btn.textContent='Upload to MySQL';
    showUMsg('Cannot reach PHP server. Is Apache running?','err');
  }
}

function setProgress(p, lbl) {
  document.getElementById('upbar').style.width=p+'%';
  document.getElementById('uppct').textContent=Math.round(p)+'%';
  document.getElementById('uplbl').textContent=lbl;
}
function showUMsg(txt,type){
  const el=document.getElementById('umsg');
  el.className='mmsg '+(type==='ok'?'mok':'merr');
  el.style.whiteSpace='pre-wrap'; el.textContent=txt; el.classList.remove('hidden');
}

function downloadTemplate(){
  const csv='cert_id,student_name,domain,start_date,end_date\nCERT-2024-011,Student Name,Web Development,2024-07-01,2024-10-01';
  const a=document.createElement('a');
  a.href='data:text/csv,'+encodeURIComponent(csv);
  a.download='certverify_template.csv'; a.click();
}

// Drag & drop on upload zone
const uz=document.getElementById('uzone');
if(uz){
  uz.addEventListener('dragover',e=>{e.preventDefault();uz.classList.add('over')});
  uz.addEventListener('dragleave',()=>uz.classList.remove('over'));
  uz.addEventListener('drop',e=>{e.preventDefault();uz.classList.remove('over');if(e.dataTransfer.files[0])setUpFile(e.dataTransfer.files[0]);});
}

// ============================================================
// TOAST
// ============================================================
function toast(msg,type='ok'){
  const w=document.getElementById('twrap');
  const el=document.createElement('div');
  el.className='toast t'+type; el.textContent=msg;
  w.appendChild(el);
  setTimeout(()=>{el.style.opacity='0';el.style.transform='translateY(10px)';el.style.transition='.3s';setTimeout(()=>el.remove(),300)},3200);
}

// ============================================================
// BOOT — Check PHP connection
// ============================================================
(async()=>{
  const {ok, data} = await api('verify.php?id=PING');
  // Even a 404 means server is reachable
  if(ok || data.error !== 'Cannot reach PHP server. Make sure Apache/Nginx is running.') {
    setConnStatus(true);
    toast('🟢 MySQL + PHP server connected','ok');
  } else {
    setConnStatus(false);
    toast('⚠ PHP server offline — start Apache/XAMPP/WAMP','err');
  }
  // Check if already logged in
  const sess = await api('auth.php');
  if(sess.data?.data?.logged_in) {
    loggedIn=true;
    document.getElementById('agate').classList.add('hidden');
    document.getElementById('apanel').classList.remove('hidden');
  }
  // Clear all input fields on page load
window.onload = function() {
    document.getElementById('vid').value = '';
    document.getElementById('auser').value = '';
    document.getElementById('apass').value = '';
};
})();
</script>
</body>
</html>
