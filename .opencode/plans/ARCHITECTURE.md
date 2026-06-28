# Xoos Digital — Architecture

## Overview

Single Next.js 14+ (App Router) application serving both the **public-facing website** and **admin panel** under the `/admin` route group. Shared database, auth, and utilities via a monorepo-style `src/` structure.

## Tech Stack

| Layer | Technology |
|-------|-----------|
| **Framework** | Next.js 14+ (App Router) |
| **Language** | TypeScript (strict) |
| **Styling** | Tailwind CSS + `@tailwindcss/typography` |
| **UI Library** | shadcn/ui (Radix UI primitives) |
| **Database** | Supabase PostgreSQL via Prisma ORM |
| **Auth** | Supabase Auth + `@supabase/ssr` |
| **Forms** | React Hook Form + Zod |
| **Tables** | TanStack Table |
| **Rich Text** | TipTap |
| **Charts** | Recharts |
| **PDF** | @react-pdf/renderer |
| **Icons** | Lucide React |
| **Drag & Drop** | @dnd-kit |
| **Animation** | Framer Motion |
| **Toasts** | Sonner |

## Project Structure

```
src/
├── app/
│   ├── (public)/           # Public frontend route group
│   │   ├── page.tsx        # Home
│   │   ├── about/page.tsx
│   │   ├── services/page.tsx
│   │   ├── portfolio/
│   │   │   ├── page.tsx
│   │   │   └── [slug]/page.tsx
│   │   ├── blog/
│   │   │   ├── page.tsx
│   │   │   └── [slug]/page.tsx
│   │   ├── contact/page.tsx
│   │   ├── policy/page.tsx
│   │   ├── not-found.tsx
│   │   └── layout.tsx        # navbar, footer, chatbot
│   │
│   ├── (admin)/              # Admin route group
│   │   ├── admin/
│   │   │   ├── login/page.tsx
│   │   │   ├── forgot-password/page.tsx
│   │   │   ├── dashboard/page.tsx
│   │   │   ├── blog/
│   │   │   ├── services/
│   │   │   ├── packages/
│   │   │   ├── portfolio/
│   │   │   ├── portfolio-categories/
│   │   │   ├── testimonials/
│   │   │   ├── brands/
│   │   │   ├── faq/
│   │   │   ├── messages/
│   │   │   ├── leads/
│   │   │   │   ├── page.tsx
│   │   │   │   ├── analytics/page.tsx
│   │   │   │   ├── finder/page.tsx
│   │   │   │   ├── campaigns/page.tsx
│   │   │   │   └── templates/page.tsx
│   │   │   ├── media/
│   │   │   ├── tasks/
│   │   │   ├── notes/
│   │   │   ├── quote-invoice/
│   │   │   ├── post-generator/
│   │   │   ├── team/
│   │   │   ├── ai/page.tsx
│   │   │   ├── settings/
│   │   │   └── layout.tsx    # sidebar, header, auth check
│   │   └── layout.tsx        # root admin auth gate
│   │
│   └── api/                  # Route handlers
│       ├── auth/
│       ├── blog/
│       ├── services/
│       ├── packages/
│       ├── portfolio/
│       ├── testimonials/
│       ├── brands/
│       ├── faq/
│       ├── messages/
│       ├── leads/
│       ├── media/
│       ├── tasks/
│       ├── notes/
│       ├── quote-invoice/
│       ├── post-generator/
│       ├── team/
│       ├── settings/
│       ├── chat/
│       ├── ai/
│       └── upload/
│
├── components/
│   ├── ui/                   # shadcn components
│   ├── public/               # Frontend-specific
│   └── admin/                # Admin-specific
│
├── lib/
│   ├── prisma.ts
│   ├── supabase/
│   │   ├── client.ts
│   │   ├── server.ts
│   │   └── admin.ts
│   ├── auth.ts
│   ├── ai.ts
│   ├── utils.ts
│   └── validations/
│
├── hooks/
├── types/
└── middleware.ts
```

## Data Flow

```
Browser → Next.js App Router → Server Component / Route Handler
                                  ↓
                             Prisma ORM
                                  ↓
                          Supabase PostgreSQL
```

## Auth Flow

```
Login → supabase.auth.signInWithPassword() → session cookies
middleware.ts checks session on /admin/*
Admin layout checks TeamMember role → restrict access
Logout → signOut() + clear cookies → redirect /admin/login
```

## AI Abstraction

```
lib/ai.ts → unified interface for:
  Groq (primary), OpenAI, Claude, OpenRouter, DeepSeek, Together, Gemini
```
