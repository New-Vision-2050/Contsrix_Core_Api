# Product Requirements Document: Employee Attendance System - Flutter Mobile App

## 1. Task/Feature Title
Employee Attendance System - Flutter Mobile App

## 2. Requester
USER (via Cascade AI)

## 3. Date
**Created:** 2025-06-18  
**Last Updated:** 2025-06-21

## 4. Problem Statement / Goal
*   Provide a convenient and efficient native mobile experience (iOS and Android) for employees to perform essential attendance and leave management tasks on the go.
*   Enable supervisors to quickly manage team leave requests from their mobile devices.
*   **API Integration:** RESTful API integration with the Contsrix_Core_Api backend following these patterns:
    * Response handling aligned with backend Json::item() and Json::items() format
    * Request payloads structured to match backend Form Request expectations
    * Proper handling of pagination metadata from backend responses
    * JWT token management with automatic refresh capabilities
*   Focus on core functionalities suitable for a mobile context, prioritizing ease of use and quick interactions.

## 5. Target Audience / User Stories

### As an Employee:
*   I want to quickly clock in and clock out using a simple tap on my mobile device, so I can record my work time accurately, especially when away from a desktop.
*   I want to be able to start and end my breaks from the app with minimal effort.
*   I want to view my current attendance status (e.g., Clocked In, On Break) and my work duration for the current day at a glance.
*   I want to access a summary of my attendance history (e.g., weekly/monthly view) on my mobile.
*   I want to submit leave requests easily through the app, selecting dates and leave type, and optionally adding a reason or attachment.
*   I want to view the status of my submitted leave requests and any associated comments.
*   I want to receive push notifications on my mobile device for important updates, such as leave request approvals/rejections or reminders (e.g., to clock out).
*   (Optional) I want to use biometric authentication (fingerprint/face ID) for quick and secure login to the app.
*   (Optional) I want the app to support offline clock-in/out, queueing the action if I have no internet, and syncing automatically when connectivity is restored.

### As a Supervisor/Manager:
*   I want to receive push notifications when a team member submits a leave request that requires my approval.
*   I want to be able to view pending leave requests for my team members directly on my mobile app.
*   I want to quickly approve or reject leave requests from my team members via the app, with an option to add comments, so I can respond promptly even when not at my desk.
*   (Optional) I want a simplified view of my team members' current clock-in status (e.g., who is currently working).

## 6. Proposed Solution / Key Features

### 6.0. Architecture & Implementation Approach

#### Mobile App Architecture
* **State Management:** BLoC pattern for separation of UI, business logic, and data layers
* **Dependency Injection:** GetIt for service locator pattern and dependency management
* **API Integration:** Dio HTTP client with interceptors for JWT handling
* **Local Storage:** Secure storage for authentication tokens and offline data
* **Code Organization:** Feature-based folder structure aligning with backend modules

#### API Integration Strategy
* **API Client:** Dio-based client with interceptors for authentication and error handling
* **Response Handling:** Standardized handling of Json responses from backend
* **DTO Mapping:** Mobile models matching backend DTOs for consistent data structure
* **Error Handling:** Comprehensive error handling for API failures with user-friendly messages
* **Offline Support:** Local caching of essential data with synchronization capabilities

### 6.1. Home/Dashboard Screen
*   Prominent Clock In/Out button (changes contextually based on current status).
*   Display of current attendance status (e.g., "Clocked In since 9:00 AM", "On Break").
*   Summary of today's work duration.
*   Quick access to start/end breaks.
*   Indicator for pending leave requests or notifications.

### 6.2. Clock In/Out Functionality
*   Single-tap interface for Clock In, Clock Out, Start Break, End Break.
*   Confirmation of action with timestamp.
*   **Optional Features (configurable by admin, require user permissions):**
    *   **Geolocation Capture:** Record GPS coordinates on clock-in/out events.
    *   **Photo Capture:** Capture a selfie on clock-in/out for verification (primarily for field staff).
    *   **Offline Mode:** Allow clock-in/out actions when the device is offline. Actions are timestamped locally and synced with the server once connectivity is restored. Visual indicator for pending sync items.

### 6.3. Attendance View (Employee)
*   **Daily Log:** View clock-in/out times and break times for the current day and past days.
*   **Monthly Summary:** A simple calendar or list view showing daily status (Present, On Leave, Holiday, Absent) for the current/past months.

### 6.4. Leave Management (Employee & Supervisor)
*   **Submit Leave Request (Employee):**
    *   User-friendly form to select leave type, start and end dates (using native date pickers), and enter a reason.
    *   Option to attach files (photos from gallery/camera or documents) if required by leave type.
