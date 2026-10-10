# SETTLEXPERT CRM — MASTER TECHNICAL BLUEPRINT & SYSTEM SPECIFICATION

> **Document Type:** Reverse-Engineered Master System Specification & Technical Data Dictionary  
> **Source Project:** SettleXpert CRM (settl-expert-admin v1.0.0)  
> **Inspection Date:** October 2026  
> **Classification:** Confidential / Internal Engineering Blueprint  

---

## 01 — CRM Overview

**SettleXpert CRM** is an end-to-end debt settlement, creditor harassment mitigation, legal defense, and installment fee collections platform. The system coordinates the entire client lifecycle across four core functional tiers:
1. **Sales & Telecalling (Employee CRM):** Inbound/outbound lead acquisition, debt qualification, follow-ups, and conversion.
2. **Operations & Case Management (Manager Center):** Lead quota distribution, employee calling audits, payment verification approvals, and escalation triage.
3. **Legal Advocacy (Advocate Portal):** Legal representation, anti-harassment legal notices, Section 138 NI Act counter-replies, Demand Notices (`DN-XXXX`), and Speed Post dispatch tracking.
4. **Corporate Governance & Finance (Super Admin):** Staff directory, Aadhaar KYC verification, department/team hierarchy, fee plans, collections ledger, and security audit trails.

### Core Business Flow
```
LEAD (Inquiry) 
  ↓ [Calling / Qualification / Follow-ups]
CONVERTED CLIENT (360° Profile & Creditor Portfolio)
  ↓ [Annexure A Lenders & Fee Schedule]
CONSULTANCY AGREEMENT (Contract Execution: AGR-XXXX)
  ↓ [Installment Plan & Payment Proof Submission]
PENDING VERIFICATION (Manager Review Queue)
  ↓ [Verify & Approve]
VERIFIED PAYMENT & ACTIVE LEGAL DEFENSE
  ↓ [Anti-Harassment Notices & Settlement Negotiations]
CASE CLOSURE & NO DUE CERTIFICATE (NDC)
```

---

## 02 — Technology Stack

- **Frontend Core:** React 19.2.8, React DOM 19.2.8, TypeScript 7.0.2, Vite 8.2.2.
- **Styling & UI:** Tailwind CSS 3.4.17, PostCSS 8.5.26, Autoprefixer 10.5.4, Lucide React (v1.33.0 icons), `clsx`, `tailwind-merge`.
- **Backend Core:** Node.js (CommonJS), Express.js 5.2.1, Morgan logger, CORS, dotenv.
- **Database Architecture (Dual Engine):**
  - *Local / Low Latency:* SQLite 3 via `better-sqlite3` v13.0.3 (`server/data/settlexpert.db`).
  - *Cloud Production:* Supabase PostgreSQL via `@supabase/supabase-js` v2.112.4.
  - *Synchronization:* Two-way sync queue (`server/db/syncQueue.js`), schema deployer (`deploy_schema.js`), pull/push scripts.
- **Authentication & Security:** JSON Web Tokens (`jsonwebtoken` v9.0.3), password hashing via `bcryptjs` v3.0.3, account lockout thresholds (5 failed attempts), IP and User-Agent capture in immutable audit tables.
- **Data Processing:** `xlsx` v0.18.5 (Excel parsing and generation), `uuid` v14.0.2 (ID generation).

---

## 03 — Complete Module List

1. **Authentication & Session Security** (`/login`, `/me`)
2. **Executive Admin Dashboard** (`/dashboard`)
3. **Staff Directory & KYC Management** (`/organization/staff-directory`)
4. **Managers Management** (`/organization/managers`)
5. **Employees Management** (`/organization/employees`)
6. **Departments Management** (`/organization/departments`)
7. **Teams Management** (`/organization/teams`)
8. **Manager Types & Classifications** (`/organization/manager-types`)
9. **Roles & Granular Permissions Matrix** (`/organization/roles-permissions`)
10. **Leads Pipeline & Calling Desk** (`/crm/leads`)
11. **Lead Import & Smart Distribution Engine** (`/crm/lead-import-distribution`)
12. **Clients Directory & Master Ledger** (`/crm/clients`)
13. **Client 360° Unified Workspace (19 Integrated Sub-Tabs)**
14. **Client Onboarding Form Engine** (`/crm/onboarding-form`)
15. **Agreements & Contracts Engine** (`/crm/agreements`)
16. **Advocates Directory & Empanelment** (`/advocates`)
17. **Advocate Legal Portal & Case Management** (`/advocate-portal`)
18. **Legal Notices Engine (Anti-Harassment & Sec 138)** (`/advocate-portal/legal-notices`)
19. **Demand Notices Engine** (`/advocate-portal/demand-notices`)
20. **Finance & Payment Verification Desk** (`/finance/payments`)
21. **Master Fee Plans** (`/finance/fee-plans`)
22. **Operations & Cross-Functional Tasks** (`/operations/tasks`)
23. **Follow-Ups Master Calendar** (`/operations/follow-ups`)
24. **Document Repository Locker** (`/operations/documents`)
25. **Escalations & Grievance Desk** (`/operations/escalations`)
26. **Reports & Executive Analytics** (`/reports`)
27. **Security & Audit Logs Trail** (`/audit`)
28. **Admin Platform Settings** (`/settings`)

---

## 04 — Complete Page & Screen Inventory

