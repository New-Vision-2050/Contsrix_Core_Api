# Product Requirements Document: Employee Attendance System - React Frontend

## 1. Task/Feature Title
Employee Attendance System - React Frontend

## 2. Requester
USER (via Cascade AI)

## 3. Date
**Created:** 2025-06-18  
**Last Updated:** 2025-06-21

## 4. Problem Statement / Goal
*   Provide a comprehensive, user-friendly web-based interface for employees, supervisors, and HR personnel to manage all aspects of employee attendance and leave requests.
*   **API Integration:** RESTful API integration with the Contsrix_Core_Api backend following these patterns:
    * Response handling aligned with backend Json::item() and Json::items() format
    * Request payloads structured to match backend Form Request expectations
    * Proper handling of pagination metadata from backend responses
    * JWT token management with automatic refresh capabilities, leveraging its existing functionalities and data structures.
*   Streamline attendance tracking, simplify leave management processes, and provide actionable insights through reports.

## 5. Target Audience / User Stories

### As an Employee:
*   I want to easily clock in when I start my workday and clock out when I finish, so my work hours are accurately recorded.
*   I want to be able to start and end my breaks (e.g., lunch break) through the system, so break times are tracked correctly.
*   I want to view my complete attendance history, including daily clock-in/out times, break durations, total work hours, and overtime, so I can verify my records.
*   I want to submit leave requests for various leave types (vacation, sick leave, etc.), specifying dates, reasons, and attaching supporting documents if necessary, so I can formally request time off.
*   I want to view the current status of my submitted leave requests (pending, approved, rejected) and any comments from my supervisor, so I am informed about the outcome.
*   I want to see my available leave balances for different leave types, so I know how much leave I am entitled to.
*   I want to receive in-app notifications for updates to my leave requests (e.g., approval, rejection), so I am promptly informed.
*   I want to be able to cancel a leave request if it's still pending and my plans change.

### As a Supervisor/Manager:
*   I want to view the real-time attendance status (clocked-in, clocked-out, on break) of my direct team members, so I can monitor team presence.
*   I want to view the detailed attendance history and work patterns of my team members, so I can manage team productivity and address any attendance issues.
*   I want to receive notifications for new leave requests submitted by my team members that require my approval.
*   I want to be able to review, approve, or reject leave requests from my team members, with the option to add comments, so I can manage team schedules effectively.
*   I want to view a team calendar showing approved leaves for my team members, so I can plan workload and coverage.
*   I want to generate attendance summaries and leave reports for my team, so I can analyze trends and report to upper management.
*   I want to be able to make minor corrections to my team members' attendance records (e.g., missed clock-out) with a clear audit trail, subject to HR policy.

### As an HR Personnel:
*   I want to have a global view of all employee attendance records across the company, so I can ensure compliance and manage payroll accurately.
*   I want to manage all aspects of leave requests, including overriding approvals/rejections if necessary, and handling complex leave scenarios.
*   I want to define and manage different leave types (e.g., annual, sick, unpaid), their accrual policies, and carry-over rules, so the system reflects company policy.
*   I want to manage and update employee leave balances, including manual adjustments with audit trails, so records are always accurate.
*   I want to generate comprehensive attendance, leave, overtime, and compliance reports for the entire company or specific departments/employees, so I can fulfill reporting requirements and analyze workforce data.
*   I want to configure company-wide attendance settings, such as standard work hours, workdays, overtime calculation rules, and public holiday calendars, so the system operates according to company standards.
*   I want to manage user roles and permissions within the attendance system, ensuring users only access appropriate features and data.
*   I want to view audit logs for critical actions within the system (e.g., manual attendance changes, leave balance adjustments) for accountability.

## 6. Proposed Solution / Key Features

### 6.0. Architecture & Implementation Approach

#### Frontend Architecture
* **Component Structure:** Modular component architecture with reusable UI components
* **State Management:** Redux Toolkit for global state management with slice pattern
* **API Integration:** Custom React hooks for API calls with standardized response handling
* **Form Handling:** Formik with Yup validation schema matching backend Form Request validation
* **Code Organization:** Feature-based folder structure aligning with backend modules

#### API Integration Strategy
* **API Client:** Axios-based client with interceptors for JWT handling and error processing
* **Response Handling:** Standardized handling of Json responses from backend
* **DTO Mapping:** Frontend models matching backend DTOs for consistent data structure
* **Error Handling:** Comprehensive error handling for API failures with user-friendly messages
* **Pagination:** Standardized pagination component consuming backend pagination metadata

