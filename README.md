# ARIES-ONSITE (Onsite Enrollment System)

A web-based enrollment application for FEU Alabang and FEU Diliman built with Symfony 7. It handles student registration, document uploads, and administrative management, featuring a robust database audit trail system.

## 1. Project Specifications

* **Framework:** Symfony 7.4
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
* MySQL Server
* Symfony CLI (optional, but recommended)

### Steps

**1. Clone the Repository**

```bash
git clone <repository_url>
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

**4. Database Setup**

Create the database and apply migrations (this includes tables and audit triggers).

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

### Clearing Data

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

The system uses Database Triggers to log all `INSERT`, `UPDATE`, and `DELETE` actions on applicant tables.

1.  **Modify a Record:**
    * Update a student's details via the Admin Dashboard (`/alabang-admin/registration/{id}/edit`).
    * Or update a record directly in your database software (e.g., PHPMyAdmin).

2.  **Verify the Log:**
    Check the corresponding `audit_*` table (e.g., `audit_bed_applicants`).

    ```sql
    SELECT * FROM audit_bed_applicants ORDER BY audit_datetime DESC;
    ```

    You should see the change logged with the `audit_action` (UPDATE) and the user who performed it.
