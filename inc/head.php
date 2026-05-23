<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php
$pageTitle ??= 'Xoos Digital — Creative Agency, Dhaka, Bangladesh';
$pageDesc  ??= 'Xoos Digital — eXcellence | Opportunity | Outcome | Success. Creative agency based in Dhaka, Bangladesh specializing in branding, web development, digital marketing, SEO, and video production.';
$pageImage ??= BASE_URL . '/images/logo.png';
$canonical = BASE_URL . rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$ogType    = 'website';
if (strpos($_SERVER['REQUEST_URI'], '/post/') === 0) $ogType = 'article';
?>
  <title><?= h($pageTitle) ?></title>
  <meta name="description" content="<?= h($pageDesc) ?>">
  <link rel="icon" href="<?= BASE_URL ?>/images/Icons/favicon.png" type="image/png">
  <link rel="canonical" href="<?= $canonical ?>">
  <meta property="og:title" content="<?= h($pageTitle) ?>">
  <meta property="og:description" content="<?= h($pageDesc) ?>">
  <meta property="og:url" content="<?= $canonical ?>">
  <meta property="og:type" content="<?= $ogType ?>">
  <meta property="og:image" content="<?= $pageImage ?>">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= h($pageTitle) ?>">
  <meta name="twitter:description" content="<?= h($pageDesc) ?>">
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700;800;900&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
<base href="<?= BASE_URL ?>/">
<style>
/* ── Project Form Popup ── */
.pf-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.85);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);z-index:9999;display:flex;align-items:center;justify-content:center;padding:1rem;opacity:0;pointer-events:none;transition:opacity 0.4s cubic-bezier(0.16,1,0.3,1)}
.pf-overlay.open{opacity:1;pointer-events:all}
.pf-modal{background:#111111;border:1px solid #222222;border-radius:1.25rem;width:100%;max-width:680px;position:relative;overflow:hidden;transform:translateY(24px) scale(0.97);transition:transform 0.5s cubic-bezier(0.16,1,0.3,1);max-height:90vh;overflow-y:auto;scrollbar-width:none}
.pf-modal::-webkit-scrollbar{display:none}
.pf-overlay.open .pf-modal{transform:translateY(0) scale(1)}
.pf-modal::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,transparent,#CCFF00,transparent)}
.pf-header{padding:2rem 2rem 1.5rem;border-bottom:1px solid #222222;display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;position:sticky;top:0;background:#111111;z-index:10}
.pf-badge{display:inline-flex;align-items:center;gap:6px;background:rgba(204,255,0,0.06);border:1px solid rgba(204,255,0,0.15);border-radius:999px;padding:4px 12px;margin-bottom:0.75rem}
.pf-badge-dot{width:5px;height:5px;border-radius:50%;background:#CCFF00;display:inline-block;animation:pfPulse 2s ease-in-out infinite}
@keyframes pfPulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:0.4;transform:scale(0.7)}}
.pf-badge span{font-family:'Orbitron',sans-serif;font-size:9px;font-weight:700;color:#CCFF00;letter-spacing:0.2em;text-transform:uppercase}
.pf-title{font-family:'Orbitron',sans-serif;font-size:1.25rem;font-weight:900;text-transform:uppercase;letter-spacing:0.02em;line-height:1.1;color:white}
.pf-sub{font-size:0.8rem;color:#9CA3AF;margin-top:4px;line-height:1.6}
.pf-close{width:36px;height:36px;border-radius:50%;border:1px solid #2a2a2a;background:transparent;color:#9CA3AF;cursor:pointer;font-size:1.1rem;display:flex;align-items:center;justify-content:center;transition:all 0.2s;flex-shrink:0;margin-top:4px}
.pf-close:hover{background:#CCFF00;color:#0A0A0A;border-color:#CCFF00}
.pf-progress-wrap{padding:1.25rem 2rem;border-bottom:1px solid #222222}
.pf-progress-steps{display:flex;align-items:center;margin-bottom:0.875rem}
.pf-p-step{display:flex;align-items:center;flex:1}
.pf-p-step:last-child{flex:0}
.pf-step-circle{width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:'Orbitron',sans-serif;font-size:0.6rem;font-weight:700;border:1.5px solid #2a2a2a;color:#555555;background:#181818;flex-shrink:0;transition:all 0.4s cubic-bezier(0.16,1,0.3,1);position:relative;z-index:1}
.pf-step-circle.pf-active{border-color:#CCFF00;color:#CCFF00;background:rgba(204,255,0,0.1);box-shadow:0 0 16px rgba(204,255,0,0.2)}
.pf-step-circle.pf-done{border-color:#CCFF00;background:#CCFF00;color:#0A0A0A}
.pf-step-line{flex:1;height:1.5px;background:#2a2a2a;transition:background 0.4s;margin:0 4px}
.pf-step-line.pf-done{background:#CCFF00}
.pf-step-labels{display:flex;justify-content:space-between}
.pf-step-label{font-family:'Orbitron',sans-serif;font-size:0.55rem;letter-spacing:0.1em;text-transform:uppercase;color:#555;transition:color 0.3s;text-align:center;flex:1}
.pf-step-label:last-child{flex:0;min-width:50px;text-align:right}
.pf-step-label.pf-active{color:#CCFF00}
.pf-step-label.pf-done{color:#9CA3AF}
.pf-steps-container{padding:2rem;min-height:320px}
.pf-step{display:none}
.pf-step.pf-step-active{display:block}
.pf-step-title{font-family:'Orbitron',sans-serif;font-size:1.15rem;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#CCFF00;margin-bottom:0.5rem}
.pf-step-desc{font-size:0.82rem;color:#9CA3AF;margin-bottom:1.75rem;line-height:1.6}
.pf-form-row{display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem}
.pf-form-row.pf-single{grid-template-columns:1fr}
.pf-form-group{display:flex;flex-direction:column;gap:6px}
.pf-label{font-family:'Orbitron',sans-serif;font-size:0.6rem;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:#9CA3AF}
.pf-label .pf-req{color:#CCFF00}
.pf-input,.pf-select,.pf-textarea{background:#181818;border:1px solid #2a2a2a;color:white;padding:0.75rem 1rem;border-radius:0.625rem;font-family:'Inter',sans-serif;font-size:0.875rem;outline:none;transition:border-color 0.3s,box-shadow 0.3s;width:100%}
.pf-input:focus,.pf-select:focus,.pf-textarea:focus{border-color:#CCFF00;box-shadow:0 0 0 3px rgba(204,255,0,0.08)}
.pf-input::placeholder,.pf-textarea::placeholder{color:#555}
.pf-select{appearance:none;cursor:pointer;background-image:url("data:image/svg+xml,%3Csvg width='12' height='8' viewBox='0 0 12 8' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1 1L6 7L11 1' stroke='%23555' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 1rem center}
.pf-select option{background:#1a1a1a;color:white}
.pf-textarea{resize:vertical;min-height:100px;line-height:1.6}
.pf-field-error{font-size:0.7rem;color:#ff6b6b;margin-top:3px;display:none}
.pf-input.pf-error,.pf-select.pf-error,.pf-textarea.pf-error{border-color:#ff6b6b}
.pf-char-count{display:none}
.pf-service-grid{display:grid;grid-template-columns:1fr 1fr;gap:0.75rem}
.pf-svc-card{background:#181818;border:1.5px solid #2a2a2a;border-radius:0.875rem;padding:1rem;cursor:pointer;transition:all 0.3s;position:relative;overflow:hidden;text-align:left}
.pf-svc-card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:#CCFF00;transform:scaleX(0);transition:transform 0.3s}
.pf-svc-card:hover{border-color:rgba(204,255,0,0.35);background:#1a1a0a}
.pf-svc-card.pf-selected{border-color:#CCFF00;background:rgba(204,255,0,0.06)}
.pf-svc-card.pf-selected::before{transform:scaleX(1)}
.pf-svc-icon{width:40px;height:40px;border-radius:0.5rem;background:rgba(204,255,0,0.08);border:1px solid rgba(204,255,0,0.15);display:flex;align-items:center;justify-content:center;margin-bottom:0.75rem;font-size:1.1rem}
.pf-svc-card.pf-selected .pf-svc-icon{background:rgba(204,255,0,0.15);border-color:rgba(204,255,0,0.3)}
.pf-svc-name{font-family:'Orbitron',sans-serif;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:white;margin-bottom:3px;transition:color 0.3s}
.pf-svc-card.pf-selected .pf-svc-name{color:#CCFF00}
.pf-svc-desc{font-size:0.72rem;color:#9CA3AF;line-height:1.5}
.pf-svc-check{position:absolute;top:0.75rem;right:0.75rem;width:20px;height:20px;border-radius:50%;background:#CCFF00;color:#0A0A0A;display:flex;align-items:center;justify-content:center;font-size:0.65rem;font-weight:900;opacity:0;transform:scale(0.5);transition:all 0.3s cubic-bezier(0.16,1,0.3,1)}
.pf-svc-card.pf-selected .pf-svc-check{opacity:1;transform:scale(1)}
.pf-budget-display{text-align:center;margin-bottom:1.5rem;background:#181818;border:1px solid #2a2a2a;border-radius:0.875rem;padding:1.25rem}
.pf-budget-amount{font-family:'Orbitron',sans-serif;font-size:2rem;font-weight:900;color:#CCFF00}
.pf-budget-lbl{font-size:0.75rem;color:#9CA3AF;margin-top:4px}
input[type=range].pf-slider{width:100%;height:4px;-webkit-appearance:none;appearance:none;background:#2a2a2a;border-radius:999px;outline:none;cursor:pointer}
input[type=range].pf-slider::-webkit-slider-thumb{-webkit-appearance:none;width:22px;height:22px;border-radius:50%;background:#CCFF00;cursor:pointer;border:none;box-shadow:0 0 12px rgba(204,255,0,0.4);transition:transform 0.2s}
input[type=range].pf-slider::-webkit-slider-thumb:hover{transform:scale(1.2)}
input[type=range].pf-slider::-moz-range-thumb{width:22px;height:22px;border-radius:50%;background:#CCFF00;cursor:pointer;border:none}
.pf-range-labels{display:flex;justify-content:space-between;margin-top:0.625rem}
.pf-range-labels span{font-size:0.7rem;color:#555}
.pf-timeline-opts{display:grid;grid-template-columns:repeat(3,1fr);gap:0.625rem;margin-top:1rem}
.pf-tl-opt{background:#181818;border:1.5px solid #2a2a2a;border-radius:0.625rem;padding:0.75rem 0.5rem;text-align:center;cursor:pointer;transition:all 0.3s}
.pf-tl-opt:hover{border-color:rgba(204,255,0,0.3)}
.pf-tl-opt.pf-selected{border-color:#CCFF00;background:rgba(204,255,0,0.06)}
.pf-tl-name{font-family:'Orbitron',sans-serif;font-size:0.65rem;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:white;transition:color 0.3s}
.pf-tl-opt.pf-selected .pf-tl-name{color:#CCFF00}
.pf-tl-sub{font-size:0.65rem;color:#9CA3AF;margin-top:2px}
.pf-review-card{background:#181818;border:1px solid #2a2a2a;border-radius:0.875rem;overflow:hidden;margin-bottom:1rem}
.pf-review-header{padding:0.75rem 1rem;border-bottom:1px solid #222;display:flex;align-items:center}
.pf-review-header-label{font-family:'Orbitron',sans-serif;font-size:0.6rem;font-weight:700;color:#CCFF00;letter-spacing:0.1em;text-transform:uppercase}
.pf-review-body{padding:1rem}
.pf-review-row{display:flex;justify-content:space-between;align-items:flex-start;padding:0.375rem 0;border-bottom:1px solid rgba(255,255,255,0.04)}
.pf-review-row:last-child{border-bottom:none}
.pf-review-key{font-size:0.75rem;color:#9CA3AF}
.pf-review-val{font-size:0.8rem;color:white;font-weight:500;text-align:right;max-width:60%}
.pf-svc-tags{display:flex;flex-wrap:wrap;gap:4px;justify-content:flex-end}
.pf-svc-tag{background:rgba(204,255,0,0.08);border:1px solid rgba(204,255,0,0.2);color:#CCFF00;font-size:0.65rem;font-family:'Orbitron',sans-serif;font-weight:700;padding:2px 8px;border-radius:999px;letter-spacing:0.06em;text-transform:uppercase}
.pf-terms-row{display:flex;align-items:flex-start;gap:0.75rem;padding:1rem;background:rgba(204,255,0,0.03);border:1px solid rgba(204,255,0,0.1);border-radius:0.75rem;cursor:pointer;transition:background 0.2s}
.pf-terms-row:hover{background:rgba(204,255,0,0.06)}
.pf-checkbox{width:20px;height:20px;border-radius:4px;border:1.5px solid #2a2a2a;background:#181818;flex-shrink:0;margin-top:1px;display:flex;align-items:center;justify-content:center;transition:all 0.2s}
.pf-checkbox.pf-checked{background:#CCFF00;border-color:#CCFF00}
.pf-checkbox.pf-checked::after{content:'\2713';font-size:0.7rem;font-weight:900;color:#0A0A0A}
.pf-terms-text{font-size:0.8rem;color:#9CA3AF;line-height:1.6}
.pf-success-wrap{text-align:center;padding:2.5rem 1rem}
.pf-success-ring{width:80px;height:80px;border-radius:50%;background:rgba(204,255,0,0.1);border:2px solid #CCFF00;display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;animation:pfSuccessPop 0.6s cubic-bezier(0.16,1,0.3,1) forwards}
@keyframes pfSuccessPop{0%{transform:scale(0.5);opacity:0}100%{transform:scale(1);opacity:1}}
.pf-success-check{font-size:2rem;color:#CCFF00}
.pf-success-title{font-family:'Orbitron',sans-serif;font-size:1.25rem;font-weight:900;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.75rem;color:white}
.pf-success-sub{font-size:0.875rem;color:#9CA3AF;line-height:1.7;max-width:380px;margin:0 auto}
.pf-success-ref{margin-top:1.5rem;background:#181818;border:1px solid #2a2a2a;border-radius:0.75rem;padding:0.875rem 1.25rem;display:inline-flex;align-items:center;gap:0.75rem}
.pf-ref-label{font-size:0.7rem;color:#9CA3AF}
.pf-ref-code{font-family:'Orbitron',sans-serif;font-size:0.9rem;font-weight:700;color:#CCFF00;letter-spacing:0.1em}
.pf-success-btns{display:flex;gap:0.75rem;justify-content:center;margin-top:1.5rem;flex-wrap:wrap}
.pf-success-btn{display:inline-flex;align-items:center;gap:6px;padding:0.625rem 1.25rem;border-radius:999px;font-size:0.75rem;font-weight:600;text-decoration:none;transition:all 0.2s}
.pf-wa-btn{background:rgba(37,211,102,0.1);border:1px solid rgba(37,211,102,0.3);color:#25D366}
.pf-wa-btn:hover{background:rgba(37,211,102,0.2)}
.pf-em-btn{background:rgba(204,255,0,0.08);border:1px solid rgba(204,255,0,0.2);color:#CCFF00}
.pf-em-btn:hover{background:rgba(204,255,0,0.14)}
.pf-footer{padding:1.25rem 2rem;border-top:1px solid #222222;display:flex;align-items:center;justify-content:space-between;gap:1rem;background:#111111;position:sticky;bottom:0;z-index:10}
.pf-step-counter{font-family:'Orbitron',sans-serif;font-size:0.65rem;color:#555;letter-spacing:0.1em}
.pf-footer-right{display:flex;gap:0.75rem;align-items:center}
.pf-btn-back{background:transparent;border:1px solid #2a2a2a;color:#9CA3AF;padding:0.75rem 1.5rem;font-family:'Orbitron',sans-serif;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;cursor:pointer;border-radius:0;transition:all 0.2s}
.pf-btn-back:hover{border-color:#9CA3AF;color:white}
.pf-btn-next{background:#CCFF00;color:#0A0A0A;border:none;padding:0.75rem 2rem;font-family:'Orbitron',sans-serif;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;cursor:pointer;border-radius:0;transition:all 0.2s;display:flex;align-items:center;gap:6px;min-width:140px;justify-content:center}
.pf-btn-next:hover{background:#b8e600;transform:translateX(2px)}
.pf-btn-next:disabled{opacity:0.4;cursor:not-allowed;transform:none}
@media(max-width:640px){
  .pf-form-row{grid-template-columns:1fr}
  .pf-service-grid{grid-template-columns:1fr}
  .pf-timeline-opts{grid-template-columns:1fr 1fr}
  .pf-header{padding:1.5rem 1.25rem 1rem}
  .pf-steps-container{padding:1.5rem 1.25rem}
  .pf-footer{padding:1rem 1.25rem}
  .pf-progress-wrap{padding:1rem 1.25rem}
  .pf-title{font-size:1rem}
  .pf-step-label{display:none}
}
/* ══ Xoos AI Chatbot Widget ══ */
#xdWidget{position:fixed;bottom:1.5rem;right:1.5rem;z-index:49}
#xdChatBtn{width:58px;height:58px;border-radius:50%;background:linear-gradient(135deg,#0d0d0d,#1a1a1a);border:1.5px solid rgba(204,255,0,0.4);cursor:pointer;display:flex;align-items:center;justify-content:center;position:relative;transition:transform 0.3s,box-shadow 0.3s;box-shadow:0 4px 20px rgba(0,0,0,0.5),0 0 0 0 rgba(204,255,0,0.3);animation:xdBtnIdlePulse 3s ease-in-out infinite}
@keyframes xdBtnIdlePulse{0%{box-shadow:0 4px 20px rgba(0,0,0,0.5),0 0 0 0 rgba(204,255,0,0.4);border-color:rgba(204,255,0,0.35)}50%{box-shadow:0 6px 28px rgba(0,0,0,0.6),0 0 0 8px rgba(204,255,0,0.08);border-color:rgba(204,255,0,0.7)}100%{box-shadow:0 4px 20px rgba(0,0,0,0.5),0 0 0 0 rgba(204,255,0,0);border-color:rgba(204,255,0,0.35)}}
#xdChatBtn .xd-ripple{position:absolute;inset:-4px;border-radius:50%;border:1.5px solid rgba(204,255,0,0.25);animation:xdRippleExpand 3s ease-out infinite;pointer-events:none}
#xdChatBtn .xd-ripple-2{position:absolute;inset:-4px;border-radius:50%;border:1px solid rgba(204,255,0,0.12);animation:xdRippleExpand 3s ease-out infinite 1s;pointer-events:none}
@keyframes xdRippleExpand{0%{transform:scale(1);opacity:0.8}70%{transform:scale(1.5);opacity:0.1}100%{transform:scale(1.6);opacity:0}}
#xdChatBtn .xd-btn-icon{animation:xdIconFloat 2.5s ease-in-out infinite;display:flex;align-items:center;justify-content:center;position:relative;z-index:1}
@keyframes xdIconFloat{0%,100%{transform:translateY(0) rotate(0deg)}25%{transform:translateY(-3px) rotate(-5deg)}75%{transform:translateY(2px) rotate(3deg)}}
#xdChatBtn:hover{animation:none;transform:scale(1.12);border-color:#CCFF00;box-shadow:0 8px 32px rgba(0,0,0,0.6),0 0 24px rgba(204,255,0,0.2),0 0 0 0 rgba(204,255,0,0)}
#xdChatBtn:hover .xd-ripple,#xdChatBtn:hover .xd-ripple-2{animation:none;opacity:0}
#xdChatBtn:hover .xd-btn-icon{animation:none;transform:scale(1.1)}
#xdChatBtn.xd-is-open{animation:none;border-color:rgba(204,255,0,0.5);box-shadow:0 4px 20px rgba(0,0,0,0.5)}
#xdChatBtn.xd-is-open .xd-ripple,#xdChatBtn.xd-is-open .xd-ripple-2{display:none}
#xdChatBtn.xd-is-open .xd-btn-icon{animation:none}
.xd-bubble{position:absolute;bottom:70px;right:0;background:#1a1a1a;border:1px solid rgba(204,255,0,0.3);border-radius:1rem 1rem 0.25rem 1rem;padding:0.625rem 1rem;white-space:nowrap;pointer-events:none;z-index:10;opacity:0;transform:translateY(8px) scale(0.92);transform-origin:bottom right;transition:opacity 0.4s cubic-bezier(0.16,1,0.3,1),transform 0.4s cubic-bezier(0.16,1,0.3,1)}
.xd-bubble.xd-bubble-show{opacity:1;transform:translateY(0) scale(1)}
.xd-bubble::after{content:'';position:absolute;bottom:-7px;right:18px;width:0;height:0;border-left:7px solid transparent;border-right:7px solid transparent;border-top:7px solid rgba(204,255,0,0.3)}
.xd-bubble::before{content:'';position:absolute;bottom:-6px;right:19px;width:0;height:0;border-left:6px solid transparent;border-right:6px solid transparent;border-top:6px solid #1a1a1a;z-index:1}
#xdBubbleText{font-family:'Inter',sans-serif;font-size:0.78rem;font-weight:500;color:white;line-height:1;display:inline-block;animation:none}
.xd-cursor{display:inline-block;width:2px;height:14px;background:#CCFF00;margin-left:2px;animation:xdCursorBlink 0.7s step-end infinite;vertical-align:middle}
@keyframes xdCursorBlink{0%,100%{opacity:1}50%{opacity:0}}
.xd-bubble-glow{position:absolute;top:0;left:15%;right:15%;height:1.5px;border-radius:999px;background:linear-gradient(90deg,transparent,rgba(204,255,0,0.5),transparent)}
#xdChatBtn.xd-is-open ~ #xdBubble,.xd-bubble-hidden{opacity:0 !important;pointer-events:none;transform:translateY(8px) scale(0.92) !important}
@media(max-width:640px){.xd-bubble{bottom:66px;white-space:nowrap}#xdBubbleText{font-size:0.72rem}}
#xdChatWindow{position:absolute;bottom:68px;right:0;width:340px;border-radius:1.25rem;background:#111111;border:1px solid #222222;overflow:hidden;transform:scale(0.85) translateY(10px);opacity:0;pointer-events:none;transition:all 0.4s cubic-bezier(0.16,1,0.3,1);transform-origin:bottom right}
#xdChatWindow::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,transparent,#CCFF00,transparent)}
#xdChatWindow.xd-open{transform:scale(1) translateY(0);opacity:1;pointer-events:all}
.xd-header{padding:1rem 1.25rem;border-bottom:1px solid #1e1e1e;display:flex;align-items:center;gap:10px;background:#111111}
.xd-avatar{width:36px;height:36px;border-radius:50%;background:rgba(204,255,0,0.1);border:1.5px solid rgba(204,255,0,0.3);display:flex;align-items:center;justify-content:center;font-family:'Orbitron',sans-serif;font-size:0.65rem;font-weight:700;color:#CCFF00;flex-shrink:0}
.xd-header-info{flex:1;min-width:0}
.xd-header-name{font-family:'Orbitron',sans-serif;font-size:0.75rem;font-weight:700;color:white}
.xd-header-status{display:flex;align-items:center;gap:5px;font-size:0.63rem;color:#9CA3AF;margin-top:2px}
.xd-status-dot{width:5px;height:5px;border-radius:50%;background:#22c55e;flex-shrink:0;animation:xdStatusPulse 2s ease-in-out infinite}
@keyframes xdStatusPulse{0%,100%{opacity:1}50%{opacity:0.4}}
.xd-clear-btn{background:none;border:none;cursor:pointer;font-size:0.65rem;color:#555;font-family:'Inter',sans-serif;transition:color 0.2s;padding:4px}
.xd-clear-btn:hover{color:#CCFF00}
#xdMessages{height:300px;overflow-y:auto;padding:1rem;display:flex;flex-direction:column;gap:0.75rem;scroll-behavior:smooth;scrollbar-width:thin;scrollbar-color:#222 transparent}
#xdMessages::-webkit-scrollbar{width:3px}
#xdMessages::-webkit-scrollbar-thumb{background:#222;border-radius:999px}
.xd-msg-user{align-self:flex-end;background:rgba(204,255,0,0.1);border:1px solid rgba(204,255,0,0.2);border-radius:1rem 1rem 0.25rem 1rem;padding:0.625rem 0.875rem;max-width:80%;font-family:'Inter',sans-serif;font-size:0.82rem;color:white;line-height:1.5}
.xd-msg-bot{align-self:flex-start;background:#1a1a1a;border:1px solid #222;border-radius:0.25rem 1rem 1rem 1rem;padding:0.625rem 0.875rem;max-width:85%;font-family:'Inter',sans-serif;font-size:0.82rem;color:#e5e7eb;line-height:1.65}
.xd-typing{align-self:flex-start;background:#1a1a1a;border:1px solid #222;border-radius:0.25rem 1rem 1rem 1rem;padding:0.75rem 1rem;display:flex;gap:4px;align-items:center}
.xd-dot{width:6px;height:6px;border-radius:50%;background:#555;display:inline-block;animation:xdDotBounce 1.2s infinite}
.xd-dot:nth-child(2){animation-delay:0.2s}
.xd-dot:nth-child(3){animation-delay:0.4s}
@keyframes xdDotBounce{0%,60%,100%{transform:translateY(0);background:#555}30%{transform:translateY(-6px);background:#CCFF00}}
#xdQuickReplies{padding:0 1rem 0.875rem;display:flex;flex-wrap:wrap;gap:0.5rem}
.xd-qr{background:#181818;border:1px solid #2a2a2a;border-radius:999px;padding:5px 12px;font-family:'Inter',sans-serif;font-size:0.7rem;color:#9CA3AF;cursor:pointer;transition:all 0.2s;white-space:nowrap}
.xd-qr:hover{border-color:#CCFF00;color:#CCFF00;background:rgba(204,255,0,0.05)}
.xd-input-area{padding:0.75rem 1rem;border-top:1px solid #1e1e1e;display:flex;gap:0.5rem;align-items:flex-end;background:#111111}
.xd-textarea{flex:1;background:#181818;border:1px solid #2a2a2a;border-radius:0.75rem;padding:0.625rem 0.875rem;color:white;font-family:'Inter',sans-serif;font-size:0.82rem;outline:none;resize:none;min-height:38px;max-height:100px;line-height:1.5;overflow-y:auto;transition:border-color 0.3s,box-shadow 0.3s}
.xd-textarea::placeholder{color:#444}
.xd-textarea:focus{border-color:rgba(204,255,0,0.4);box-shadow:0 0 0 2px rgba(204,255,0,0.06)}
.xd-send-btn{width:36px;height:36px;border-radius:50%;background:#CCFF00;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.2s;flex-shrink:0}
.xd-send-btn:hover{background:#b8e600;transform:scale(1.1)}
.xd-send-btn:disabled{background:#2a2a2a;cursor:not-allowed;transform:none}
.xd-send-btn:disabled svg{stroke:#555}
.xd-powered{padding:0.5rem 1rem;border-top:1px solid #1a1a1a;text-align:center;font-family:'Inter',sans-serif;font-size:0.6rem;color:#333}
@media(max-width:640px){#xdChatWindow{width:calc(100vw - 2rem);max-width:320px}#xdMessages{height:260px}#xdWidget{bottom:1rem;right:1rem}}
</style>
</head>

<body class="overflow-safe">

  <!-- TOP BAR -->
  <div class="top-bar"></div>

  <!-- CUSTOM CURSOR -->
  <div class="cursor-dot" id="cursorDot"></div>
  <div class="cursor-ring" id="cursorRing"></div>

  <!-- LIGHTBOX -->
  <div class="lightbox" id="lightbox">
    <button class="lightbox-close" id="lightboxClose">✕</button>
    <div class="lightbox-content">
      <img id="lightboxImg" src="" alt="">
      <div class="lightbox-badge" id="lightboxCategory"></div>
      <div class="lightbox-title" id="lightboxTitle"></div>
      <div class="lightbox-desc" id="lightboxDesc"></div>
    </div>
  </div>
