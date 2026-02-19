# ARIES-ONSITE (Onsite Enrollment System)

A web-based enrollment application for FEU Alabang and FEU Diliman built with Symfony 7. It handles student registration, document uploads, and administrative management, featuring a robust database audit trail system.

## 1. Project Specifications

* **Framework:** Symfony 7.4.5 (Latest)
* **Language:** PHP >= 8.2
* **Database:** MySQL / MariaDB (managed via Doctrine ORM)
* **Frontend:**
    * Twig Templates
    * Tailwind CSS (via `symfonycasts/tailwind-bundle`)
* **Key Libraries:**
    * `doctrine/orm ^3.6` (Data persistence)
    * `symfony/mailer` (Email notifications)
    * `symfony/security-bundle` (Admin authentication)

## 2. Installation & Setup

Follow these steps to set up the project locally.

### Prerequisites
* PHP 8.2 or higher
* Composer
* XAMPP or any SQL Server
* Symfony CLI (optional, but recommended)

### Steps

**1. Clone the Repository**

```bash
git clone https://github.com/iAmMigs/ARIES-ONSITE.git
cd aries-onsite
```

**2. Install Dependencies**

```bash
composer install
```

**3. Configure Environment Variables**

Duplicate the example environment file:

```bash
cp .env .env.local
```

Open `.env.local` and configure your database URL:

```dotenv
# .env.local
DATABASE_URL="mysql://db_user:db_password@127.0.0.1:3306/aries_db?serverVersion=10.11.2-MariaDB&charset=utf8mb4"
```

> **Note:** Ensure your database user has TRIGGER creation privileges, as the audit trail relies heavily on MySQL Triggers.

**4. Database Setup**

Create the database and apply migrations (this includes tables and dynamic audit triggers).

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

**5. Build Assets**

Compile Tailwind CSS and install asset maps.

```bash
php bin/console tailwind:build
php bin/console asset-map:compile
```

**6. Start the Server**

```bash
symfony server:start
# OR using built-in PHP server
php -S localhost:8000 -t public/
```

Access the application at `http://localhost:8000`.

## 3. Useful Commands & Testing

This project includes custom console commands to help you populate data for testing and manage the database.

### Populating Data (Seeding)

Use these commands to generate dummy applicants and admin accounts for testing purposes.

**Create Admin Accounts:**
Generates default admin users for the dashboard.
```bash
php bin/console app:create-admins
```

**Populate Alabang Applicants:**
Seeds the database with test applicants for the Alabang campus.
```bash
php bin/console app:populate-alabang
```

**Populate Diliman Applicants:**
Seeds the database with test applicants for the Diliman campus.
```bash
php bin/console app:populate-diliman
```

### Clearing Mock Data
> **WARNING:** THIS WILL CLEAR ALL APPLICANT DATA FROM THEIR RESPECTIVE TABLES. THIS IS FOR EXPERIMENTAL USE ONLY.

**Clear Alabang Data:**
Removes all applicant records for Alabang.
```bash
php bin/console app:clear-alabang
```

**Clear Diliman Data:**
Removes all applicant records for Diliman.
```bash
php bin/console app:clear-diliman
```

*(Note: Use `php bin/console list` to confirm the exact command signatures if the above alias examples differ from your specific Command class configurations.)*

### Testing Audit Trails

The system uses dynamic Database Triggers paired with a Symfony Event Subscriber (`DatabaseAuditSubscriber`) to log all `UPDATE` actions across the applicant tables, strictly differentiating between application edits and direct database tampering.

1.  **Modify a Record via the App:**
    * Update a student's details via the Admin Dashboard (`/alabang-admin/registration/{id}/edit`).
    * The audit table will log the logged-in admin's ID in the `emp_num` column and leave the `remarks` column `NULL`.

2.  **Modify a Record directly in the Database (Tampering):**
    * Update a record directly in your database software (e.g., PHPMyAdmin or raw SQL).
    * Because the Symfony application is bypassed, the database trigger will log `NULL` for `emp_num` and flag the action as `BACKDOOR` in the `remarks` column.

3.  **Verify the Log:**
    Check the corresponding `audit_*` table (e.g., `audit_bed_applicants`).

    ```sql
    SELECT * FROM audit_bed_applicants ORDER BY audit_date_time DESC;
    ```
