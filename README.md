# 🔐 ADMS (Attendance Device Management System)

ADMS is a comprehensive Attendance Device Management System designed to handle biometric and access control data from various devices. This system is built using Laravel, a PHP framework, provides functionalities to store, manage user and fingerprint data.

## ✨ Features

-   🔒 Fingerprint data storage
-   📊 Device status monitoring

## 📸 Screenshots

Device Connected
![App Screenshot](https://github.com/saifulcoder/adms-server-ZKTeco/blob/main/Screenshot_7.png)
Attendance Recorded
![App Screenshot](https://github.com/saifulcoder/adms-server-ZKTeco/blob/main/Screenshot_8.png)
Device Log
![App Screenshot](https://github.com/saifulcoder/adms-server-ZKTeco/blob/main/Screenshot_9.png)
Attendence Log
![App Screenshot](https://github.com/saifulcoder/adms-server-ZKTeco/blob/main/Screenshot_10.png)

## 🖥️ Device Support

-   Solutions X100C
-

## 📦 Installation

### ⚙️ Prerequisites

Before you begin, ensure you have the following installed on your system:

-   PHP >= 8.2 (Laravel 12 requirement)
-   Composer 2.x
-   MySQL or any other supported database
-   Web server (Apache, Nginx, etc.)

> **Upgrading from Laravel 10?** The framework has been upgraded to
> **Laravel 12** (see [Framework version](#-framework-version)). The
> application skeleton is unchanged — Laravel 11/12 still support the
> classic Laravel 10 structure, so no `bootstrap/app.php` rewrite was
> needed. Only the dependency constraints and a handful of call sites
> changed. See [Bugs found during the upgrade](#-bugs-found-during-the-upgrade)
> for the pre-existing issues that surfaced.

### 📋 Steps

1. **Clone the repository**

    ```bash
    git clone https://github.com/saifulcoder/adms-server-ZKTeco.git adms-server
    cd adms-server
    ```

2. **Install dependencies**

    ```bash
    composer install
    ```

3. **Copy the `.env` file**

    ```bash
    cp .env.example .env
    ```

4. **Generate application key**

    ```bash
    php artisan key:generate
    ```

5. **Configure the `.env` file**
   Open the `.env` file and set your database credentials and other environment variables:

    ```env
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=adms
    DB_USERNAME=root
    DB_PASSWORD=
    ```

6. **Run the migrations**

    ```bash
    php artisan migrate
    ```

7. **Serve the application**
    ```bash
    php artisan serve
    ```

### 📡 Monitoring Device Status

You can monitor the status of devices by querying the `devices` table where the `online` field indicates the last time the device was online.

## 🧱 Framework version

| Component          | Version    |
| ------------------ | ---------- |
| Laravel Framework  | `^12.0`    |
| PHP                | `^8.2`     |
| Laravel Sanctum    | `^4.0`     |
| PHPUnit            | `^11.0`    |
| Carbon (via Laravel) | `^3.0`   |

Dependency resolution is pinned to PHP 8.2 via `config.platform.php` in
`composer.json`, so `composer update` always produces a tree that runs on
the same PHP version used by CI and production. This matters because
newer Symfony releases (8.x) require PHP >= 8.4.1 and would otherwise be
pulled in automatically.

## 🐞 Bugs found during the upgrade

Two **pre-existing** issues were uncovered while upgrading from Laravel 10.
Neither is caused by the upgrade itself, but both had to be handled.

### 1. SQLite foreign-key mismatch on `attendances`

The schema declares `attendances.idoficina -> oficinas.idoficina`, but
`oficinas.idoficina` is only **indexed, not unique**. MySQL accepts a
foreign key that points at a non-unique indexed column; SQLite does not
and fails on insert with:

```
SQLSTATE[HY000]: General error: 1 foreign key mismatch - "attendances" referencing "oficinas"
```

Laravel 12 now actually applies that constraint on SQLite, which broke
`PullFingerprintsTest::test_attendance_uploads_are_unaffected`.

**Resolution:** the production database is MySQL, so the schema is left
untouched. Instead `phpunit.xml` sets:

```xml
<env name="DB_FOREIGN_KEYS" value="false"/>
```

> ⚠️ Do not "fix" this by adding a unique index to `oficinas.idoficina`
> without checking production data first. `storeOficina()` performs no
> uniqueness validation, so duplicate `idoficina` values may already
> exist in the live database.

### 2. Carbon 3 `diffIn*()` now returns a signed value

Carbon 3 (pulled in by Laravel 12) changed `diffInMinutes()` and friends
from returning an **unsigned** value to a **signed float**, and flipped
the `$absolute` parameter default from `true` to `false`. Code that
relied on the old behaviour — e.g. `if ($diff > 20)` — would silently
invert when the second argument is earlier than the first.

**Resolution:** every `diffIn*()` comparison was wrapped in `abs()` to
preserve the pre-upgrade meaning. Affected call sites:

- `app/Models/Device.php`
- `app/Console/Commands/MonitorDesfases.php`
- `app/Console/Commands/PullFingerprints.php`
- `app/Http/Controllers/DeviceController.php`
- `resources/views/devices/{attendance,fingerdata,index,monitor}.blade.php`

Keep this in mind for any new code that compares timestamps.

## 📮 Postman Collection

For testing and interacting with the API endpoints, you can use the provided Postman collection:
[Postman Collection](https://github.com/saifulcoder/adms-server-ZKTeco/blob/main/ADMS server ZKTeco.postman_collection.json)

## 👨‍💻 Authors

-   [@saifulcoder](https://github.com/saifulcoder)

## 💡 For Improvement and project

contact us saiful.coder@gmail.com

## 🤝 Contributing

This project helps you and you want to help keep it going? Buy me a coffee:
<br> <a href="https://www.buymeacoffee.com/saifulcoder" target="_blank"><img src="https://www.buymeacoffee.com/assets/img/custom_images/orange_img.png" alt="Buy Me A Coffee" style="height: 61px !important;width: 174px !important;box-shadow: 0px 3px 2px 0px rgba(190, 190, 190, 0.5) !important;" ></a><br>
or via <br>
<a href="https://saweria.co/saifulcoder">https://saweria.co/saifulcoder</a>

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