| Screen Name | File Component Path | Primary URL / Navigation Key | Primary Roles |
|---|---|---|---|
| **Login Screen** | `src/components/auth/LoginView.tsx` | `/` (Unauthenticated) | All Users |
| **Admin Master Dashboard** | `src/components/dashboard/AdminDashboard.tsx` | `dashboard` | Admin |
| **Staff Directory & KYC** | `src/components/staff/StaffDirectory.tsx` | `staff-directory` | Admin |
| **Managers Management** | `src/components/organization/ManagersView.tsx` | `managers` | Admin |
| **Employees Management** | `src/components/organization/EmployeesView.tsx` | `employees` | Admin |
| **Departments** | `src/components/organization/DepartmentsView.tsx` | `departments` | Admin |
| **Teams** | `src/components/organization/TeamsView.tsx` | `teams` | Admin |
| **Manager Types** | `src/components/organization/ManagerTypesView.tsx` | `manager-types` | Admin |
| **Roles & Permissions** | `src/components/organization/RolesPermissionsView.tsx` | `roles-permissions` | Admin |
| **Leads Pipeline** | `src/components/crm/LeadsView.tsx` | `leads` | Admin, Manager |
| **Lead Import & Distribution** | `src/components/leads/LeadImportDistributionView.tsx` | `lead-import-distribution` | Admin, Manager |
| **Clients Master List** | `src/components/crm/ClientsView.tsx` | `clients` | Admin |
| **Client 360° Profile** | `src/components/crm/ClientDetailsUnifiedView.tsx` | (Sub-view / Modal) | Admin, Manager, Employee |
| **Client Onboarding Form** | `src/components/crm/ClientOnboardingMainView.tsx` | `onboarding-form` | Admin, Employee |
| **Agreements Master List** | `src/components/crm/AgreementsView.tsx` | `agreements` | Admin |
| **Printable Legal Agreement** | `src/components/employee/AgreementDocumentView.tsx`| (Document Preview Mode) | All Roles |
| **Advocates Directory** | `src/components/advocates/AdvocatesView.tsx` | `advocates` | Admin |
| **Payments Desk** | `src/components/finance/PaymentsView.tsx` | `payments` | Admin, Finance |
| **Fee Plans** | `src/components/finance/FeePlansView.tsx` | `fee-plans` | Admin |
| **Operations (Tasks/Docs)** | `src/components/operations/OperationsView.tsx` | `tasks`, `follow-ups`, `documents`, `escalations` | Admin |
| **Reports & Analytics** | `src/components/reports/ReportsView.tsx` | `reports` | Admin |
| **Security Audit Logs** | `src/components/audit/AuditLogsView.tsx` | `audit` | Admin |
| **Platform Settings** | `src/components/settings/AdminSettingsView.tsx` | `settings` | Admin |
| **Manager Workspace** | `src/components/manager/ManagerWorkspace.tsx` | (Dedicated Manager Root) | Manager |
| **Manager Dashboard** | `src/components/manager/ManagerDashboardView.tsx` | `dashboard` | Manager |
| **Manager Team View** | `src/components/manager/ManagerTeamView.tsx` | `team` | Manager |
| **Manager Leads View** | `src/components/manager/ManagerLeadsView.tsx` | `leads` | Manager |
| **Manager Clients View** | `src/components/manager/ManagerClientsView.tsx` | `clients` | Manager |
| **Manager Verification Queue**| `src/components/manager/ManagerPaymentsVerificationView.tsx` | `verification` | Manager |
| **Manager Payment Management**| `src/components/manager/ManagerPaymentManagementView.tsx` | `payments` | Manager |
| **Manager Agreements View** | `src/components/manager/ManagerAgreementsView.tsx` | `agreements` | Manager |
| **Manager Follow-Ups View** | `src/components/manager/ManagerFollowUpsView.tsx` | `follow-ups` | Manager |
| **Manager Tasks View** | `src/components/manager/ManagerTasksView.tsx` | `tasks` | Manager |
| **Manager Advocates View** | `src/components/manager/ManagerAdvocatesView.tsx` | `advocates` | Manager |
| **Manager Activity Trail** | `src/components/manager/ManagerActivityView.tsx` | `activity` | Manager |
| **Employee Workspace** | `src/components/employee/EmployeeWorkspace.tsx` | (Dedicated Employee Root) | Employee |
| **Employee Dashboard** | `src/components/employee/EmployeeDashboardView.tsx` | `dashboard` | Employee |
| **Employee Leads Desk** | `src/components/employee/EmployeeLeadsView.tsx` | `leads` | Employee |
| **Employee Clients Desk** | `src/components/employee/EmployeeClientsView.tsx` | `clients` | Employee |
| **Employee Agreements Desk**| `src/components/employee/EmployeeAgreementsView.tsx` | `agreements` | Employee |
| **Advocate Workspace** | `src/components/advocate/AdvocateWorkspace.tsx` | (Dedicated Advocate Root) | Advocate |
| **Advocate Dashboard** | `src/components/advocate/AdvocateDashboardView.tsx` | `dashboard` | Advocate |
| **Advocate Cases Directory**| `src/components/advocate/AdvocateCasesView.tsx` | `cases` | Advocate |
| **Advocate Legal Notices** | `src/components/advocate/AdvocateLegalNoticesView.tsx` | `legal_notices` | Advocate |
| **Legal Notice Document** | `src/components/advocate/LegalNoticeDocumentView.tsx` | (Printable Notice Mode) | Advocate |
| **Advocate Demand Notices** | `src/components/advocate/AdvocateDemandNoticesView.tsx` | `demand_notices` | Advocate |
| **Demand Notice Document** | `src/components/advocate/DemandNoticeDocumentView.tsx` | (Printable Demand Mode) | Advocate |
| **Advocate Tasks** | `src/components/advocate/AdvocateTasksView.tsx` | `tasks` | Advocate |

---

## 05 — Role-Wise Complete Page Inventory

### 1. Super Admin Role
- `dashboard` (Executive KPIs, revenue stats, active pipeline, advocate summaries)
- `staff-directory` (All staff, role filters, Aadhaar KYC verification modal)
- `managers` (Manager roster, department heads, manager types, assign team)
- `employees` (Consultants/telecallers, reporting managers, passwords)
- `departments` (Department codes, head manager, team counts)
- `teams` (Team lead assignment, employee roster)
- `manager-types` (Sales, Legal, Operations, Finance permissions)
- `roles-permissions` (Fine-grained capability matrix)
- `leads` (Full company leads pipeline, bulk actions, reassignment)
- `lead-import-distribution` (Excel/CSV upload, deduplication, auto round-robin)
- `clients` (Full client directory, 360 unified view, status toggles)
- `agreements` (Company-wide contracts, execution tracking)
- `onboarding-form` (Full multi-step customer onboarding)
- `advocates` (Bar Council roster, active caseloads)
- `payments` (All payments, Verification queue, Collections ledger)
- `fee-plans` (Master pricing tiers and installment definitions)
- `tasks`, `follow-ups`, `documents`, `escalations` (Central Operations)
- `reports` (Performance, targets, collections)
- `audit` (Immutable system logs)
- `settings` (Company info, security lockout parameters, webhooks)
- *1-Click Workspace Preview Mode:* Can preview Manager, Employee, and Advocate workspaces at will via header buttons.

### 2. Manager Role
- `dashboard` (Team KPI cards, calling metrics, team target progress)
- `team` (Assigned consultants, active status, reset employee password)
- `leads` (Team lead pool, status filters, manual reassignment to agents)
- `lead-import-distribution` (Import and distribute leads to team members)
- `clients` (Team client directory, 360 access, advocate assignment)
- `onboarding-form` (Onboard customer directly)
- `agreements` (Agreements generated by team consultants)
- `verification` (Audit employee submitted payments, Verify & Approve, Reject with mandatory reason)
- `payments` (Team monthly collection schedule and client EMI tracker)
- `follow-ups` (Calendar and list of team scheduled callbacks)
- `tasks` (Assign operational tasks to consultants)
- `advocates` (View assigned legal counsel per client)
- `activity` (Audit trail of team actions)

### 3. Employee (Consultant / Telecaller) Role
- `dashboard` (Personal lead pipeline counters, conversion rate, monthly collections)
- `leads` (Personal assigned leads, calling statuses, 18-field edit lead, follow-up drawer, convert lead)
- `onboarding-form` (Multi-section onboarding form)
- `clients` (Personal converted clients, 360 profile, creditor lenders management)
- `agreements` (Agreement creation, loan details annexure, legal template generator)
- *Payments Tab (Inside Client 360):* "Submit Payment" action (Amount > 0, Payment Date, Mode, UTR, mandatory Screenshot Proof). Cannot self-verify.

### 4. Advocate (Legal Counsel) Role
- `dashboard` (Assigned active cases, closed cases, total debt under protection)
- `cases` (Client debt summary, creditor loan accounts, legal task logging)
- `legal_notices` (Draft, Dispatched, Delivered notices; Speed Post tracking; printable advocate letterhead notices)
- `demand_notices` (Formal counter-demand notices to aggressive collection agencies)
- `tasks` (Court hearing dates, reply filing deadlines, police station representation)

---

## 06 — Permission Matrix

| Module | Super Admin | Manager | Employee | Advocate |
|---|---|---|---|---|
| **Users / Staff** | View, Create, Edit, Delete | View Team | View Self | View Self |
| **KYC Verification** | View, Approve, Reject | View | None | View Self |
| **Leads** | View All, Create, Edit, Delete, Assign | View Team, Assign | View Assigned, Create, Edit | None |
| **Lead Conversion** | Execute | Execute | Execute | None |
| **Clients** | View All, Edit, Delete, Assign | View Team, Assign Adv | View Assigned, Edit | View Assigned Cases |
| **Lenders / Creditors**| View, Add, Delete | View, Add, Delete | View, Add, Delete | View |
| **Agreements** | View All, Generate, Delete | View Team, Generate | View Assigned, Generate | View Linked |
| **Payment Submission**| Submit | Submit | Submit (Pending Only) | None |
| **Payment Verification**| Approve, Reject | Approve, Reject | **FORBIDDEN (403)** | None |
| **Legal Notices** | View All | View | None | Draft, Dispatch, Print |
| **Fee Plans** | Create, Edit, Toggle | View | View | None |
| **Audit Logs** | View All, Filter | View Team Activity | None | None |
| **Settings** | Full Access | None | None | None |

---

## 07 — Lead Module Specification

### 1. Data Fields

