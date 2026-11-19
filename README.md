# ARIES-ONSITE (FEU Alabang & FEU Diliman Onsite Enrollment System)

**ARIES-ONSITE** is a centralized Symfony web platform and multi-campus Basic Education (K–10 and Senior High School) admissions management system built to streamline onsite student registration, document compliance, administrative verification, and reporting for **FEU Alabang** and **FEU Diliman**.

---

## Features

### Public Enrollment Portals (`/diliman`, `/alabang`)
- **Campus Entry Portals (`/diliman`, `/alabang`)**: Campus-specific landing pages displaying active enrollment periods and routing applicants into the appropriate Basic Education application flow.
- **Guided K–10 & SHS Application Funnel (`/diliman/apply`, `/alabang/apply`)**: Multi-section registration workflow capturing personal details, standardized Philippine address lookups (`/api/address/*` for Region, Province, City/Municipality, and Barangay), citizenship/religion/country lookups, parent/guardian records (including parent email addresses), education history, FEU-enrolled siblings, foreign passport records, and discovery survey sources.
- **Real-Time LRN & Duplicate Pre-Checks**: Validates Learner Reference Numbers (`/apply/api/check-lrn`) and flags potential duplicate applicant submissions (`/apply/api/check-duplicate-applicant`) before record creation.
- **Dynamic Document Uploads & Promissory Agreements**: Renders required admission documents dynamically per applicant type and campus, enforces file type and size constraints, and tracks promissory note deadlines for pending submissions.
- **Automated Student ID & Confirmation (`/apply/success/{studentNumber}`)**: Generates campus student numbers (`StudentIdGenerator`) and sends admissions confirmation emails (`AdmissionsEmailService`).

### Administration Console (`/diliman-admin`, `/alabang-admin`)
- **Campus-Isolated RBAC & Security (`/admin/diliman/login`, `/admin/alabang/login`)**: Authenticated via Symfony Security (`ROLE_ADMIN`) backed by associate employee accounts (`AssocAdminUserProvider`), CSRF protection, login throttling (15 attempts), idle session timeout, and tiered admin levels (**Master Admin**, **Senior Admin**, **Staff Admin**) with granular module permissions (`Registrations`, `Finance`, `Documents`, `School Year`, `Tracks`, `Survey Options`, `Admin Access`).
- **Dynamic Analytics Dashboard (`/diliman-admin/`, `/alabang-admin/`)**:
  - Live KPI summaries for K–10, Senior High School, and document compliance statuses.
  - Dynamic **SHS Track/Strand** and **Discovery Survey** analytics loaded from campus configurations while retaining historical applicant counts for disabled options.
- **Registration Directory & Applicant Management (`/{campus}-admin/registrations`)**:
  - Server-side paginated applicant table with search, filtering, and bulk export (`/{campus}-admin/registrations/export`).
  - Full applicant profile viewer (`view`) and in-place editor (`edit`) featuring exam/payment status controls (with restricted edit mode for Finance-only admins), sibling management (add and soft-delete applied on **Save Changes**), and interactive per-document **Actions** dropdowns (**Upload**, **Replace**, **Waive/Unwaive**, and **Soft Delete**).
- **PDF Student Summary Export (`/{campus}-admin/registration/{id}/export-pdf`)**: Generates formatted, printable applicant summary sheets—including personal info, family/guardian records, FEU siblings, education history, submitted files, and general information—via **Dompdf**.
- **Configurable Campus Modules**:
  - **Document Requirements Setup (`/{campus}-admin/document-setup`)**: Configure required admission documents, allowed formats, and storage folders per applicant type.
  - **School Year Management (`/admin/school-year/{campus}`)**: Activate/deactivate school years, toggle enrollment access, and manage promissory deadlines.
  - **SHS Tracks & Strands (`/admin/tracks/{campus}`)**: Add, update, enable/disable, and soft-delete SHS tracks/strands.
  - **Discovery Survey Options (`/admin/survey/{campus}`)**: Add, edit, toggle, and soft-delete marketing/discovery survey options.
  - **Admin Access Control (`/admin/access/{campus}`)**: Provision campus admins from associate records, assign module access, toggle account status, and transfer Master Admin ownership.

---

## Tech Stack

- **Backend**: PHP 8.2+, Symfony 7.4, Doctrine ORM 3.6, Doctrine DBAL, Doctrine Migrations 3.7
- **Frontend**: Twig, Tailwind CSS (`symfonycasts/tailwind-bundle`), Symfony AssetMapper, Hotwire Stimulus (`@hotwired/stimulus`), Hotwire Turbo (`@hotwired/turbo`), Metronic UI assets
- **PDF & Notifications**: Dompdf 3.1 (`dompdf/dompdf`), Symfony Mailer
- **Security & Reliability**: Symfony Security Bundle, Rate Limiter, CSRF, Lock, Server-Side Pagination
- **Database**: MariaDB 10.4+ / MySQL 8.0+ (`utf8mb4`) with multi-connection campus routing and SQL audit triggers
- **Testing & Tooling**: PHPUnit 11.5, Symfony Web Profiler, Symfony Maker Bundle

