# Xoos Digital — Next.js Rebuild

## Project Overview
Full rebuild of a digital agency website + admin SaaS platform (CRM/CMS/ERP) from PHP → Next.js 14+ App Router, TypeScript, Tailwind CSS, shadcn/ui, Prisma ORM + Supabase PostgreSQL.

## Tech Stack
- **Framework:** Next.js 14+ (App Router), TypeScript (strict), Tailwind CSS
- **UI:** shadcn/ui (Radix UI), Lucide Icons, Framer Motion
- **Auth:** Supabase Auth + @supabase/ssr
- **Database:** Prisma ORM → Supabase PostgreSQL
- **Forms/Validation:** React Hook Form + Zod
- **Tables:** TanStack Table
- **Charts:** Recharts
- **Rich Text:** TipTap
- **PDF:** @react-pdf/renderer
- **Drag & Drop:** @dnd-kit
- **Notifications:** Sonner
- **Theme:** next-themes

## Architecture
- Single Next.js app with two route groups: `(public)/` and `(admin)/` under `/admin`
- Route handlers in `api/` for all CRUD + AI endpoints
- Middleware for admin auth protection
- AI abstraction layer supporting Groq (primary), OpenAI, Claude, OpenRouter, DeepSeek, Together, Gemini

## Project Structure
```
src/
├── app/
│   ├── (public)/           # Public frontend pages
│   ├── (admin)/admin/      # Admin panel pages
│   └── api/                # Route handlers
├── components/
│   ├── ui/                 # shadcn components
│   ├── public/             # Frontend components
│   └── admin/              # Admin components
├── lib/
│   ├── prisma.ts           # Prisma client
│   ├── supabase/           # Supabase clients
│   ├── auth.ts             # Auth helpers
│   ├── ai.ts               # AI provider abstraction
│   └── validations/        # Zod schemas
├── hooks/
├── types/
└── middleware.ts
```

## Database (30 Models)
Service, Package, Testimonial, Brand, Faq, PortfolioCategory, Portfolio, BlogCategory, BlogPost, ContactMessage, Lead, LeadEmail, LeadWhatsApp, LeadActivity, OutreachTemplate, AdminTask, NoteCategory, Note, NoteChecklistItem, CompanyInfo, Quotation, QuotationItem, Invoice, InvoiceItem, PostProfile, GeneratedPost, PostTrainingData, PostVersion, PostHashtag, Setting, MediaFile, TeamMember

## Phases
1. **Foundation** — Init Next.js, Prisma, Supabase, folder structure
2. **Auth** — Login, session, middleware, roles
3. **Admin Shell** — Sidebar, header, DataTable, dashboard
4. **Content Modules** — 9 CRUD modules (services, portfolio, blog, etc.)
5. **Public Frontend** — 7 pages (home, about, services, portfolio, blog, contact, policy)
6. **CRM Modules** — Leads, tasks, notes, messages
7. **Business Modules** — Media, quotes/invoices, post generator, team, settings
8. **AI & Polish** — AI integration, performance, accessibility
9. **Deployment** — Vercel, CI/CD, DNS switchover

## Current Session
- **Phase:** 1 (Foundation)
- **Completed:** AGENTS.md, session checkpoint
- **Next:** Create Next.js project scaffold

## Reference Files (`.opencode/plans/`)
- `ARCHITECTURE.md` — Full architecture
- `ROADMAP.md` — Phase checklist with tasks
- `SCHEMA.md` — Complete Prisma schema
- `API.md` — All API endpoints
- `COMPONENTS.md` — UI component inventory
