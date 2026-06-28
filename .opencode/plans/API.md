# API Reference

## Conventions

- Mutation endpoints require auth (session cookie)
- Public endpoints: `/api/chat`, `/api/contact`
- Request/Response: JSON, format `{ data?, error? }`
- Pagination: `{ data: Item[], total, page, limit }`
- Admin endpoints check TeamMember role server-side

---

## Auth

| Method | Endpoint | Body | Response |
|--------|----------|------|----------|
| POST | /api/auth/login | `{ email, password }` | `{ session }` |
| POST | /api/auth/logout | — | `{ success }` |
| GET | /api/auth/session | — | `{ session \| null }` |
| POST | /api/auth/reset-password | `{ email }` | `{ success }` |

## Content CRUD Pattern

For: blog, services, packages, portfolio, portfolio-categories, testimonials, brands, faq, blog-categories

| Method | Endpoint | Query/Body | Response |
|--------|----------|------------|----------|
| GET | /api/{module}/list | `?page,limit,search,sortBy,sortOrder,status` | `{ data[], total, page, limit }` |
| GET | /api/{module}/get/[id] | — | `{ data }` |
| POST | /api/{module}/save | `{ id?, ...fields }` | `{ data }` |
| DELETE | /api/{module}/delete/[id] | — | `{ success }` |
| POST | /api/{module}/bulk | `{ action, ids[] }` | `{ count }` |

## Messages

| POST | /api/messages/save | `{ id, isRead }` | `{ data }` |
| DELETE | /api/messages/delete/[id] | — | `{ success }` |

## Leads

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/leads/list | Paginated with filters (status, niche, country, search) |
| GET | /api/leads/get/[id] | Full detail with emails, WhatsApp, activity, tasks |
| POST | /api/leads/save | Create/update lead |
| DELETE | /api/leads/delete/[id] | Delete + cascade |
| POST | /api/leads/bulk | Bulk: delete, status change, blacklist, export CSV |
| POST | /api/leads/merge | Merge duplicates: `{ primaryId, duplicateIds[] }` |
| POST | /api/leads/email | Send outreach: `{ leadId, subject, body }` |
| POST | /api/leads/whatsapp | Log WhatsApp: `{ leadId, message }` |
| POST | /api/leads/google | Scrape Google Maps: `{ query, location, maxResults? }` |
| POST | /api/leads/ai/cold-email | AI cold email generation |
| POST | /api/leads/ai/audit | AI website audit |
| POST | /api/leads/ai/score | AI lead scoring |
| GET | /api/leads/analytics | Pipeline statistics |
| POST | /api/leads/import-csv | CSV import (FormData) |

## Lead Templates

| GET | /api/leads/templates/list | List templates |
| POST | /api/leads/templates/save | Create/update template |
| DELETE | /api/leads/templates/delete/[id] | Delete template |

## Tasks

| GET | /api/tasks/list | With filters |
| POST | /api/tasks/save | Create/update |
| POST | /api/tasks/reorder | Drag-drop reorder: `{ id, status, position }` |
| DELETE | /api/tasks/delete/[id] | Delete |

## Notes

| GET | /api/notes/list | With filters (categoryId, isPinned, search) |
| POST | /api/notes/save | With checklistItems[] |
| DELETE | /api/notes/delete/[id] | Delete |

## Quote/Invoice

| GET | /api/quote-invoice/quotes/list | Paginated quotes |
| GET | /api/quote-invoice/invoices/list | Paginated invoices |
| POST | /api/quote-invoice/quotes/save | Create/update quote with items |
| POST | /api/quote-invoice/invoices/save | Create/update invoice with items |
| POST | /api/quote-invoice/generate-pdf | `{ type, id }` → `{ url }` |
| GET | /api/quote-invoice/company-info | Get company info |
| POST | /api/quote-invoice/company-info/save | Save company info |

## Post Generator

| GET | /api/post-generator/profiles/list | List profiles |
| POST | /api/post-generator/profiles/save | Create/update profile |
| POST | /api/post-generator/generate | AI generate: `{ profileId, topic, platform }` → `{ data }` |
| GET | /api/post-generator/posts/list | With profileId, status filters |
| GET | /api/post-generator/posts/get/[id] | Includes versions |
| POST | /api/post-generator/training-data/save | Add training data |

## Media

| GET | /api/media/list | `?directory=` |
| POST | /api/media/upload | FormData (files[]) |
| DELETE | /api/media/delete/[id] | Delete |

## Team

| GET | /api/team/list | List members |
| POST | /api/team/save | Create/update |
| POST | /api/team/invite | Send invite: `{ email, role }` |
| DELETE | /api/team/delete/[id] | Remove |

## Settings

| GET | /api/settings | All settings as key-value |
| POST | /api/settings/save | `{ key, value }` |
| POST | /api/settings/save-many | `{ settings: {} }` |

## AI & Chat

| POST | /api/chat | Public chatbot (rate-limited): `{ message, history? }` → `{ reply }` |
| POST | /api/ai/task | Admin AI tools: `{ task, input, params? }` → `{ result }` |

## Upload

| POST | /api/upload | FormData (file, directory?) → `{ url, filename, size }` |

---

## Error Format

```json
{ "error": { "code": "UNAUTHORIZED", "message": "...", "details?": {} } }
```

## Status Codes

| Code | Meaning |
|------|---------|
| 200 | Success |
| 201 | Created |
| 400 | Validation |
| 401 | Unauthenticated |
| 403 | Forbidden |
| 404 | Not found |
| 429 | Rate limited |
| 500 | Server error |