---

## Project Structure

```text
ARIES-ONSITE/
├── assets/
│   ├── controllers/            # Stimulus controllers (CSRF, polling, UI helpers)
│   └── styles/                 # Tailwind CSS entrypoint
├── config/
│   ├── packages/               # Doctrine multi-connection, Security, Tailwind, Mailer configs
│   └── routes.yaml             # Attribute-based routing loader
├── migrations/                 # Doctrine database migrations and audit triggers
├── public/
│   ├── css/                    # Custom stylesheets and page styles
│   ├── js/                     # Enrollment, validation, PH address lookup, phone picker, idle timeout
│   ├── metronic/               # Admin theme assets and icons
│   └── uploads/                # Uploaded 2x2 ID photos and dynamic applicant requirement folders
├── src/
│   ├── Command/                # Campus DB setup, admin creation, and sandbox seed/purge commands
│   ├── Controller/
│   │   ├── Api/                # Document setup & associate employee JSON endpoints
│   │   └── *.php               # Campus enrollment, admin dashboards, RBAC, tracks, survey, school year
│   ├── Entity/
│   │   ├── Audit/              # Tamper-evident audit trail entities and shared audit traits
│   │   └── *.php               # Applicant, Guardian, Sibling, Requirement, Lookup, Setup entities
│   ├── EventSubscriber/        # Campus access/login checks, DB audit session binding, no-cache headers
│   ├── Repository/             # Doctrine repositories
│   ├── Security/               # Associate admin user provider, campus entry points, access handlers
│   ├── Service/                # CampusDatabaseSwitcher, DocumentComplianceService, StudentIdGenerator, Mailer
│   └── Twig/                   # Location lookup Twig extensions
├── templates/
│   ├── admin-onsite/           # Campus admin views, PDF summary template, RBAC, survey, tracks, school year
│   ├── emails/                 # Admissions email notification templates
│   ├── enrollment-onsite/      # Campus public enrollment forms and confirmation pages
│   ├── home-onsite/            # Campus landing pages
│   ├── layouts/                # Shared public headers and footers
│   └── security/               # Campus-specific administrator login screens
└── tests/                      # PHPUnit test suite
```

---

## Getting Started

### Prerequisites
- **PHP** `>= 8.2` with extensions `ctype`, `iconv`, `pdo_mysql`, `mbstring`, `intl`, and `xml`
- **Composer**
- **MariaDB** `10.4+` or **MySQL** `8.0+` (database user must have privileges to create databases and execute `CREATE TRIGGER` statements for the audit trail)
- **Symfony CLI** (recommended for local development) or an Apache/Nginx web server

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/iAmMigs/ARIES-ONSITE.git
   cd ARIES-ONSITE
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Configure local environment variables**
   Create an uncommitted `.env.local` file in the project root to override defaults without committing secrets:
   ```dotenv
   APP_ENV=dev
   APP_SECRET=<generate-a-secure-random-secret>

   DATABASE_URL="mysql://<db_user>:<db_password>@127.0.0.1:3306/aries_diliman_db?serverVersion=10.4.32-MariaDB&charset=utf8mb4"
   DILIMAN_DATABASE_URL="mysql://<db_user>:<db_password>@127.0.0.1:3306/aries_diliman_db?serverVersion=10.4.32-MariaDB&charset=utf8mb4"
   ALABANG_DATABASE_URL="mysql://<db_user>:<db_password>@127.0.0.1:3306/aries_alabang_db?serverVersion=10.4.32-MariaDB&charset=utf8mb4"

   ASSOC_DATABASE_URL="mysql://<db_user>:<db_password>@127.0.0.1:3306/aries_diliman_assoc_login?serverVersion=10.4.32-MariaDB&charset=utf8mb4"
   DILIMAN_ASSOCIATES_DATABASE_URL="mysql://<db_user>:<db_password>@127.0.0.1:3306/aries_diliman_associates?serverVersion=10.4.32-MariaDB&charset=utf8mb4"
   ALABANG_ASSOCIATES_DATABASE_URL="mysql://<db_user>:<db_password>@127.0.0.1:3306/aries_alabang_associates?serverVersion=10.4.32-MariaDB&charset=utf8mb4"
   DILIMAN_ASSOC_LOGIN_DATABASE_URL="mysql://<db_user>:<db_password>@127.0.0.1:3306/aries_diliman_assoc_login?serverVersion=10.4.32-MariaDB&charset=utf8mb4"
   ALABANG_ASSOC_LOGIN_DATABASE_URL="mysql://<db_user>:<db_password>@127.0.0.1:3306/aries_alabang_assoc_login?serverVersion=10.4.32-MariaDB&charset=utf8mb4"

   MAILER_DSN=null://null
   ```

