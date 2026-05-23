<!-- ══ PROJECT FORM POPUP ══ -->
<div class="pf-overlay" id="pfOverlay" onclick="pfHandleOverlay(event)">
<div class="pf-modal" id="pfModal">

  <div class="pf-header">
    <div>
      <div class="pf-badge"><span class="pf-badge-dot"></span><span>New Project Inquiry</span></div>
      <h2 class="pf-title">Let's Build Together</h2>
      <p class="pf-sub">Fill in your project details — we'll respond within 24 hours.</p>
    </div>
    <button class="pf-close" onclick="closeProjectForm()">✕</button>
  </div>

  <div class="pf-progress-wrap" id="pfProgressWrap">
    <div class="pf-progress-steps">
      <div class="pf-p-step">
        <div class="pf-step-circle pf-active" id="pfsc1">01</div>
        <div class="pf-step-line" id="pfsl1"></div>
      </div>
      <div class="pf-p-step">
        <div class="pf-step-circle" id="pfsc2">02</div>
        <div class="pf-step-line" id="pfsl2"></div>
      </div>
      <div class="pf-p-step">
        <div class="pf-step-circle" id="pfsc3">03</div>
        <div class="pf-step-line" id="pfsl3"></div>
      </div>
      <div class="pf-p-step">
        <div class="pf-step-circle" id="pfsc4">04</div>
      </div>
    </div>
    <div class="pf-step-labels">
      <span class="pf-step-label pf-active" id="pfsl-1">You</span>
      <span class="pf-step-label" id="pfsl-2">Service</span>
      <span class="pf-step-label" id="pfsl-3">Budget</span>
      <span class="pf-step-label" id="pfsl-4">Review</span>
    </div>
  </div>

  <div class="pf-steps-container">

    <div class="pf-step pf-step-active" id="pfStep1">
      <div class="pf-step-title">Tell us about you</div>
      <div class="pf-step-desc">We'd love to know who we're working with before diving in.</div>
      <div class="pf-form-row">
        <div class="pf-form-group">
          <label class="pf-label">Full Name <span class="pf-req">*</span></label>
          <input type="text" class="pf-input" id="pfName" placeholder="Your full name">
          <div class="pf-field-error" id="pfEName">Please enter your full name</div>
        </div>
        <div class="pf-form-group">
          <label class="pf-label">Email Address <span class="pf-req">*</span></label>
          <input type="email" class="pf-input" id="pfEmail" placeholder="you@example.com">
          <div class="pf-field-error" id="pfEEmail">Please enter a valid email</div>
        </div>
      </div>
      <div class="pf-form-row">
        <div class="pf-form-group">
          <label class="pf-label">Phone / WhatsApp</label>
          <input type="tel" class="pf-input" id="pfPhone" placeholder="+1 234 567 8900">
        </div>
        <div class="pf-form-group">
          <label class="pf-label">Company / Brand Name</label>
          <input type="text" class="pf-input" id="pfCompany" placeholder="Your company name">
        </div>
      </div>
      <div class="pf-form-row pf-single">
        <div class="pf-form-group">
          <label class="pf-label">Your Country <span class="pf-req">*</span></label>
          <select class="pf-select" id="pfCountry">
            <option value="">Select your country...</option>
            <option>Bangladesh</option>
            <option>United States</option>
            <option>United Kingdom</option>
            <option>Australia</option>
            <option>Canada</option>
            <option>United Arab Emirates</option>
            <option>Germany</option>
            <option>Netherlands</option>
            <option>Japan</option>
            <option>India</option>
            <option>Other</option>
          </select>
          <div class="pf-field-error" id="pfECountry">Please select your country</div>
        </div>
      </div>
    </div>

    <div class="pf-step" id="pfStep2">
      <div class="pf-step-title">What do you need?</div>
      <div class="pf-step-desc">Select one or more services. You can always discuss more later.</div>
      <div class="pf-service-grid">
        <div class="pf-svc-card" data-service="Creative Branding" onclick="pfToggleSvc(this)">
          <div class="pf-svc-icon">🎨</div>
          <div class="pf-svc-name">Creative Branding</div>
          <div class="pf-svc-desc">Logo, identity, color system, brand guidelines</div>
          <div class="pf-svc-check">✓</div>
        </div>
        <div class="pf-svc-card" data-service="WordPress & E-Commerce" onclick="pfToggleSvc(this)">
          <div class="pf-svc-icon">🌐</div>
          <div class="pf-svc-name">WordPress & E-Commerce</div>
          <div class="pf-svc-desc">Custom websites, WooCommerce, landing pages</div>
          <div class="pf-svc-check">✓</div>
        </div>
        <div class="pf-svc-card" data-service="Digital Marketing" onclick="pfToggleSvc(this)">
          <div class="pf-svc-icon">📣</div>
          <div class="pf-svc-name">Digital Marketing</div>
          <div class="pf-svc-desc">Social media, Facebook & Google ads, content</div>
          <div class="pf-svc-check">✓</div>
        </div>
        <div class="pf-svc-card" data-service="SEO & Organic Growth" onclick="pfToggleSvc(this)">
          <div class="pf-svc-icon">📈</div>
          <div class="pf-svc-name">SEO & Organic Growth</div>
          <div class="pf-svc-desc">Rankings, technical SEO, keyword strategy</div>
          <div class="pf-svc-check">✓</div>
        </div>
        <div class="pf-svc-card" data-service="Video Production" onclick="pfToggleSvc(this)">
          <div class="pf-svc-icon">🎬</div>
          <div class="pf-svc-name">Video Production</div>
          <div class="pf-svc-desc">Brand films, reels, motion graphics</div>
          <div class="pf-svc-check">✓</div>
        </div>
        <div class="pf-svc-card" data-service="Full Package" onclick="pfToggleSvc(this)">
          <div class="pf-svc-icon">⚡</div>
          <div class="pf-svc-name">Full Package</div>
          <div class="pf-svc-desc">Complete digital presence — everything included</div>
          <div class="pf-svc-check">✓</div>
        </div>
      </div>
      <div class="pf-field-error" id="pfESvc" style="margin-top:0.75rem">Please select at least one service</div>
    </div>

    <div class="pf-step" id="pfStep3">
      <div class="pf-step-title">Budget & Timeline</div>
      <div class="pf-step-desc">Help us understand the scale so we can plan the right approach.</div>
      <div class="pf-form-group" style="margin-bottom:1.5rem">
        <label class="pf-label">Project Budget (USD) <span class="pf-req">*</span></label>
        <div class="pf-budget-display">
          <div class="pf-budget-amount" id="pfBudgetDisplay">$450</div>
          <div class="pf-budget-lbl">Estimated project budget</div>
        </div>
        <div style="padding:0 0.5rem">
          <input type="range" class="pf-slider" id="pfBudgetSlider" min="0" max="23" value="5" step="1" oninput="pfUpdateBudget(this.value)">
          <div class="pf-range-labels"><span>$199</span><span>$3,999</span></div>
        </div>
      </div>
      <div class="pf-form-group" style="margin-bottom:1.5rem">
        <label class="pf-label">Desired Timeline <span class="pf-req">*</span></label>
        <div class="pf-timeline-opts" id="pfTimelineOpts">
          <div class="pf-tl-opt" data-val="ASAP (Rush)" onclick="pfSelectTL(this)"><div class="pf-tl-name">ASAP</div><div class="pf-tl-sub">Rush delivery</div></div>
          <div class="pf-tl-opt" data-val="1–2 Weeks" onclick="pfSelectTL(this)"><div class="pf-tl-name">1–2 Weeks</div><div class="pf-tl-sub">Fast turnaround</div></div>
          <div class="pf-tl-opt pf-selected" data-val="2–4 Weeks" onclick="pfSelectTL(this)"><div class="pf-tl-name">2–4 Weeks</div><div class="pf-tl-sub">Standard</div></div>
          <div class="pf-tl-opt" data-val="1–2 Months" onclick="pfSelectTL(this)"><div class="pf-tl-name">1–2 Months</div><div class="pf-tl-sub">Relaxed pace</div></div>
          <div class="pf-tl-opt" data-val="Flexible" onclick="pfSelectTL(this)"><div class="pf-tl-name">Flexible</div><div class="pf-tl-sub">No rush</div></div>
        </div>
      </div>
      <div class="pf-form-row pf-single">
        <div class="pf-form-group">
          <label class="pf-label">Project Description <span class="pf-req">*</span></label>
          <textarea class="pf-textarea" id="pfDesc" placeholder="Tell us about your project, your brand, your goals..." oninput="pfCharCount(this)" maxlength="1000"></textarea>
          <div class="pf-char-count" id="pfCharCount">0 / 1000</div>
          <div class="pf-field-error" id="pfEDesc">Please describe your project (min 20 characters)</div>
        </div>
      </div>
    </div>

    <div class="pf-step" id="pfStep4">
      <div class="pf-step-title">Review & Submit</div>
      <div class="pf-step-desc">Everything look good? Submit and we'll be in touch within 24 hours.</div>
      <div class="pf-review-card">
        <div class="pf-review-header"><span class="pf-review-header-label">01 · YOUR INFO</span></div>
        <div class="pf-review-body">
          <div class="pf-review-row"><span class="pf-review-key">Name</span><span class="pf-review-val" id="pfRName">—</span></div>
          <div class="pf-review-row"><span class="pf-review-key">Email</span><span class="pf-review-val" id="pfREmail">—</span></div>
          <div class="pf-review-row"><span class="pf-review-key">Phone</span><span class="pf-review-val" id="pfRPhone">—</span></div>
          <div class="pf-review-row"><span class="pf-review-key">Company</span><span class="pf-review-val" id="pfRCompany">—</span></div>
          <div class="pf-review-row"><span class="pf-review-key">Country</span><span class="pf-review-val" id="pfRCountry">—</span></div>
        </div>
      </div>
      <div class="pf-review-card">
        <div class="pf-review-header"><span class="pf-review-header-label">02 · SERVICES</span></div>
        <div class="pf-review-body">
          <div class="pf-review-row"><span class="pf-review-key">Selected</span><div class="pf-svc-tags" id="pfRSvcs"></div></div>
        </div>
      </div>
      <div class="pf-review-card" style="margin-bottom:1rem">
        <div class="pf-review-header"><span class="pf-review-header-label">03 · PROJECT DETAILS</span></div>
        <div class="pf-review-body">
          <div class="pf-review-row"><span class="pf-review-key">Budget</span><span class="pf-review-val" id="pfRBudget">—</span></div>
          <div class="pf-review-row"><span class="pf-review-key">Timeline</span><span class="pf-review-val" id="pfRTimeline">—</span></div>
          <div class="pf-review-row" style="align-items:flex-start"><span class="pf-review-key">Description</span><span class="pf-review-val" id="pfRDesc" style="font-size:0.75rem;color:#9CA3AF;max-width:70%">—</span></div>
        </div>
      </div>
      <div class="pf-terms-row" id="pfTermsRow" onclick="pfToggleTerms()">
        <div class="pf-checkbox" id="pfCheckbox"></div>
        <div class="pf-terms-text">I agree that Xoos Digital may contact me regarding this inquiry via email or WhatsApp. My data will only be used to respond to this project request.</div>
      </div>
      <div class="pf-field-error" id="pfETerms" style="margin-top:0.5rem">Please accept the terms to continue</div>
    </div>

    <div class="pf-step" id="pfStep5">
      <div class="pf-success-wrap">
        <div class="pf-success-ring"><span class="pf-success-check">✓</span></div>
        <h3 class="pf-success-title">Project Submitted!</h3>
        <p class="pf-success-sub">Thank you! We've received your inquiry and will review it within <strong style="color:white">24 hours</strong>. Raihan will reach out personally to discuss next steps.</p>
        <div class="pf-success-ref">
          <div>
            <div class="pf-ref-label">Reference ID</div>
            <div class="pf-ref-code" id="pfRefCode">XD-000000</div>
          </div>
        </div>
        <div class="pf-success-btns">
          <a href="https://wa.me/8801600008085" class="pf-success-btn pf-wa-btn" target="_blank">💬 WhatsApp Us</a>
          <a href="mailto:xoosdigital@gmail.com" class="pf-success-btn pf-em-btn">✉ Send Email</a>
        </div>
      </div>
    </div>

  </div>

  <div class="pf-footer" id="pfFooter">
    <span class="pf-step-counter" id="pfCounter">STEP 1 OF 4</span>
    <div class="pf-footer-right">
      <button class="pf-btn-back" id="pfBtnBack" onclick="pfPrev()" style="display:none">← BACK</button>
      <button class="pf-btn-next" id="pfBtnNext" onclick="pfNext()">NEXT →</button>
    </div>
  </div>

</div>
</div>
<script>var WEB3FORMS_KEY = '<?= WEB3FORMS_ACCESS_KEY ?>';</script>
<!-- ══ END PROJECT FORM POPUP ══ -->
