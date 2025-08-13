# Project Management System - Database Design (ERD)

**Document Version:** 1.0  
**Date:** July 27, 2025  
**Prepared By:** Development Team  
**For:** Management Review  

---

## 📋 Executive Summary

This document presents the Entity Relationship Diagram (ERD) for a comprehensive Project Management System database. The design includes polymorphic relationships for flexible entity management, complete audit trails, and detailed financial reporting capabilities.

### Key Features:
- ✅ **Flexible Entity Management** - Polymorphic types and status for any entity
- ✅ **Complete Audit Trail** - Track all status changes with full history
- ✅ **Financial Reporting** - Planned vs Actual cost and cash flow tracking
- ✅ **Scalable Design** - Support for multiple companies, contracts, and users
- ✅ **Data Integrity** - Proper foreign key relationships and constraints

---

## 🏗️ Database Architecture Overview

### Core Tables:
1. **Companies** - Organization management
2. **Users** - System users and creators
3. **Contracts** - Contract management
4. **Entity_Types** - Polymorphic type definitions
5. **Entity_Status** - Polymorphic status management
6. **Projects** - Main project entities
7. **Project_Financial_Reports** - Financial tracking
8. **Status_Histories** - Audit trail for status changes

---

## 📊 Detailed Table Specifications

### 🏢 **COMPANIES**
| Column | Type | Constraint | Description |
|--------|------|------------|-------------|
| id | bigint | PK | Primary identifier |
| name | string | NOT NULL | Company name |
| created_at | timestamp | | Record creation time |
| updated_at | timestamp | | Last update time |

**Relationships:**
- **Has Many:** Projects (1:M via company_id)

---

### 👥 **USERS**
| Column | Type | Constraint | Description |
|--------|------|------------|-------------|
| id | bigint | PK | Primary identifier |
| name | string | NOT NULL | User full name |
| email | string | UNIQUE | User email address |
| created_at | timestamp | | Record creation time |
| updated_at | timestamp | | Last update time |

**Relationships:**
- **Has Many:** Projects as creator (1:M via created_by)
- **Has Many:** Financial Reports as creator (1:M via created_by)
- **Has Many:** Status Histories as changer (1:M via changed_by)

---

### 📄 **CONTRACTS**
| Column | Type | Constraint | Description |
|--------|------|------------|-------------|
| id | bigint | PK | Primary identifier |
| name | string | NOT NULL | Contract name/reference |
| created_at | timestamp | | Record creation time |
| updated_at | timestamp | | Last update time |

**Relationships:**
- **Has Many:** Projects (1:M via contract_id)

---

### 🏷️ **ENTITY_TYPES** (Polymorphic)
| Column | Type | Constraint | Description |
|--------|------|------------|-------------|
| id | bigint | PK | Primary identifier |
| typeable_id | string | | Polymorphic entity ID |
| typeable_type | string | | Polymorphic entity class |
| name | string | NOT NULL | Type name |
| description | text | NULLABLE | Type description |
| is_active | boolean | DEFAULT true | Active status |
| created_at | timestamp | | Record creation time |
| updated_at | timestamp | | Last update time |

**Relationships:**
- **Polymorphic:** Can be assigned to any entity type
- **Has Many:** Projects (1:M via project_type_id)

---

### 📊 **ENTITY_STATUS** (Polymorphic)
| Column | Type | Constraint | Description |
|--------|------|------------|-------------|
| id | bigint | PK | Primary identifier |
| statusable_id | string | | Polymorphic entity ID |
| statusable_type | string | | Polymorphic entity class |
| name | string | NOT NULL | Status name |
| description | text | NULLABLE | Status description |
| color | string | NULLABLE | UI color code |
| sort_order | int | | Status progression order |
| is_active | boolean | DEFAULT true | Active status |
| created_at | timestamp | | Record creation time |
| updated_at | timestamp | | Last update time |

**Relationships:**
- **Polymorphic:** Can be assigned to any entity type
- **Has Many:** Projects current status (1:M via current_status_id)
- **Has Many:** Status Histories from/to (1:M via from_status_id, to_status_id)

---

### 🏗️ **PROJECTS** (Main Entity)
| Column | Type | Constraint | Description |
|--------|------|------------|-------------|
| id | bigint | PK | Primary identifier |
| name | string | NOT NULL | Project name |
| description | text | NULLABLE | Project description |
| **company_id** | bigint | FK → Companies | Owner company |
| serial_number | string | UNIQUE per company | Project serial |
| **project_type_id** | bigint | FK → Entity_Types | Project type |
| **current_status_id** | bigint | FK → Entity_Status | Current status |
| start_date | date | NOT NULL | Project start date |
| end_date | date | NULLABLE | Project end date |
| **contract_id** | bigint | FK → Contracts | Related contract |
| budget_total | decimal | NULLABLE | Total budget |
| **created_by** | bigint | FK → Users | Creator user |
| created_at | timestamp | | Record creation time |
| updated_at | timestamp | | Last update time |

**Relationships:**
- **Belongs To:** Companies, Entity_Types, Entity_Status, Contracts, Users
- **Has Many:** Project_Financial_Reports (1:M)
- **Has Many:** Status_Histories (1:M polymorphic)

---