| Field Name | Display Label | Data Type | Required | Default Value | Notes |
|---|---|---|---|---|---|
| `id` | Internal ID | String (UUID) | Yes | Auto `lead-xxxxxxxx` | Primary Key |
| `lead_number` | Lead # | String | Yes | Auto `LEAD-0001` | Sequential Unique Code |
| `name` | Full Name | String | Yes | — | Customer Name |
| `phone` | Phone Number | String | Yes | — | 10-digit, deduplicated |
| `email` | Email Address | String | No | null | Deduplicated (NOCASE) |
| `city` | City | String | No | null | Client City |
| `state` | State | String | No | null | Client State |
| `address` | Full Address | String | No | null | Street address |
| `total_debt` | Total Debt | Numeric | No | 0.00 | Total loan/card dues |
| `monthly_income` | Monthly Income | Numeric | No | 0.00 | Net monthly income |
| `service_needed` | Service Needed | String | No | 'Debt Settlement'| e.g. Personal Loan, Card |
| `paying_emis` | Paying EMIs | Enum/String | No | 'Yes' | 'Yes', 'No', 'Irregular' |
| `harassment_calls`| Harassment Calls| Enum/String | No | 'No' | 'No', 'Mild', 'Severe' |
| `employment_status`| Employment Status| String | No | 'Employed' | 'Employed', 'Self-Employed'|
| `employment_type` | Employment Type | String | No | 'Salaried' | 'Salaried', 'Business' |
| `settlement_needed`| Settlement Needed| String | No | 'Yes' | 'Yes', 'No' |
| `consultation_timing`| Consultation Time| String | No | '10:00 AM - 12:00 PM' | Slot preferences |
| `credit_card_dues`| Credit Card Dues| Numeric | No | 0.00 | Breakdown |
| `personal_loan_dues`| Personal Loan Dues| Numeric| No | 0.00 | Breakdown |
| `service_fee` | Service Fee | Numeric | No | 0.00 | Estimated SX fee |
| `bank_name` | Bank / Creditor | String | No | null | Primary lender |
| `status` | Lead Status | Enum | Yes | 'new' | Pipeline status |
| `employee_id` | Assigned Agent | String (FK) | No | Logged in user | References `users.id` |
| `manager_id` | Assigned Manager| String (FK) | No | Agent's manager| References `users.id` |
| `created_by` | Creator Name | String | No | Logged in name | Audit field |
| `created_at` | Created At | Timestamp | Yes | CURRENT_TIMESTAMP| Filter anchor |
| `updated_at` | Updated At | Timestamp | Yes | CURRENT_TIMESTAMP| Last activity |

### 2. Lead Status Values
- `all`: Meta-tab representing all non-converted, non-deleted leads.
- `new`: Freshly imported or uncontacted lead.
- `connected`: Telecaller initiated call and customer answered.
- `contacted`: Initial discussion regarding debt relief completed.
- `interested`: Customer confirmed interest in debt settlement terms.
- `follow_up`: Scheduled callback booked for a specific date and time.
- `converted`: Successfully enrolled and transitioned to a Client record.
- `not_interested` / `dropped`: Customer declined services or loans ineligible.
- `deleted`: Soft-deleted record.

### 3. Follow-Up Action Drawer
- **Past Follow-Up History Table:** Displays historical interactions with `Date & Time`, `Agent Name`, `Call Status`, `Interest Level`, `Remarks`.
- **New Follow-Up Form:**
  - `Call Status` Dropdown: `Connected`, `Not Reachable`, `Busy`, `Switch Off`, `Wrong Number`.
  - `Interest Level` Dropdown: `High`, `Medium`, `Low`.
  - `Final Status` Dropdown: `Interested`, `Follow_up`, `Converted`, `Dropped`.
  - `Next Follow-Up Date & Time`: Integrated Date & Time Picker.
  - `Call Remark`: Textarea for detailed call synopsis.

---

## 08 — Lead to Client Conversion Engine

The conversion from a Lead to a Client is a traceable transaction that guarantees no data re-entry and prevents duplicate records.

### Conversion Logic & Field Mapping

| Lead Source Field | Client Target Field | Transformation / Logic |
|---|---|---|
| `lead.id` | `client.lead_id` | Preserved as Foreign Key link |
| Auto-generated | `client.client_number` | Sequential `CL-0001`, `CL-0002`... |
| `lead.name` | `client.name` | Direct copy |
| `lead.phone` | `client.phone` | Direct copy (Unique index) |
| `lead.email` | `client.email` | Direct copy |
| `lead.city` | `client.city` | Direct copy |
| `lead.address` | `client.address` | Direct copy |
| `lead.state` | `client.state` | Direct copy |
| `lead.employment_status` | `client.employment_status` | Direct copy |
| `lead.employment_type` | `client.employment_type` | Direct copy |
| `lead.total_debt` | `client.total_debt` | Direct copy |
| `lead.monthly_income` | `client.monthly_income` | Direct copy |
| `lead.credit_card_dues` | `client.credit_card_dues` | Direct copy |
| `lead.personal_loan_dues`| `client.personal_loan_dues`| Direct copy |
| `lead.bank_name` / `service`| `client.loan_type` | `Credit Card & Loans (${bank_name})` |
| `lead.paying_emis` | `client.paying_emis` | Direct copy |
| `lead.harassment_calls` | `client.harassment_calls` | Direct copy |
| `lead.settlement_needed` | `client.settlement_needed` | Direct copy |
| `lead.consultation_timing`| `client.consultation_timing`| Direct copy |
| `lead.total_debt` | `client.settlement_target` | Auto-calculated: `ROUND(total_debt * 0.45)` (45% target) |
| `lead.service_fee` | `client.sx_fee` | Auto-populated: `lead.service_fee || 25000` |
| Execution Date | `client.fees_date` | `CURRENT_DATE` |
| Initial State | `client.fees_status` | `'Pending'` |
| Initial State | `client.total_received` | `0.00` |
| Initial State | `client.pending_amount` | Equals `client.sx_fee` |
| Initial State | `client.status` | `'active'` |
| Initial State | `client.case_status` | `'active'` |
| `lead.employee_id` | `client.employee_id` | Direct ownership transfer |
| `lead.manager_id` | `client.manager_id` | Direct hierarchy transfer |

### Automated Secondary Operations on Conversion:
1. **Initial Creditor Creation:** If `lead.bank_name` is present, a record is automatically inserted into `lenders`:
   - `bank_name = lead.bank_name`
   - `loan_type = 'Credit Card / Personal Loan'`
   - `balance = lead.total_debt`
   - `status = 'Defaulted'`
2. **Consultancy Agreement Creation:** Automatically executes `ensureAgreementForClient()`:
   - Generates sequential `AGR-XXXX`.
   - Links `client_id`.
   - Populates Annexure A with the initial bank lender.
   - Populates Annexure B with total consultancy fee and 6-month monthly installment breakdown (`ROUND(totalFee / 6)`).
3. **Status Update:** `UPDATE leads SET status = 'converted', updated_at = CURRENT_TIMESTAMP WHERE id = ?`.
4. **Duplicate Prevention:** If a client already exists with `lead_id = lead.id` or `phone = lead.phone`, the system does NOT create a duplicate record; it re-links the existing client.

---

## 09 — Client 360° Unified Workspace (All 19 Sub-Tabs)

When opening a client record in `ClientDetailsUnifiedView.tsx`, the following 19 sub-tabs are rendered:

### Tab 1: `onboarding-form` (Client Onboarding Form)
- **Purpose:** Full digital onboarding form containing KYC documents, proof of income, household budget, creditor loan list, and legal consent checkboxes.

### Tab 2: `info` (Profile & Debt Overview)
- **Purpose:** Client summary master card. Displays contact particulars, total enrolled debt, 45% settlement target, Sx service fee, assigned consultant, and quick-update Case Status dropdown (`active`, `closed`, `on_hold`, `dropped`).

### Tab 3: `lenders` (Creditors & Loan Accounts)
- **Purpose:** Bank-by-bank ledger of unsecured debts.
- **Table Columns:** `Bank Name`, `Loan Type` (Personal Loan / Credit Card), `Balance (₹)`, `Default Date`, `Status` (Defaulted / NPA), `Statement Link`.
- **Actions:** `+ Add More` modal (Bank Name, Type, Balance), `Remove` (delete lender with confirmation dialog).

### Tab 4: `income-emi` (Financial Assessment)
- **Purpose:** Affordability index calculation.
- **Fields:** Net Monthly Income vs Fixed Expenses (`House Rent`, `Groceries`, `School Fees`, `Utilities`, `Medical`).
- **Calculation:** Auto-computes **Net Disposable Surplus** available for the monthly settlement escrow pool.