### 6.1. Dashboard
*   Personalized overview based on user role.
*   **Employee:** Current clock-in status, quick clock-in/out/break buttons, today's work summary, pending leave requests, leave balances.
*   **Supervisor:** Team attendance overview, pending leave approvals, quick links to team reports.
*   **HR:** Company-wide attendance snapshot, pending approvals, system alerts, quick links to admin functions.

### 6.2. Clock In/Out Module
*   Prominent and intuitive Clock In / Clock Out buttons.
*   Start Break / End Break functionality.
*   Real-time display of current work session duration and break duration.
*   (Optional, policy-dependent) Geolocation capture on clock-in/out, with user consent and clear indication.

### 6.3. Attendance Management
*   **Personal View:**
    *   Calendar view displaying daily status (present, absent, on leave, holiday).
    *   List view of attendance records with filtering (date range, status) and sorting.
    *   Detailed daily log: all clock-in/out punches, break times, calculated total work hours, overtime hours.
*   **Team View (Supervisor):**
    *   Similar to personal view but for all direct reports.
    *   Ability to drill down into individual team member's records.
*   **Company View (HR):**
    *   Similar to team view but for all employees, with advanced filtering (department, location, etc.).
*   **Manual Adjustments (HR/Supervisor with approval workflow):**
    *   Ability to add missed punches or correct existing ones.
    *   Clear audit trail for all manual changes, including reason for change.

### 6.4. Leave Management Module
*   **Leave Request Submission (Employee):**
    *   Intuitive form to select leave type, start/end dates (with date picker), reason.
    *   Option to attach supporting documents.
    *   Display available balance for the selected leave type.
    *   Show conflicting leaves or holidays.
*   **Leave History & Status (Employee):**
    *   List of all submitted leave requests with current status (Pending, Approved, Rejected, Cancelled).
    *   Ability to withdraw pending requests.
*   **Leave Approval Workflow (Supervisor/HR):**
    *   Dedicated section for pending leave approvals.
    *   View request details, employee's leave balance, and team leave calendar for conflicts.
    *   Approve/Reject buttons with an option to add mandatory comments for rejection.
*   **Leave Balance Management (HR):**
    *   View and manage leave balances for all employees.
    *   Tools for bulk accrual processing based on policies.
    *   Manual adjustment of leave balances with audit trail.
*   **Leave Type & Policy Management (HR):**
    *   Interface to create and configure leave types (name, code, accrual rules, max carry-over, requires attachment, etc.).
*   **Company Leave Calendar (HR/Supervisor):**
    *   View approved leaves across the company or specific teams/departments.

### 6.5. Attendance Constraints Management

* **Constraint Configuration:**
  * Interface for creating and managing attendance constraints (location, time, device, role, behavioral, security, compliance)
  * JSON schema-based configuration editor for constraint parameters
  * Visual indicators for constraint priority and status

* **Constraint Assignment:**
  * Assign constraints to departments, roles, or individual employees
  * Bulk assignment capabilities with effective date ranges
  * Conflict detection when assigning multiple constraints

* **Violation Management:**
  * Dashboard for viewing all constraint violations
  * Filtering by severity, status, constraint type, and employee
  * Resolution workflow with approval process
  * Audit trail for all violation resolutions

* **Constraint Testing:**
  * Sandbox environment to test constraint configurations
  * Simulation tools to verify constraint behavior
  * Validation against historical attendance data

### 6.6. Reporting Module
*   **Standard Reports:**
    *   Daily/Weekly/Monthly Attendance Report (total hours, overtime, lateness, absences).
    *   Leave Taken Report (by type, employee, department).
    *   Leave Balance Report.
    *   Overtime Report.
*   **Customizable Filters:** Date range, employee, department, leave type, status.
*   **Export Options:** CSV, PDF.

### 6.6. User Profile & Settings
*   **Employee:** View personal details, manage notification preferences.
*   **HR (System Settings):**
    *   Define company workweek, standard work hours.
    *   Manage public holiday calendar.
    *   Configure attendance policies (grace periods, rounding rules for clock-ins, overtime thresholds).
    *   Configure leave policies.

### 6.7. API Endpoints

#### Authentication Endpoints
- `POST /api/auth/login` - Authenticate user and receive JWT token
- `POST /api/auth/refresh` - Refresh JWT token
- `POST /api/auth/logout` - Invalidate current JWT token

