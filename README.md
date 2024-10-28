==================================================================================================
Laravel Project Setup Instructions
==================================================================================================


Prerequisites:
--------------
PHP Version: Ensure you have PHP 8.1 or higher installed on your machine.
Database: PostgreSQL should be installed and running.


Steps to Set Up the Project:
----------------------------
1. Clone the Repository
git clone https://github.com/gitofshivesh/activity_tracker_app.git
cd activity_tracker_app


2. Intall Required Dependencies
composer install


3. Create the Database in PostgreSQL
CREATE DATABASE activity_tracker_app;


4. Open the .env file and update the database configuration section to match your PostgreSQL credentials


5. Generate Application Key
php artisan key:generate


6. Run Migrations
php artisan migrate

==================================================================================================