### 💰 **PROJECT_FINANCIAL_REPORTS**
| Column | Type | Constraint | Description |
|--------|------|------------|-------------|
| id | bigint | PK | Primary identifier |
| **project_id** | bigint | FK → Projects | Related project |
| report_date | date | NOT NULL | Report date |
| reporting_period | enum | daily,weekly,monthly,quarterly | Reporting frequency |
| cash_planned | decimal(15,2) | NOT NULL | Planned cash flow |
| cash_actual | decimal(15,2) | NOT NULL | Actual cash flow |
| cost_planned | decimal(15,2) | NOT NULL | Planned costs |
| cost_actual | decimal(15,2) | NOT NULL | Actual costs |
| variance_cash | decimal(15,2) | CALCULATED | Cash variance (actual-planned) |
| variance_cost | decimal(15,2) | CALCULATED | Cost variance (actual-planned) |
| notes | text | NULLABLE | Additional notes |
| **created_by** | bigint | FK → Users | Creator user |
| created_at | timestamp | | Record creation time |
| updated_at | timestamp | | Last update time |

**Relationships:**
- **Belongs To:** Projects (M:1)
- **Belongs To:** Users as creator (M:1)

---

### 📋 **STATUS_HISTORIES** (Polymorphic Audit Trail)
| Column | Type | Constraint | Description |
|--------|------|------------|-------------|
| id | bigint | PK | Primary identifier |
| historable_id | string | | Polymorphic entity ID |
| historable_type | string | | Polymorphic entity class |
| **from_status_id** | bigint | FK → Entity_Status, NULLABLE | Previous status |
| **to_status_id** | bigint | FK → Entity_Status | New status |
| **changed_by** | bigint | FK → Users | User who made change |
| change_reason | text | NULLABLE | Reason for change |
| changed_at | timestamp | NOT NULL | When change occurred |
| created_at | timestamp | | Record creation time |
| updated_at | timestamp | | Last update time |

**Relationships:**
- **Belongs To:** Entity_Status (from/to status)
- **Belongs To:** Users as changer (M:1)
- **Polymorphic:** Can track any entity type

---

## 🔄 **Relationship Flow Diagram**

```
┌─────────────┐    1:M    ┌──────────────┐    1:M    ┌─────────────────────────┐
│  COMPANIES  │ ────────▶ │   PROJECTS   │ ────────▶ │ PROJECT_FINANCIAL_REPORTS│
└─────────────┘           └──────────────┘           └─────────────────────────┘
                                │
                               1:M
                                │
                                ▼
                          ┌──────────────┐
                          │STATUS_HISTORIES│ (Polymorphic)
                          └──────────────┘
                                │
                               M:1
                                │
                                ▼
┌─────────────┐           ┌──────────────┐
│    USERS    │ ◀─────────┤ENTITY_STATUS │
└─────────────┘    M:1    └──────────────┘
     │                           │
    1:M                         1:M
     │                           │
     ▼                           ▼
┌─────────────┐           ┌──────────────┐
│ CONTRACTS   │ ────────▶ │ENTITY_TYPES  │ (Polymorphic)
└─────────────┘    1:M    └──────────────┘
```

---

## 🎯 **Business Benefits**

### 1. **Comprehensive Project Tracking**
- Full project lifecycle management from start to finish
- Multiple project types support via polymorphic entity types
- Real-time status tracking with complete audit history

### 2. **Financial Control & Reporting**
- Planned vs Actual cost and cash flow comparison
- Automatic variance calculations
- Flexible reporting periods (daily, weekly, monthly, quarterly)
- Budget tracking and control

### 3. **Audit & Compliance**
- Complete status change history with user attribution
- Timestamped audit trail for all critical changes
- Change reason tracking for compliance requirements

### 4. **Scalability & Flexibility**
- Polymorphic design allows extension to other entity types
- Multi-company support
- Configurable status workflows
- Extensible type system

---

## 🚀 **Implementation Recommendations**

### Phase 1: Core Tables
1. Create base tables (Companies, Users, Contracts)
2. Implement Entity_Types and Entity_Status polymorphic tables
3. Create Projects table with basic relationships

### Phase 2: Financial Tracking
1. Implement Project_Financial_Reports
2. Add calculated fields for variance tracking
3. Create reporting interfaces

### Phase 3: Audit & History
1. Implement Status_Histories polymorphic tracking
2. Add audit triggers and automated logging
3. Create history reporting interfaces

### Phase 4: Advanced Features
1. Add polymorphic support for additional entity types
2. Implement advanced reporting and analytics
3. Add workflow automation

---

## 📝 **Technical Notes**

- **Database Engine:** MySQL/PostgreSQL compatible
- **Primary Keys:** Auto-incrementing BIGINT for scalability
- **Polymorphic Relations:** Uses standard Laravel polymorphic conventions
- **Decimal Precision:** 15 digits with 2 decimal places for financial fields
- **Indexing:** Recommended indexes on all foreign keys and polymorphic columns
- **Constraints:** Foreign key constraints ensure data integrity

---

**Document Status:** ✅ Ready for Implementation  
**Next Steps:** Approve design → Create migrations → Implement models → Build APIs

---

*This document provides a complete database design for the Project Management System. For technical implementation details or modifications, please consult with the development team.*