### Tab 5: `emi` (Monthly EMI Schedule Tracker)
- **Purpose:** Month-by-month installment schedule from Month 1 to Month 12+.
- **Columns:** `Month #`, `Due Date`, `Expected Amount`, `Received Amount`, `Balance`, `Status` (`Paid`, `Pending`, `Overdue`).

### Tab 6: `bank` (Bank & NACH / Auto-Debit Controls)
- **Purpose:** Client bank account details (`Bank Name`, `A/C Number`, `IFSC Code`, `Branch`).
- **Tool:** **NACH / E-Mandate Disablement Advisory** toggle to guide the client on cancelling auto-debits to eliminate bank bounce penalty charges.

### Tab 7: `documents` (Client Document Locker)
- **Purpose:** Secure digital file storage.
- **Categories:** Aadhaar Front/Back, PAN Card, Last 6 Months Bank Statements, Loan Sanction Letters, Creditor Demand Notices.
- **Actions:** Drag-and-drop file upload, document preview modal, download, delete.

### Tab 8: `boarding-call` (Welcome / Boarding Call Verification)
- **Purpose:** Operational QA check.
- **Form:** Checklist confirming client was briefed on settlement duration, creditor harassment response protocol, and fee payment rules. Uploads call audio recording and consultant signoff notes.

### Tab 9: `conversation` (Communication Logs)
- **Purpose:** Interactive timestamped activity thread.
- **Fields:** Category selector (`Call`, `WhatsApp`, `Email`, `Internal Staff Note`), free-text note area, `Add Note` button. Full conversational audit trail.

### Tab 10: `client-action` (Action Triggers & Follow-up Scheduling)
- **Purpose:** Cross-departmental task assignment.
- **Fields:** `Next Call Date & Time`, `Assign Task To` (Legal Advocate / Accounts / Manager), `Priority` (Urgent, High, Normal), `Action Instructions`.

### Tab 11: `case-closure` (Case Closure & NDC)
- **Purpose:** Final settlement milestone desk.
- **Actions:** Upload Bank Settlement Letter, upload official Bank No Due Certificate (NDC), record final fee clearance, and update master client status to `closed`.

### Tab 12: `client-attribute` (Risk & Severity Attributes)
- **Purpose:** Severity tags: `Severe Harassment`, `Police Complaint Threat`, `High Net-Worth`, `Arbitration Notice Issued`, `Cooperative`.

### Tab 13: `notices` (Bank Legal Notices)
- **Purpose:** Central registry of legal notices received by the client from banks: Section 138 NI Act, Section 25 Payment & Settlement Act, Arbitration summons, Lok Adalat notices.

### Tab 14: `sales-handover` (Consultant Handover)
- **Purpose:** Handover protocol from Sales Telecaller to Operations Team. Captures special client commitments, emotional state, payment frequency promises, and handover signoff.

### Tab 15: `payment-history` (Receipts & Payment Ledger)
- **Purpose:** Complete canonical transaction history for this client.
- **Table Columns:** `Receipt #`, `Payment Date`, `Amount (₹)`, `Payment Mode`, `Transaction / UTR ID`, `Proof Document Link`, `Status Badge` (Pending Verification / Verified / Rejected), `Verified / Rejected By`, `Rejection Reason`.
- **Action:** `+ Submit Payment` button (opens manual payment submission modal).

### Tab 16: `escalation-form` (Harassment & Grievance Escalation)
- **Purpose:** Urgent ticket submission to Manager or Legal Lead.
- **Fields:** `Subject`, `Escalation Category` (Recovery agent home visit, third-party abusive calls, employer contact), `Urgency` (Critical, High), `Incident Description`.

### Tab 17: `financial-form` (Distress Balance Sheet)
- **Purpose:** Complete debt-to-income (DTI) evaluation, total liquid assets vs unsecured debt liabilities, affordability score.

### Tab 18: `sales-feedback-form` (CSAT & Quality Check)
- **Purpose:** 5-star rating matrix on sales transparency, fee clarity, consultant professionalism, and client comments.

### Tab 19: `history` (Audit Trail Timeline)
- **Purpose:** Full chronological audit log of all status transitions, field edits, advocate assignments, and payment verifications for this specific client profile.

---

## 10 — Agreements Module & Legal Contract Specification

### Agreement Form Sections (`EmployeeAgreementsView.tsx`)

#### 1. Client Details Section
- `Name *`: Text input (Client Full Name).
- `PIN Number / PAN`: Text input (`placeholder="e.g. FGWPK0024B"`).
- `Date of Birth`: Date picker (`type="date"`).
- `Phone *`: Tel input (10 digits).
- `Email *`: Email input.
- `Start Date`: Date picker.
- `Address`: Textarea (2 rows).

#### 2. Loan Details (Lenders Table)
- Dynamic table with rows:
  - `Lender Name`: Input box (`placeholder="Lender Name"`).
  - `Loan Type`: Input box (`placeholder="e.g. Personal Loan, Credit Card"`).
  - `Loan Amount`: Numeric input (`font-mono`, e.g. `150000`).
  - `Remove`: Red button (`Trash` icon), disabled if only 1 lender exists.
- `+ Add More`: Blue button, appends a new lender row.
- `Total Loan`: Disabled read-only field calculating `SUM(balance)` live.

#### 3. Agreement Details Section
- `Agreement Duration`: Dropdown / Text input (e.g. `1 Month`, `2 Months`, `4 Months`, `6 Months`, `12 Months`, `Lifetime`). Automatically updates monthly installment fee.
- `Consultancy Fees (Monthly)`: Auto-calculated or manual override (e.g. `Rs. 7,500`).
- `Resolution Duration`: Text input (e.g. `6 Months`).
- `Prepared By`: Text input (e.g. `Dhruv Consultant`).
- `Executed Date`: Date picker.
- `Monthly Income (₹)`: Numeric input.

#### 4. Official 12-Clause Agreement Contract Body
The system dynamically injects variables into the official legal contract text:
- **Title:** `CONSULTANCY AGREEMENT`
- **Execution Date:** Dynamically populated `${executed_date}`.
- **First Party:** M/s. SettleXpert LLP, CB-201, Naraina Vihar, Ring Road, New Delhi, Delhi, India.
- **Second Party:** `${clientName}`, residing at `${clientAddress}`, DOB: `${clientDob}`, Mobile: `${clientPhone}`, Email: `${clientEmail}`.
- **Clause 1 (Purpose):** Appointment of company for financial consultancy and debt resolution advisory.
- **Clause 2 (Scope of Services):** Evaluating liabilities, formulating resolution strategies, communicating/negotiating with lenders, drafting replies/representations, anti-harassment guidance.
- **Clause 3 (Company Obligations):** Diligence, confidentiality, regular client updates.
- **Clause 4 (Client Obligations):** Providing truthful information, timely fee payments, prompt notification of lender communications, non-borrowing during tenure.
- **Clause 5 (Client Acknowledgements):** Advisory only, no guarantee of waiver/settlement, timeline depends on lenders.
- **Clause 6 (Fees & Payment Terms):** Non-refundable consultancy fee, strict installment schedule, service suspension on default.
- **Clause 7 (Term & Termination):** Tenure duration, termination upon notice or non-payment.
- **Clause 8 (Limitation of Liability):** Company not liable for lender legal actions or recovery agency conduct.
- **Clause 9 (Confidentiality):** Non-disclosure of sensitive financial data.
- **Clause 10 (Grievance Redressal):** Escalation procedure before legal recourse.
- **Clause 11 (Governing Law & Jurisdiction):** Courts of New Delhi, India.
- **Clause 12 (Entire Agreement):** Supersedes prior discussions.
- **Annexure A:** Auto-formatted dynamic table of enrolled lenders and loan balances.
- **Annexure B:** Fee breakdown: Total Agreed Fee (`₹${totalFee}`), Monthly Installment (`₹${monthlyFee}`), Tenure (`${tenure}`).
- **Execution Blocks:** Dual signature blocks for Authorized Signatory (SettleXpert LLP) and Client Signature.

---

## 11 — Payment & Verification Workflow (No Razorpay)

The payment flow is strictly manual payment submission followed by managerial verification. Razorpay has been removed from this operational workflow.

