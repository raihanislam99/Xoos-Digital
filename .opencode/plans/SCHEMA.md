# Database Schema — Prisma

## Overview

30 models mapping existing Supabase PostgreSQL tables. All use `uuid` primary keys with `cuid()` default.

## Enums

```prisma
enum TaskPriority { URGENT HIGH MEDIUM LOW }
enum TaskStatus { TODO IN_PROGRESS DONE }
enum LeadStatus { NEW CONTACTED QUALIFIED PROPOSAL NEGOTIATION WON LOST BLACKLISTED }
enum PackageTier { STARTER POPULAR PREMIUM }
enum BillingCycle { ONE_TIME MONTHLY }
enum TeamRole { ADMIN EDITOR VIEWER }
enum BlogStatus { DRAFT PUBLISHED }
enum QuotationStatus { DRAFT SENT ACCEPTED REJECTED }
enum InvoiceStatus { PENDING PAID OVERDUE CANCELLED }
enum Currency { USD BDT EUR GBP }
```

## Content Models (8)

- **Service** — name, description, features[], hashtags[], price, sortOrder
- **Package** — name, tier, tagline, price, features[], billingCycle, isActive
- **Testimonial** — clientName, quote, rating, serviceUsed, clientImage, clientCountry, platform
- **Brand** — name, logoUrl, industry, country, service, bloomColor, sortOrder
- **Faq** — question, answer, category, sortOrder
- **PortfolioCategory** — name (unique), slug (unique), sortOrder → hasMany Portfolio
- **Portfolio** — projectName, client, service, description, imageUrl, slug (unique), challenge, solution, results, technologies[], videoUrl, metaTitle, metaDescription, belongsTo PortfolioCategory
- **BlogCategory** — name (unique), slug (unique), sortOrder → hasMany BlogPost
- **BlogPost** — title, slug (unique), content (rich text), meta fields, tags[], status (DRAFT/PUBLISHED), featuredImage, readTime, belongsTo BlogCategory

## Contact (1)

- **ContactMessage** — name, email, phone, company, services[], budget, message, ipAddress, isRead

## CRM Models (10)

- **Lead** — businessName, ownerName, email, phone, whatsapp, website, social, city, country, niche, leadScore, status, source, tags[], isBlacklisted, googleMapsUrl, hasWebsite, websiteScore, aiAudit → hasMany LeadEmail, LeadWhatsApp, LeadActivity, AdminTask
- **LeadEmail** — leadId, subject, body, status (sent/opened/replied/bounced)
- **LeadWhatsApp** — leadId, message, status
- **LeadActivity** — leadId, type (note/email/call/meeting/status_change), content
- **OutreachTemplate** — name, type (email/whatsapp), subject, body, isDefault
- **AdminTask** — title, description, status, priority, dueDate, category, optional leadId, assigneeType, assigneeName
- **NoteCategory** — name, color, icon, sortOrder → hasMany Note
- **Note** — title, content, belongsTo NoteCategory, isPinned, noteColor, tags[], noteType (text/checklist), reminderAt → hasMany NoteChecklistItem
- **NoteChecklistItem** — noteId, text, isChecked, sortOrder

## Quote/Invoice Models (5)

- **CompanyInfo** — singleton (id="default"): companyName, address, phone, email, logo, taxId, bank info
- **Quotation** — quoteNumber (unique), date, validUntil, client info, subtotal, taxPercent, taxAmount, total, currency, status, notes → hasMany QuotationItem
- **QuotationItem** — quotationId, description, quantity, unit, rate, amount
- **Invoice** — invoiceNumber (unique), date, dueDate, client info, subtotal, taxPercent, taxAmount, total, currency, status, paidDate, notes → hasMany InvoiceItem
- **InvoiceItem** — invoiceId, description, quantity, unit, rate, amount

## Post Generator Models (5)

- **PostProfile** — platform, profileUrl, name, language, tone, niche, color, brandVoice, targetAudience → hasMany GeneratedPost, PostTrainingData
- **GeneratedPost** — profileId, content, language, status (draft/published/archived), topic, linkedinContent, facebookContent, hashtagsUsed[] → hasMany PostVersion
- **PostTrainingData** — profileId, content, type (post/caption/hook/example)
- **PostVersion** — postId, content
- **PostHashtag** — platform, tag (compound unique)

## System Models (3)

- **Setting** — key (unique), value (JSON string)
- **MediaFile** — filename, originalName, filepath, filesize, mimeType, directory, uploadedBy
- **TeamMember** — email (unique), name, role, permissions[], supabaseUid (unique), isActive, onboardingComplete, ownApiKey, ownApiProvider

## Relations Summary

```
PortfolioCategory ──< Portfolio
BlogCategory ──< BlogPost
NoteCategory ──< Note ──< NoteChecklistItem
Lead ──< LeadEmail, LeadWhatsApp, LeadActivity, AdminTask
PostProfile ──< GeneratedPost ──< PostVersion
PostProfile ──< PostTrainingData
Quotation ──< QuotationItem
Invoice ──< InvoiceItem
```