*   **View Leave Requests (Employee):**
    *   List of submitted leave requests with their status (Pending, Approved, Rejected).
    *   Ability to cancel pending requests.
*   **Leave Approval (Supervisor):**
    *   Dedicated section listing pending leave requests from team members.
    *   View request details (employee name, dates, type, reason, attachments).
    *   Simple Approve/Reject buttons. Option to add a comment, especially for rejections.

### 6.5. Profile/Settings
*   View basic user information.
*   Manage notification preferences within the app.
*   Option to enable/disable biometric login (if supported).
*   Logout functionality.
*   About section with app version and support contact.

### 6.6. API Endpoints

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

#### Leave Management Endpoints
- `GET /api/leave-types` - List available leave types
- `GET /api/leave-balance` - Get employee leave balance
- `POST /api/leave-requests` - Submit new leave request
- `GET /api/leave-requests` - List leave requests with pagination
- `GET /api/leave-requests/{id}` - Get specific leave request details
- `PUT /api/leave-requests/{id}` - Update leave request
- `DELETE /api/leave-requests/{id}` - Cancel leave request

#### Supervisor Endpoints
- `GET /api/attendance/team` - Get team attendance (for supervisors)
- `GET /api/leave-requests/pending-approvals` - View pending leave requests requiring approval
- `POST /api/leave-requests/{id}/approve` - Approve leave request
- `POST /api/leave-requests/{id}/reject` - Reject leave request

#### Attendance Constraints Endpoints
- `POST /api/attendance-constraints/validate` - Validate attendance against constraints
- `GET /api/attendance-constraints/violations` - List constraint violations for current user

### 6.7. Push Notifications
*   **For Employees:**
    *   Leave request submitted/approved/rejected.
    *   (Optional) Reminders to clock in/out if not done by a certain time (based on work schedule, if available).
*   **For Supervisors:**
    *   New leave request submitted by a team member requiring approval.

### 6.7. Attendance Constraints Compliance
* **Pre-Clock Validation:**
  * Validate attendance actions against applicable constraints before submission
  * Show clear feedback when constraints would be violated
  * Provide override request workflow for exceptional situations

* **Constraint Violation Handling:**
  * Receive and display constraint violation notifications
  * Allow employees to provide explanations for violations
  * Track violation status and resolution process

* **Compliance Dashboard:**
  * Personal compliance status overview
  * Historical compliance metrics and trends
  * Actionable insights to improve compliance

### 6.8. Security
*   Secure login using credentials from Contsrix_Core_Api.
*   Secure storage of JWT/auth tokens (e.g., using `flutter_secure_storage`).
*   (Optional) Biometric authentication (Fingerprint/Face ID) integration using `local_auth` or similar.

## 7. Acceptance Criteria
*   All specified user stories for the mobile app are implemented and function correctly on both iOS and Android target devices/OS versions.
*   The app successfully integrates with all relevant Contsrix_Core_Api attendance and leave management endpoints.
*   UI is intuitive, responsive, and adheres to platform-specific design guidelines (Material Design for Android, Cupertino for iOS) while maintaining a consistent brand feel.
*   Role-based access control is correctly implemented (features for supervisors are not accessible to regular employees).
*   Data displayed in the app is accurate and synchronized with the backend in a timely manner.
*   App performance is smooth, with quick load times and responsive interactions.
*   Push notifications are reliably delivered and handled correctly by the app.
*   Offline functionality (if implemented) correctly queues actions and syncs with the server upon reconnection, handling potential conflicts gracefully.
*   Biometric authentication (if implemented) is secure and user-friendly.
*   Error handling is robust, providing clear messages to the user for network issues or API errors.
*   App functions correctly across various screen sizes and resolutions typical for smartphones.
*   Code coverage for unit and widget tests meets the project's defined threshold (e.g., >75%).

## 8. Success Metrics
*   High adoption rate among employees, particularly those who are frequently mobile or work in the field (>70% of eligible employees using the app regularly within 3 months).
*   Measurable increase in the convenience and speed of clocking in/out.
*   Faster turnaround time for leave request approvals by supervisors using the mobile app.
*   Positive user feedback and high ratings on app stores (e.g., >4.0 stars).
*   Reliable offline data synchronization with minimal data loss or conflicts (if offline feature is implemented).

