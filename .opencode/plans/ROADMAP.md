# Xoos Digital — Rebuild Roadmap

## Phase 1: Foundation (Days 1-4) ✅

- [x] create-next-app with TypeScript + Tailwind
- [x] Configure next.config.ts, tsconfig.json (strict), tailwind.config.ts
- [x] Initialize shadcn/ui + install all dependencies
- [x] Set up Prisma, write full schema.prisma (30 models), generate client
- [x] Set up Prisma + Supabase PostgreSQL adapter
- [x] Create Supabase clients (client.ts, server.ts, admin.ts)
- [x] Create middleware.ts for admin route protection
- [x] Set up environment variables (.env.local)
- [x] Create complete folder scaffold (public routes, admin routes, API routes, components, lib)
- [x] **Copy static assets (31 files):** favicons, manifest, logo, icons, brand logos, OG image → public/
- [x] **Copy admin uploads (43 files):** → public/uploads/
- [x] Init all 20+ shadcn/ui components

## Phase 2: Auth System (Days 5-7) ✅

- [x] Build login page with email/password + Supabase Auth
- [x] Set up @supabase/ssr cookie-based session handling
- [x] Build forgot password flow + update-password page
- [x] Build logout flow (header + API route)
- [x] Admin root layout with TeamMember role/permission check
- [x] Team invite page + onboarding flow for new members
- [x] Team management CRUD (list, invite, activate/deactivate, delete)
- [x] Auth middleware with public path exemptions
- [x] Session API endpoint

## Phase 3: Admin Shell (Days 8-10)

- [ ] Admin sidebar navigation (collapsible, grouped by category)
- [ ] Admin header (breadcrumb, global search, user menu, theme toggle)
- [ ] Reusable DataTable wrapper (TanStack Table + shadcn)
- [ ] Reusable StatCard component
- [ ] Reusable ActivityFeed component
- [ ] Dashboard charts via Recharts
- [ ] Responsive admin layout

## Phase 4: Content Modules (Days 11-18)

Each module: List (DataTable) + Create/Edit (Dialog/Sheet) + Delete.

- [ ] Services module CRUD
- [ ] Packages module CRUD
- [ ] Testimonials module CRUD
- [ ] Brands module CRUD
- [ ] FAQ module CRUD
- [ ] Portfolio Categories CRUD
- [ ] Portfolio module CRUD (with case study fields)
- [ ] Blog Categories CRUD
- [ ] Blog module CRUD (TipTap editor, featured image, SEO meta)

## Phase 5: Public Frontend (Days 19-25)

- [ ] About page
- [ ] Services page
- [ ] Portfolio grid + project detail [slug]
- [ ] Blog listing + single post [slug]
- [ ] Contact page (form → ContactMessage)
- [ ] Policy page
- [ ] 404 / 500 error pages
- [ ] Project form modal
- [ ] AI chatbot widget
- [ ] SEO metadata, OG tags, JSON-LD structured data

## Phase 6: CRM Modules (Days 26-32)

- [ ] Messages module — read/unread, delete
- [ ] Leads — My Leads (DataTable with filters, bulk actions, CSV export)
- [ ] Leads — Analytics (charts, pipeline stats)
- [ ] Leads — Finder (manual add, CSV import, Google Maps scraper)
- [ ] Leads — Campaigns
- [ ] Leads — Templates (email/message templates CRUD)
- [ ] Leads API endpoints (email, WhatsApp, AI cold email, audit, scoring)
- [ ] Tasks — Kanban board (dnd-kit) + DataTable view
- [ ] Notes — Sticky notes with categories, checklists, colors

## Phase 7: Business Modules (Days 33-38)

- [ ] Media Library — grid, upload, preview, delete (Supabase Storage)
- [ ] Quote/Invoice — CRUD with line items, tax, currency
- [ ] PDF generation (@react-pdf/renderer)
- [ ] Post Generator — wizard: profile → topic → generate → versions
- [ ] Settings (contact, social, founder, stats, AI providers)
- [ ] AI Tools page (blog ideas, image prompts, case studies, grammar, SEO, translate)

## Phase 8a: Admin Uploads → Supabase Storage (Days 39-41)

- [ ] Create uploads bucket in Supabase Storage
- [ ] Upload all 43 admin files to bucket
- [ ] Update DB references (portfolio, blog, team) to Supabase public URLs
- [ ] Build /api/upload route handler with Supabase client
- [ ] Build MediaLibrary admin component (browse, upload, preview, delete)

## Phase 8b: System Overhaul (Days 42-45)

- [ ] Dynamic sitemap.xml (auto-generated from blog + portfolio + pages)
- [ ] RSS feed for blog (app/blog/feed.xml/route.ts)
- [ ] JSON-LD structured data (Organization, WebSite, Article, LocalBusiness schemas)
- [ ] PWA support (service worker, offline fallback)
- [ ] Performance optimization (Next.js Image, lazy loading, code splitting, ISR)
- [ ] Privacy-first analytics (Plausible/Umami)
- [ ] Security headers in next.config.ts (CSP, HSTS, X-Frame-Options)
- [ ] Full responsive audit (mobile, tablet, desktop)
- [ ] Accessibility audit (keyboard nav, screen readers, ARIA)

## Phase 9: Deployment (Days 46-48)

- [ ] Set up Vercel project, link GitHub
- [ ] Configure environment variables on Vercel
- [ ] Custom domain setup
- [ ] Prisma migration on production DB
- [ ] Full test in production
- [ ] CI/CD (GitHub Actions → Vercel)
- [ ] DNS switchover (keep PHP app until verified)
- [ ] Post-deployment monitoring & cleanup

---

**Total Estimate: ~48 days (full-time)**
