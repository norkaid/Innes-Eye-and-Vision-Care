-- Innes Eye & Visioncare Centre: complete SQLite database (all tables + DEMO DATA).
-- DEMO DATA — REPLACE BEFORE PRODUCTION. For local testing only; never import into a live server.
-- Demo logins: superadmin@ / admin@ / reception@ / content@ / customer@innes.test, password Demo-Password-2026
-- Generated from the repository's migrations and seeders (php artisan migrate:fresh --seed).
-- DB Browser for SQLite: Execute SQL tab -> paste or open this file -> Execute all (F5) -> Write Changes (Ctrl+S).
PRAGMA foreign_keys=OFF;
DROP TABLE IF EXISTS "appointment_status_histories";
DROP TABLE IF EXISTS "appointments";
DROP TABLE IF EXISTS "article_categories";
DROP TABLE IF EXISTS "audit_logs";
DROP TABLE IF EXISTS "branch_opening_hours";
DROP TABLE IF EXISTS "branch_service";
DROP TABLE IF EXISTS "branches";
DROP TABLE IF EXISTS "business_settings";
DROP TABLE IF EXISTS "cache";
DROP TABLE IF EXISTS "cache_locks";
DROP TABLE IF EXISTS "enquiries";
DROP TABLE IF EXISTS "enquiry_notes";
DROP TABLE IF EXISTS "failed_jobs";
DROP TABLE IF EXISTS "health_articles";
DROP TABLE IF EXISTS "job_batches";
DROP TABLE IF EXISTS "jobs";
DROP TABLE IF EXISTS "media";
DROP TABLE IF EXISTS "media_contents";
DROP TABLE IF EXISTS "migrations";
DROP TABLE IF EXISTS "notification_deliveries";
DROP TABLE IF EXISTS "password_reset_tokens";
DROP TABLE IF EXISTS "product_categories";
DROP TABLE IF EXISTS "products";
DROP TABLE IF EXISTS "role_user";
DROP TABLE IF EXISTS "roles";
DROP TABLE IF EXISTS "services";
DROP TABLE IF EXISTS "sessions";
DROP TABLE IF EXISTS "users";
CREATE TABLE "appointment_status_histories" ("id" integer primary key autoincrement not null, "appointment_id" integer not null, "old_status" varchar, "new_status" varchar not null, "changed_by" integer, "internal_note" text, "created_at" datetime, foreign key("appointment_id") references "appointments"("id") on delete cascade, foreign key("changed_by") references "users"("id") on delete set null);
INSERT INTO "appointment_status_histories" VALUES(1,1,NULL,'pending',NULL,'Request submitted online (demo)','2026-11-14 19:23:54');
INSERT INTO "appointment_status_histories" VALUES(2,2,NULL,'pending',NULL,'Request submitted online (demo)','2026-11-14 19:23:54');
INSERT INTO "appointment_status_histories" VALUES(3,2,'pending','confirmed',NULL,'Demo status change','2026-11-14 19:23:54');
INSERT INTO "appointment_status_histories" VALUES(4,3,NULL,'pending',NULL,'Request submitted online (demo)','2026-11-14 19:23:54');
INSERT INTO "appointment_status_histories" VALUES(5,4,NULL,'pending',NULL,'Request submitted online (demo)','2026-11-14 19:23:54');
INSERT INTO "appointment_status_histories" VALUES(6,4,'pending','declined',NULL,'Demo status change','2026-11-14 19:23:54');
CREATE TABLE "appointments" ("id" integer primary key autoincrement not null, "reference_code" varchar not null, "user_id" integer, "branch_id" integer not null, "service_id" integer not null, "customer_name" varchar not null, "customer_email" varchar not null, "customer_phone" varchar not null, "requested_date" date not null, "requested_time" time, "proposed_start_at" datetime, "confirmed_start_at" datetime, "confirmed_end_at" datetime, "status" varchar not null default 'pending', "customer_note" varchar, "staff_note" text, "handled_by" integer, "status_updated_at" datetime, "created_at" datetime, "updated_at" datetime, foreign key("user_id") references "users"("id") on delete set null, foreign key("branch_id") references "branches"("id") on delete restrict, foreign key("service_id") references "services"("id") on delete restrict, foreign key("handled_by") references "users"("id") on delete set null);
INSERT INTO "appointments" VALUES(1,'IEV-BQVQAXVZ',5,1,1,'Demo Customer','customer@innes.test','+233 00 000 0001','2026-11-17 00:00:00','10:00',NULL,NULL,NULL,'pending','Demo note.',NULL,NULL,'2026-11-14 19:23:54','2026-11-14 19:23:54','2026-11-14 19:23:54');
INSERT INTO "appointments" VALUES(2,'IEV-DGXEAURD',5,1,4,'Demo Customer','customer@innes.test','+233 00 000 0001','2026-11-20 00:00:00','11:30',NULL,'2026-11-20 11:30:00','2026-11-20 12:00:00','confirmed',NULL,NULL,NULL,'2026-11-14 19:23:54','2026-11-14 19:23:54','2026-11-14 19:23:54');
INSERT INTO "appointments" VALUES(3,'IEV-B8K5WTC7',NULL,2,2,'Guest Example (Demo)','guest@example.com','+233 00 000 0002','2026-11-18 00:00:00','09:00',NULL,NULL,NULL,'pending',NULL,NULL,NULL,'2026-11-14 19:23:54','2026-11-14 19:23:54','2026-11-14 19:23:54');
INSERT INTO "appointments" VALUES(4,'IEV-SSSZZMUP',NULL,2,3,'Another Guest (Demo)','guest2@example.com','+233 00 000 0003','2026-11-19 00:00:00','14:00',NULL,NULL,NULL,'declined',NULL,NULL,NULL,'2026-11-14 19:23:54','2026-11-14 19:23:54','2026-11-14 19:23:54');
CREATE TABLE "article_categories" ("id" integer primary key autoincrement not null, "name" varchar not null, "slug" varchar not null, "created_at" datetime, "updated_at" datetime);
INSERT INTO "article_categories" VALUES(1,'Eye-care tips','eye-care-tips','2026-11-14 19:23:54','2026-11-14 19:23:54');
INSERT INTO "article_categories" VALUES(2,'Eye conditions','eye-conditions','2026-11-14 19:23:54','2026-11-14 19:23:54');
CREATE TABLE "audit_logs" ("id" integer primary key autoincrement not null, "user_id" integer, "action" varchar not null, "entity_type" varchar, "entity_id" integer, "metadata" text, "created_at" datetime, foreign key("user_id") references "users"("id") on delete set null);
CREATE TABLE "branch_opening_hours" ("id" integer primary key autoincrement not null, "branch_id" integer not null, "day_of_week" integer not null, "opens_at" time, "closes_at" time, "is_closed" tinyint(1) not null default '0', "created_at" datetime, "updated_at" datetime, foreign key("branch_id") references "branches"("id") on delete cascade);
INSERT INTO "branch_opening_hours" VALUES(1,1,1,'08:30','17:00',0,'2026-11-14 19:23:54','2026-11-14 19:23:54');
INSERT INTO "branch_opening_hours" VALUES(2,1,2,'08:30','17:00',0,'2026-11-14 19:23:54','2026-11-14 19:23:54');
INSERT INTO "branch_opening_hours" VALUES(3,1,3,'08:30','17:00',0,'2026-11-14 19:23:54','2026-11-14 19:23:54');
INSERT INTO "branch_opening_hours" VALUES(4,1,4,'08:30','17:00',0,'2026-11-14 19:23:54','2026-11-14 19:23:54');
INSERT INTO "branch_opening_hours" VALUES(5,1,5,'08:30','17:00',0,'2026-11-14 19:23:54','2026-11-14 19:23:54');
INSERT INTO "branch_opening_hours" VALUES(6,1,6,'08:30','13:00',0,'2026-11-14 19:23:54','2026-11-14 19:23:54');
INSERT INTO "branch_opening_hours" VALUES(7,1,7,NULL,NULL,1,'2026-11-14 19:23:54','2026-11-14 19:23:54');
INSERT INTO "branch_opening_hours" VALUES(8,2,1,'08:30','17:00',0,'2026-11-14 19:23:54','2026-11-14 19:23:54');
INSERT INTO "branch_opening_hours" VALUES(9,2,2,'08:30','17:00',0,'2026-11-14 19:23:54','2026-11-14 19:23:54');
INSERT INTO "branch_opening_hours" VALUES(10,2,3,'08:30','17:00',0,'2026-11-14 19:23:54','2026-11-14 19:23:54');
INSERT INTO "branch_opening_hours" VALUES(11,2,4,'08:30','17:00',0,'2026-11-14 19:23:54','2026-11-14 19:23:54');
INSERT INTO "branch_opening_hours" VALUES(12,2,5,'08:30','17:00',0,'2026-11-14 19:23:54','2026-11-14 19:23:54');
INSERT INTO "branch_opening_hours" VALUES(13,2,6,'08:30','13:00',0,'2026-11-14 19:23:54','2026-11-14 19:23:54');
INSERT INTO "branch_opening_hours" VALUES(14,2,7,NULL,NULL,1,'2026-11-14 19:23:54','2026-11-14 19:23:54');
CREATE TABLE "branch_service" ("branch_id" integer not null, "service_id" integer not null, "created_at" datetime, "updated_at" datetime, foreign key("branch_id") references "branches"("id") on delete cascade, foreign key("service_id") references "services"("id") on delete cascade, primary key ("branch_id", "service_id"));
INSERT INTO "branch_service" VALUES(1,1,'2026-11-14 19:23:54','2026-11-14 19:23:54');
INSERT INTO "branch_service" VALUES(2,1,'2026-11-14 19:23:54','2026-11-14 19:23:54');
INSERT INTO "branch_service" VALUES(1,2,'2026-11-14 19:23:54','2026-11-14 19:23:54');
INSERT INTO "branch_service" VALUES(2,2,'2026-11-14 19:23:54','2026-11-14 19:23:54');
INSERT INTO "branch_service" VALUES(1,3,'2026-11-14 19:23:54','2026-11-14 19:23:54');
INSERT INTO "branch_service" VALUES(2,3,'2026-11-14 19:23:54','2026-11-14 19:23:54');
INSERT INTO "branch_service" VALUES(1,4,'2026-11-14 19:23:54','2026-11-14 19:23:54');
INSERT INTO "branch_service" VALUES(2,4,'2026-11-14 19:23:54','2026-11-14 19:23:54');
INSERT INTO "branch_service" VALUES(1,5,'2026-11-14 19:23:54','2026-11-14 19:23:54');
INSERT INTO "branch_service" VALUES(1,6,'2026-11-14 19:23:54','2026-11-14 19:23:54');
CREATE TABLE "branches" ("id" integer primary key autoincrement not null, "name" varchar not null, "slug" varchar not null, "address" varchar, "city" varchar, "region" varchar, "phone" varchar, "email" varchar, "map_url" varchar, "description" text, "is_active" tinyint(1) not null default '1', "display_order" integer not null default '0', "created_at" datetime, "updated_at" datetime, "deleted_at" datetime);
INSERT INTO "branches" VALUES(1,'Accra Branch (Demo)','accra-branch-demo','DEMO ADDRESS – replace with verified address','Accra','Greater Accra','+233 00 000 0000',NULL,NULL,'DEMO DATA — REPLACE BEFORE PRODUCTION. Branch description, address, phone number and opening hours are placeholders awaiting confirmation from the centre.',1,1,'2026-11-14 19:23:54','2026-11-14 19:23:54',NULL);
INSERT INTO "branches" VALUES(2,'Kumasi Branch (Demo)','kumasi-branch-demo','DEMO ADDRESS – replace with verified address','Kumasi','Ashanti','+233 00 000 0000',NULL,NULL,'DEMO DATA — REPLACE BEFORE PRODUCTION. Branch description, address, phone number and opening hours are placeholders awaiting confirmation from the centre.',1,2,'2026-11-14 19:23:54','2026-11-14 19:23:54',NULL);
CREATE TABLE "business_settings" ("id" integer primary key autoincrement not null, "key" varchar not null, "value" text, "created_at" datetime, "updated_at" datetime);
CREATE TABLE "cache" ("key" varchar not null, "value" text not null, "expiration" integer not null, primary key ("key"));
CREATE TABLE "cache_locks" ("key" varchar not null, "owner" varchar not null, "expiration" integer not null, primary key ("key"));
CREATE TABLE "enquiries" ("id" integer primary key autoincrement not null, "user_id" integer, "product_id" integer, "name" varchar not null, "email" varchar not null, "phone" varchar, "subject" varchar not null, "message" text not null, "status" varchar not null default 'new', "assigned_to" integer, "created_at" datetime, "updated_at" datetime, foreign key("user_id") references "users"("id") on delete set null, foreign key("product_id") references "products"("id") on delete set null, foreign key("assigned_to") references "users"("id") on delete set null);
INSERT INTO "enquiries" VALUES(1,NULL,1,'Guest Example (Demo)','guest@example.com',NULL,'Product enquiry: Classic Round Frame (Demo)','Hello, is this frame available at the Accra branch? (Demo enquiry)','new',NULL,'2026-11-14 19:23:54','2026-11-14 19:23:54');
INSERT INTO "enquiries" VALUES(2,5,NULL,'Demo Customer','customer@innes.test',NULL,'Opening hours question','Are you open on public holidays? (Demo enquiry)','in_progress',NULL,'2026-11-14 19:23:54','2026-11-14 19:23:54');
CREATE TABLE "enquiry_notes" ("id" integer primary key autoincrement not null, "enquiry_id" integer not null, "user_id" integer, "note" text not null, "created_at" datetime, "updated_at" datetime, foreign key("enquiry_id") references "enquiries"("id") on delete cascade, foreign key("user_id") references "users"("id") on delete set null);
CREATE TABLE "failed_jobs" ("id" integer primary key autoincrement not null, "uuid" varchar not null, "connection" varchar not null, "queue" varchar not null, "payload" text not null, "exception" text not null, "failed_at" datetime not null default CURRENT_TIMESTAMP);
CREATE TABLE "health_articles" ("id" integer primary key autoincrement not null, "article_category_id" integer, "title" varchar not null, "slug" varchar not null, "excerpt" varchar, "body" text not null, "featured_image_path" varchar, "author_id" integer, "author_display_name" varchar, "status" varchar not null default 'draft', "published_at" datetime, "created_at" datetime, "updated_at" datetime, "deleted_at" datetime, foreign key("article_category_id") references "article_categories"("id") on delete set null, foreign key("author_id") references "users"("id") on delete set null);
INSERT INTO "health_articles" VALUES(1,1,'How often should you have an eye test?','how-often-should-you-have-an-eye-test','Regular eye tests help keep track of your vision and eye health.','Regular eye examinations help detect changes in vision and eye health early.

## Why regular checks matter

Many eye conditions develop gradually and may not cause noticeable symptoms at first.

## How often?

Your optometrist will recommend how often you should be seen based on your age and eye health.

> DEMO DATA — REPLACE BEFORE PRODUCTION. This article is sample text and has not been clinically reviewed by the centre.',NULL,NULL,'Demo Author','published','2026-11-04 19:23:54','2026-11-14 19:23:54','2026-11-14 19:23:54',NULL);
INSERT INTO "health_articles" VALUES(2,1,'Protecting your eyes from the sun','protecting-your-eyes-from-the-sun','Simple ways to protect your eyes outdoors.','Bright sunlight can be uncomfortable and ultraviolet (UV) light can affect the eyes.

- Wear sunglasses that offer UV protection.
- Use a wide-brimmed hat.
- Never look directly at the sun.

> DEMO DATA — REPLACE BEFORE PRODUCTION.',NULL,NULL,'Demo Author','published','2026-10-25 19:23:54','2026-11-14 19:23:54','2026-11-14 19:23:54',NULL);
INSERT INTO "health_articles" VALUES(3,2,'Understanding glaucoma screening','understanding-glaucoma-screening','What glaucoma screening involves and why it matters.','Glaucoma is a group of conditions that can damage the optic nerve.

## What happens during screening?

Screening may include measuring eye pressure and examining the optic nerve.

> DEMO DATA — REPLACE BEFORE PRODUCTION. Content requires clinical review.',NULL,NULL,'Demo Author','published','2026-10-15 19:23:54','2026-11-14 19:23:54','2026-11-14 19:23:54',NULL);
INSERT INTO "health_articles" VALUES(4,1,'Screen time and tired eyes','screen-time-and-tired-eyes','Tips to reduce eye strain when using screens.','Long periods of screen use can make eyes feel tired.

Try the **20-20-20** habit: every 20 minutes, look at something 20 feet away for 20 seconds.

> DEMO DATA — REPLACE BEFORE PRODUCTION.',NULL,NULL,'Demo Author','published','2026-10-05 19:23:54','2026-11-14 19:23:54','2026-11-14 19:23:54',NULL);
INSERT INTO "health_articles" VALUES(5,2,'Dry eye: causes and relief (draft)','draft-dry-eye-article','Draft awaiting clinical review.','Draft content – not visible publicly.',NULL,NULL,NULL,'draft',NULL,'2026-11-14 19:23:54','2026-11-14 19:23:54',NULL);
CREATE TABLE "job_batches" ("id" varchar not null, "name" varchar not null, "total_jobs" integer not null, "pending_jobs" integer not null, "failed_jobs" integer not null, "failed_job_ids" text not null, "options" text, "cancelled_at" integer, "created_at" integer not null, "finished_at" integer, primary key ("id"));
CREATE TABLE "jobs" ("id" integer primary key autoincrement not null, "queue" varchar not null, "payload" text not null, "attempts" integer not null, "reserved_at" integer, "available_at" integer not null, "created_at" integer not null);
CREATE TABLE "media" ("id" integer primary key autoincrement not null, "folder" varchar not null, "path" varchar not null, "original_name" varchar not null, "alt_text" varchar, "mime_type" varchar not null, "size_bytes" integer not null, "width" integer, "height" integer, "uploaded_by" integer, "created_at" datetime, "updated_at" datetime, foreign key("uploaded_by") references "users"("id") on delete set null);
CREATE TABLE "media_contents" ("media_id" integer not null, "data" blob not null, foreign key("media_id") references "media"("id") on delete cascade, primary key ("media_id"));
CREATE TABLE "migrations" ("id" integer primary key autoincrement not null, "migration" varchar not null, "batch" integer not null);
INSERT INTO "migrations" VALUES(1,'0001_01_01_000000_create_users_table',1);
INSERT INTO "migrations" VALUES(2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO "migrations" VALUES(3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO "migrations" VALUES(4,'2026_01_01_000100_create_roles_tables',1);
INSERT INTO "migrations" VALUES(5,'2026_01_01_000200_create_branches_tables',1);
INSERT INTO "migrations" VALUES(6,'2026_01_01_000300_create_services_tables',1);
INSERT INTO "migrations" VALUES(7,'2026_01_01_000400_create_products_tables',1);
INSERT INTO "migrations" VALUES(8,'2026_01_01_000500_create_appointments_tables',1);
INSERT INTO "migrations" VALUES(9,'2026_01_01_000600_create_enquiries_tables',1);
INSERT INTO "migrations" VALUES(10,'2026_01_01_000700_create_health_articles_tables',1);
INSERT INTO "migrations" VALUES(11,'2026_01_01_000800_create_settings_audit_notification_tables',1);
INSERT INTO "migrations" VALUES(12,'2026_01_01_000900_create_media_table',1);
INSERT INTO "migrations" VALUES(13,'2026_01_01_001000_create_media_contents_table',1);
CREATE TABLE "notification_deliveries" ("id" integer primary key autoincrement not null, "notification_type" varchar not null, "channel" varchar not null, "notifiable_type" varchar, "notifiable_id" integer, "status" varchar not null, "error" varchar, "created_at" datetime);
CREATE TABLE "password_reset_tokens" ("email" varchar not null, "token" varchar not null, "created_at" datetime, primary key ("email"));
CREATE TABLE "product_categories" ("id" integer primary key autoincrement not null, "name" varchar not null, "slug" varchar not null, "description" text, "is_active" tinyint(1) not null default '1', "display_order" integer not null default '0', "created_at" datetime, "updated_at" datetime);
INSERT INTO "product_categories" VALUES(1,'Optical Frames','optical-frames','DEMO DATA — REPLACE BEFORE PRODUCTION.',1,1,'2026-11-14 19:23:54','2026-11-14 19:23:54');
INSERT INTO "product_categories" VALUES(2,'Sunglasses','sunglasses','DEMO DATA — REPLACE BEFORE PRODUCTION.',1,2,'2026-11-14 19:23:54','2026-11-14 19:23:54');
INSERT INTO "product_categories" VALUES(3,'Contact Lenses','contact-lenses','DEMO DATA — REPLACE BEFORE PRODUCTION.',1,3,'2026-11-14 19:23:54','2026-11-14 19:23:54');
INSERT INTO "product_categories" VALUES(4,'Lenses','lenses','DEMO DATA — REPLACE BEFORE PRODUCTION.',1,4,'2026-11-14 19:23:54','2026-11-14 19:23:54');
CREATE TABLE "products" ("id" integer primary key autoincrement not null, "product_category_id" integer not null, "name" varchar not null, "slug" varchar not null, "brand" varchar, "short_description" varchar, "description" text, "image_path" varchar, "price" numeric, "availability_status" varchar not null default 'enquire', "is_published" tinyint(1) not null default '0', "created_at" datetime, "updated_at" datetime, "deleted_at" datetime, foreign key("product_category_id") references "product_categories"("id") on delete restrict);
INSERT INTO "products" VALUES(1,1,'Classic Round Frame (Demo)','classic-round-frame-demo','Demo Brand A','Lightweight round frame in a timeless shape.','DEMO DATA — REPLACE BEFORE PRODUCTION.
This is a sample catalogue item. No price or stock information is implied.',NULL,NULL,'enquire',1,'2026-11-14 19:23:54','2026-11-14 19:23:54',NULL);
INSERT INTO "products" VALUES(2,1,'Rectangular Acetate Frame (Demo)','rectangular-acetate-frame-demo','Demo Brand B','Bold rectangular frame in durable acetate.','DEMO DATA — REPLACE BEFORE PRODUCTION.
This is a sample catalogue item. No price or stock information is implied.',NULL,NULL,'enquire',1,'2026-11-14 19:23:54','2026-11-14 19:23:54',NULL);
INSERT INTO "products" VALUES(3,1,'Slim Metal Frame (Demo)','slim-metal-frame-demo','Demo Brand A','Minimal metal frame with adjustable nose pads.','DEMO DATA — REPLACE BEFORE PRODUCTION.
This is a sample catalogue item. No price or stock information is implied.',NULL,NULL,'enquire',1,'2026-11-14 19:23:54','2026-11-14 19:23:54',NULL);
INSERT INTO "products" VALUES(4,1,'Cat-Eye Frame (Demo)','cat-eye-frame-demo','Demo Brand C','Elegant cat-eye silhouette.','DEMO DATA — REPLACE BEFORE PRODUCTION.
This is a sample catalogue item. No price or stock information is implied.',NULL,NULL,'enquire',1,'2026-11-14 19:23:54','2026-11-14 19:23:54',NULL);
INSERT INTO "products" VALUES(5,1,'Children''s Flexible Frame (Demo)','childrens-flexible-frame-demo','Demo Brand D','Flexible frame designed for active children.','DEMO DATA — REPLACE BEFORE PRODUCTION.
This is a sample catalogue item. No price or stock information is implied.',NULL,NULL,'available',1,'2026-11-14 19:23:54','2026-11-14 19:23:54',NULL);
INSERT INTO "products" VALUES(6,1,'Rimless Frame (Demo)','rimless-frame-demo','Demo Brand B','Discreet rimless design.','DEMO DATA — REPLACE BEFORE PRODUCTION.
This is a sample catalogue item. No price or stock information is implied.',NULL,NULL,'enquire',1,'2026-11-14 19:23:54','2026-11-14 19:23:54',NULL);
INSERT INTO "products" VALUES(7,2,'Aviator Sunglasses (Demo)','aviator-sunglasses-demo','Demo Brand E','Classic aviator style sunglasses.','DEMO DATA — REPLACE BEFORE PRODUCTION.
This is a sample catalogue item. No price or stock information is implied.',NULL,NULL,'enquire',1,'2026-11-14 19:23:54','2026-11-14 19:23:54',NULL);
INSERT INTO "products" VALUES(8,2,'Wayfarer-Style Sunglasses (Demo)','wayfarer-style-sunglasses-demo','Demo Brand E','Everyday sunglasses with a square shape.','DEMO DATA — REPLACE BEFORE PRODUCTION.
This is a sample catalogue item. No price or stock information is implied.',NULL,NULL,'enquire',1,'2026-11-14 19:23:54','2026-11-14 19:23:54',NULL);
INSERT INTO "products" VALUES(9,3,'Daily Disposable Lenses (Demo)','daily-disposable-lenses-demo','Demo Brand F','Single-use lenses. Fitting required.','DEMO DATA — REPLACE BEFORE PRODUCTION.
This is a sample catalogue item. No price or stock information is implied.',NULL,NULL,'enquire',1,'2026-11-14 19:23:54','2026-11-14 19:23:54',NULL);
INSERT INTO "products" VALUES(10,3,'Monthly Lenses (Demo)','monthly-lenses-demo','Demo Brand F','Reusable monthly lenses. Fitting required.','DEMO DATA — REPLACE BEFORE PRODUCTION.
This is a sample catalogue item. No price or stock information is implied.',NULL,NULL,'available',1,'2026-11-14 19:23:54','2026-11-14 19:23:54',NULL);
INSERT INTO "products" VALUES(11,4,'Single Vision Lenses (Demo)','single-vision-lenses-demo',NULL,'Lenses for one viewing distance.','DEMO DATA — REPLACE BEFORE PRODUCTION.
This is a sample catalogue item. No price or stock information is implied.',NULL,NULL,'enquire',1,'2026-11-14 19:23:54','2026-11-14 19:23:54',NULL);
INSERT INTO "products" VALUES(12,4,'Blue-Light Filtering Lenses (Demo)','blue-light-filtering-lenses-demo',NULL,'Lens coating option. Ask in branch for details.','DEMO DATA — REPLACE BEFORE PRODUCTION.
This is a sample catalogue item. No price or stock information is implied.',NULL,NULL,'enquire',1,'2026-11-14 19:23:54','2026-11-14 19:23:54',NULL);
INSERT INTO "products" VALUES(13,4,'Progressive Lenses (Demo)','progressive-lenses-demo',NULL,'Lenses for near, intermediate and distance vision.','DEMO DATA — REPLACE BEFORE PRODUCTION.
This is a sample catalogue item. No price or stock information is implied.',NULL,NULL,'enquire',1,'2026-11-14 19:23:54','2026-11-14 19:23:54',NULL);
CREATE TABLE "role_user" ("user_id" integer not null, "role_id" integer not null, "created_at" datetime, "updated_at" datetime, foreign key("user_id") references "users"("id") on delete cascade, foreign key("role_id") references "roles"("id") on delete cascade, primary key ("user_id", "role_id"));
INSERT INTO "role_user" VALUES(1,1,'2026-11-14 19:23:53','2026-11-14 19:23:53');
INSERT INTO "role_user" VALUES(2,2,'2026-11-14 19:23:53','2026-11-14 19:23:53');
INSERT INTO "role_user" VALUES(3,3,'2026-11-14 19:23:54','2026-11-14 19:23:54');
INSERT INTO "role_user" VALUES(4,4,'2026-11-14 19:23:54','2026-11-14 19:23:54');
CREATE TABLE "roles" ("id" integer primary key autoincrement not null, "name" varchar not null, "slug" varchar not null, "description" varchar, "rank" integer not null default '0', "created_at" datetime, "updated_at" datetime);
INSERT INTO "roles" VALUES(1,'Super Admin','super-admin','Full system configuration, staff management and all modules.',100,'2026-11-14 19:23:53','2026-11-14 19:23:53');
INSERT INTO "roles" VALUES(2,'Administrator','administrator','Day-to-day business content, appointments, enquiries, products, services and branches.',80,'2026-11-14 19:23:53','2026-11-14 19:23:53');
INSERT INTO "roles" VALUES(3,'Receptionist','receptionist','Appointment requests and customer enquiries, with the customer details needed for those tasks.',40,'2026-11-14 19:23:53','2026-11-14 19:23:53');
INSERT INTO "roles" VALUES(4,'Content Manager','content-manager','Website content: services, products and articles. No access to private appointment information.',40,'2026-11-14 19:23:53','2026-11-14 19:23:53');
CREATE TABLE "services" ("id" integer primary key autoincrement not null, "name" varchar not null, "slug" varchar not null, "short_description" varchar, "description" text, "preparation" text, "image_path" varchar, "is_published" tinyint(1) not null default '0', "display_order" integer not null default '0', "created_at" datetime, "updated_at" datetime, "deleted_at" datetime);
INSERT INTO "services" VALUES(1,'Comprehensive Eye Examination','comprehensive-eye-examination','A thorough check of your vision and the health of your eyes.','A comprehensive eye examination typically includes a discussion of your eye-health history, vision tests and an examination of the front and back of the eye. Your optometrist will explain the results and any next steps.

DEMO DATA — REPLACE BEFORE PRODUCTION. This description is sample text and has not been approved by the centre.','Bring your current glasses or contact lenses and a list of any questions.',NULL,1,1,'2026-11-14 19:23:54','2026-11-14 19:23:54',NULL);
INSERT INTO "services" VALUES(2,'Vision Assessment','vision-assessment','Checks how clearly you see at different distances.','A vision assessment measures how well you see near and far and whether glasses or lenses could help.

DEMO DATA — REPLACE BEFORE PRODUCTION. This description is sample text and has not been approved by the centre.',NULL,NULL,1,2,'2026-11-14 19:23:54','2026-11-14 19:23:54',NULL);
INSERT INTO "services" VALUES(3,'Glaucoma Screening','glaucoma-screening','Screening tests that can help detect signs of glaucoma.','Glaucoma screening may include measuring eye pressure and examining the optic nerve. Your optometrist will advise whether further tests are needed.

DEMO DATA — REPLACE BEFORE PRODUCTION. This description is sample text and has not been approved by the centre.','Let the team know if anyone in your family has glaucoma.',NULL,1,3,'2026-11-14 19:23:54','2026-11-14 19:23:54',NULL);
INSERT INTO "services" VALUES(4,'Contact Lens Fitting','contact-lens-fitting','Measurement and fitting for comfortable contact lenses.','A contact lens fitting measures your eyes to find suitable lenses and includes guidance on safe handling and care.

DEMO DATA — REPLACE BEFORE PRODUCTION. This description is sample text and has not been approved by the centre.','If you already wear lenses, please bring your lens details.',NULL,1,4,'2026-11-14 19:23:54','2026-11-14 19:23:54',NULL);
INSERT INTO "services" VALUES(5,'Geriatric Eye Care','geriatric-eye-care','Eye care focused on the needs of older adults.','Eye-care appointments for older adults focus on age-related changes in vision and eye health.

DEMO DATA — REPLACE BEFORE PRODUCTION. This description is sample text and has not been approved by the centre.',NULL,NULL,1,5,'2026-11-14 19:23:54','2026-11-14 19:23:54',NULL);
INSERT INTO "services" VALUES(6,'Children''s Eye Test','childrens-eye-test','Friendly vision checks for children.','Children''s eye tests are adapted to the child''s age and help identify vision problems that may affect learning.

DEMO DATA — REPLACE BEFORE PRODUCTION. This description is sample text and has not been approved by the centre.',NULL,NULL,1,6,'2026-11-14 19:23:54','2026-11-14 19:23:54',NULL);
INSERT INTO "services" VALUES(7,'Low Vision Support (draft)','low-vision-support','Draft service awaiting confirmation – not visible publicly.',NULL,NULL,NULL,0,99,'2026-11-14 19:23:54','2026-11-14 19:23:54',NULL);
CREATE TABLE "sessions" ("id" varchar not null, "user_id" integer, "ip_address" varchar, "user_agent" text, "payload" text not null, "last_activity" integer not null, primary key ("id"));
CREATE TABLE "users" ("id" integer primary key autoincrement not null, "first_name" varchar not null, "last_name" varchar not null, "email" varchar not null, "phone" varchar, "password" varchar not null, "account_type" varchar not null default 'customer', "is_active" tinyint(1) not null default '1', "email_verified_at" datetime, "last_login_at" datetime, "remember_token" varchar, "created_at" datetime, "updated_at" datetime, "deleted_at" datetime);
INSERT INTO "users" VALUES(1,'Demo','SuperAdmin','superadmin@innes.test',NULL,'$2y$12$VcbDogivm.YaJVWO5cd4seYvkk1LLLt5fENcoPkSJcyq91bvQPAGS','staff',1,NULL,NULL,NULL,'2026-11-14 19:23:53','2026-11-14 19:23:53',NULL);
INSERT INTO "users" VALUES(2,'Demo','Administrator','admin@innes.test',NULL,'$2y$12$IvA4oDe3xJR4fhOnsULZ/.vrdFwVmqlHOzCFgPdIVvbG9oaYajbQ2','staff',1,NULL,NULL,NULL,'2026-11-14 19:23:53','2026-11-14 19:23:53',NULL);
INSERT INTO "users" VALUES(3,'Demo','Receptionist','reception@innes.test',NULL,'$2y$12$sK/pD9wQK/alhBljfHjehuzRwQR2qJ/wCLliKG.1i/MyNiOGekBWS','staff',1,NULL,NULL,NULL,'2026-11-14 19:23:54','2026-11-14 19:23:54',NULL);
INSERT INTO "users" VALUES(4,'Demo','ContentManager','content@innes.test',NULL,'$2y$12$nc442hGntiwMVFFMXEZcY.OmkTKu8.5/8rCvBgtO9gipATuI8Qz3.','staff',1,NULL,NULL,NULL,'2026-11-14 19:23:54','2026-11-14 19:23:54',NULL);
INSERT INTO "users" VALUES(5,'Demo','Customer','customer@innes.test','+233 00 000 0001','$2y$12$LHUwZffqL4adGjYhmHkRbu5.eVN2SFNjFuib.MPFf8hCGhOqfrSvm','customer',1,NULL,NULL,NULL,'2026-11-14 19:23:54','2026-11-14 19:23:54',NULL);
CREATE UNIQUE INDEX "users_email_unique" on "users" ("email");
CREATE INDEX "users_account_type_index" on "users" ("account_type");
CREATE INDEX "users_is_active_index" on "users" ("is_active");
CREATE INDEX "sessions_user_id_index" on "sessions" ("user_id");
CREATE INDEX "sessions_last_activity_index" on "sessions" ("last_activity");
CREATE INDEX "cache_expiration_index" on "cache" ("expiration");
CREATE INDEX "cache_locks_expiration_index" on "cache_locks" ("expiration");
CREATE INDEX "jobs_queue_index" on "jobs" ("queue");
CREATE INDEX "failed_jobs_connection_queue_failed_at_index" on "failed_jobs" ("connection", "queue", "failed_at");
CREATE UNIQUE INDEX "failed_jobs_uuid_unique" on "failed_jobs" ("uuid");
CREATE UNIQUE INDEX "roles_name_unique" on "roles" ("name");
CREATE UNIQUE INDEX "roles_slug_unique" on "roles" ("slug");
CREATE UNIQUE INDEX "branches_slug_unique" on "branches" ("slug");
CREATE INDEX "branches_is_active_index" on "branches" ("is_active");
CREATE UNIQUE INDEX "branch_opening_hours_branch_id_day_of_week_unique" on "branch_opening_hours" ("branch_id", "day_of_week");
CREATE UNIQUE INDEX "services_slug_unique" on "services" ("slug");
CREATE INDEX "services_is_published_index" on "services" ("is_published");
CREATE INDEX "services_display_order_index" on "services" ("display_order");
CREATE UNIQUE INDEX "product_categories_slug_unique" on "product_categories" ("slug");
CREATE INDEX "product_categories_is_active_index" on "product_categories" ("is_active");
CREATE UNIQUE INDEX "products_slug_unique" on "products" ("slug");
CREATE INDEX "products_brand_index" on "products" ("brand");
CREATE INDEX "products_is_published_index" on "products" ("is_published");
CREATE INDEX "appointments_branch_id_status_confirmed_start_at_index" on "appointments" ("branch_id", "status", "confirmed_start_at");
CREATE UNIQUE INDEX "appointments_reference_code_unique" on "appointments" ("reference_code");
CREATE INDEX "appointments_customer_email_index" on "appointments" ("customer_email");
CREATE INDEX "appointments_customer_phone_index" on "appointments" ("customer_phone");
CREATE INDEX "appointments_requested_date_index" on "appointments" ("requested_date");
CREATE INDEX "appointments_status_index" on "appointments" ("status");
CREATE INDEX "enquiries_created_at_index" on "enquiries" ("created_at");
CREATE INDEX "enquiries_status_index" on "enquiries" ("status");
CREATE UNIQUE INDEX "article_categories_slug_unique" on "article_categories" ("slug");
CREATE UNIQUE INDEX "health_articles_slug_unique" on "health_articles" ("slug");
CREATE INDEX "health_articles_status_index" on "health_articles" ("status");
CREATE INDEX "health_articles_published_at_index" on "health_articles" ("published_at");
CREATE UNIQUE INDEX "business_settings_key_unique" on "business_settings" ("key");
CREATE INDEX "audit_logs_entity_type_entity_id_index" on "audit_logs" ("entity_type", "entity_id");
CREATE INDEX "audit_logs_action_index" on "audit_logs" ("action");
CREATE INDEX "audit_logs_created_at_index" on "audit_logs" ("created_at");
CREATE INDEX "notification_deliveries_notifiable_type_notifiable_id_index" on "notification_deliveries" ("notifiable_type", "notifiable_id");
CREATE INDEX "notification_deliveries_status_index" on "notification_deliveries" ("status");
CREATE INDEX "media_folder_index" on "media" ("folder");
CREATE UNIQUE INDEX "media_path_unique" on "media" ("path");
DELETE FROM "sqlite_sequence";
INSERT INTO "sqlite_sequence" VALUES('migrations',13);
INSERT INTO "sqlite_sequence" VALUES('roles',4);
INSERT INTO "sqlite_sequence" VALUES('users',5);
INSERT INTO "sqlite_sequence" VALUES('branches',2);
INSERT INTO "sqlite_sequence" VALUES('branch_opening_hours',14);
INSERT INTO "sqlite_sequence" VALUES('services',7);
INSERT INTO "sqlite_sequence" VALUES('product_categories',4);
INSERT INTO "sqlite_sequence" VALUES('products',13);
INSERT INTO "sqlite_sequence" VALUES('article_categories',2);
INSERT INTO "sqlite_sequence" VALUES('health_articles',5);
INSERT INTO "sqlite_sequence" VALUES('appointments',4);
INSERT INTO "sqlite_sequence" VALUES('appointment_status_histories',6);
INSERT INTO "sqlite_sequence" VALUES('enquiries',2);
PRAGMA foreign_keys=ON;
