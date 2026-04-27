# ARIES-ONSITE (Onsite Enrollment System)

Welcome to the **ARIES-ONSITE** project repository! This system is a centralized, web-based enrollment application tailored for **FEU Alabang** and **FEU Diliman**. 

## 1. Details of the Project
The ARIES-ONSITE application is designed to streamline the onsite student registration and enrollment workflow. It serves as a unified platform for administrative management, providing essential tools to process applicants seamlessly across multiple campuses.

### Core Features
*   **Multi-Campus Operations:** Dedicated administrative dashboards and application funnels tailored specifically for the records and processes of FEU Alabang and FEU Diliman.
*   **Comprehensive Student Registration:** Facilitates the capture of essential applicant details, academic program choices, and securely handles required document uploads.
*   **Data Integrity & Security:** Contains highly advanced tracking through dynamic database triggers coupled with a robust audit trail mechanism. It tracks all updates and strictly distinguishes between legitimate edits made via the system and direct tampering/changes made at the database backdoor level.
*   **Performance Optimization:** Engineered for high responsiveness during peak enrollment volume surges, utilizing server-side pagination, strict file upload constraints, and index-optimized database search queries.

## 2. Specifics and Requirements

To run this project, ensure your environment meets the following software architectural structural requirements.

### Technology Stack
*   **Backend Framework:** Symfony 7.4.5
*   **Programming Language:** PHP 8.2 (or higher)
*   **Database Management:** MySQL or MariaDB (Integrated via Doctrine ORM)
*   **Frontend Technologies:**
    *   Twig Templating Engine
    *   Tailwind CSS (Compiled via `symfonycasts/tailwind-bundle`)

### System Prerequisites
*   **PHP:** Version >= 8.2 (Required Extensions: `ctype`, `iconv`, `pdo_mysql`)
*   **Composer:** Needed for managing PHP library dependencies properly.
*   **Database Server:** XAMPP, WAMP, or any dedicated local MySQL/MariaDB server instance.
*   **Git:** For version control and cloning the repository base.
*   **Symfony CLI:** (Optional but highly recommended for launching a robust local development server)

## 3. Complete Instructions on How to Install

Follow these step-by-step instructions to get the application running fully functional on your local machine.

### Step 1: Clone the Repository
Open your terminal or command prompt window and clone the project to your chosen local directory:
```bash
git clone https://github.com/iAmMigs/ARIES-ONSITE.git
cd ARIES-ONSITE
```

### Step 2: Install PHP Dependencies
Run Composer to read the `composer.lock` file and install all necessary vendor libraries and packages:
```bash
composer install
```

### Step 3: Configure Environment Variables
You need to establish your local environment file. This file contains critical sensitive details such as your database connection string.
1. Duplicate the templated environment file `.env` and name the copy `.env.local`:
   ```bash
   cp .env .env.local
   ```
2. Open `.env.local` in your preferred code editor.
3. Locate the `DATABASE_URL` variable line. Update it accurately with your current local database credentials using the following structure format:
   ```dotenv
   DATABASE_URL="mysql://username:password@127.0.0.1:3306/aries_db?serverVersion=10.11.2-MariaDB&charset=utf8mb4"
   ```
   *(Critical Note: Ensure your configured database user account has administrative privileges to create databases AND execute/create TRIGGERS, as the security audit trail relies heavily on them).*

### Step 4: Create and Setup the Database
*(Skip to Step 5 if you were already handed an existing `.sql` database backup).* 
If setting up from scratch, let Doctrine create the database schema and execute migrations for you:
```bash
# 1. Instruct Doctrine to provision the new database
php bin/console doctrine:database:create

# 2. Execute migrations to generate tables, columns, and audit triggers
php bin/console doctrine:migrations:migrate
```

### Step 5: Build Frontend Assets
Compile the Tailwind CSS structural utilities to ensure all responsive grids and visual styles load successfully:
```bash
# Build the Tailwind CSS files
php bin/console tailwind:build

# Compile the final asset mapping
php bin/console asset-map:compile
```

### Step 6: Start the Local Development Server
Launch your server listener so you can interact with the app in your browser:
```bash
# Preferred method using Symfony CLI
symfony server:start

# Alternative method using PHP's standard built-in server utility
php -S localhost:8000 -t public/
```
You can now access the running application by navigating to `http://localhost:8000` (or the port specified by the CLI) via your web browser.

---

## Testing & Useful Developer Commands

The repository comes packaged with built-in sandbox commands that allow you to rapidly populate your database with dummy data logic for testing workflows.

### Populating Mock Data
*   **Create Admin Accounts:** `php bin/console app:create-admins`
*   **Seed Alabang Applicants Mock Data:** `php bin/console app:populate-alabang`
*   **Seed Diliman Applicants Mock Data:** `php bin/console app:populate-diliman`

### Clearing Mock Data
> ⚠️ **WARNING:** These commands will aggressively wipe ALL applicant records for the specified campus. Use exclusively for isolated test environments.
*   **Purge Alabang Data:** `php bin/console app:clear-alabang`
*   **Purge Diliman Data:** `php bin/console app:clear-diliman`

### Validating the Audit Trail
The platform's native event subscriber and triggers pair together to lock down database modification trails. Use these steps to test it: 
1. **Via Dashboard:** Modify a record through the Admin Dashboard UI. The database logs your active Admin ID as the accessor.
2. **Via Third-Party Database Tool (Tampering Context):** Attempt to modify a row data value externally via a database management GUI like PHPMyAdmin or DBeaver. The background SQL trigger will immediately intercept the raw query, log a `NULL` admin ID indicating absence of application-level authentication, and forcefully insert a `BACKDOOR` system remark.
3. **Monitor Logs:** Query your system directly to view these interceptions using: `SELECT * FROM audit_bed_applicants ORDER BY audit_date_time DESC;`
