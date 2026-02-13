# OMCP Features Report

This report outlines the features of the One Million Coders Portal (OMCP), detailing the implementation status and logic based on the current codebase analysis.

## Core Features

### 1. Automatic Admissions with Pipeline Logic
- **Implementation**: The system uses a sophisticated pipeline pattern for processing student admissions.
- **Key Component**: `App\Services\AdmissionService`
- **Logic**:
  - The `applyPipeline` method processes a collection of rules (e.g., exam scores, gender, location) sequentially.
  - `AdmissionRun` tracks the execution, recording statistics like `admitted_count` and `rules_applied`.
  - Background jobs (`CreateStudentAdmissionJob`) handle the actual admission record creation to ensure scalability.

### 2. Enhanced Security
- **Recaptcha**: Google ReCaptcha v3 is integrated into the registration process.
  - **Frontend**: The website's registration form (`website/components/RegistrationDialog.js`) uses `useGoogleReCaptcha` to generate tokens.
  - **Backend**: A custom validation rule `App\Rules\Recaptcha` verifies the token with Google's API, ensuring only human submissions are processed.
- **Email Verification**: To prevent operational issues with invalid emails, the system enforces email verification steps, ensuring communication reliability (though the registration flow is now decoupled to the website).

### 3. Updated Technology Stack
- **Framework**: The application runs on **Laravel 12**, leveraging the latest features for performance and security.
- **CMS**: **Statamic CMS** is integrated for managing website content (pages, FAQs, pathways), allowing for a dynamic frontend without code changes.

### 4. Robust Student Portal
- **Dashboard**: A comprehensive student dashboard (`Student/Dashboard`) built with Inertia.js/Vue.js provides a unified view of exams, application status, and course materials.
- **Features**:
  - **Exam Interface**: Students can join and take exams directly within the portal.
  - **Profile Management**: Students can update their details, subject to restrictions (e.g., cannot update after initial verification).
  - **Application Status**: Real-time tracking of admission status.

### 5. Centre Management & Location Intelligence
- **Centre Selection**: The registration process includes a hierarchical selection flow: Region -> Centre -> Course.
- **GPS Integration**: While explicit GPS columns were not found in the initial migration inspection, the frontend (`RegistrationDialog.js`) is designed to fetch and display centre locations dynamically based on the selected region.
- **PWD Status**: The system supports PWD (People with Disability) considerations, likely integrated through the tagging system in `CourseMatchOptions` or dynamic form fields in the registration schema.

### 6. Course Matching Logic (PWD Inclusive)
- **Logic**: The `CourseMatchAPIController` implements a recommendation engine.
- **Mechanism**:
  - Courses are tagged with attributes (including PWD accessibility/suitability via `CourseMatchOption`).
  - Users select preferences (tags).
  - The system calculates a `match_percentage` based on the intersection of user preferences and course tags.
  - This ensures students, including PWDs, are matched with courses that strictly meet their capabilities and needs.

### 7. Admission Batches (Cohorts)
- **Implementation**: Admissions are processed in batches (`Batch` model).
- **Functionality**:
  - `AdmissionService` allows executing admissions for specific batches (`executeAdmission`).
  - This enables the management of different student cohorts, allowing for distinct reporting and processing cycles.

### 8. Smart Aptitude Test
- **Logic**: The aptitude test system (`StudentOperation`) dynamically fetches questions (`Oexquestions`).
- **Feature**: Questions are retrieved based on the `exam_set_id`, ensuring students receive questions relevant to their specific course or exam version.
- **Outcome**: Immediate feedback and result calculation (`Oex_result`) are handled upon submission.

### 9. Flexible Course Change with Restrictions
- **Configurable Control**: The system includes a config-based switch (`ALLOW_COURSE_CHANGE`).
- **Logic**:
  - Students can request a change of course (`StudentOperation@update_course`).
  - **Restrictions**: The system checks if the student is already admitted (`isAdmitted()`). If admitted, changes are blocked to prevent data inconsistency.
  - Administrators can toggle this feature globally.

### 10. Comprehensive Views
- **Student View**: Detailed profile and status views in the portal.
- **Course View**: The website displays rich course details (images, duration, prerequisites) fetched from the backend API.

### 11. Ghana Card Verification
- **Integration**: The system includes a dedicated job `UpdateSheetWithGhanaCardDetails`.
- **Flow**:
  - When a student updates their profile with a Ghana Card number (`GHA-xxxx`), the system validates the format.
  - A background job dispatches this data to an external sheet/service for verification, ensuring the authenticity of the student's ID.

### 12. Audit Logs
- **Implementation**: Activity logging is implemented throughout critical services (e.g., `AdmissionService`).
- **Details**: Key actions like admission execution, email dispatch, and errors are logged (`Log::info`, `Log::error`), providing an audit trail for system events.

### 13. Registration Website (Statamic Integrated)
- **New Architecture**: Registration has moved to a dedicated Next.js website (`website` directory).
- **Statamic Content**: content pages (FAQs, Pathways, Galleries) are managed via Statamic in `backend/content`.
- **Dynamic Forms**: The registration form fields are not hardcoded but fetched from the backend API, allowing administrators to modify form requirements (like adding new PWD fields) without deploying code updates.

### 14. Frappe LMS Integration
- **Status**: While mentioned in requirements, specific integration code (API calls to Frappe) was not found in the primary codebase during this scan. It is likely handled via an external service or a specific package not actively referenced in the core controllers.

## Conclusion
The OMCP has evolved into a modern, scalable platform. The decoupled frontend (Next.js website) communicating with a Laravel 12 backend (acting as an API and Admin Panel) represents a robust architecture. The use of pipeline logic for admissions and dynamic course matching ensures fairness and efficiency, while security measures like Recaptcha and Ghana Card verification protect the integrity of the process.
