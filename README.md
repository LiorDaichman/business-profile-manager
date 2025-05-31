# Business Profile Management System

This project is a Full Stack application to manage business profiles, including functionalities for viewing and editing business details such as basic information, header image, and business hours. The system has two primary pages: a Business Profile View page (for public viewing) and a Business Profile Edit page (for the admin to update information).

### Technology Stack:
- **Backend:** PHP 8+ (Pure PHP with Composer)
- **Database:** MySQL
- **Frontend:** Bootstrap 5, jQuery
- **Version Control:** Bitbucket
- **Cloud Storage:** AWS S3 for image storage
- **Image Editing:** Pintura Image Editor for header image modifications
- **Calendar:** FullCalendar.io for displaying business hours

---

### Pages:
1. **Business Profile View Page (Public)**:
   - Displays business details, including:
     - Header image
     - Business name
     - Address
     - Phone number
     - Business hours visualization using FullCalendar.io

2. **Business Profile Edit Page (Admin)**:
   - Allows the admin to edit the following:
     - **Basic Information**: Business name, address, and phone number.
     - **Header Image Management**: Upload, edit, and save header image to AWS S3, and show current header image with upload/edit progress.
     - **Business Hours Management**: Set and edit business hours for each day, with validation to prevent invalid time ranges and option to mark days as closed.

---

### Database Schema:
The database schema contains two tables: `business_profile` and `business_hours`.

#### 1. `business_profile` Table:

CREATE TABLE business_profile (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    address TEXT NOT NULL,
    phone VARCHAR(20) NOT NULL,
    header_image VARCHAR(255), -- S3 image URL
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

#### 2. `business_hours` Table:

CREATE TABLE business_hours (
    id INT AUTO_INCREMENT PRIMARY KEY,
    business_id INT NOT NULL,
    day_of_week ENUM('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') NOT NULL,
    open_time TIME,
    close_time TIME,
    is_closed BOOLEAN DEFAULT 0, -- If the business is closed that day
    FOREIGN KEY (business_id) REFERENCES business_profile(id) ON DELETE CASCADE
);

#### Install:
1. composer install - https://getcomposer.org/download/
2. isntall AWS CLI - https://docs.aws.amazon.com/cli/latest/userguide/getting-started-install.html
3. git clone https://bitbucket.org/your-username/business-profile-management.git
cd business-profile-management
4. In cmd insdie the folder:
# npm install
# npm install @aws-sdk/client-s3
# npm install @pqina/pintura
# npm install @pqina/jquery-pintura

#### How tu run:
open cmd inside the folder and enter: php -S localhost:8000
open in the browser the website http://localhost:8000/

https://github.com/LiorDaichman/business-profile-manager/issues/1#issue-3105166517