#### Attendance Endpoints
- `POST /api/attendance/clock-in` - Record employee clock-in
- `POST /api/attendance/clock-out` - Record employee clock-out
- `POST /api/attendance/break/start` - Start break period
- `POST /api/attendance/break/end` - End break period
- `GET /api/attendance/status` - Get current attendance status
- `GET /api/attendance/history` - Get attendance history with pagination
- `GET /api/attendance/team` - Get team attendance (for managers)
- `GET /api/attendance/late-arrivals` - Get late arrival records with filtering
- `GET /api/attendance/early-departures` - Get early departure records with filtering
- `GET /api/attendance/overtime-records` - Get overtime records with filtering

#### Leave Management Endpoints
- `GET /api/leave-types` - List available leave types
- `POST /api/leave-types` - Create new leave type (HR only)
- `PUT /api/leave-types/{id}` - Update leave type (HR only)
- `DELETE /api/leave-types/{id}` - Delete leave type (HR only)
- `GET /api/leave-balance` - Get employee leave balance
- `PUT /api/leave-balance/{id}` - Adjust leave balance (HR only)
- `POST /api/leave-requests` - Submit new leave request
- `GET /api/leave-requests` - List leave requests with pagination and filtering
- `GET /api/leave-requests/{id}` - Get specific leave request details
- `PUT /api/leave-requests/{id}` - Update leave request
- `DELETE /api/leave-requests/{id}` - Cancel leave request
- `POST /api/leave-requests/{id}/approve` - Approve leave request
- `POST /api/leave-requests/{id}/reject` - Reject leave request
- `GET /api/leave-requests/pending-approvals` - View pending leave requests requiring approval
- `GET /api/leave-requests/calendar` - View leave calendar
- `GET /api/leave-requests/check-conflicts` - Check for leave request conflicts

#### Attendance Constraints Endpoints
- `GET /api/attendance-constraints` - List attendance constraints with filtering
- `POST /api/attendance-constraints` - Create new constraint
- `GET /api/attendance-constraints/{id}` - Get constraint details
- `PUT /api/attendance-constraints/{id}` - Update constraint
- `DELETE /api/attendance-constraints/{id}` - Delete constraint
- `POST /api/attendance-constraints/validate` - Validate attendance against constraints
- `GET /api/attendance-constraints/violations` - List constraint violations with filtering
- `POST /api/attendance-constraints/violations/{id}/resolve` - Resolve violation
- `POST /api/attendance-constraints/violations/{id}/dismiss` - Dismiss violation
- `GET /api/attendance-constraints/statistics` - Get constraint statistics

#### Reporting Endpoints
- `GET /api/reports/attendance` - Generate attendance reports with filtering
- `GET /api/reports/overtime` - Generate overtime reports with filtering
- `GET /api/reports/leave` - Generate leave utilization reports with filtering
- `GET /api/reports/violations` - Generate constraint violation reports with filtering
- `GET /api/statistics/attendance` - Get attendance statistics

### 6.8. Administration Module
*   **Standard Reports:**
    *   Daily/Weekly/Monthly Attendance Report (total hours, overtime, lateness, absences).
    *   Leave Taken Report (by type, employee, department).
    *   Leave Balance Report.
    *   Overtime Report.
*   **Customizable Filters:** Date range, employee, department, leave type, status.
*   **Export Options:** CSV, PDF.

### 6.8. Notifications
*   In-app notifications for:
    *   Leave request submission confirmation.
    *   Leave request status updates (approved, rejected).
    *   Reminders for pending approvals (Supervisors/HR).
    *   (Optional) System announcements related to attendance/leave.

## 7. Acceptance Criteria
*   All user stories listed above are implemented and function as described.
*   The React frontend successfully integrates with all specified Contsrix_Core_Api endpoints for attendance and leave management (using DTOs and JSON presenter patterns).
*   The UI is fully responsive, providing an optimal viewing experience on desktops, tablets, and mobile web browsers (latest two versions of Chrome, Firefox, Safari, Edge).
*   Role-based access control (RBAC) is strictly enforced: users can only see and perform actions appropriate to their role (Employee, Supervisor, HR).
*   Data displayed on the frontend is consistently accurate and reflects real-time backend data.
*   Application performance is acceptable: page load times under 3 seconds, API interactions feel responsive.
*   Error handling is robust: clear, user-friendly error messages are displayed for API errors or validation failures.
*   All forms include client-side validation complementing backend validation.
*   The application is secure, protecting against common web vulnerabilities (XSS, CSRF).
*   Code coverage for unit and integration tests meets the project's defined threshold (e.g., >80%).

