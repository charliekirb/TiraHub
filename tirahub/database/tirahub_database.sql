-- ============================================================
--  TiraHub Dormitory Management System
--  Complete Database Script - phpMyAdmin Compatible
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET FOREIGN_KEY_CHECKS = 0;

DROP DATABASE IF EXISTS `tirahub_db`;
CREATE DATABASE `tirahub_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `tirahub_db`;

-- ============================================================
-- TABLES
-- ============================================================

CREATE TABLE `users` (
    `user_id`       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username`      VARCHAR(60)  NOT NULL UNIQUE,
    `email`         VARCHAR(120) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role`          ENUM('admin','student') NOT NULL DEFAULT 'student',
    `is_active`     TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_role`      (`role`),
    INDEX `idx_users_email`     (`email`),
    INDEX `idx_users_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `students` (
    `student_id`       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`          INT UNSIGNED NOT NULL UNIQUE,
    `student_number`   VARCHAR(30)  NOT NULL UNIQUE,
    `first_name`       VARCHAR(60)  NOT NULL,
    `last_name`        VARCHAR(60)  NOT NULL,
    `middle_name`      VARCHAR(60)  DEFAULT NULL,
    `gender`           ENUM('Male','Female','Other') NOT NULL,
    `birthdate`        DATE         NOT NULL,
    `contact_number`   VARCHAR(20)  DEFAULT NULL,
    `address`          TEXT         DEFAULT NULL,
    `course`           VARCHAR(100) DEFAULT NULL,
    `year_level`       TINYINT UNSIGNED DEFAULT NULL,
    `guardian_name`    VARCHAR(120) DEFAULT NULL,
    `guardian_contact` VARCHAR(20)  DEFAULT NULL,
    `profile_photo`    VARCHAR(255) DEFAULT NULL,
    `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_students_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX `idx_students_user_id`   (`user_id`),
    INDEX `idx_students_last_name` (`last_name`),
    INDEX `idx_students_number`    (`student_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `buildings` (
    `building_id`   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `building_name` VARCHAR(80) NOT NULL UNIQUE,
    `description`   TEXT        DEFAULT NULL,
    `is_active`     TINYINT(1)  NOT NULL DEFAULT 1,
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `rooms` (
    `room_id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `building_id`       INT UNSIGNED NOT NULL,
    `room_number`       VARCHAR(20)  NOT NULL,
    `floor`             TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `room_type`         ENUM('Single','Double','Triple','Quad','Suite') NOT NULL DEFAULT 'Double',
    `capacity`          TINYINT UNSIGNED NOT NULL DEFAULT 2,
    `current_occupants` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `monthly_rate`      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `description`       TEXT DEFAULT NULL,
    `status`            ENUM('Available','Full','Under Maintenance','Reserved') NOT NULL DEFAULT 'Available',
    `created_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_rooms_building` FOREIGN KEY (`building_id`) REFERENCES `buildings`(`building_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    UNIQUE KEY `uq_room_building` (`building_id`, `room_number`),
    INDEX `idx_rooms_status`   (`status`),
    INDEX `idx_rooms_building` (`building_id`),
    INDEX `idx_rooms_type`     (`room_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `applications` (
    `application_id`      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `student_id`          INT UNSIGNED NOT NULL,
    `preferred_room_type` ENUM('Single','Double','Triple','Quad','Suite') DEFAULT NULL,
    `reason`              TEXT DEFAULT NULL,
    `status`              ENUM('Pending','Under Review','Approved','Rejected','Cancelled') NOT NULL DEFAULT 'Pending',
    `remarks`             TEXT DEFAULT NULL,
    `reviewed_by`         INT UNSIGNED DEFAULT NULL,
    `reviewed_at`         DATETIME DEFAULT NULL,
    `submitted_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_applications_student`  FOREIGN KEY (`student_id`)  REFERENCES `students`(`student_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_applications_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users`(`user_id`)    ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX `idx_applications_student`  (`student_id`),
    INDEX `idx_applications_status`   (`status`),
    INDEX `idx_applications_submitted`(`submitted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `room_assignments` (
    `assignment_id`  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `student_id`     INT UNSIGNED NOT NULL,
    `room_id`        INT UNSIGNED NOT NULL,
    `assigned_by`    INT UNSIGNED NOT NULL,
    `check_in_date`  DATE NOT NULL,
    `check_out_date` DATE DEFAULT NULL,
    `status`         ENUM('Active','Checked Out','Transferred') NOT NULL DEFAULT 'Active',
    `notes`          TEXT DEFAULT NULL,
    `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_assign_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`student_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_assign_room`    FOREIGN KEY (`room_id`)    REFERENCES `rooms`(`room_id`)    ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_assign_admin`   FOREIGN KEY (`assigned_by`)REFERENCES `users`(`user_id`)    ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX `idx_assign_student` (`student_id`),
    INDEX `idx_assign_room`    (`room_id`),
    INDEX `idx_assign_status`  (`status`),
    INDEX `idx_assign_checkin` (`check_in_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `billing` (
    `bill_id`       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `student_id`    INT UNSIGNED NOT NULL,
    `room_id`       INT UNSIGNED NOT NULL,
    `billing_month` DATE NOT NULL,
    `amount_due`    DECIMAL(10,2) NOT NULL,
    `amount_paid`   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `due_date`      DATE NOT NULL,
    `status`        ENUM('Unpaid','Partial','Paid','Overdue') NOT NULL DEFAULT 'Unpaid',
    `generated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_billing_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`student_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_billing_room`    FOREIGN KEY (`room_id`)    REFERENCES `rooms`(`room_id`)    ON DELETE RESTRICT ON UPDATE CASCADE,
    UNIQUE KEY `uq_bill_student_month` (`student_id`, `billing_month`),
    INDEX `idx_billing_student` (`student_id`),
    INDEX `idx_billing_status`  (`status`),
    INDEX `idx_billing_due`     (`due_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `payments` (
    `payment_id`     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `bill_id`        INT UNSIGNED NOT NULL,
    `student_id`     INT UNSIGNED NOT NULL,
    `amount`         DECIMAL(10,2) NOT NULL,
    `payment_method` ENUM('Cash','Bank Transfer','GCash','Maya','Other') NOT NULL DEFAULT 'Cash',
    `reference_no`   VARCHAR(80) DEFAULT NULL,
    `received_by`    INT UNSIGNED DEFAULT NULL,
    `payment_date`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `notes`          TEXT DEFAULT NULL,
    CONSTRAINT `fk_payments_bill`    FOREIGN KEY (`bill_id`)     REFERENCES `billing`(`bill_id`)  ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_payments_student` FOREIGN KEY (`student_id`)  REFERENCES `students`(`student_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_payments_admin`   FOREIGN KEY (`received_by`) REFERENCES `users`(`user_id`)    ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX `idx_payments_bill`    (`bill_id`),
    INDEX `idx_payments_student` (`student_id`),
    INDEX `idx_payments_date`    (`payment_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `announcements` (
    `announcement_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `posted_by`       INT UNSIGNED NOT NULL,
    `title`           VARCHAR(200) NOT NULL,
    `content`         TEXT NOT NULL,
    `priority`        ENUM('Normal','Important','Urgent') NOT NULL DEFAULT 'Normal',
    `is_published`    TINYINT(1) NOT NULL DEFAULT 1,
    `publish_date`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `expiry_date`     DATETIME DEFAULT NULL,
    `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_announce_admin` FOREIGN KEY (`posted_by`) REFERENCES `users`(`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX `idx_announce_published` (`is_published`),
    INDEX `idx_announce_priority`  (`priority`),
    INDEX `idx_announce_date`      (`publish_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `notifications` (
    `notification_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`         INT UNSIGNED NOT NULL,
    `title`           VARCHAR(200) NOT NULL,
    `message`         TEXT NOT NULL,
    `type`            ENUM('Info','Success','Warning','Error') NOT NULL DEFAULT 'Info',
    `is_read`         TINYINT(1) NOT NULL DEFAULT 0,
    `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX `idx_notif_user`    (`user_id`),
    INDEX `idx_notif_is_read` (`is_read`),
    INDEX `idx_notif_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `audit_logs` (
    `log_id`     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`    INT UNSIGNED DEFAULT NULL,
    `action`     VARCHAR(100) NOT NULL,
    `table_name` VARCHAR(60)  NOT NULL,
    `record_id`  INT UNSIGNED DEFAULT NULL,
    `old_values` JSON DEFAULT NULL,
    `new_values` JSON DEFAULT NULL,
    `ip_address` VARCHAR(45)  DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX `idx_audit_user`    (`user_id`),
    INDEX `idx_audit_table`   (`table_name`),
    INDEX `idx_audit_created` (`created_at`),
    INDEX `idx_audit_action`  (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- VIEWS
-- ============================================================

CREATE OR REPLACE VIEW `vw_students_full` AS
SELECT s.student_id, s.user_id, u.username, u.email, u.is_active,
    s.student_number, s.first_name, s.last_name, s.middle_name,
    CONCAT(s.first_name, ' ', COALESCE(s.middle_name,''), ' ', s.last_name) AS full_name,
    s.gender, s.birthdate, s.contact_number, s.address,
    s.course, s.year_level, s.guardian_name, s.guardian_contact,
    s.profile_photo, s.created_at
FROM students s
INNER JOIN users u ON s.user_id = u.user_id;

CREATE OR REPLACE VIEW `vw_room_occupancy` AS
SELECT r.room_id, b.building_name, r.room_number, r.floor, r.room_type,
    r.capacity, r.current_occupants,
    (r.capacity - r.current_occupants) AS available_slots,
    r.monthly_rate, r.status,
    ROUND((r.current_occupants / r.capacity) * 100, 1) AS occupancy_pct
FROM rooms r
INNER JOIN buildings b ON r.building_id = b.building_id;

CREATE OR REPLACE VIEW `vw_active_assignments` AS
SELECT ra.assignment_id, ra.student_id, sf.full_name AS student_name,
    sf.student_number, sf.course, sf.year_level,
    ra.room_id, ro.building_name, ro.room_number, ro.room_type, ro.monthly_rate,
    ra.check_in_date, ra.check_out_date, ra.status, ra.notes
FROM room_assignments ra
INNER JOIN vw_students_full sf ON ra.student_id = sf.student_id
INNER JOIN vw_room_occupancy ro ON ra.room_id = ro.room_id
WHERE ra.status = 'Active';

CREATE OR REPLACE VIEW `vw_applications_detail` AS
SELECT a.application_id, a.student_id, sf.full_name AS student_name,
    sf.student_number, sf.email, sf.course, sf.year_level, sf.gender,
    a.preferred_room_type, a.reason, a.status, a.remarks,
    u.username AS reviewed_by_name, a.reviewed_at, a.submitted_at
FROM applications a
INNER JOIN vw_students_full sf ON a.student_id = sf.student_id
LEFT JOIN users u ON a.reviewed_by = u.user_id;

CREATE OR REPLACE VIEW `vw_billing_summary` AS
SELECT b.student_id, sf.full_name AS student_name, sf.student_number,
    COUNT(b.bill_id) AS total_bills,
    SUM(b.amount_due) AS total_due,
    SUM(b.amount_paid) AS total_paid,
    SUM(b.amount_due - b.amount_paid) AS total_balance,
    SUM(CASE WHEN b.status='Unpaid'  THEN 1 ELSE 0 END) AS unpaid_count,
    SUM(CASE WHEN b.status='Overdue' THEN 1 ELSE 0 END) AS overdue_count,
    SUM(CASE WHEN b.status='Paid'    THEN 1 ELSE 0 END) AS paid_count
FROM billing b
INNER JOIN vw_students_full sf ON b.student_id = sf.student_id
GROUP BY b.student_id, sf.full_name, sf.student_number;

CREATE OR REPLACE VIEW `vw_dashboard_stats` AS
SELECT
    (SELECT COUNT(*) FROM users WHERE role='student' AND is_active=1) AS total_students,
    (SELECT COUNT(*) FROM rooms WHERE status='Available')              AS available_rooms,
    (SELECT COUNT(*) FROM rooms WHERE status='Full')                   AS full_rooms,
    (SELECT COUNT(*) FROM rooms)                                       AS total_rooms,
    (SELECT COUNT(*) FROM applications WHERE status='Pending')         AS pending_applications,
    (SELECT COUNT(*) FROM applications WHERE status='Approved')        AS approved_applications,
    (SELECT COUNT(*) FROM room_assignments WHERE status='Active')      AS active_tenants,
    (SELECT COALESCE(SUM(amount_due - amount_paid),0) FROM billing WHERE status IN ('Unpaid','Partial','Overdue')) AS total_outstanding,
    (SELECT COALESCE(SUM(amount),0) FROM payments WHERE MONTH(payment_date)=MONTH(CURDATE()) AND YEAR(payment_date)=YEAR(CURDATE())) AS revenue_this_month;


-- ============================================================
-- STORED PROCEDURES
-- ============================================================

DELIMITER //

CREATE PROCEDURE `sp_register_student` (
    IN  p_username       VARCHAR(60),
    IN  p_email          VARCHAR(120),
    IN  p_password_hash  VARCHAR(255),
    IN  p_student_number VARCHAR(30),
    IN  p_first_name     VARCHAR(60),
    IN  p_last_name      VARCHAR(60),
    IN  p_middle_name    VARCHAR(60),
    IN  p_gender         VARCHAR(10),
    IN  p_birthdate      DATE,
    IN  p_contact_number VARCHAR(20),
    IN  p_address        TEXT,
    IN  p_course         VARCHAR(100),
    IN  p_year_level     TINYINT UNSIGNED,
    OUT p_user_id        INT UNSIGNED,
    OUT p_student_id     INT UNSIGNED,
    OUT p_message        VARCHAR(255)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_user_id    = 0;
        SET p_student_id = 0;
        SET p_message    = 'Registration failed due to a database error.';
    END;

    SET p_user_id    = 0;
    SET p_student_id = 0;
    SET p_message    = '';

    IF EXISTS (SELECT 1 FROM users WHERE username = p_username) THEN
        SET p_message = 'Username already taken.';
    ELSEIF EXISTS (SELECT 1 FROM users WHERE email = p_email) THEN
        SET p_message = 'Email already registered.';
    ELSEIF EXISTS (SELECT 1 FROM students WHERE student_number = p_student_number) THEN
        SET p_message = 'Student number already exists.';
    ELSE
        START TRANSACTION;

        INSERT INTO users (username, email, password_hash, role, is_active)
        VALUES (p_username, p_email, p_password_hash, 'student', 1);
        SET p_user_id = LAST_INSERT_ID();

        INSERT INTO students (
            user_id, student_number, first_name, last_name, middle_name,
            gender, birthdate, contact_number, address, course, year_level
        ) VALUES (
            p_user_id, p_student_number, p_first_name, p_last_name, p_middle_name,
            p_gender, p_birthdate, p_contact_number, p_address, p_course, p_year_level
        );
        SET p_student_id = LAST_INSERT_ID();

        COMMIT;
        SET p_message = 'Registration successful.';
    END IF;
END //

CREATE PROCEDURE `sp_submit_application` (
    IN  p_student_id          INT UNSIGNED,
    IN  p_preferred_room_type VARCHAR(20),
    IN  p_reason              TEXT,
    OUT p_application_id      INT UNSIGNED,
    OUT p_message             VARCHAR(255)
)
BEGIN
    DECLARE v_active_app INT DEFAULT 0;
    DECLARE v_has_room   INT DEFAULT 0;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_application_id = 0;
        SET p_message = 'Application submission failed.';
    END;

    SET p_application_id = 0;

    SELECT COUNT(*) INTO v_active_app FROM applications
    WHERE student_id = p_student_id AND status IN ('Pending','Under Review','Approved');

    SELECT COUNT(*) INTO v_has_room FROM room_assignments
    WHERE student_id = p_student_id AND status = 'Active';

    IF v_active_app > 0 THEN
        SET p_message = 'You already have an active or approved application.';
    ELSEIF v_has_room > 0 THEN
        SET p_message = 'You are already assigned to a room.';
    ELSE
        START TRANSACTION;
        INSERT INTO applications (student_id, preferred_room_type, reason, status)
        VALUES (p_student_id, p_preferred_room_type, p_reason, 'Pending');
        SET p_application_id = LAST_INSERT_ID();
        COMMIT;
        SET p_message = 'Application submitted successfully.';
    END IF;
END //

CREATE PROCEDURE `sp_review_application` (
    IN  p_application_id INT UNSIGNED,
    IN  p_admin_user_id  INT UNSIGNED,
    IN  p_new_status     VARCHAR(20),
    IN  p_remarks        TEXT,
    OUT p_message        VARCHAR(255)
)
BEGIN
    DECLARE v_current_status VARCHAR(20);

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_message = 'Review update failed.';
    END;

    SELECT status INTO v_current_status FROM applications WHERE application_id = p_application_id;

    IF v_current_status IS NULL THEN
        SET p_message = 'Application not found.';
    ELSEIF v_current_status NOT IN ('Pending','Under Review') THEN
        SET p_message = CONCAT('Cannot update application with status: ', v_current_status);
    ELSE
        START TRANSACTION;
        UPDATE applications
        SET status = p_new_status, remarks = p_remarks,
            reviewed_by = p_admin_user_id, reviewed_at = NOW()
        WHERE application_id = p_application_id;
        COMMIT;
        SET p_message = CONCAT('Application status updated to ', p_new_status, '.');
    END IF;
END //

CREATE PROCEDURE `sp_assign_room` (
    IN  p_student_id    INT UNSIGNED,
    IN  p_room_id       INT UNSIGNED,
    IN  p_admin_user_id INT UNSIGNED,
    IN  p_check_in_date DATE,
    OUT p_assignment_id INT UNSIGNED,
    OUT p_message       VARCHAR(255)
)
BEGIN
    DECLARE v_capacity    TINYINT UNSIGNED;
    DECLARE v_occupants   TINYINT UNSIGNED;
    DECLARE v_room_status VARCHAR(30);
    DECLARE v_has_room    INT DEFAULT 0;
    DECLARE v_app_approved INT DEFAULT 0;
    DECLARE v_monthly_rate DECIMAL(10,2);

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_assignment_id = 0;
        SET p_message = 'Room assignment failed due to a database error.';
    END;

    SET p_assignment_id = 0;

    START TRANSACTION;

    SELECT capacity, current_occupants, status, monthly_rate
    INTO v_capacity, v_occupants, v_room_status, v_monthly_rate
    FROM rooms WHERE room_id = p_room_id FOR UPDATE;

    SELECT COUNT(*) INTO v_has_room FROM room_assignments
    WHERE student_id = p_student_id AND status = 'Active';

    SELECT COUNT(*) INTO v_app_approved FROM applications
    WHERE student_id = p_student_id AND status = 'Approved';

    IF v_room_status = 'Under Maintenance' THEN
        ROLLBACK;
        SET p_message = 'Room is under maintenance and cannot be assigned.';
    ELSEIF v_occupants >= v_capacity THEN
        ROLLBACK;
        SET p_message = 'Room is full. Please choose another room.';
    ELSEIF v_has_room > 0 THEN
        ROLLBACK;
        SET p_message = 'Student is already assigned to a room.';
    ELSEIF v_app_approved = 0 THEN
        ROLLBACK;
        SET p_message = 'Student does not have an approved application.';
    ELSE
        INSERT INTO room_assignments (student_id, room_id, assigned_by, check_in_date, status)
        VALUES (p_student_id, p_room_id, p_admin_user_id, p_check_in_date, 'Active');
        SET p_assignment_id = LAST_INSERT_ID();

        UPDATE rooms
        SET current_occupants = current_occupants + 1,
            status = IF((current_occupants + 1) >= capacity, 'Full', 'Available')
        WHERE room_id = p_room_id;

        INSERT INTO billing (student_id, room_id, billing_month, amount_due, due_date, status)
        VALUES (p_student_id, p_room_id,
                DATE_FORMAT(p_check_in_date, '%Y-%m-01'),
                v_monthly_rate,
                LAST_DAY(p_check_in_date),
                'Unpaid');

        COMMIT;
        SET p_message = 'Room assigned successfully and billing generated.';
    END IF;
END //

CREATE PROCEDURE `sp_record_payment` (
    IN  p_bill_id        INT UNSIGNED,
    IN  p_student_id     INT UNSIGNED,
    IN  p_amount         DECIMAL(10,2),
    IN  p_payment_method VARCHAR(30),
    IN  p_reference_no   VARCHAR(80),
    IN  p_received_by    INT UNSIGNED,
    OUT p_payment_id     INT UNSIGNED,
    OUT p_message        VARCHAR(255)
)
BEGIN
    DECLARE v_amount_due  DECIMAL(10,2);
    DECLARE v_amount_paid DECIMAL(10,2);
    DECLARE v_new_paid    DECIMAL(10,2);
    DECLARE v_new_status  VARCHAR(20);

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_payment_id = 0;
        SET p_message = 'Payment recording failed.';
    END;

    SET p_payment_id = 0;

    START TRANSACTION;

    SELECT amount_due, amount_paid INTO v_amount_due, v_amount_paid
    FROM billing WHERE bill_id = p_bill_id FOR UPDATE;

    IF v_amount_due IS NULL THEN
        ROLLBACK;
        SET p_message = 'Bill not found.';
    ELSEIF p_amount <= 0 THEN
        ROLLBACK;
        SET p_message = 'Payment amount must be greater than zero.';
    ELSE
        SET v_new_paid = v_amount_paid + p_amount;
        IF v_new_paid >= v_amount_due THEN
            SET v_new_status = 'Paid';
        ELSEIF v_new_paid > 0 THEN
            SET v_new_status = 'Partial';
        ELSE
            SET v_new_status = 'Unpaid';
        END IF;

        INSERT INTO payments (bill_id, student_id, amount, payment_method, reference_no, received_by)
        VALUES (p_bill_id, p_student_id, p_amount, p_payment_method, p_reference_no, p_received_by);
        SET p_payment_id = LAST_INSERT_ID();

        UPDATE billing SET amount_paid = v_new_paid, status = v_new_status
        WHERE bill_id = p_bill_id;

        COMMIT;
        SET p_message = 'Payment recorded successfully.';
    END IF;
END //

CREATE PROCEDURE `sp_generate_monthly_billing` (
    IN  p_billing_month DATE,
    OUT p_generated     INT,
    OUT p_message       VARCHAR(255)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_generated = 0;
        SET p_message = 'Billing generation failed.';
    END;

    START TRANSACTION;

    INSERT INTO billing (student_id, room_id, billing_month, amount_due, due_date, status)
    SELECT ra.student_id, ra.room_id,
           DATE_FORMAT(p_billing_month, '%Y-%m-01'),
           r.monthly_rate,
           LAST_DAY(p_billing_month),
           'Unpaid'
    FROM room_assignments ra
    INNER JOIN rooms r ON ra.room_id = r.room_id
    WHERE ra.status = 'Active'
      AND NOT EXISTS (
          SELECT 1 FROM billing b
          WHERE b.student_id = ra.student_id
            AND b.billing_month = DATE_FORMAT(p_billing_month, '%Y-%m-01')
      );

    SET p_generated = ROW_COUNT();
    COMMIT;
    SET p_message = CONCAT(p_generated, ' billing record(s) generated.');
END //

CREATE PROCEDURE `sp_checkout_student` (
    IN  p_student_id    INT UNSIGNED,
    IN  p_admin_user_id INT UNSIGNED,
    IN  p_checkout_date DATE,
    OUT p_message       VARCHAR(255)
)
BEGIN
    DECLARE v_assignment_id INT UNSIGNED;
    DECLARE v_room_id       INT UNSIGNED;
    DECLARE v_balance       DECIMAL(10,2);

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_message = 'Checkout failed.';
    END;

    SELECT assignment_id, room_id INTO v_assignment_id, v_room_id
    FROM room_assignments
    WHERE student_id = p_student_id AND status = 'Active'
    LIMIT 1;

    IF v_assignment_id IS NULL THEN
        SET p_message = 'No active assignment found for this student.';
    ELSE
        SELECT COALESCE(SUM(amount_due - amount_paid), 0) INTO v_balance
        FROM billing
        WHERE student_id = p_student_id AND status IN ('Unpaid','Partial','Overdue');

        IF v_balance > 0 THEN
            SET p_message = CONCAT('Student has outstanding balance of PHP ', FORMAT(v_balance,2), '. Please settle before checkout.');
        ELSE
            START TRANSACTION;
            UPDATE room_assignments
            SET status = 'Checked Out', check_out_date = p_checkout_date
            WHERE assignment_id = v_assignment_id;

            UPDATE rooms
            SET current_occupants = GREATEST(0, current_occupants - 1),
                status = IF((current_occupants - 1) < capacity, 'Available', status)
            WHERE room_id = v_room_id;
            COMMIT;
            SET p_message = 'Student checked out successfully.';
        END IF;
    END IF;
END //

CREATE PROCEDURE `sp_get_student_dashboard` (
    IN p_student_id INT UNSIGNED
)
BEGIN
    SELECT application_id, status, remarks, submitted_at, reviewed_at
    FROM applications
    WHERE student_id = p_student_id
    ORDER BY submitted_at DESC LIMIT 1;

    SELECT ra.check_in_date, r.room_number, b.building_name, r.room_type, r.monthly_rate
    FROM room_assignments ra
    INNER JOIN rooms r ON ra.room_id = r.room_id
    INNER JOIN buildings b ON r.building_id = b.building_id
    WHERE ra.student_id = p_student_id AND ra.status = 'Active' LIMIT 1;

    SELECT bill_id, billing_month, amount_due, amount_paid,
           (amount_due - amount_paid) AS balance, due_date, status
    FROM billing
    WHERE student_id = p_student_id AND status IN ('Unpaid','Partial','Overdue')
    ORDER BY due_date ASC;

    SELECT n.notification_id, n.title, n.message, n.type, n.created_at
    FROM notifications n
    WHERE n.user_id = (SELECT user_id FROM students WHERE student_id = p_student_id)
      AND n.is_read = 0
    ORDER BY n.created_at DESC;
END //

DELIMITER ;

-- ============================================================
-- TRIGGERS
-- ============================================================

DELIMITER //

CREATE TRIGGER `trg_before_room_assignment_insert`
BEFORE INSERT ON `room_assignments`
FOR EACH ROW
BEGIN
    DECLARE v_capacity  TINYINT UNSIGNED;
    DECLARE v_occupants TINYINT UNSIGNED;
    DECLARE v_status    VARCHAR(30);

    SELECT capacity, current_occupants, status
    INTO v_capacity, v_occupants, v_status
    FROM rooms WHERE room_id = NEW.room_id;

    IF v_occupants >= v_capacity THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cannot assign: Room is already at full capacity.';
    END IF;

    IF v_status = 'Under Maintenance' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cannot assign: Room is under maintenance.';
    END IF;
END //

CREATE TRIGGER `trg_after_application_update`
AFTER UPDATE ON `applications`
FOR EACH ROW
BEGIN
    DECLARE v_user_id INT UNSIGNED;
    DECLARE v_title   VARCHAR(200);
    DECLARE v_msg     TEXT;
    DECLARE v_type    VARCHAR(20);

    IF OLD.status <> NEW.status THEN
        SELECT u.user_id INTO v_user_id
        FROM students s INNER JOIN users u ON s.user_id = u.user_id
        WHERE s.student_id = NEW.student_id;

        IF NEW.status = 'Under Review' THEN
            SET v_title = 'Application Under Review';
            SET v_msg   = 'Your dormitory application is now being reviewed by the admin.';
            SET v_type  = 'Info';
        ELSEIF NEW.status = 'Approved' THEN
            SET v_title = 'Application Approved!';
            SET v_msg   = 'Congratulations! Your dormitory application has been approved. Please wait for room assignment.';
            SET v_type  = 'Success';
        ELSEIF NEW.status = 'Rejected' THEN
            SET v_title = 'Application Rejected';
            SET v_msg   = CONCAT('Your dormitory application has been rejected. Remarks: ', COALESCE(NEW.remarks,'N/A'));
            SET v_type  = 'Error';
        ELSE
            SET v_title = 'Application Update';
            SET v_msg   = CONCAT('Your application status has been updated to: ', NEW.status);
            SET v_type  = 'Info';
        END IF;

        INSERT INTO notifications (user_id, title, message, type)
        VALUES (v_user_id, v_title, v_msg, v_type);

        INSERT INTO audit_logs (user_id, action, table_name, record_id, old_values, new_values)
        VALUES (NEW.reviewed_by, 'UPDATE_APPLICATION_STATUS', 'applications', NEW.application_id,
                JSON_OBJECT('status', OLD.status),
                JSON_OBJECT('status', NEW.status, 'remarks', NEW.remarks));
    END IF;
END //

CREATE TRIGGER `trg_after_room_assignment_insert`
AFTER INSERT ON `room_assignments`
FOR EACH ROW
BEGIN
    DECLARE v_user_id   INT UNSIGNED;
    DECLARE v_room_info VARCHAR(100);

    SELECT u.user_id INTO v_user_id
    FROM students s INNER JOIN users u ON s.user_id = u.user_id
    WHERE s.student_id = NEW.student_id;

    SELECT CONCAT(b.building_name, ' - Room ', r.room_number) INTO v_room_info
    FROM rooms r INNER JOIN buildings b ON r.building_id = b.building_id
    WHERE r.room_id = NEW.room_id;

    INSERT INTO notifications (user_id, title, message, type)
    VALUES (v_user_id, 'Room Assigned',
            CONCAT('You have been assigned to ', v_room_info, '. Check-in date: ', NEW.check_in_date),
            'Success');

    INSERT INTO audit_logs (action, table_name, record_id, new_values)
    VALUES ('INSERT_ROOM_ASSIGNMENT', 'room_assignments', NEW.assignment_id,
            JSON_OBJECT('student_id', NEW.student_id, 'room_id', NEW.room_id, 'check_in_date', NEW.check_in_date));
END //

CREATE TRIGGER `trg_after_billing_insert`
AFTER INSERT ON `billing`
FOR EACH ROW
BEGIN
    DECLARE v_user_id INT UNSIGNED;

    SELECT u.user_id INTO v_user_id
    FROM students s INNER JOIN users u ON s.user_id = u.user_id
    WHERE s.student_id = NEW.student_id;

    INSERT INTO notifications (user_id, title, message, type)
    VALUES (v_user_id, 'New Bill Generated',
            CONCAT('A new bill of PHP ', FORMAT(NEW.amount_due,2),
                   ' has been generated for ', DATE_FORMAT(NEW.billing_month,'%M %Y'),
                   '. Due date: ', NEW.due_date),
            'Warning');
END //

CREATE TRIGGER `trg_after_payment_insert`
AFTER INSERT ON `payments`
FOR EACH ROW
BEGIN
    DECLARE v_user_id     INT UNSIGNED;
    DECLARE v_bill_status VARCHAR(20);

    SELECT b.status INTO v_bill_status FROM billing b WHERE b.bill_id = NEW.bill_id;

    SELECT u.user_id INTO v_user_id
    FROM students s INNER JOIN users u ON s.user_id = u.user_id
    WHERE s.student_id = NEW.student_id;

    IF v_bill_status = 'Paid' THEN
        INSERT INTO notifications (user_id, title, message, type)
        VALUES (v_user_id, 'Payment Confirmed – Bill Fully Paid',
                CONCAT('Your payment of PHP ', FORMAT(NEW.amount,2), ' has been received. Your bill is now fully paid.'),
                'Success');
    ELSE
        INSERT INTO notifications (user_id, title, message, type)
        VALUES (v_user_id, 'Payment Received',
                CONCAT('Your payment of PHP ', FORMAT(NEW.amount,2), ' has been recorded. You still have a remaining balance.'),
                'Info');
    END IF;

    INSERT INTO audit_logs (action, table_name, record_id, new_values)
    VALUES ('INSERT_PAYMENT', 'payments', NEW.payment_id,
            JSON_OBJECT('bill_id', NEW.bill_id, 'amount', NEW.amount, 'method', NEW.payment_method));
END //

CREATE TRIGGER `trg_after_user_update`
AFTER UPDATE ON `users`
FOR EACH ROW
BEGIN
    IF OLD.is_active <> NEW.is_active OR OLD.role <> NEW.role THEN
        INSERT INTO audit_logs (action, table_name, record_id, old_values, new_values)
        VALUES ('UPDATE_USER', 'users', NEW.user_id,
                JSON_OBJECT('is_active', OLD.is_active, 'role', OLD.role),
                JSON_OBJECT('is_active', NEW.is_active, 'role', NEW.role));
    END IF;
END //

DELIMITER ;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Admin account | password: Admin@tirahub2024
INSERT INTO `users` (username, email, password_hash, role, is_active) VALUES
('admin', 'admin@tirahub.edu', '$2y$12$8gcu3hXHeJrw1a5.DHlYFe88V8.oRXZDohvN5NJSoC1.cwCrj338q', 'admin', 1);

-- Buildings
INSERT INTO `buildings` (building_name, description) VALUES
('Sampaguita Hall', 'Main dormitory building for female students.'),
('Narra Hall',      'Main dormitory building for male students.'),
('Acacia Hall',     'Co-ed dormitory for graduate students.');

-- Rooms
INSERT INTO `rooms` (building_id, room_number, floor, room_type, capacity, monthly_rate, status) VALUES
(1,'101',1,'Single',1,2500.00,'Available'),
(1,'102',1,'Double',2,1800.00,'Available'),
(1,'103',1,'Double',2,1800.00,'Available'),
(1,'201',2,'Triple',3,1500.00,'Available'),
(1,'202',2,'Quad',  4,1200.00,'Available'),
(1,'203',2,'Suite', 2,3500.00,'Available'),
(2,'101',1,'Single',1,2500.00,'Available'),
(2,'102',1,'Double',2,1800.00,'Available'),
(2,'103',1,'Double',2,1800.00,'Available'),
(2,'201',2,'Triple',3,1500.00,'Available'),
(2,'202',2,'Quad',  4,1200.00,'Available'),
(3,'101',1,'Single',1,2800.00,'Available'),
(3,'102',1,'Double',2,2000.00,'Available'),
(3,'201',2,'Suite', 2,4000.00,'Available');

-- Welcome announcement
INSERT INTO `announcements` (posted_by, title, content, priority, is_published) VALUES
(1, 'Welcome to TiraHub Dormitory',
 'Welcome to the official dormitory management portal. Students may apply for dormitory accommodation through the Apply for Dorm section. Please ensure all information is accurate before submitting.',
 'Important', 1);

SET FOREIGN_KEY_CHECKS = 1;