### Step-by-Step Payment Lifecycle
```
1. EMPLOYEE SUBMISSION:
   Employee opens Client 360 → Tab 15 (Payment History) → Clicks "+ Submit Payment"
   Fields entered: Amount Paid (> 0), Payment Date, Payment Mode (UPI, IMPS, NEFT, Cash), 
   Transaction/UTR ID (Required for non-cash), Payment Proof Screenshot (MANDATORY), Remarks.
   ↓
2. PENDING VERIFICATION:
   Payment inserted into `payments` table with `status = 'pending_verification'`.
   Audit log recorded: "Payment Submitted".
   Employee CANNOT self-verify or approve payment.
   ↓
3. MANAGER REVIEW QUEUE:
   Payment appears in Manager Work Center (`ManagerPaymentsVerificationView.tsx`) 
   and Finance Desk (`PaymentsView.tsx`).
   Manager reviews: Client Name, Agreement #, Amount, Payment Date, Payment Mode, UTR, Proof Screenshot.
   ↓
4. VERIFICATION / REJECTION:
   - ACTION A: VERIFY & APPROVE
     - Sets `status = 'verified'`, `verified_by = req.user.name`, `verified_at = CURRENT_TIMESTAMP`.
     - Atomically recalculates client balance:
       `total_received = SUM(amount WHERE status = 'verified')`
       `pending_amount = MAX(0, sx_fee - total_received)`
       `fees_status = total_received >= sx_fee ? 'Paid' : 'Partially Paid'`
     - Syncs to client profile and finance reports.
   - ACTION B: REJECT PAYMENT
     - Rejection reason is MANDATORY (e.g. UTR mismatch, amount not credited, invalid proof).
     - Sets `status = 'rejected'`, `rejected_by = req.user.name`, `rejected_at = CURRENT_TIMESTAMP`, `rejection_reason = reason`.
     - Employee sees "PAYMENT REJECTED" with the exact reason and can resubmit with corrected proof.
```

---

## 12 — Database Schema (All 26 Tables)

### Table 1: `users`
- `id` TEXT PRIMARY KEY
- `name` TEXT NOT NULL
- `email` TEXT NOT NULL UNIQUE
- `phone` TEXT
- `password_hash` TEXT NOT NULL
- `role` TEXT NOT NULL CHECK(role IN ('admin', 'manager', 'employee', 'advocate'))
- `manager_id` TEXT REFERENCES users(id) ON DELETE SET NULL
- `department_id` TEXT REFERENCES departments(id) ON DELETE SET NULL
- `team_id` TEXT REFERENCES teams(id) ON DELETE SET NULL
- `manager_type_id` TEXT REFERENCES manager_types(id) ON DELETE SET NULL
- `emp_or_mgr_id` TEXT UNIQUE
- `profile_image` TEXT
- `joining_date` TEXT
- `id_type` TEXT
- `id_front` TEXT
- `id_back` TEXT
- `status` TEXT NOT NULL DEFAULT 'active' CHECK(status IN ('active', 'inactive'))
- `failed_login_attempts` INTEGER DEFAULT 0
- `locked_until` DATETIME
- `last_failed_login_at` DATETIME
- `last_login_at` DATETIME
- `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
- `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP

### Table 2: `departments`
- `id` TEXT PRIMARY KEY
- `name` TEXT NOT NULL
- `code` TEXT NOT NULL UNIQUE
- `description` TEXT
- `head_manager_id` TEXT REFERENCES users(id) ON DELETE SET NULL
- `status` TEXT NOT NULL DEFAULT 'active' CHECK(status IN ('active', 'inactive'))
- `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
- `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP

### Table 3: `teams`
- `id` TEXT PRIMARY KEY
- `name` TEXT NOT NULL
- `code` TEXT NOT NULL UNIQUE
- `department_id` TEXT REFERENCES departments(id) ON DELETE SET NULL
- `manager_id` TEXT REFERENCES users(id) ON DELETE SET NULL
- `status` TEXT NOT NULL DEFAULT 'active' CHECK(status IN ('active', 'inactive'))
- `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
- `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP

### Table 4: `manager_types`
- `id` TEXT PRIMARY KEY
- `name` TEXT NOT NULL
- `code` TEXT NOT NULL UNIQUE
- `description` TEXT
- `permissions_json` TEXT DEFAULT '{}'
- `status` TEXT NOT NULL DEFAULT 'active' CHECK(status IN ('active', 'inactive'))
- `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
- `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP

### Table 5: `user_permissions`
- `id` TEXT PRIMARY KEY
- `user_id` TEXT NOT NULL REFERENCES users(id) ON DELETE CASCADE
- `module` TEXT NOT NULL
- `can_view` INTEGER NOT NULL DEFAULT 1
- `can_create` INTEGER NOT NULL DEFAULT 0
- `can_edit` INTEGER NOT NULL DEFAULT 0
- `can_delete` INTEGER NOT NULL DEFAULT 0
- `can_assign` INTEGER NOT NULL DEFAULT 0
- `can_verify` INTEGER NOT NULL DEFAULT 0
- UNIQUE(user_id, module)

### Table 6: `advocates`
- `id` TEXT PRIMARY KEY
- `name` TEXT NOT NULL
- `advocate_id` TEXT NOT NULL UNIQUE
- `mobile` TEXT NOT NULL
- `email` TEXT NOT NULL UNIQUE
- `registration_number` TEXT NOT NULL UNIQUE
- `specialization` TEXT NOT NULL
- `address` TEXT
- `profile_image` TEXT
- `status` TEXT NOT NULL DEFAULT 'active' CHECK(status IN ('active', 'inactive'))
- `notes` TEXT
- `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP

### Table 7: `fee_plans`
- `id` TEXT PRIMARY KEY
- `name` TEXT NOT NULL
- `duration` TEXT NOT NULL
- `default_fee` NUMERIC(12, 2) NOT NULL
- `status` TEXT NOT NULL DEFAULT 'active' CHECK(status IN ('active', 'inactive'))
- `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP

### Table 8: `audit_logs`
- `id` TEXT PRIMARY KEY
- `user_id` TEXT
- `user_name` TEXT
- `role` TEXT
- `action` TEXT NOT NULL
- `module` TEXT NOT NULL
- `record_id` TEXT
- `details_json` TEXT
- `ip_address` TEXT
- `user_agent` TEXT
- `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP

### Table 9: `leads`
- `id` TEXT PRIMARY KEY
- `lead_number` TEXT UNIQUE NOT NULL
- `name` TEXT NOT NULL
- `phone` TEXT NOT NULL
- `email` TEXT
- `city` TEXT
- `state` TEXT
- `address` TEXT
- `total_debt` REAL DEFAULT 0
- `monthly_income` REAL DEFAULT 0
- `service_needed` TEXT
- `paying_emis` TEXT DEFAULT 'Yes'
- `harassment_calls` TEXT DEFAULT 'No'
- `employment_status` TEXT DEFAULT 'Employed'
- `employment_type` TEXT DEFAULT 'Salaried'
- `settlement_needed` TEXT DEFAULT 'Yes'
- `consultation_timing` TEXT
- `credit_card_dues` REAL DEFAULT 0
- `personal_loan_dues` REAL DEFAULT 0
- `service_fee` REAL DEFAULT 0
- `bank_name` TEXT
- `status` TEXT NOT NULL DEFAULT 'new'
- `manager_id` TEXT REFERENCES users(id) ON DELETE SET NULL
- `employee_id` TEXT REFERENCES users(id) ON DELETE SET NULL
- `department_id` TEXT REFERENCES departments(id) ON DELETE SET NULL
- `created_by` TEXT
- `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
- `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP

### Table 10: `follow_ups`
- `id` TEXT PRIMARY KEY
- `lead_id` TEXT NOT NULL REFERENCES leads(id) ON DELETE CASCADE
- `user_id` TEXT REFERENCES users(id) ON DELETE SET NULL
- `user_name` TEXT
- `call_status` TEXT NOT NULL
- `interested_level` TEXT
- `final_status` TEXT NOT NULL
- `remark` TEXT
- `next_follow_up_date` TEXT
- `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP

