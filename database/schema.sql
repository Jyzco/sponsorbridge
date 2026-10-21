-- Skema Database SponsorBridge (MySQL)
-- Kompatibel dengan MySQL 5.7+ / MariaDB 10.4+ / PHP MySQLi

CREATE DATABASE IF NOT EXISTS `sponsorbridge_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `sponsorbridge_db`;

-- 1. Tabel Users
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('eo', 'sponsor', 'admin') NOT NULL,
    `phone` VARCHAR(30) NULL,
    `avatar` VARCHAR(255) NULL,
    `is_verified` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Profil Event Organizer (EO)
CREATE TABLE IF NOT EXISTS `eo_profiles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `organization_name` VARCHAR(200) NOT NULL,
    `description` TEXT NULL,
    `logo` VARCHAR(255) NULL,
    `address` VARCHAR(255) NULL,
    `city` VARCHAR(100) NULL,
    `website` VARCHAR(200) NULL,
    `instagram` VARCHAR(100) NULL,
    `linkedin` VARCHAR(100) NULL,
    `total_events_held` INT DEFAULT 0,
    `avg_rating` DECIMAL(3,2) DEFAULT 5.00,
    `categories` VARCHAR(255) NULL,
    `track_record` TEXT NULL,
    `portfolio_json` TEXT NULL,
    `testimonials_json` TEXT NULL,
    `bank_name` VARCHAR(100) NULL,
    `bank_account` VARCHAR(100) NULL,
    `bank_holder` VARCHAR(150) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Profil Sponsor / Brand
CREATE TABLE IF NOT EXISTS `sponsor_profiles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `company_name` VARCHAR(200) NOT NULL,
    `industry` VARCHAR(100) NOT NULL,
    `description` TEXT NULL,
    `logo` VARCHAR(255) NULL,
    `address` VARCHAR(255) NULL,
    `city` VARCHAR(100) NULL,
    `website` VARCHAR(200) NULL,
    `min_budget` BIGINT DEFAULT 10000000,
    `max_budget` BIGINT DEFAULT 500000000,
    `target_audience` VARCHAR(255) NULL,
    `csr_focus` VARCHAR(255) NULL,
    `contact_person` VARCHAR(150) NULL,
    `contact_email` VARCHAR(150) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Tabel Event
CREATE TABLE IF NOT EXISTS `events` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `eo_id` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `category` VARCHAR(100) NOT NULL,
    `location` VARCHAR(255) NOT NULL,
    `city` VARCHAR(100) NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `attendee_estimate` INT NOT NULL,
    `target_audience` VARCHAR(255) NOT NULL,
    `total_budget` BIGINT NOT NULL,
    `sponsorship_target` BIGINT NOT NULL,
    `funds_raised` BIGINT DEFAULT 0,
    `description` TEXT NOT NULL,
    `rundown` TEXT NULL,
    `poster` VARCHAR(255) NULL,
    `status` ENUM('draft', 'open', 'ongoing', 'completed', 'cancelled') DEFAULT 'open',
    `is_featured` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`eo_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Paket Sponsorship
CREATE TABLE IF NOT EXISTS `sponsorship_packages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `event_id` INT NOT NULL,
    `tier` ENUM('Bronze', 'Silver', 'Gold', 'Platinum') NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `price` BIGINT NOT NULL,
    `quota` INT NOT NULL DEFAULT 1,
    `slots_taken` INT NOT NULL DEFAULT 0,
    `benefits` TEXT NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Tabel Proposal
CREATE TABLE IF NOT EXISTS `proposals` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `event_id` INT NOT NULL,
    `eo_id` INT NOT NULL,
    `sponsor_id` INT NOT NULL,
    `package_id` INT NULL,
    `title` VARCHAR(255) NOT NULL,
    `background` TEXT NOT NULL,
    `target_audience` TEXT NOT NULL,
    `promotion_plan` TEXT NOT NULL,
    `custom_benefits` TEXT NULL,
    `proposed_amount` BIGINT NOT NULL,
    `estimated_roi_pct` DECIMAL(5,2) DEFAULT 250.00,
    `attachment_url` VARCHAR(255) NULL,
    `status` ENUM('Draft', 'Dikirim', 'Dibaca', 'Review', 'Negosiasi', 'Diterima', 'Ditolak', 'Kontrak Ditandatangani', 'Selesai') DEFAULT 'Dikirim',
    `rejection_reason` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`eo_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`sponsor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`package_id`) REFERENCES `sponsorship_packages`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Negotiation Room (E2EE)
CREATE TABLE IF NOT EXISTS `negotiation_rooms` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `proposal_id` INT NOT NULL,
    `eo_id` INT NOT NULL,
    `sponsor_id` INT NOT NULL,
    `status` ENUM('Aktif', 'Kesepakatan', 'Ditolak', 'Selesai') DEFAULT 'Aktif',
    `current_offer_amount` BIGINT NOT NULL,
    `current_tier` VARCHAR(50) DEFAULT 'Gold',
    `deal_summary_json` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`proposal_id`) REFERENCES `proposals`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`eo_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`sponsor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Pesan Negosiasi (E2EE Chat)
CREATE TABLE IF NOT EXISTS `negotiation_messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `room_id` INT NOT NULL,
    `sender_id` INT NOT NULL,
    `message` TEXT NOT NULL,
    `is_system` TINYINT(1) DEFAULT 0,
    `attachment_name` VARCHAR(255) NULL,
    `attachment_url` VARCHAR(255) NULL,
    `offer_amount` BIGINT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`room_id`) REFERENCES `negotiation_rooms`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Kontrak / MoU
