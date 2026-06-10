<nav class="navbar overflow-safe" id="navbar">
    <div class="navbar-inner">
      <a href="." class="flex items-center">
        <img src="images/logo.png" alt="Xoos Digital" class="footer-logo m-0" loading="eager" width="119" height="36">
      </a>
      <div class="desktop-only flex items-center gap-8 mx-auto">
        <a href="." class="nav-link">Home</a>
        <a href="about" class="nav-link">About Us</a>
        <a href="services" class="nav-link">Services</a>
        <a href="portfolio" class="nav-link">Portfolio</a>
        <a href="blog" class="nav-link">Blog</a>
        <a href="contact" class="nav-link">Contact</a>
      </div>
      <div class="desktop-only flex items-center gap-4">
        <a href="#" onclick="event.preventDefault();openProjectForm()" class="nav-cta" data-magnetic>LET'S BUILD TOGETHER</a>
      </div>
      <button class="hamburger mobile-only" id="hamburger" aria-label="Open menu">
        <span></span><span></span><span></span>
      </button>
    </div>
  </nav>

  <div class="mobile-nav-overlay" id="mobileNavOverlay"></div>
  <div class="mobile-nav" id="mobileNav">
    <button class="mobile-nav-close" id="mobileNavClose" aria-label="Close menu">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
        <line x1="18" y1="6" x2="6" y2="18"/>
        <line x1="6" y1="6" x2="18" y2="18"/>
      </svg>
    </button>
    <div class="mobile-nav-logo">
      <a href="."><img src="images/logo.png" alt="Xoos Digital" width="119" height="36"></a>
    </div>
    <div class="mobile-nav-links">
      <a href="." class="mobile-nav-link" data-delay="0">Home</a>
      <a href="about" class="mobile-nav-link" data-delay="1">About Us</a>
      <a href="services" class="mobile-nav-link" data-delay="2">Services</a>
      <a href="portfolio" class="mobile-nav-link" data-delay="3">Portfolio</a>
      <a href="#pricing" class="mobile-nav-link" data-delay="4">Pricing</a>
      <a href="#faq" class="mobile-nav-link" data-delay="5">FAQ</a>
      <a href="blog" class="mobile-nav-link" data-delay="6">Blog</a>
      <a href="contact" class="mobile-nav-link" data-delay="7">Contact</a>
    </div>
    <a href="#" onclick="event.preventDefault();openProjectForm()" class="mobile-nav-cta">Get a Free Quote</a>
  </div>