### Table 11: `clients`
- `id` TEXT PRIMARY KEY
- `client_number` TEXT UNIQUE NOT NULL
- `lead_id` TEXT REFERENCES leads(id) ON DELETE SET NULL
- `name` TEXT NOT NULL
- `email` TEXT
- `phone` TEXT NOT NULL
- `city` TEXT
- `address` TEXT
- `state` TEXT
- `employment_status` TEXT DEFAULT 'Employed'
- `employment_type` TEXT DEFAULT 'Salaried'
- `total_debt` REAL DEFAULT 0
- `monthly_income` REAL DEFAULT 0
- `credit_card_dues` REAL DEFAULT 0
- `personal_loan_dues` REAL DEFAULT 0
- `loan_type` TEXT DEFAULT 'Credit Card & Personal Loan'
- `paying_emis` TEXT DEFAULT 'Yes'
- `harassment_calls` TEXT DEFAULT 'No'
- `settlement_needed` TEXT DEFAULT 'Yes'
- `consultation_timing` TEXT
- `settlement_target` REAL DEFAULT 0
- `sx_fee` REAL DEFAULT 0
- `fees_date` TEXT
- `fees_status` TEXT DEFAULT 'Pending'
- `pending_amount` REAL DEFAULT 0
- `total_received` REAL DEFAULT 0
- `status` TEXT NOT NULL DEFAULT 'active'
- `case_status` TEXT DEFAULT 'active'
- `notes` TEXT
- `employee_id` TEXT REFERENCES users(id) ON DELETE SET NULL
- `manager_id` TEXT REFERENCES users(id) ON DELETE SET NULL
- `advocate_id` TEXT REFERENCES advocates(id) ON DELETE SET NULL
- `fee_plan_id` TEXT REFERENCES fee_plans(id) ON DELETE SET NULL
- `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
- `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP

### Table 12: `lenders`
- `id` TEXT PRIMARY KEY
- `client_id` TEXT NOT NULL REFERENCES clients(id) ON DELETE CASCADE
- `bank_name` TEXT NOT NULL
- `loan_type` TEXT
- `balance` REAL DEFAULT 0
- `default_date` TEXT
- `status` TEXT DEFAULT 'Defaulted'
- `lender_email` TEXT
- `pdf_url` TEXT
- `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP

### Table 13: `agreements`
- `id` TEXT PRIMARY KEY
- `agreement_number` TEXT UNIQUE NOT NULL
- `client_id` TEXT REFERENCES clients(id) ON DELETE CASCADE
- `fee_plan_id` TEXT REFERENCES fee_plans(id) ON DELETE SET NULL
- `name` TEXT
- `phone` TEXT
- `email` TEXT
- `address` TEXT
- `pin_number` TEXT
- `dob` TEXT
- `start_date` TEXT
- `end_date` TEXT
- `total_fee` REAL NOT NULL
- `monthly_fee` REAL DEFAULT 0
- `monthly_income` REAL DEFAULT 0
- `resolution_duration` TEXT
- `prepared_by` TEXT
- `executed_date` TEXT
- `status` TEXT NOT NULL DEFAULT 'draft' CHECK(status IN ('draft', 'sent', 'signed', 'active', 'completed', 'cancelled'))
- `agreement_body` TEXT
- `created_by` TEXT
- `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
- `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP

### Table 14: `payments`
- `id` TEXT PRIMARY KEY
- `receipt_number` TEXT UNIQUE
- `client_id` TEXT REFERENCES clients(id) ON DELETE CASCADE
- `agreement_id` TEXT REFERENCES agreements(id) ON DELETE SET NULL
- `amount` REAL NOT NULL
- `payment_method` TEXT NOT NULL
- `payment_mode` TEXT
- `transaction_id` TEXT
- `proof_document` TEXT
- `remarks` TEXT
- `status` TEXT NOT NULL DEFAULT 'pending_verification' CHECK(status IN ('pending_verification', 'verified', 'rejected'))
- `submitted_by` TEXT REFERENCES users(id) ON DELETE SET NULL
- `submitted_by_name` TEXT
- `submitted_at` DATETIME
- `verified_by` TEXT
- `verified_at` DATETIME
- `rejected_by` TEXT REFERENCES users(id) ON DELETE SET NULL
- `rejected_by_name` TEXT
- `rejected_at` DATETIME
- `rejection_reason` TEXT
- `payment_date` TEXT
- `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
- `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP

### Table 15: `monthly_payment_records`
- `id` TEXT PRIMARY KEY
- `client_id` TEXT NOT NULL REFERENCES clients(id) ON DELETE CASCADE
- `agreement_id` TEXT REFERENCES agreements(id) ON DELETE SET NULL
- `month_number` INTEGER NOT NULL
- `due_date` TEXT NOT NULL
- `expected_amount` REAL NOT NULL DEFAULT 0
- `received_amount` REAL NOT NULL DEFAULT 0
- `payment_date` TEXT
- `payment_status` TEXT NOT NULL DEFAULT 'Pending' CHECK(payment_status IN ('Pending', 'Partially Paid', 'Paid', 'Overdue'))
- `remarks` TEXT
- `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
- `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP

### Table 16: `monthly_payment_history`
- `id` TEXT PRIMARY KEY
- `monthly_record_id` TEXT NOT NULL REFERENCES monthly_payment_records(id) ON DELETE CASCADE
- `client_id` TEXT NOT NULL REFERENCES clients(id) ON DELETE CASCADE
- `agreement_id` TEXT REFERENCES agreements(id) ON DELETE SET NULL
- `month_number` INTEGER NOT NULL
- `payment_date` TEXT NOT NULL
- `amount_received` REAL NOT NULL
- `cumulative_received` REAL NOT NULL
- `payment_status` TEXT NOT NULL
- `remarks` TEXT
- `updated_by_id` TEXT
- `updated_by_name` TEXT
- `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP

### Table 17: `payment_due_notifications`
- `id` TEXT PRIMARY KEY
- `client_id` TEXT NOT NULL REFERENCES clients(id) ON DELETE CASCADE
- `agreement_id` TEXT REFERENCES agreements(id) ON DELETE SET NULL
- `monthly_record_id` TEXT REFERENCES monthly_payment_records(id) ON DELETE CASCADE
- `month_number` INTEGER NOT NULL
- `notification_type` TEXT NOT NULL CHECK(notification_type IN ('PAYMENT_DUE_TODAY', 'PARTIAL_PAYMENT', 'PAYMENT_OVERDUE', 'PAYMENT_RECEIVED'))
- `title` TEXT NOT NULL
- `message` TEXT NOT NULL
- `due_date` TEXT NOT NULL
- `expected_amount` REAL NOT NULL DEFAULT 8000
- `received_amount` REAL NOT NULL DEFAULT 0
- `pending_amount` REAL NOT NULL DEFAULT 0
- `days_overdue` INTEGER NOT NULL DEFAULT 0
- `status` TEXT NOT NULL DEFAULT 'active' CHECK(status IN ('active', 'resolved'))
- `is_read` INTEGER NOT NULL DEFAULT 0
- `employee_id` TEXT REFERENCES users(id) ON DELETE SET NULL
- `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
- `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP

### Table 18: `tasks`
- `id` TEXT PRIMARY KEY
- `title` TEXT NOT NULL
- `description` TEXT
- `module` TEXT
- `priority` TEXT DEFAULT 'medium' CHECK(priority IN ('low', 'medium', 'high', 'urgent'))
- `due_date` TEXT
- `status` TEXT NOT NULL DEFAULT 'pending' CHECK(status IN ('pending', 'in_progress', 'completed', 'cancelled'))
- `assigned_to` TEXT REFERENCES users(id) ON DELETE SET NULL
- `created_by` TEXT
- `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP

### Table 19: `lead_imports`
- `id` TEXT PRIMARY KEY
- `import_number` TEXT UNIQUE NOT NULL
- `file_name` TEXT
- `uploaded_by` TEXT REFERENCES users(id)
- `uploaded_by_name` TEXT
- `uploaded_by_role` TEXT
- `total_rows` INTEGER DEFAULT 0
- `valid_leads` INTEGER DEFAULT 0
- `duplicate_leads` INTEGER DEFAULT 0
- `invalid_rows` INTEGER DEFAULT 0
- `imported_leads` INTEGER DEFAULT 0
- `distribution_status` TEXT DEFAULT 'pending'
- `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP

### Table 20: `lead_distributions`
- `id` TEXT PRIMARY KEY
- `distribution_number` TEXT UNIQUE NOT NULL
- `import_id` TEXT REFERENCES lead_imports(id) ON DELETE SET NULL
- `distributed_by` TEXT REFERENCES users(id)
- `distributed_by_name` TEXT
- `distributed_by_role` TEXT
- `distribution_mode` TEXT NOT NULL
- `assignment_method` TEXT NOT NULL
- `total_leads` INTEGER NOT NULL
- `employee_count` INTEGER NOT NULL
- `summary_json` TEXT
- `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP

### Table 21: `staff_extended_profiles`
- `user_id` TEXT PRIMARY KEY REFERENCES users(id) ON DELETE CASCADE
- `date_of_birth` TEXT
- `gender` TEXT
- `father_name` TEXT
- `mother_name` TEXT
- `alt_phone` TEXT
- `whatsapp_number` TEXT
- `current_address` TEXT
- `permanent_address` TEXT
- `city` TEXT
- `state` TEXT
- `pin_code` TEXT
- `designation` TEXT
- `employment_status` TEXT DEFAULT 'Active'
- `staff_type` TEXT DEFAULT 'Employee'
- `reporting_manager_id` TEXT REFERENCES users(id) ON DELETE SET NULL
- `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
- `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP

### Table 22: `staff_kyc`
- `id` TEXT PRIMARY KEY
- `user_id` TEXT NOT NULL UNIQUE REFERENCES users(id) ON DELETE CASCADE
- `aadhaar_number` TEXT
- `aadhaar_front_doc` TEXT
- `aadhaar_back_doc` TEXT
- `kyc_status` TEXT DEFAULT 'pending' CHECK(kyc_status IN ('pending', 'uploaded', 'verified', 'rejected'))
- `verified_by` TEXT
- `verified_at` DATETIME
- `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP

### Table 23: `staff_advocate_details`
- `user_id` TEXT PRIMARY KEY REFERENCES users(id) ON DELETE CASCADE
- `registration_number` TEXT
- `bar_council_state` TEXT
- `specialization` TEXT
- `years_experience` INTEGER DEFAULT 0
- `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
- `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP

### Table 24: `advocate_assignment_history`
- `id` TEXT PRIMARY KEY
- `client_id` TEXT NOT NULL REFERENCES clients(id) ON DELETE CASCADE
- `previous_advocate_id` TEXT
- `previous_advocate_name` TEXT
- `new_advocate_id` TEXT
- `new_advocate_name` TEXT
- `assigned_by_id` TEXT
- `assigned_by_name` TEXT
- `assigned_by_role` TEXT
- `notes` TEXT
- `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP

### Table 25: `legal_notices`
- `id` TEXT PRIMARY KEY
- `notice_number` TEXT UNIQUE NOT NULL (Format: `LN-0001`)
- `client_id` TEXT REFERENCES clients(id) ON DELETE CASCADE
- `advocate_id` TEXT REFERENCES advocates(id) ON DELETE SET NULL
- `bank_name` TEXT NOT NULL
- `loan_account_no` TEXT
- `notice_type` TEXT NOT NULL (Anti-Harassment, Reply to Legal Notice, Sec 138 Reply, Arbitration Response)
- `notice_date` TEXT NOT NULL
- `speed_post_number` TEXT
- `dispatch_date` TEXT
- `delivery_date` TEXT
- `status` TEXT NOT NULL DEFAULT 'Draft' (Draft, Dispatched, Delivered)
- `notice_body` TEXT
- `created_by` TEXT
- `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
- `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP

### Table 26: `demand_notices`
- `id` TEXT PRIMARY KEY
- `demand_number` TEXT UNIQUE NOT NULL (Format: `DN-0001`)
- `client_id` TEXT REFERENCES clients(id) ON DELETE CASCADE
- `advocate_id` TEXT REFERENCES advocates(id) ON DELETE SET NULL
- `bank_name` TEXT NOT NULL
- `loan_account_no` TEXT
- `demand_type` TEXT NOT NULL
- `notice_date` TEXT NOT NULL
- `speed_post_number` TEXT
- `dispatch_date` TEXT
- `delivery_date` TEXT
- `status` TEXT NOT NULL DEFAULT 'Draft'
- `notice_body` TEXT
- `created_by` TEXT
- `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
- `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP

---

## 13 — Complete Backend API Inventory

### Auth Routes (`/api/auth`)
- `POST /login`: Body `{ email, password }`. Verifies bcrypt hash, lockout check. Returns JWT token and User profile.
- `GET /me`: Returns logged in user profile, permissions, department, manager info.
- `POST /change-password`: Body `{ current_password, new_password }`.
- `POST /logout`: Audit logout event.

### CRM Leads Routes (`/api/crm`)
- `GET /crm/leads`: Params `{ search, status, date, calendar_date, from_date, to_date, page, limit }`. Returns leads list, dynamic status counts, pagination.
- `GET /crm/leads/:id`: Returns single lead particulars and complete `follow_ups` history.
- `POST /crm/leads`: Body `{ name, phone, email, city, total_debt, monthly_income, service_needed, paying_emis, harassment_calls, ... }`. Validates phone/email deduplication. Generates `LEAD-XXXX`.
- `PUT /crm/leads/:id`: Updates lead information without altering original `created_at`.
- `DELETE /crm/leads/:id`: Soft deletes lead (`status = 'deleted'`).
- `POST /crm/leads/:id/follow-up`: Body `{ call_status, interested_level, final_status, remark, next_follow_up_date }`. Appends to follow-up history log.
- `POST /crm/leads/:id/convert`: Converts lead into Client (`CL-XXXX`), creates Annexure A lender, and generates agreement (`AGR-XXXX`).

### CRM Clients Routes (`/api/crm`)
- `GET /crm/clients`: Params `{ search, case_status, month, date, calendar_date, from_date, to_date, page, limit }`. Returns enriched client array with total received, pending fees, duration.
- `GET /crm/clients/:id`: Returns client 360 particulars, attached `lenders`, `agreements`, and `payments`.
- `POST /crm/clients`: Directly creates a new client and generates `CL-XXXX`.
- `PUT /crm/clients/:id`: Updates client particulars, address, case status, fees.
- `DELETE /crm/clients/:id`: Removes client record and cascades dependencies.
- `POST /crm/clients/:id/lenders`: Body `{ bank_name, loan_type, balance }`. Adds creditor bank.
- `DELETE /crm/clients/:id/lenders/:lenderId`: Deletes creditor lender.
- `GET /crm/clients/:id/monthly-payments`: Returns monthly EMI breakdown records.
- `POST /crm/clients/:id/monthly-payments/generate`: Generates Month 1 to Month N payment schedule.
- `PUT /crm/monthly-payments/:recordId`: Updates specific monthly record amount/status.

### CRM Agreements Routes (`/api/crm`)
- `GET /crm/agreements`: Params `{ search, status, date, calendar_date, from_date, to_date, page, limit }`.
- `GET /crm/agreements/:id`: Returns agreement details and dynamic template body.
- `POST /crm/agreements`: Body `{ client_id, total_fee, monthly_fee, resolution_duration, lenders, agreement_body, ... }`. Generates `AGR-XXXX`.
- `PUT /crm/agreements/:id`: Updates agreement terms, body, or status (`draft`, `sent`, `signed`, `active`, `completed`, `cancelled`).
- `DELETE /crm/agreements/:id`: Deletes agreement.

### CRM Payments Routes (`/api/crm`)
- `GET /crm/payments`: Params `{ search, status, date, client_id, payment_mode }`. Scoped by employee/manager/admin.
- `POST /crm/payments`: Manual payment submission. Body `{ client_id, agreement_id, amount, payment_mode, transaction_id, payment_date, proof_document, remarks }`. Validates amount > 0, required proof document, generates `REC-YYYY-XXX`, sets status to `pending_verification`.
- `PATCH /crm/payments/:id/verify`: Status update by Manager/Admin. Body `{ status: 'verified' | 'rejected', notes, rejection_reason }`. Enforces 403 for employees. Atomically recalculates client balance on approval or rejection.

### Manager Panel Routes (`/api/manager`)
- `GET /manager/context`: Returns manager role code, department, and team scope.
- `GET /manager/dashboard`: Returns team-wide lead metrics, consultant performance, collections.
- `GET /manager/team`: Returns list of consultants reporting to this manager.
- `POST /manager/team/:employeeId/reset-password`: Resets consultant password.
- `GET /manager/leads`: Returns leads assigned to this manager's consultants.
- `POST /manager/leads/:leadId/assign`: Body `{ new_employee_id, reason }`. Reassigns lead.
- `GET /manager/clients`: Returns clients managed by this manager's team.
- `PATCH /manager/clients/:clientId/advocate`: Body `{ advocate_id }`. Assigns advocate to client.
- `PATCH /manager/clients/:clientId/fee`: Updates client fee schedule.
- `GET /manager/payments/verification-queue`: Returns all payments with `status = 'pending_verification'`.
- `PATCH /manager/payments/:paymentId/verify`: Verifies or rejects payment with mandatory reason.
- `GET /manager/follow-ups`: Returns scheduled callbacks for team.
- `GET /manager/tasks`: Returns operational tasks.

### Advocate Portal Routes (`/api/advocate-portal`)
- `GET /advocate-portal/dashboard`: Assigned cases counts, debt managed, recent cases.
- `GET /advocate-portal/cases`: Filter assigned cases by status, date, search.
- `GET /advocate-portal/cases/:id`: Detailed case file, lenders, notices, tasks.
- `POST /advocate-portal/cases/:id/tasks`: Logs legal task against client case.
- `GET /advocate-portal/legal-notices`: Returns legal notices (`LN-XXXX`), status counts (Draft, Dispatched, Delivered).
- `POST /advocate-portal/legal-notices`: Creates new legal notice.
- `PUT /advocate-portal/legal-notices/:id`: Updates notice body, Speed Post tracking number, delivery status.
- `GET /advocate-portal/demand-notices`: Returns demand notices (`DN-XXXX`).
- `POST /advocate-portal/demand-notices`: Creates new demand notice.
- `PUT /advocate-portal/demand-notices/:id`: Updates demand notice particulars.

### Staff & Organization Routes
- `GET /api/staff/all`: Full staff directory with personal, KYC, and advocate details.
- `POST /api/staff/create`: Creates staff member (`users` + `staff_extended_profiles`).
- `PUT /api/staff/:id`: Updates staff personal and professional data.
- `POST /api/staff/:id/kyc`: Uploads/updates Aadhaar KYC documents and number.
- `PATCH /api/staff/:id/kyc-verify`: Body `{ kyc_status: 'verified' | 'rejected' }`.
- `GET /api/departments`: Roster of departments with head manager and employee counts.
- `POST /api/departments`: Creates department.
- `GET /api/teams`: Teams grouped under departments.
- `POST /api/teams`: Creates team and assigns team lead.
- `GET /api/manager-types`: Manager classifications and permissions JSON.
- `GET /api/fee-plans`: Master fee plans.

### Lead Import & Distribution Routes (`/api/lead-import`)
- `POST /lead-import/upload`: Parses Excel/CSV, validates phone/email, deduplicates against existing records, generates summary.
- `POST /lead-import/distribute`: Distributes parsed batch to agents using Auto Round-Robin or Custom Quota mode.
- `GET /lead-import/history`: Audit log of all imports and lead allocations.

---

## 14 — Financial Formulas & Calculation Engine

1. **Settlement Target:**
   $$\text{Settlement Target} = \text{ROUND}(\text{Total Debt} \times 0.45)$$
   *(Standard 45% settlement goal of total enrolled debt).*

2. **Total Agreement Fee:**
   $$\text{Total Agreement Fee} = \text{Client Monthly Fee} \times \text{Duration in Months}$$
   *(Default: ₹8,000 monthly $\times$ 6 months = ₹48,000).*

3. **Total Verified Received Amount (Source of Truth):**
   $$\text{Total Received} = \sum \text{amount} \quad \text{WHERE } \text{status} = \text{'verified'}$$
   *(Pending or rejected payments are strictly excluded).*

4. **Pending Client Balance:**
   $$\text{Pending Balance} = \max(0, \text{Total Agreed Fee} - \text{Total Verified Received})$$

5. **Fees Status State Machine:**
   $$\text{Fees Status} = \begin{cases} \text{'Paid'} & \text{if } \text{Total Received} \ge \text{Total Agreed Fee} \text{ and } \text{Fee} > 0 \\ \text{'Partially Paid'} & \text{if } \text{Total Received} > 0 \\ \text{'Pending'} & \text{otherwise} \end{cases}$$

6. **Net Disposable Surplus (Income-EMI Tab):**
   $$\text{Disposable Surplus} = \text{Net Monthly Income} - (\text{Rent} + \text{Groceries} + \text{School Fees} + \text{Utilities} + \text{Medical})$$

7. **Lead Conversion Ratio:**
   $$\text{Conversion Rate} = \left(\frac{\text{Total Clients}}{\text{Total Leads}}\right) \times 100$$

---

## 15 — Legacy & Deprecated Code Audit

1. **Legacy Razorpay References (`src/components/crm/ClientDetailsUnifiedView.tsx`):**
   - Lines 528 and 935 contain static placeholder text labels: `"Razorpay / UPI"`. There is no actual Razorpay SDK dependency in `package.json` or active backend webhook. In the new CRM, replace these labels with `"UPI / Net Banking"`.
2. **Legacy PHP Backend (`backend/` & `php-app/`):**
   - The root contains old PHP controllers (`backend/controllers/DashboardController.php`, `DepartmentController.php`). The active production backend is completely running on Node.js / Express (`server/index.js`). The PHP files are legacy artifacts from an earlier prototype.
3. **Standalone Zip Archives (`gh.zip`, `mingit.zip`):**
   - Stored in the root folder, these are portable Git binaries and not part of the CRM application runtime.

---

## 16 — New CRM Rebuild Blueprint (Production Architecture)

When building the brand-new SettleXpert CRM from scratch, adhere to this architectural structure:

### 1. Unified Authentication & RBAC Service
- Implement JWT authentication with role payload: `admin`, `manager`, `employee`, `advocate`.
- Enforce backend route guards on all modifying endpoints (`POST /payments`, `PATCH /payments/:id/verify`). Never rely solely on hiding frontend buttons.

### 2. Single Canonical Payment Table
- Avoid splitting payments into separate tables. Use one master `payments` table with clear lifecycle statuses:
  - `pending_verification`
  - `verified`
  - `rejected`
- Mandatory fields for submission: `amount` (> 0), `payment_date`, `payment_mode`, `transaction_id`, `proof_document` (Base64 or secure storage URL), `submitted_by`.
- Manager verification must require `rejection_reason` when rejecting and must atomically trigger client financial recalculation.

### 3. Traceable Lead-to-Client Pipeline
- Maintain a direct one-to-one or one-to-many foreign key relationship (`clients.lead_id -> leads.id`).
- When converting, copy all 18+ lead qualification fields directly into the new client record. Automatically generate sequential `CL-XXXX` and `AGR-XXXX`.

### 4. Dynamic Contract Generation
- Maintain the 12-clause legal contract template with Annexure A (Lenders) and Annexure B (Fee Schedule). Auto-compile variables into a rich-text printable view.

### 5. Multi-Tab Client 360 Workspace
- Retain the proven 19-tab information architecture in `ClientDetailsUnifiedView` to give legal, operations, sales, and accounts a single 360° pane of glass for each debtor.

---

## 17 — Summary Metric Counts

- **Total Unique Functional Screens / Views:** 48
- **Total Functional Tabs:** 32 (including all 19 Client 360 sub-tabs)
- **Total Database Tables:** 26
- **Total Backend REST Endpoints:** 58
- **Total User Roles:** 4 (`admin`, `manager`, `employee`, `advocate`)
- **Total Input Forms:** 22
- **Total Master Entity Types:** 12 (`users`, `leads`, `clients`, `lenders`, `agreements`, `payments`, `tasks`, `notices`, `departments`, `teams`, `fee_plans`, `audit_logs`)
