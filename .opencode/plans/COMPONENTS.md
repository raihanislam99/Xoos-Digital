# UI Component Inventory

## shadcn/ui Base Components

Button, Input, Textarea, Label, Form, Select, Command, Dialog, Sheet, Popover, Calendar, Card, Table, Badge, Tabs, Avatar, DropdownMenu, Separator, Skeleton, Switch, Progress, Tooltip, AlertDialog + Sonner for toasts.

---

## Public Frontend Components

### Layout
| Component | Description |
|-----------|-------------|
| Navbar | Responsive, mobile drawer, scroll effect (transparent→solid) |
| Footer | Multi-column: contact, social, quick links, copyright |
| ProjectFormModal | "Start a Project" CTA modal form |
| AIChatbot | Floating chat bubble, Groq-powered, quick replies |

### Home Page Sections
| Component | Description |
|-----------|-------------|
| HeroSection | Headline, subtitle, CTAs, animated bg |
| StatsBar | Animated counters (years, projects, clients) |
| ProcessTimeline | Horizontal/vertical timeline |
| BrandsCarousel | Infinite scroll logo carousel |
| ServicesAccordion | Expandable service cards |
| PortfolioBentoGrid | Masonry/bento grid |
| PricingCarousel | Package cards (starter/popular/premium) |
| TestimonialsCarousel | Auto-playing cards with ratings |
| BlogCarousel | Horizontal scroll of recent posts |
| FaqAccordion | Expandable by category |

### Page-Specific
| Component | Page | Description |
|-----------|------|-------------|
| AboutHero | About | Founder intro |
| ServiceCard | Services | Icon, description, features |
| PortfolioGrid | Portfolio | Filterable with category tabs |
| ProjectDetail | Portfolio/[slug] | Case study layout |
| BlogGrid | Blog | Featured + grid with pagination |
| BlogPostContent | Blog/[slug] | Rich article + TOC |
| ContactForm | Contact | Validated form |
| ContactInfo | Contact | Address, phone, social |

---

## Admin Components

### Shell
| Component | Description |
|-----------|-------------|
| AdminSidebar | Collapsible, grouped nav (Content, CRM, Business, System) |
| AdminHeader | Breadcrumb, CMD+K search, notifications, theme toggle, user avatar |
| AdminLayout | Combines sidebar + header + content area |

### Common Patterns
| Component | Description |
|-----------|-------------|
| DataTable | TanStack Table: sort, filter, search, pagination, selection, bulk actions, CSV export |
| DataTableToolbar | Search + filters + bulk action buttons |
| DataTablePagination | Page controls + page size |
| DataTableRowActions | Dropdown (edit, delete) per row |
| StatCard | Icon + label + value + trend |
| ActivityFeed | Timeline of recent actions |
| MiniChart | Recharts (bar, line, donut) |

### Form Components
| Component | Description |
|-----------|-------------|
| FormField | RHF + Zod + shadcn Input |
| FormSelect | Select wrapped with RHF |
| FormTextarea | Textarea wrapped with RHF |
| FormSwitch | Toggle with RHF |
| FormDatePicker | Calendar popover with RHF |
| FormTagInput | Multi-tag input |
| FormRichText | TipTap editor with RHF |
| FormImageUpload | Preview + upload dropzone |
| FormArrayField | Dynamic repeater (quote items, checklists) |

### Module-Specific
| Component | Module | Description |
|-----------|--------|-------------|
| BlogEditor | Blog | TipTap with image embed, tables, code blocks |
| BlogMetaEditor | Blog | SEO fields with char count |
| PortfolioCaseStudy | Portfolio | Challenge/Solution/Results editor |
| KanbanBoard | Tasks | dnd-kit columns (TODO, IN_PROGRESS, DONE) |
| KanbanCard | Tasks | Priority badge, assignee, due date |
| LeadTable | Leads | Advanced DataTable with status, score, tags |
| LeadDetail | Leads | Full profile with tabs (info, emails, activity) |
| LeadAnalytics | Leads/Analytics | Pipeline funnel, conversion metrics |
| LeadFinder | Leads/Finder | Search + results + CSV import |
| NoteSticky | Notes | Colorful card, edit-in-place, checklist |
| MediaGrid | Media | Image grid with preview, upload |
| MediaUploader | Media | Multi-file with progress |
| QuoteForm | Quote/Invoice | Dynamic items, tax calc, currency |
| InvoiceForm | Quote/Invoice | Same + status/payment tracking |
| QuotePDF / InvoicePDF | Quote/Invoice | PDF preview via @react-pdf |
| PostGeneratorForm | Post Gen | Profile → topic → generate → review |
| PostVersionHistory | Post Gen | Version diffs accordion |
| TeamMemberCard | Team | Role badge, status, actions |
| AITaskForm | AI Tools | Task type → input → generate → copy |

---

## State Management

| Type | Tool | Usage |
|------|------|-------|
| Server state | React Server Components | Initial page data, SEO |
| Server mutations | Server Actions / Route Handlers | CRUD operations |
| Client data fetching | TanStack Query | Tables, leads, search |
| Client UI state | useState / useReducer | Modals, toggles, forms |
| Global client state | Zustand | Theme, sidebar, preferences |
| URL state | useSearchParams | Filters, pagination, search |
