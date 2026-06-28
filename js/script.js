document.addEventListener('DOMContentLoaded', function() {

  // ── 14. CONSOLE BRANDING ──
  console.log('%c Xoos Digital ', 'background:#CCFF00;color:#0A0A0A;font-size:14px;font-weight:bold;padding:8px 16px;border-radius:4px');
  console.log('%c eXcellence · Opportunity · Outcome · Success ', 'color:#9CA3AF;font-size:12px;padding:4px 8px');

  // ── 13. HERO RAYS ──
  const heroRays = document.getElementById('heroRays');
  if (heroRays) {
    for (let i = 0; i < 28; i++) {
      const ray = document.createElement('div');
      ray.className = 'ray';
      ray.style.left = `calc(${i} / 28 * 100%)`;
      ray.style.animationDelay = `${i * 0.12}s`;
      heroRays.appendChild(ray);
    }
  }

  // ── 1. CUSTOM CURSOR ──
  const isMobileDevice = window.innerWidth <= 768 || ('ontouchstart' in window);
  const cursorDot = document.getElementById('cursorDot');
  const cursorRing = document.getElementById('cursorRing');
  if (!isMobileDevice && cursorDot && cursorRing) {
    let mouseX = 0, mouseY = 0, ringX = 0, ringY = 0;
    let cursorRafId = null;
    let cursorMoving = false;

    function animateRing() {
      cursorDot.style.left = mouseX + 'px';
      cursorDot.style.top = mouseY + 'px';
      ringX += (mouseX - ringX) * 0.1;
      ringY += (mouseY - ringY) * 0.1;
      cursorRing.style.left = ringX + 'px';
      cursorRing.style.top = ringY + 'px';
      if (cursorMoving) {
        cursorRafId = requestAnimationFrame(animateRing);
      }
    }

    let cursorIdleTimer = null;
    document.addEventListener('mousemove', function(e) {
      mouseX = e.clientX;
      mouseY = e.clientY;
      if (!cursorMoving) {
        cursorMoving = true;
        cursorRafId = requestAnimationFrame(animateRing);
      }
      clearTimeout(cursorIdleTimer);
      cursorIdleTimer = setTimeout(function() {
        cursorMoving = false;
        if (cursorRafId) cancelAnimationFrame(cursorRafId);
      }, 100);
    });

    const hoverTargets = 'a, button, [data-magnetic], .blog-card, .hamburger, .brand-glass-card, .p-card, .svc-item, .pricing-card';
    document.querySelectorAll(hoverTargets).forEach(function(el) {
      el.addEventListener('mouseenter', function() {
        cursorDot.classList.add('is-hovering');
        cursorRing.classList.add('is-hovering');
      });
      el.addEventListener('mouseleave', function() {
        cursorDot.classList.remove('is-hovering');
        cursorRing.classList.remove('is-hovering');
      });
    });
  }

  // ── 2. NAVBAR SCROLL ──
  const navbar = document.getElementById('navbar');
  let scrollTicking = false;
  window.addEventListener('scroll', function() {
    if (!scrollTicking) {
      requestAnimationFrame(function() {
        navbar.classList.toggle('scrolled', window.scrollY > 50);
        scrollTicking = false;
      });
      scrollTicking = true;
    }
  }, { passive: true });

  // ── 3. MOBILE MENU ──
  const hamburger = document.getElementById('hamburger');
  const mobileNav = document.getElementById('mobileNav');
  const mobileNavOverlay = document.getElementById('mobileNavOverlay');
  const mobileNavClose = document.getElementById('mobileNavClose');
  const mobileLinks = mobileNav.querySelectorAll('a.mobile-nav-link');

  function openMobile() {
    mobileNav.classList.add('open');
    mobileNavOverlay.classList.add('open');
    hamburger.classList.add('active');
    document.body.style.overflow = 'hidden';
  }

  function closeMobile() {
    mobileNav.classList.remove('open');
    mobileNavOverlay.classList.remove('open');
    hamburger.classList.remove('active');
    document.body.style.overflow = '';
  }

  hamburger.addEventListener('click', function() {
    const isOpen = mobileNav.classList.contains('open');
    if (isOpen) {
      closeMobile();
    } else {
      openMobile();
    }
  });

  mobileNavClose.addEventListener('click', closeMobile);
  mobileNavOverlay.addEventListener('click', closeMobile);

  mobileNav.querySelectorAll('a').forEach(function(link) {
    link.addEventListener('click', closeMobile);
  });

  // ── 4. SCROLL ANIMATIONS ──
  const animObserver = new IntersectionObserver(function(entries) {
    entries.forEach(function(entry) {
      if (entry.isIntersecting) {
        const el = entry.target;
        const delay = parseInt(el.dataset.delay || 0);
        setTimeout(function() { el.classList.add('is-visible'); }, delay);
        animObserver.unobserve(el);
      }
    });
  }, { threshold: 0.1, rootMargin: ['0px', '0px', '-50px', '0px'].join(' ') });

  document.querySelectorAll('[data-animate]').forEach(function(el) {
    animObserver.observe(el);
  });

  // ── 5. SKILL BARS ──
  const skillObserver = new IntersectionObserver(function(entries) {
    entries.forEach(function(entry) {
      if (entry.isIntersecting) {
        const fill = entry.target.querySelector('.skill-fill');
        if (fill) {
          setTimeout(function() {
            fill.style.width = fill.dataset.width + '%';
          }, 300);
        }
        skillObserver.unobserve(entry.target);
      }
    });
  }, { threshold: 0.3 });

  document.querySelectorAll('.skill-item').forEach(function(item) {
    skillObserver.observe(item);
  });

  // ── 6. COUNT-UP ──
  function easeOutCubic(t) { return 1 - Math.pow(1 - t, 3); }

  const countObserver = new IntersectionObserver(function(entries) {
    entries.forEach(function(entry) {
      if (entry.isIntersecting) {
        const el = entry.target;
        const target = parseInt(el.dataset.count);
        const suffix = el.dataset.suffix || '';
        const duration = 2000;
        const start = performance.now();

        function update(now) {
          const elapsed = now - start;
          const t = Math.min(elapsed / duration, 1);
          const eased = easeOutCubic(t);
          el.textContent = Math.floor(eased * target) + suffix;
          if (t < 1) requestAnimationFrame(update);
        }
        requestAnimationFrame(update);
        countObserver.unobserve(el);
      }
    });
  }, { threshold: 0.5 });

  document.querySelectorAll('[data-count]').forEach(function(el) {
    countObserver.observe(el);
  });

  // ── 7. MAGNETIC BUTTONS ──
  document.querySelectorAll('[data-magnetic]').forEach(function(el) {
    el.style.willChange = 'transform';
    el.style.transition = 'transform 0.15s cubic-bezier(0.16, 1, 0.3, 1)';
    el.addEventListener('mousemove', function(e) {
      const rect = el.getBoundingClientRect();
      const x = e.clientX - rect.left - rect.width / 2;
      const y = e.clientY - rect.top - rect.height / 2;
      el.style.transform = 'translate(' + (x * 0.25) + 'px, ' + (y * 0.25) + 'px)';
    });
    el.addEventListener('mouseleave', function() {
      el.style.transform = 'translate(0, 0)';
    });
  });

  // ── 8. TESTIMONIALS — FOCUS CAROUSEL ──
  (function() {
    var raw = window.testimonialsData || [];
    var testimonials = raw.map(function(t, i) {
      var grad = t.gradient || (function() {
        var hues = ['135deg, #1a1a3e, #3d2a7a','135deg, #1a3a20, #2a7a3d','135deg, #3a1a1a, #7a2a2a','135deg, #1a2d3a, #2a5a7a','135deg, #2d1a3a, #5a2a7a','135deg, #1a3a2d, #2a7a5a','135deg, #1f2a3d, #2a3d7a','135deg, #2a1f3d, #4a2a7a'];
        return 'linear-gradient(' + (hues[i % hues.length] || '135deg, #1a1a3e, #3d2a7a') + ')';
      })();
      var letter = t.client_name ? t.client_name.charAt(0).toUpperCase() : '?';
      return {
        letter: letter,
        gradient: grad,
        image: t.client_image || '',
        name: t.client_name || 'Client',
        flag: t.country_flag || '🌍',
        role: (t.service_used || 'Client') + (t.country ? ', ' + t.country : ''),
        stars: t.rating || 5,
        quote: t.quote || '',
        tag: t.service_used || 'Client',
        platform: t.platform || 'Direct'
      };
    });

    const track   = document.getElementById('tCarouselTrack');
    const dotsWrap = document.getElementById('tDots');
    const prevBtn = document.getElementById('tPrev');
    const nextBtn = document.getElementById('tNext');
    if (!track || !dotsWrap || !prevBtn || !nextBtn) return;

    let current = 0;
    let isAnimating = false;
    let autoTimer = null;

    testimonials.forEach(function(t, i) {
      var stars = '\u2605'.repeat(t.stars);
      var card = document.createElement('div');
      card.className = 't-card';
      card.dataset.index = i;
      var avatarHtml = t.image
        ? '<div class="t-avatar" style="overflow:hidden"><img src="' + t.image + '" alt="' + t.name + '" width="46" height="46" style="width:100%;height:100%;object-fit:cover" loading="lazy"></div>'
        : '<div class="t-avatar" style="background:' + t.gradient + '">' + t.letter + '</div>';
      card.innerHTML = '<div class="t-quote-bg">\u275D</div><div class="t-card-top">' + avatarHtml + '<div class="t-meta"><div class="t-name">' + t.name + ' ' + t.flag + '</div><div class="t-role">' + t.role + '</div></div><div class="t-stars">' + stars + '</div></div><p class="t-quote">"' + t.quote + '"</p><div class="t-card-bottom"><span class="t-tag">' + t.tag + '</span><div class="t-platform-badge"><span class="t-platform-dot"></span><span class="t-platform-name">' + t.platform + '</span><span class="t-verified">\u2713 VERIFIED</span></div></div><div class="t-center-line"></div>';
      track.appendChild(card);
    });

    testimonials.forEach(function(_, i) {
      var dot = document.createElement('div');
      dot.className = 't-dot' + (i === 0 ? ' active' : '');
      dot.addEventListener('click', function() { goTo(i); });
      dotsWrap.appendChild(dot);
    });

    function getPosClass(offset) {
      var map = { 0: 't-pos-center', 1: 't-pos-next1', 2: 't-pos-next2', '-1': 't-pos-prev1', '-2': 't-pos-prev2' };
      return map[offset] || 't-pos-hidden';
    }

    function updateCarousel() {
      var cards = track.querySelectorAll('.t-card');
      var n = testimonials.length;
      cards.forEach(function(card, i) {
        card.classList.remove('t-pos-center', 't-pos-prev1', 't-pos-prev2', 't-pos-next1', 't-pos-next2', 't-pos-hidden');
        var offset = i - current;
        if (offset > n / 2)  offset -= n;
        if (offset < -n / 2) offset += n;
        var clampedOffset = Math.max(-2, Math.min(2, offset));
        var posClass = (Math.abs(offset) <= 2) ? getPosClass(clampedOffset) : 't-pos-hidden';
        card.classList.add(posClass);
      });
      dotsWrap.querySelectorAll('.t-dot').forEach(function(dot, i) {
        dot.classList.toggle('active', i === current);
      });
    }

    function goTo(index) {
      if (isAnimating) return;
      isAnimating = true;
      current = (index + testimonials.length) % testimonials.length;
      updateCarousel();
      setTimeout(function() { isAnimating = false; }, 700);
      resetAuto();
    }

    function nextSlide() { goTo(current + 1); }
    function prevSlide() { goTo(current - 1); }

    prevBtn.addEventListener('click', prevSlide);
    nextBtn.addEventListener('click', nextSlide);

    track.addEventListener('click', function(e) {
      var card = e.target.closest('.t-card');
      if (!card) return;
      var idx = parseInt(card.dataset.index);
      if (idx !== current) goTo(idx);
    });

    document.addEventListener('keydown', function(e) {
      if (e.key === 'ArrowLeft')  prevSlide();
      if (e.key === 'ArrowRight') nextSlide();
    });

    var touchStartX = 0;
    track.addEventListener('touchstart', function(e) {
      touchStartX = e.touches[0].clientX;
    }, { passive: true });
    track.addEventListener('touchend', function(e) {
      var diff = touchStartX - e.changedTouches[0].clientX;
      if (Math.abs(diff) > 50) {
        diff > 0 ? nextSlide() : prevSlide();
      }
    }, { passive: true });

    function startAuto() {
      autoTimer = setInterval(nextSlide, 5000);
    }
    function resetAuto() {
      clearInterval(autoTimer);
      startAuto();
    }

    track.addEventListener('mouseenter', function() { clearInterval(autoTimer); });
    track.addEventListener('mouseleave', startAuto);
    document.addEventListener('visibilitychange', function() {
      if (document.hidden) clearInterval(autoTimer); else startAuto();
    });

    updateCarousel();

    // Stagger entrance: cards appear one after another
    var entranceCards = track.querySelectorAll('.t-card');
    entranceCards.forEach(function(c, i) {
      c.style.transitionDelay = (i * 0.07) + 's';
    });
    // Force reflow, then clear delay so subsequent slides are instant
    requestAnimationFrame(function() {
      entranceCards.forEach(function(c) { c.style.transitionDelay = ''; });
    });

    startAuto();
  })();

  // ── 10. FAQ ACCORDION ──
  (function() {
    var triggers = document.querySelectorAll('.faq-trigger');
    triggers.forEach(function(trigger) {
      trigger.addEventListener('click', function() {
        var item = trigger.closest('.faq-item');
        var answer = item.querySelector('.faq-answer');
        var isOpen = item.classList.contains('active');

        document.querySelectorAll('.faq-item').forEach(function(i) {
          i.classList.remove('active');
          i.querySelector('.faq-answer').style.maxHeight = '0';
          i.querySelector('.faq-answer').style.paddingBottom = '0';
        });

        if (!isOpen) {
          item.classList.add('active');
          answer.style.maxHeight = answer.scrollHeight + 'px';
          answer.style.paddingBottom = '1.25rem';
        }
      });
    });
  })();

  // ── 11. SERVICES ACCORDION ──
  (function() {
    var svcItems = document.querySelectorAll('.svc-item');
    svcItems.forEach(function(item) {
      var trigger = item.querySelector('.svc-trigger');
      trigger.addEventListener('click', function() {
        var isActive = item.classList.contains('active');
        svcItems.forEach(function(i) {
          i.classList.remove('active');
          var body = i.querySelector('.svc-body');
          if (body) body.style.maxHeight = '0';
        });
        if (!isActive) {
          item.classList.add('active');
          var body = item.querySelector('.svc-body');
          if (body) body.style.maxHeight = body.scrollHeight + 'px';
        }
      });
    });
    if (svcItems.length > 0) {
      var firstItem = svcItems[0];
      firstItem.classList.add('active');
      var firstBody = firstItem.querySelector('.svc-body');
      if (firstBody) firstBody.style.maxHeight = firstBody.scrollHeight + 'px';
    }
  })();

  // ── 12. PORTFOLIO FILTER + LIGHTBOX ──
  (function() {
    var filters = document.querySelectorAll('.p-filter');
    var cards = document.querySelectorAll('.p-card[data-category]');

    filters.forEach(function(btn) {
      btn.addEventListener('click', function() {
        filters.forEach(function(b) { b.classList.remove('active'); });
        btn.classList.add('active');
        var cat = btn.dataset.filter;

        cards.forEach(function(card) {
          var match = cat === 'all' || card.dataset.category === cat;
          card.style.opacity = '0';
          card.style.transform = 'scale(0.95)';
          card.style.transition = 'opacity 0.35s cubic-bezier(0.16, 1, 0.3, 1), transform 0.35s cubic-bezier(0.16, 1, 0.3, 1)';
          requestAnimationFrame(function() {
            if (match) {
              card.style.display = '';
              card.style.visibility = '';
              card.style.pointerEvents = '';
              requestAnimationFrame(function() {
                card.style.opacity = '1';
                card.style.transform = '';
              });
            } else {
              card.style.visibility = 'hidden';
              card.style.pointerEvents = 'none';
            }
          });
        });
      });
    });

    // Card click → lightbox
    cards.forEach(function(card) {
      card.addEventListener('click', function() {
        var img = (card.querySelector('.p-card-img') || {}).src;
        var title = (card.querySelector('.p-card-title') || {}).textContent;
        var cat = (card.querySelector('.p-badge') || {}).textContent;
        var desc = card.dataset.description || (card.querySelector('.p-card-sub') || {}).textContent || '';
        if (img && typeof openLightbox === 'function') {
          openLightbox(img, title, cat, desc);
        }
      });
    });
  })();

  // ── 13. LIGHTBOX ──
  const lightbox = document.getElementById('lightbox');
  const lightboxClose = document.getElementById('lightboxClose');
  const lightboxImg = document.getElementById('lightboxImg');
  const lightboxTitle = document.getElementById('lightboxTitle');
  const lightboxCategory = document.getElementById('lightboxCategory');
  const lightboxDesc = document.getElementById('lightboxDesc');

  window.openLightbox = function(img, title, cat, desc, caseStudyUrl) {
    lightboxImg.src = img || '';
    lightboxTitle.textContent = title || '';
    lightboxCategory.textContent = cat || '';
    lightboxDesc.textContent = desc || '';
    var csLink = document.getElementById('lightboxCaseStudyLink');
    if (csLink && caseStudyUrl) {
      csLink.href = caseStudyUrl;
      csLink.style.display = 'inline-flex';
    } else if (csLink) {
      csLink.style.display = 'none';
    }
    lightbox.classList.add('open');
    document.body.style.overflow = 'hidden';
  };

  document.querySelectorAll('[data-lightbox]').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
      e.stopPropagation();
      window.openLightbox(btn.dataset.img, btn.dataset.title, btn.dataset.category, btn.dataset.description);
    });
  });

  function closeLightbox() {
    lightbox.classList.remove('open');
    document.body.style.overflow = '';
  }

  lightboxClose.addEventListener('click', closeLightbox);
  lightbox.addEventListener('click', function(e) {
    if (e.target === lightbox) closeLightbox();
  });
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeLightbox();
  });

  // ── 11. NAV ACTIVE STATE ──
  const sections = document.querySelectorAll('section[id], footer[id]');
  const navLinks = document.querySelectorAll('.nav-link');

  const sectionObserver = new IntersectionObserver(function(entries) {
    entries.forEach(function(entry) {
      if (entry.isIntersecting) {
        const id = entry.target.id;
        navLinks.forEach(function(link) {
          link.classList.remove('active');
          if (link.getAttribute('href') === '#' + id) {
            link.classList.add('active');
          }
        });
      }
    });
  }, { threshold: 0.4 });

  sections.forEach(function(section) { sectionObserver.observe(section); });

  // ── NEWSLETTER FORM ──
  const newsletterBtn = document.querySelector('.newsletter-form button');
  const newsletterInput = document.querySelector('.newsletter-form input');
  if (newsletterBtn && newsletterInput) {
    newsletterBtn.addEventListener('click', function() {
      if (newsletterInput.value && newsletterInput.value.includes('@')) {
        newsletterBtn.textContent = 'THANKS!';
        newsletterBtn.style.background = '#0A0A0A';
        newsletterBtn.style.color = '#CCFF00';
        newsletterInput.value = '';
        setTimeout(function() {
          newsletterBtn.textContent = 'SUBSCRIBE';
          newsletterBtn.style.background = '#CCFF00';
          newsletterBtn.style.color = '#0A0A0A';
        }, 3000);
      }
    });
  }

  // ── PORTFOLIO BUTTON HOVER EFFECTS ──
  const prevBento = document.getElementById('prevBento');
  const nextBento = document.getElementById('nextBento');
  [prevBento, nextBento].forEach(function(btn) {
    if (btn) {
      btn.addEventListener('mouseenter', function() {
        btn.style.background = '#CCFF00';
        btn.style.color = '#0A0A0A';
        btn.style.borderColor = '#CCFF00';
      });
      btn.addEventListener('mouseleave', function() {
        btn.style.background = 'none';
        btn.style.color = '#9CA3AF';
        btn.style.borderColor = '#333';
      });
    }
  });

  // ── BRANDS FOCUS CAROUSEL ──
  (function() {
    var rawBrands = window.brandsData || [];
    var brands = rawBrands.map(function(b) {
      return {
        name: b.name || '',
        logo: b.logo_url || '',
        industry: b.industry || '',
        country: b.country || '',
        service: b.service || '',
        bloom: b.bloom_color || 'rgba(0,0,0,0.18)'
      };
    });

    const track    = document.getElementById('brandsTrack');
    const dotsWrap = document.getElementById('brandsDots');
    const prevBtn  = document.getElementById('brandsPrev');
    const nextBtn  = document.getElementById('brandsNext');

    if (!track) return;

    let current     = 0;
    let isAnimating = false;
    let autoTimer   = null;

    /* ── Live region for screen readers ── */
    const liveRegion = document.createElement('div');
    liveRegion.setAttribute('aria-live', 'polite');
    liveRegion.setAttribute('aria-atomic', 'true');
    liveRegion.className = 'sr-only';
    liveRegion.style.cssText = 'position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0;';
    track.parentElement.appendChild(liveRegion);

    /* ── 1. Render cards ── */
    brands.forEach(function(b, i) {
      const card = document.createElement('div');
      card.className = 'brand-glass-card';
      card.dataset.index = i;
      card.setAttribute('role', 'group');
      card.setAttribute('aria-label', b.name + (b.industry ? ', ' + b.industry : ''));
      card.setAttribute('tabindex', '0');
      card.style.setProperty('--bloom-color', b.bloom);
      card.innerHTML =
        '<div class="card-bloom"></div>' +
        '<div class="card-glass-surface">' +
          '<div class="card-top-row">' +
            '<div class="card-logo-wrap">' +
              '<img src="' + b.logo + '" alt="' + b.name + '" class="card-logo-img" width="52" height="52" loading="lazy">' +
            '</div>' +
            '<div class="card-verified-badge">' +
              '<svg width="9" height="9" viewBox="0 0 10 10" aria-hidden="true">' +
                '<path d="M5 0L6.12 3.38L9.51 3.38L6.88 5.49L7.94 8.88L5 6.88L2.06 8.88L3.12 5.49L0.49 3.38L3.88 3.38Z" fill="#CCFF00"/>' +
              '</svg>' +
              '<span>PARTNER</span>' +
            '</div>' +
          '</div>' +
          '<h3 class="card-brand-name">' + b.name + '</h3>' +
          '<span class="card-industry-tag">' + b.industry + '</span>' +
          '<div class="card-bottom-row">' +
            '<span class="card-country">' + b.country + '</span>' +
            '<span class="card-service-dot"></span>' +
            '<span class="card-service">' + b.service + '</span>' +
          '</div>' +
        '</div>';
      track.appendChild(card);
    });

    /* ── 2. Render dots ── */
    brands.forEach(function(b, i) {
      const dot = document.createElement('div');
      dot.className = 'brands-dot' + (i === 0 ? ' active' : '');
      dot.setAttribute('role', 'button');
      dot.setAttribute('tabindex', '0');
      dot.setAttribute('aria-label', 'Go to ' + b.name);
      dot.addEventListener('click', function() { goTo(i); });
      dot.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); goTo(i); }
      });
      dotsWrap.appendChild(dot);
    });

    /* ── 3. Update carousel ── */
    function updateCarousel() {
      const cards = track.querySelectorAll('.brand-glass-card');
      const n = brands.length;
      const spacing = 220;

      cards.forEach(function(card, i) {
        let offset = i - current;
        if (offset > n / 2)  offset -= n;
        if (offset < -n / 2) offset += n;

        const abs = Math.abs(offset);
        const x = offset * spacing;

        var scale, blurAmt, opacityVal, zIdx;
        if (abs === 0) {
          scale = 1; blurAmt = 0; opacityVal = 1; zIdx = 5;
        } else if (abs === 1) {
          scale = 0.82; blurAmt = 3; opacityVal = 0.4; zIdx = 3;
        } else if (abs === 2) {
          scale = 0.70; blurAmt = 6; opacityVal = 0.15; zIdx = 1;
        } else {
          scale = 0.5; blurAmt = 15; opacityVal = 0; zIdx = 0;
        }

        card.style.transform = 'translate(calc(-50% + ' + x + 'px), -50%) scale(' + scale + ')';
        card.style.filter = 'blur(' + blurAmt + 'px)';
        card.style.opacity = opacityVal;
        card.style.zIndex = zIdx;
        card.style.pointerEvents = abs > 2 ? 'none' : '';

        card.classList.toggle('b-pos-center', abs === 0);
      });

      dotsWrap.querySelectorAll('.brands-dot').forEach(function(d, i) {
        d.classList.toggle('active', i === current);
      });

      // Announce current brand to screen readers
      if (brands[current]) {
        liveRegion.textContent = 'Showing: ' + brands[current].name + (brands[current].industry ? ', ' + brands[current].industry : '');
      }
    }

    /* ── 5. Navigate ── */
    function goTo(index) {
      if (isAnimating) return;
      isAnimating = true;
      current = (index + brands.length) % brands.length;
      updateCarousel();
      setTimeout(function() { isAnimating = false; }, 750);
      resetAuto();
    }

    function next() { goTo(current + 1); }
    function prev() { goTo(current - 1); }

    prevBtn.addEventListener('click', prev);
    nextBtn.addEventListener('click', next);

    /* ── 6. Click / Enter side cards to focus ── */
    track.addEventListener('click', function(e) {
      var card = e.target.closest('.brand-glass-card');
      if (!card) return;
      var idx = parseInt(card.dataset.index);
      if (idx !== current) goTo(idx);
    });
    track.addEventListener('keydown', function(e) {
      if (e.key === 'Enter' || e.key === ' ') {
        var card = e.target.closest('.brand-glass-card');
        if (!card) return;
        var idx = parseInt(card.dataset.index);
        if (idx !== current) { e.preventDefault(); goTo(idx); }
      }
    });

    /* ── 7. Keyboard ── */
    document.addEventListener('keydown', function(e) {
      if (e.key === 'ArrowLeft')  prev();
      if (e.key === 'ArrowRight') next();
    });

    /* ── 8. Touch swipe ── */
    var touchStartX = 0;
    track.addEventListener('touchstart', function(e) {
      touchStartX = e.touches[0].clientX;
    }, { passive: true });
    track.addEventListener('touchend', function(e) {
      var diff = touchStartX - e.changedTouches[0].clientX;
      if (Math.abs(diff) > 50) {
        diff > 0 ? next() : prev();
      }
    }, { passive: true });

    /* ── 9. Auto-advance every 5 seconds ── */
    function startAuto() {
      autoTimer = setInterval(next, 5000);
    }
    function resetAuto() {
      clearInterval(autoTimer);
      startAuto();
    }

    /* ── 10. Pause on hover ── */
    track.addEventListener('mouseenter', function() { clearInterval(autoTimer); });
    track.addEventListener('mouseleave', startAuto);
    document.addEventListener('visibilitychange', function() {
      if (document.hidden) clearInterval(autoTimer); else startAuto();
    });

    /* ── 11. Init ── */
    updateCarousel();

    // Stagger entrance: cards appear one after another
    var entranceCards = track.querySelectorAll('.brand-glass-card');
    entranceCards.forEach(function(c, i) {
      c.style.transitionDelay = (i * 0.06) + 's';
    });
    requestAnimationFrame(function() {
      entranceCards.forEach(function(c) { c.style.transitionDelay = ''; });
    });

    startAuto();

  })();

  // ── PRICING CAROUSEL ──
  (function() {
    var toggleBtns = document.querySelectorAll('.pricing-toggle-btn');
    var track = document.getElementById('pricingTrack');
    var prev  = document.getElementById('pricingPrev');
    var next  = document.getElementById('pricingNext');
    var dotsWrap = document.getElementById('pricingDots');
    if (!track || !dotsWrap) return;

    var gap = 24;
    var current = 0;
    var isAnimating = false;
    var autoTimer = null;
    var currentCycle = 'one-time';
    var stepCache = 0;

    function getStep() {
      var visibleCards = Array.from(track.children).filter(function(c) { return c.style.display !== 'none'; });
      stepCache = (visibleCards[0] ? visibleCards[0].offsetWidth : 0) + gap;
      return stepCache;
    }

    function applyPosition() {
      track.style.transform = 'translateX(-' + (current * (stepCache || getStep())) + 'px)';
    }

    function updateCarousel() {
      // Clear existing dots
      dotsWrap.innerHTML = '';
      clearInterval(autoTimer);

      var cards = Array.from(track.children);
      var visibleCards = cards.filter(function(card) {
        var cycle = card.getAttribute('data-billing-cycle') || 'one-time';
        var show = (cycle === currentCycle);
        card.style.display = show ? 'block' : 'none';
        return show;
      });

      var totalVisible = visibleCards.length;
      var slideCount = window.matchMedia('(max-width: 640px)').matches ? totalVisible : Math.max(totalVisible - 2, 1);

      // Reset positions
      current = 0;
      track.style.transform = 'translateX(0)';

      if (slideCount <= 1) {
        if (prev) prev.style.display = 'none';
        if (next) next.style.display = 'none';
        return;
      } else {
        if (prev) prev.style.display = 'flex';
        if (next) next.style.display = 'flex';
      }

      for (var i = 0; i < slideCount; i++) {
        var dot = document.createElement('div');
        dot.className = 'pricing-dot' + (i === 0 ? ' active' : '');
        dot.addEventListener('click', function(idx) {
          return function() { goTo(idx, slideCount); };
        }(i));
        dotsWrap.appendChild(dot);
      }

      if (!isMobileDevice) {
        startAuto(slideCount);
      }
    }

    function goTo(index, slideCount) {
      if (isAnimating) return;
      isAnimating = true;
      current = (index + slideCount) % slideCount;
      applyPosition();
      var dots = dotsWrap.querySelectorAll('.pricing-dot');
      for (var i = 0; i < dots.length; i++) {
        dots[i].classList.toggle('active', i === current);
      }
      setTimeout(function() { isAnimating = false; }, 900);
      resetAuto(slideCount);
    }

    function slideNext(slideCount) { goTo(current + 1, slideCount); }
    function slidePrev(slideCount) { goTo(current - 1, slideCount); }

    function startAuto(slideCount) {
      autoTimer = setInterval(function() { slideNext(slideCount); }, 5000);
    }
    function resetAuto(slideCount) {
      clearInterval(autoTimer);
      startAuto(slideCount);
    }

    // Set up toggle buttons
    toggleBtns.forEach(function(btn) {
      btn.addEventListener('click', function() {
        if (btn.classList.contains('active')) return;
        toggleBtns.forEach(function(b) { b.classList.remove('active'); });
        btn.classList.add('active');

        // Determine cycle based on button text
        var text = btn.textContent.trim().toUpperCase();
        if (text.includes('MONTHLY') || text.includes('RETAINER')) {
          currentCycle = 'monthly';
        } else {
          currentCycle = 'one-time';
        }

        updateCarousel();
      });
    });

    // Set up arrow listeners (using closures to get correct slide count dynamically)
    if (prev) {
      prev.addEventListener('click', function() {
        var visibleCards = Array.from(track.children).filter(function(c) { return c.style.display !== 'none'; });
        var slideCount = window.matchMedia('(max-width: 640px)').matches ? visibleCards.length : Math.max(visibleCards.length - 2, 1);
        goTo(current - 1, slideCount);
      });
    }
    if (next) {
      next.addEventListener('click', function() {
        var visibleCards = Array.from(track.children).filter(function(c) { return c.style.display !== 'none'; });
        var slideCount = window.matchMedia('(max-width: 640px)').matches ? visibleCards.length : Math.max(visibleCards.length - 2, 1);
        goTo(current + 1, slideCount);
      });
    }

    document.addEventListener('keydown', function(e) {
      var visibleCards = Array.from(track.children).filter(function(c) { return c.style.display !== 'none'; });
      var slideCount = window.matchMedia('(max-width: 640px)').matches ? visibleCards.length : Math.max(visibleCards.length - 2, 1);
      if (e.key === 'ArrowLeft') goTo(current - 1, slideCount);
      if (e.key === 'ArrowRight') goTo(current + 1, slideCount);
    });

    var touchStartX = 0;
    track.addEventListener('touchstart', function(e) {
      touchStartX = e.touches[0].clientX;
    }, { passive: true });
    track.addEventListener('touchend', function(e) {
      var diff = touchStartX - e.changedTouches[0].clientX;
      if (Math.abs(diff) > 50) {
        var visibleCards = Array.from(track.children).filter(function(c) { return c.style.display !== 'none'; });
        var slideCount = window.matchMedia('(max-width: 640px)').matches ? visibleCards.length : Math.max(visibleCards.length - 2, 1);
        diff > 0 ? goTo(current + 1, slideCount) : goTo(current - 1, slideCount);
      }
    }, { passive: true });

    window.addEventListener('resize', applyPosition);

    if (!isMobileDevice) {
      track.addEventListener('mouseenter', function() { clearInterval(autoTimer); });
      track.addEventListener('mouseleave', function() {
        var visibleCards = Array.from(track.children).filter(function(c) { return c.style.display !== 'none'; });
        var slideCount = window.matchMedia('(max-width: 640px)').matches ? visibleCards.length : Math.max(visibleCards.length - 2, 1);
        startAuto(slideCount);
      });
      document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
          clearInterval(autoTimer);
        } else {
          var visibleCards = Array.from(track.children).filter(function(c) { return c.style.display !== 'none'; });
          var slideCount = window.matchMedia('(max-width: 640px)').matches ? visibleCards.length : Math.max(visibleCards.length - 2, 1);
          startAuto(slideCount);
        }
      });
    }

    // Initialize first cycle
    updateCarousel();

  })();

  // ── BLOG CAROUSEL ──
  (function() {
    var track = document.getElementById('blogTrack');
    var prev  = document.getElementById('blogPrev');
    var next  = document.getElementById('blogNext');
    var dotsWrap = document.getElementById('blogDots');
    if (!track || !dotsWrap) return;

    var cards = track.children;
    var total = cards.length;
    var gap = 24;
    var slideCount = window.matchMedia('(max-width: 640px)').matches ? total : Math.max(total - 2, 1);
    if (slideCount <= 1) {
      if (prev) prev.style.display = 'none';
      if (next) next.style.display = 'none';
      return;
    }

    var current = 0;
    var isAnimating = false;
    var autoTimer = null;
    var stepCache = 0;

    function getStep() {
      stepCache = cards[0].offsetWidth + gap;
      return stepCache;
    }

    function applyPosition() {
      track.style.transform = 'translateX(-' + (current * (stepCache || getStep())) + 'px)';
    }

    for (var i = 0; i < slideCount; i++) {
      var dot = document.createElement('div');
      dot.className = 'blog-dot' + (i === 0 ? ' active' : '');
      dot.addEventListener('click', function(idx) {
        return function() { goTo(idx); };
      }(i));
      dotsWrap.appendChild(dot);
    }

    function goTo(index) {
      if (isAnimating) return;
      isAnimating = true;
      current = (index + slideCount) % slideCount;
      applyPosition();
      var dots = dotsWrap.querySelectorAll('.blog-dot');
      for (var i = 0; i < dots.length; i++) {
        dots[i].classList.toggle('active', i === current);
      }
      setTimeout(function() { isAnimating = false; }, 900);
      resetAuto();
    }

    function slideNext() { goTo(current + 1); }
    function slidePrev() { goTo(current - 1); }

    if (prev) prev.addEventListener('click', slidePrev);
    if (next) next.addEventListener('click', slideNext);

    document.addEventListener('keydown', function(e) {
      if (e.key === 'ArrowLeft') slidePrev();
      if (e.key === 'ArrowRight') slideNext();
    });

    var touchStartX = 0;
    track.addEventListener('touchstart', function(e) {
      touchStartX = e.touches[0].clientX;
    }, { passive: true });
    track.addEventListener('touchend', function(e) {
      var diff = touchStartX - e.changedTouches[0].clientX;
      if (Math.abs(diff) > 50) {
        diff > 0 ? slideNext() : slidePrev();
      }
    }, { passive: true });

    window.addEventListener('resize', applyPosition);

    function startAuto() { autoTimer = setInterval(slideNext, 5000); }
    function resetAuto() { clearInterval(autoTimer); startAuto(); }

    track.addEventListener('mouseenter', function() { clearInterval(autoTimer); });
    track.addEventListener('mouseleave', startAuto);
    document.addEventListener('visibilitychange', function() {
      if (document.hidden) clearInterval(autoTimer); else startAuto();
    });

    applyPosition();
    startAuto();
  })();

  // ── BLOG CARD CLICK ──
  document.addEventListener('click', function(e) {
    var card = e.target.closest('.blog-card[data-href]');
    if (!card) return;
    if (e.target.closest('a, button')) e.preventDefault();
    var href = card.getAttribute('data-href');
    if (href && href !== '#') window.location.href = href;
  });

  // ── VIEW ALL PROJECTS LINK HOVER ──
  document.querySelectorAll('.p-view-all-link').forEach(function(link) {
    link.addEventListener('mouseenter', function() {
      link.style.borderColor = '#CCFF00';
      link.style.color = 'white';
    });
    link.addEventListener('mouseleave', function() {
      link.style.borderColor = '#222';
      link.style.color = '#9CA3AF';
    });
  });

}); // end DOMContentLoaded

// ── SERVICES ACCORDION TOGGLE ──
function toggleService(el) {
  var item = el.closest('.svc-item');
  if (!item) return;
  var isActive = item.classList.contains('active');
  document.querySelectorAll('.svc-item.active').forEach(function(i) { i.classList.remove('active'); });
  if (!isActive) item.classList.add('active');
}