## 9. Technical Considerations / Constraints
*   **Framework:** Flutter (latest stable version).
*   **State Management:** BLoC pattern with flutter_bloc package.
*   **API Client:** Dio HTTP client with interceptors for authentication, error handling, and response transformation.
*   **Local Storage:** flutter_secure_storage for sensitive data and Hive for offline data storage.
*   **Form Validation:** Implement validation rules matching backend Form Request validation.
*   **Target Platforms:** iOS (e.g., version 12+) and Android (e.g., API level 21+ Lollipop).
*   **State Management:** BLoC/Cubit or Riverpod (Provider might be too simple for growing complexity, especially with offline sync).
*   **API Communication:** `dio` package for HTTP requests, with interceptors for JWT token handling, logging, and error management.
*   **Authentication:** Integrate with Contsrix_Core_Api's JWT. Secure token storage using `flutter_secure_storage`.
*   **Error Handling:** Standardized error handling for API errors that properly processes CustomException responses from the backend.
*   **Navigation:** `go_router` for declarative routing.
*   **Local Storage (for offline mode):** `sqflite` for structured data or `isar` / `hive` for NoSQL-like storage. `isar` is often preferred for complex querying and speed.
*   **Push Notifications:** Firebase Cloud Messaging (FCM).
*   **Biometric Authentication:** `local_auth` package.
*   **Permissions Handling:** Use packages like `permission_handler` for managing permissions (location, camera, notifications).
*   **Testing:** `flutter_test` for unit and widget tests, `integration_test` for E2E testing.
*   **Architecture:** Clean Architecture, Feature-First, or similar well-structured approach to organize code for maintainability and scalability.
*   **Build & Distribution:** CI/CD pipeline for automated builds and deployment to app stores (e.g., Codemagic, GitHub Actions with Fastlane).
*   **Code Quality:** Effective Dart linting rules, strong mode, null safety.

## 10. Implementation Roadmap

### 10.1. Phase 1: Core Functionality (Sprint 1-2)
* Authentication and user profile
* Clock in/out functionality with basic validation
* View attendance status and history
* Simple leave request submission

### 10.2. Phase 2: Advanced Features (Sprint 3-4)
* Offline mode support
* Push notifications integration
* Complete leave management workflow
* Biometric authentication

### 10.3. Phase 3: Compliance and Optimization (Sprint 5-6)
* Attendance constraints compliance features
* Performance optimization
* Enhanced security features
* UX improvements based on user feedback

### 10.4. Phase 4: Integration and Enhancement (Sprint 7-8)
* Integration with device features (camera, location)
* Advanced offline capabilities
* Analytics and reporting features
* Final polishing and stabilization

## 11. Out of Scope
*   Full administrative features available on the React web frontend (e.g., managing leave types, company-wide attendance policies, comprehensive reporting). The mobile app focuses on core employee and supervisor tasks.
*   Advanced reporting or analytics (users will be directed to the web frontend for these).
*   Tablet-specific UI optimizations (initial focus on phone layouts).
*   Web version of the Flutter app (focus on native mobile).

## 11. Open Questions / Points for Discussion
*   What is the relative priority of optional features like offline mode, biometric authentication, geolocation, and photo capture? Should they be in V1 or later iterations?
*   Are there specific UI design mockups or branding guidelines for the mobile app?
*   What are the exact minimum iOS and Android OS versions to be supported?
*   Are there any Mobile Device Management (MDM) policies or security constraints that the app needs to comply with?
*   How should data conflicts be handled if offline actions contradict server-side changes made while the device was offline?
*   Specific requirements for error logging or crash reporting from the mobile app (e.g., Sentry, Firebase Crashlytics)?

## 12. Next Steps

1. **Review and Approval:** Stakeholder review of this PRD
2. **UI/UX Design:** Create detailed wireframes and UI designs for the mobile app
3. **Technical Specification:** Detailed technical design document
4. **Development Planning:** Sprint planning and task breakdown
5. **Implementation:** Begin development following the implementation roadmap
6. **Testing:** Comprehensive testing on various devices and OS versions
7. **Deployment:** Beta testing followed by app store submission

---

**Document Status:** Draft  
**Version:** 1.1  
**Last Updated By:** abou7agar

---

*This PRD serves as the foundation for the Employee Attendance System Flutter Mobile App development. All stakeholders should review and provide feedback before development begins.*

---

## 13. Changelog

| Date | Version | Author | Changes |
|------|---------|--------|--------|
| 2025-06-18 | 1.0 | abou7agar | Initial document creation |
| 2025-06-21 | 1.1 | abou7agar | Enhanced with implementation details, architecture approach, constraints compliance, implementation roadmap |