## 8. Success Metrics
*   High user adoption rate (>90% of employees actively using the system within 3 months post-launch).
*   Reduction in time spent by HR/Supervisors on manual attendance tracking and leave processing by at least 30%.
*   Decrease in attendance-related errors and discrepancies by >50%.
*   Average leave request approval time reduced to <24 hours.
*   Positive user satisfaction scores (e.g., >4/5 in user surveys).

## 9. Technical Considerations / Constraints
*   **Framework:** React (latest stable version, e.g., React 18+).
*   **State Management:** Redux Toolkit with slice pattern for modular state management.
*   **Form Management:** Formik with Yup validation schemas that mirror backend Form Request validation rules.
*   **API Client:** Axios with interceptors for authentication, error handling, and response transformation.
*   **UI Component Library:** Material-UI (MUI) v5+ for consistent design system implementation.
*   **Styling:** Tailwind CSS or Material UI (MUI). Decision based on desired design flexibility vs. pre-built components.
*   **API Communication:** Axios for HTTP requests, with interceptors for JWT token handling and error management.
*   **Authentication & Authorization:** Integrate seamlessly with Contsrix_Core_Api's JWT mechanism. Frontend will store tokens securely (e.g., HttpOnly cookies if SSR is involved, or secure browser storage for SPA).
*   **Routing:** React Router (latest stable version).
*   **Build Tool:** Vite (preferred for speed) or Create React App.
*   **Forms:** React Hook Form or Formik for efficient form handling and validation.
*   **Testing:** Jest for unit tests, React Testing Library for component/integration tests. Cypress or Playwright for E2E tests.
*   **Architecture:** Single Page Application (SPA).
*   **Design:** Component-based architecture, promoting reusability and maintainability. Adherence to SOLID principles where applicable in frontend context.
*   **Accessibility:** Strive for WCAG 2.1 AA compliance.
*   **Code Quality:** ESLint, Prettier, TypeScript (strongly recommended over JavaScript for type safety and maintainability).
*   **Deployment:** Dockerized container, CI/CD pipeline.

## 10. Implementation Roadmap

### 10.1. Phase 1: Core Functionality (Sprint 1-2)
* Authentication and user profile
* Dashboard with key metrics
* Basic attendance management (clock in/out, history view)
* Simple leave request submission and approval

### 10.2. Phase 2: Advanced Features (Sprint 3-4)
* Complete leave management system
* Team attendance views for supervisors
* Basic reporting capabilities
* Notification system

### 10.3. Phase 3: Administrative Features (Sprint 5-6)
* Attendance constraints management
* Advanced reporting and analytics
* HR administrative functions
* System configuration

### 10.4. Phase 4: Optimization and Enhancement (Sprint 7-8)
* Performance optimization
* UX improvements based on user feedback
* Advanced filtering and search capabilities
* Integration with other system modules

## 11. Out of Scope
*   Direct payroll processing or integration (system will provide data for payroll).
*   Advanced Business Intelligence (BI) or predictive analytics dashboards.
*   Native mobile application (this is covered by a separate PRD for Flutter).
*   Offline functionality for the web application.
*   Shift scheduling or rostering features (may be a future module).

## 12. Open Questions / Points for Discussion
*   Are there existing UI/UX design guidelines, mockups, or a design system for Contsrix products to adhere to?
*   Specific requirements for geolocation capture (accuracy, user consent mechanism, storage)?
*   Detailed notification preferences (e.g., email notifications in addition to in-app)?
*   Are there any existing shared React component libraries within New Vision 2050 that should be leveraged?
*   What are the specific data retention policies for attendance and leave records that the frontend might need to consider (e.g., for displaying historical data)?
*   Branding guidelines (logos, color schemes)?

## 13. Next Steps
* Review and finalize the PRD with all stakeholders.
* Begin designing the UI/UX based on the agreed-upon design system and branding guidelines.
* Start implementing the core functionality in Phase 1.

## 14. Changelog

| Date | Version | Author | Changes |
|------|---------|--------|--------|
| 2025-06-18 | 1.0 | abou7agar | Initial document creation |
| 2025-06-21 | 1.1 | abou7agar | Enhanced with implementation details, architecture approach, constraints management, implementation roadmap |
