# ARIES-ONSITE (Onsite Enrollment System)

**ARIES-ONSITE** is a web-based Basic Education (K–10 and Senior High School) onsite student registration and admissions management platform built for **FEU Alabang** and **FEU Diliman**.

## Project Overview

ARIES-ONSITE streamlines the end-to-end onsite admissions workflow—from applicant registration and document submission to administrative verification, compliance tracking, payment/exam recording, and printable student summary exports—while maintaining strict campus isolation and database-level audit integrity.

## Core Features

### Multi-Campus Operations
* **Campus-Specific Portals & Dashboards:** Dedicated public enrollment flows and administrative panels for **FEU Alabang** and **FEU Diliman**.
* **Isolated Campus Databases:** Dynamic campus database routing (`CampusDatabaseSwitcher`) separating Alabang and Diliman applicant records, configurations, and associate employee authentication databases.

### Student Registration & Admissions Workflow
* **K–10 & SHS Application Funnel:** Captures personal information, standardized Philippine address hierarchy (Region, Province, City/Municipality, Barangay), citizenship/religion/country lookups, educational history, parent/guardian details, FEU-enrolled siblings, and foreign passport records.
* **Automated Student ID & Notifications:** Generates campus student numbers (`StudentIdGenerator`) and dispatches admissions email notifications (`AdmissionsEmailService`).
* **Document Compliance & Promissory Tracking:** Supports per-student-type document requirements, file validation/uploads, promissory deadlines, and automated compliance status synchronization (`DocumentComplianceService`).

### Administrative Management & Reporting
* **Dynamic Analytics Dashboard:** Tracks registration volumes, K–10 and SHS track/strand breakdowns, and marketing/discovery survey metrics dynamically from campus configurations while preserving historical counts for disabled options.
* **Applicant Review & Editing:** Server-side paginated registration directory with search/filtering, in-place applicant editing, sibling management, exam/payment status controls, and per-document action menus (Upload, Replace, Waive/Unwaive, and Soft Delete).
* **PDF Student Summary Export:** Generates formatted applicant summary sheets (personal info, family/guardian details, FEU siblings, education history, submitted documents, and general info) via **Dompdf**.

### Configurable Campus Modules
* **Document Setup:** Manage required admission documents and allowed file formats per applicant type.
* **School Year Management:** Configure active enrollment school years per campus.
* **SHS Tracks & Strands:** Manage selectable academic tracks/strands with active toggles and soft deletion.
* **Discovery Survey Options:** Manage marketing/discovery survey options with active toggles and soft deletion.

### Security, Access Control & Data Integrity
* **Role-Based Access Control (RBAC):** Tiered admin roles (`Master Admin`, `Senior Admin`, `Staff Admin`) and granular module permissions (`Registrations`, `Finance`, `Documents`, `School Year`, `Tracks`, `Survey Options`, `Admin Access`) integrated with associate employee accounts.
* **Application Hardening:** CSRF protection, login throttling, campus access subscribers, and no-cache response headers on protected routes.
* **Soft Deletes:** Administrative deletions across documents, applicant siblings, tracks, and survey options use `is_deleted` soft-delete flags to preserve historical records and dashboard accuracy.
* **Tamper-Evident Audit Trail:** Combines Symfony event subscribers (`DatabaseAuditSubscriber`) with database triggers and dedicated `Audit*` tables to log every modification with the acting admin ID and flag unauthorized direct database edits (`BACKDOOR`).

## Technology Stack

* **Backend:** PHP 8.2+, Symfony 7.4
* **Database & ORM:** MySQL / MariaDB with Doctrine ORM 3.6 & Doctrine DBAL
* **Frontend:** Twig, Tailwind CSS (`symfonycasts/tailwind-bundle`), Stimulus, Symfony UX Turbo
* **PDF Export:** Dompdf (`dompdf/dompdf`)
* **Messaging & Security:** Symfony Mailer, Symfony Security Bundle, Rate Limiter, CSRF