4. **Create the databases and run migrations**
   *(Skip if restoring directly from an existing campus `.sql` backup.)*
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```
   To provision and mirror both campus schemas (`aries_diliman_db` and `aries_alabang_db`) along with audit tables and triggers:
   ```bash
   php bin/console app:setup-campus-databases
   ```

5. **Install AssetMapper packages and build Tailwind CSS**
   ```bash
   php bin/console importmap:install
   php bin/console tailwind:build
   php bin/console asset-map:compile
   ```
   During active development, you can watch for CSS changes with:
   ```bash
   php bin/console tailwind:build --watch
   ```

6. **Ensure upload directories are writable**
   Verify that the web server has write permissions for:
   - `public/uploads/onsite-id-pics/`
   - `public/uploads/` (for dynamic requirement folders configured under Document Setup)

7. **Provision Administrator accounts**
   Seed initial campus administrator accounts using the built-in command:
   ```bash
   php bin/console app:create-admins
   ```
   Once signed in at `/admin/diliman/login` or `/admin/alabang/login`, additional admins and module permissions can be managed through **Admin Access**.

8. **Start the local development server**
   ```bash
   symfony server:start
   ```
   Or using PHP's built-in server:
   ```bash
   php -S 127.0.0.1:8000 -t public
   ```

---

## Data Integrity, Soft Deletes & Audit Trail

- **Soft-Delete Preservation**: Administrative deletions across applicant requirements (`ApplicantBedRequirement`), applicant siblings (`ApplicantBedSibling`), SHS tracks (`TrackSetup`), and discovery survey options (`SurveyOption`) set `is_deleted = 1` instead of removing rows, protecting historical applicant records and dashboard reporting accuracy.
- **Tamper-Evident Audit Trail**: `DatabaseAuditSubscriber` binds the authenticated admin identity to the database session while SQL triggers record mutations into dedicated `audit_*` tables (such as `audit_bed_applicants`).
  - Edits made via the Admin Console log the active **Admin ID**.
  - Direct external modifications made through a SQL GUI (e.g., phpMyAdmin or DBeaver) lack an authenticated session context and are automatically intercepted with a `NULL` admin ID and a `BACKDOOR` remark.
  - Inspect audit entries with:
    ```sql
    SELECT * FROM audit_bed_applicants ORDER BY audit_date_time DESC;
    ```

---

## Developer Commands & Testing

### Sandbox Data Commands
- **Seed Alabang Mock Applicants**: `php bin/console app:populate-alabang`
- **Seed Diliman Mock Applicants**: `php bin/console app:populate-diliman`

> **Warning:** The purge commands below permanently delete applicant records for the specified campus. Use exclusively in isolated local or testing environments.
- **Clear Alabang Applicants**: `php bin/console app:clear-alabang`
- **Clear Diliman Applicants**: `php bin/console app:clear-diliman`

### Running Tests
Execute the PHPUnit test suite with:
```bash
php bin/phpunit
```

---

## Routes Reference

| Route | Access | Description |
| :--- | :--- | :--- |
| `/diliman`, `/alabang` | Public | Campus-specific enrollment landing pages |
| `/diliman/apply`, `/alabang/apply` | Public | Basic Education (K–10 & SHS) online/onsite registration forms |
| `/diliman/apply/success/{studentNumber}` | Public | Diliman registration confirmation & generated Student ID view |
| `/alabang/apply/success/{studentNumber}` | Public | Alabang registration confirmation & generated Student ID view |
| `/api/address/*` | Public | Cascading Philippine address lookup API (regions, provinces, cities, barangays) |
| `/admin/diliman/login`, `/admin/alabang/login` | Public | Campus-specific administrator login portals |
| `/diliman-admin/`, `/alabang-admin/` | `ROLE_ADMIN` | Campus analytics dashboard (KPIs, dynamic tracks & discovery survey stats) |
| `/diliman-admin/registrations`, `/alabang-admin/registrations` | `ROLE_ADMIN` | Paginated applicant directory, search/filtering, and bulk exports |
| `/{campus}-admin/registration/{id}/view` | `ROLE_ADMIN` | Full applicant profile and compliance summary view |
| `/{campus}-admin/registration/{id}/edit` | `ROLE_ADMIN` | Applicant editor, sibling management, payment/exam tracking, document actions |
| `/{campus}-admin/registration/{id}/export-pdf` | `ROLE_ADMIN` | Printable Dompdf Student Summary export |
| `/{campus}-admin/document-setup` | `ROLE_ADMIN` | Campus document requirement & file format setup |
| `/admin/school-year/{campus}` | `ROLE_ADMIN` | School year activation, enrollment toggle, and promissory deadline setup |
| `/admin/tracks/{campus}` | `ROLE_ADMIN` | SHS track/strand management (add, edit, toggle, soft delete) |
| `/admin/survey/{campus}` | `ROLE_ADMIN` | Discovery survey option management (add, edit, toggle, soft delete) |
| `/admin/access/{campus}` | `ROLE_ADMIN` | Tiered RBAC and module-level administrator access control |

---

## License

Proprietary — developed for **FEU Alabang** and **FEU Diliman**. All rights reserved.
