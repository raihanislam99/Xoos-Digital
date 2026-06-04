-- =============================================================
-- Performance Indexes for Xoos Digital PostgreSQL (Supabase)
-- Generated from codebase audit of slow queries
-- Apply via Supabase Dashboard SQL Editor
-- =============================================================

-- 1. leads: most-filtered columns (status, is_blacklisted, created_at, niche)
CREATE INDEX IF NOT EXISTS idx_leads_status ON leads (status);
CREATE INDEX IF NOT EXISTS idx_leads_is_blacklisted ON leads (is_blacklisted);
CREATE INDEX IF NOT EXISTS idx_leads_created_at ON leads (created_at);
CREATE INDEX IF NOT EXISTS idx_leads_niche ON leads (niche) WHERE niche IS NOT NULL AND niche != '';
CREATE INDEX IF NOT EXISTS idx_leads_email ON leads (email) WHERE email IS NOT NULL AND email != '';
CREATE INDEX IF NOT EXISTS idx_leads_business_name ON leads (business_name);

-- 2. contact_messages: filtered by is_read, ordered by created_at
CREATE INDEX IF NOT EXISTS idx_contact_messages_is_read ON contact_messages (is_read);
CREATE INDEX IF NOT EXISTS idx_contact_messages_created_at ON contact_messages (created_at);

-- 3. admin_tasks: filtered by status, due_date, ordered by created_at
CREATE INDEX IF NOT EXISTS idx_admin_tasks_status ON admin_tasks (status);
CREATE INDEX IF NOT EXISTS idx_admin_tasks_due_date ON admin_tasks (due_date);
CREATE INDEX IF NOT EXISTS idx_admin_tasks_created_at ON admin_tasks (created_at);

-- 4. blog_posts: filtered by status, ordered by created_at
CREATE INDEX IF NOT EXISTS idx_blog_posts_status ON blog_posts (status);
CREATE INDEX IF NOT EXISTS idx_blog_posts_created_at ON blog_posts (created_at);
CREATE INDEX IF NOT EXISTS idx_blog_posts_category_id ON blog_posts (category_id);

-- 5. generated_posts: filtered by status
CREATE INDEX IF NOT EXISTS idx_generated_posts_status ON generated_posts (status);

-- 6. Foreign key indexes for leads sub-tables
CREATE INDEX IF NOT EXISTS idx_lead_activity_lead_id ON lead_activity (lead_id);
CREATE INDEX IF NOT EXISTS idx_lead_emails_lead_id ON lead_emails (lead_id);
CREATE INDEX IF NOT EXISTS idx_lead_whatsapp_lead_id ON lead_whatsapp (lead_id);

-- 8. QI foreign keys
CREATE INDEX IF NOT EXISTS idx_quotation_items_quotation_id ON quotation_items (quotation_id);
CREATE INDEX IF NOT EXISTS idx_invoice_items_invoice_id ON invoice_items (invoice_id);

-- 9. post_versions: filtered by post_id
CREATE INDEX IF NOT EXISTS idx_post_versions_post_id ON post_versions (post_id);

ANALYZE;