CREATE TABLE IF NOT EXISTS `contracts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `proposal_id` INT NOT NULL,
    `room_id` INT NULL,
    `contract_number` VARCHAR(100) NOT NULL UNIQUE,
    `agreement_amount` BIGINT NOT NULL,
    `terms_text` TEXT NOT NULL,
    `eo_signed_at` DATETIME NULL,
    `sponsor_signed_at` DATETIME NULL,
    `eo_signature` TEXT NULL,
    `sponsor_signature` TEXT NULL,
    `status` ENUM('Draft', 'Menunggu Tanda Tangan EO', 'Menunggu Tanda Tangan Sponsor', 'Aktif', 'Selesai') DEFAULT 'Draft',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`proposal_id`) REFERENCES `proposals`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`room_id`) REFERENCES `negotiation_rooms`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. Escrow Milestones (Pembayaran Bertahap 30% - 40% - 30%)
CREATE TABLE IF NOT EXISTS `escrow_milestones` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `contract_id` INT NOT NULL,
    `milestone_number` INT NOT NULL,
    `milestone_title` VARCHAR(200) NOT NULL,
    `percentage` INT NOT NULL,
    `amount` BIGINT NOT NULL,
    `status` ENUM('Pending', 'Dana Masuk', 'Diverifikasi', 'Dicairkan', 'Disengketakan') DEFAULT 'Pending',
    `verification_notes` TEXT NULL,
    `proof_file` VARCHAR(255) NULL,
    `proof_uploaded_at` DATETIME NULL,
    `released_at` DATETIME NULL,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`contract_id`) REFERENCES `contracts`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. Event Impact Analytics
CREATE TABLE IF NOT EXISTS `event_analytics` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `event_id` INT NOT NULL,
    `sponsor_id` INT NOT NULL,
    `contract_id` INT NULL,
    `actual_attendees` INT NOT NULL,
    `social_impressions` INT NOT NULL,
    `engagement_rate` DECIMAL(5,2) NOT NULL,
    `media_coverage_count` INT NOT NULL,
    `estimated_roi_pct` DECIMAL(5,2) NOT NULL,
    `roi_multiplier` DECIMAL(4,2) NOT NULL,
    `documentation_photos_json` TEXT NULL,
    `benefit_realization_json` TEXT NULL,
    `report_document` VARCHAR(255) NULL,
    `submitted_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`sponsor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`contract_id`) REFERENCES `contracts`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 12. Notifikasi
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `message` TEXT NOT NULL,
    `link` VARCHAR(255) NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 13. Kategori & Taksonomi
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `type` ENUM('event', 'industry', 'csr') NOT NULL,
    `icon` VARCHAR(50) DEFAULT 'tag'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 14. Sengketa Pembayaran (Disputes)
CREATE TABLE IF NOT EXISTS `disputes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `contract_id` INT NOT NULL,
    `milestone_id` INT NOT NULL,
    `complainant_id` INT NOT NULL,
    `reason` TEXT NOT NULL,
    `evidence_file` VARCHAR(255) NULL,
    `status` ENUM('open', 'under_review', 'resolved', 'rejected') DEFAULT 'open',
    `admin_notes` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `resolved_at` DATETIME NULL,
    FOREIGN KEY (`contract_id`) REFERENCES `contracts`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`milestone_id`) REFERENCES `escrow_milestones`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`complainant_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
