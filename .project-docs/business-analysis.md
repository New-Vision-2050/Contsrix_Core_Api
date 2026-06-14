# Contsrix_Core_Api — Business Analysis

## 1. Domain Overview

Contsrix is an **enterprise SaaS platform** serving companies (tenants) with a suite of business management tools. The platform is multi-tenant, meaning each company gets its own isolated data environment.

### Core Business Domains

| Domain | Modules | Purpose |
|---|---|---|
| **Company Management** | Company, CompanyUser, ManagementHierarchy | Tenant onboarding, company profiles, org charts |
| **HR & Workforce** | Attendance, Leave, User, UserInfo, JobTitle, MedicalInsurance | Employee management, attendance tracking, leave, insurance |
| **E-Commerce** | Ecommerce (29 sub-modules) | Multi-vendor marketplace: products, shops, orders, payments, coupons |
| **Project Management** | Project, Program | Project tracking, task management, term services |
| **Document Management** | ArchiveLibrary, DocumentType | File/folder archives, document type classification |
| **CMS / Website** | WebsiteCMS (18 sub-modules), PageBuilder | Website builder, CMS pages, news, themes |
| **Subscriptions & Billing** | Subscription | Packages, company access programs |
| **Settings & Configuration** | Setting, ProcedureSetting, TermServiceSetting | Global and per-tenant configuration |
| **RBAC & Security** | RoleAndPermission, Auth, Audit, ActivityLog | Roles, permissions, JWT auth, audit trails |
| **Reporting** | Reports, ProdactInfo | Wizard-driven reports, product exports |
| **Communication** | ClientRequest, AdminRequest, NotificationSettings | Client requests, admin actions, push notifications |

## 2. User Roles & Hierarchy

### System-Level Roles (Spatie RBAC)
- **Super Admin** — Full system access across all tenants
- **Admin** — Administrative access within scope
- **Company-specific roles** — Defined per tenant

### Company User Roles (CompanyUserRole enum)
- **Broker** — Agent/intermediary with company access
- **Client** — End customer/client of the company
- Additional roles defined in `CompanyUser\Enum\CompanyUserRole`

### Management Hierarchy
- Tree-based organizational structure (`ManagementHierarchy` module)
- Uses `nevadskiy/laravel-tree` for tree operations
- Users assigned to hierarchy nodes
- Access control based on hierarchy position

## 3. Key Business Flows

### 3.1 Tenant Onboarding
1. Company registered via CompanyCore module (creates Company = tenant)
2. Domain assigned to company
3. Company fields configured (CompanyField)
4. Company type & registration type set
5. Management hierarchy created
6. Users added and assigned to roles & hierarchy nodes
7. Subscription package assigned (CompanyAccessProgram)

### 3.2 Authentication Flow
1. User submits email/phone → system validates company/tenant
2. Login ways configured per user (password, OTP, security questions)
3. Multi-step login: identifier → OTP verification → second factor (password or questions)
4. JWT token issued on successful auth
5. Admin impersonation supported (`login-as-admin`)

### 3.3 Attendance & Leave
1. Employee clocks in/out (Attendance module)
2. Attendance records tracked with location data
3. Leave requests submitted (Leave module)
4. Leave policies per company define accrual rules
5. Public holidays configured per country/company
6. Reports module generates attendance summaries, salary deductions

### 3.4 E-Commerce
1. Shops registered (EcoShop) with addresses (EcoShopAddress)
2. Products created (EcoProduct) with categories (EcoCategory), brands (EcoBrand)
3. Products listed with pricing, discounts (EcoDiscount), installments (EcoInstallment)
4. Clients browse and place orders (Order, EcoClient)
5. Payments processed (EcoPayment, PaymentMethod)
6. Coupons, deals (DealDay, FeatureDeal, FlashDeal), and banners managed
7. Complaints handled (EcoComplaint)
8. Warehouse management (Warehous)

### 3.5 Document Archives
1. Folders created in archive (ArchiveLibrary/Folder)
2. Files uploaded to folders (ArchiveLibrary/File)
3. File permissions managed per user/role
4. Media library integration for file handling

### 3.6 Website CMS
1. Website themes selected and configured (WebsiteTheme, WebsiteThemeSetting)
2. Home page built (WebsiteHomePage, WebsiteHomePageSetting)
3. Content pages created (PageBuilder)
4. News, projects, services published
5. Contact info and social media links configured
6. About us, terms & conditions managed

## 4. Multi-Tenant Data Model

### Tenant = Company
- Each `Company` record is a stancl/tenancy tenant
- Tenant identified via request data (`InitializeTenancyByRequestData`)
- Bootstrappers disabled (manual scoping) — database queries manually scoped to tenant
- User can belong to multiple companies (via `CompanyUserCompany` pivot)
- Login requires tenant context (user selects company at login)

### Cross-Tenant Features
- Central admin can view/manage all tenants
- Shared reference data (countries, currencies, languages) lives in Shared module
- Some settings are global (central DB) vs tenant-specific

## 5. Key Business Rules

- Users have UUID primary keys
- Companies have UUID primary keys (also tenant IDs)
- Soft deletes used for recoverable deletions
- Full audit trail on auditable models
- Activity logging for user actions
- File permissions controlled via observers
- Management hierarchy determines data access scope
- Export functionality available for lists (Excel via maatwebsite/excel)

## 6. Integrations

| Integration | Module | Purpose |
|---|---|---|
| Firebase Cloud Messaging | NotificationSettings | Push notifications to mobile |
| RabbitMQ | (whole app) | Async job processing |
| S3 / Cloud Storage | (MediaLibrary) | File storage |
| SMS Gateway | SmsChannel | SMS notifications |
| Email | MailClass | Dynamic email config per tenant |
| Laravel Reverb | (Broadcasting) | Real-time WebSocket events |
| OpenAI / LLM | OpenRouter (Shared) | AI integration placeholder |
