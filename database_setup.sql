-- ===================================================================
-- TO-DO MVC - Script tạo mới toàn bộ cơ sở dữ liệu
-- ===================================================================
-- DÙNG KHI: cài đặt lần đầu trên máy mới.
--
-- CẢNH BÁO: script này XÓA SẠCH database todo_schema nếu đã tồn tại.
-- Nếu bạn đã có dữ liệu và chỉ muốn cập nhật cấu trúc,
-- hãy chạy file database_migration_v2.sql thay cho file này.
-- ===================================================================

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- -----------------------------------------------------
-- Schema todo_schema
-- -----------------------------------------------------
DROP SCHEMA IF EXISTS `todo_schema` ;

CREATE SCHEMA IF NOT EXISTS `todo_schema` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ;
USE `todo_schema` ;

-- -----------------------------------------------------
-- Table `users`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `users` ;todo_schematodo_schema

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  -- Lưu hash bcrypt (60 ký tự), để 255 cho chắc khi PHP đổi thuật toán mặc định
  `password` VARCHAR(255) NOT NULL,
  `role` VARCHAR(20) NOT NULL DEFAULT 'user',
  `api_token` CHAR(64) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `email` (`email` ASC) VISIBLE,
  UNIQUE INDEX `uq_users_api_token` (`api_token` ASC) VISIBLE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `lists`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `lists` ;

CREATE TABLE IF NOT EXISTS `lists` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `fk_user_list` (`user_id` ASC) VISIBLE,
  CONSTRAINT `fk_user_list`
    FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`)
    ON DELETE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `tasks`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `tasks` ;

CREATE TABLE IF NOT EXISTS `tasks` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `list_id` INT NULL DEFAULT NULL,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT NULL DEFAULT NULL,
  `attachment_path` VARCHAR(255) NULL DEFAULT NULL,
  `attachment_name` VARCHAR(255) NULL DEFAULT NULL,
  `completed` TINYINT(1) NULL DEFAULT '0',
  `is_important` TINYINT(1) NULL DEFAULT '0',
  `priority` VARCHAR(20) NOT NULL DEFAULT 'normal',
  `progress` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `due_date` DATE NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  -- Xóa mềm: NULL = còn dùng, có ngày giờ = đang nằm trong thùng rác.
  -- Nhờ cột này, lỡ tay xóa vẫn khôi phục lại được.
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  `reminder_sent_at` TIMESTAMP NULL DEFAULT NULL,
  `reminder_queued_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_tasks_user_id` (`user_id` ASC) VISIBLE,
  INDEX `idx_tasks_list_id` (`list_id` ASC) VISIBLE,
  -- Gần như mọi truy vấn đều lọc "của user X và chưa xóa",
  -- nên đánh index gộp 2 cột này giúp query nhanh hơn nhiều khi dữ liệu lớn
  INDEX `idx_tasks_user_deleted` (`user_id` ASC, `deleted_at` ASC) VISIBLE,
  -- Phục vụ các bộ lọc theo hạn chót (Hôm nay / Quá hạn / Có hạn chót)
  INDEX `idx_tasks_due_date` (`due_date` ASC) VISIBLE,
  INDEX `idx_tasks_priority` (`priority` ASC) VISIBLE,
  INDEX `idx_tasks_progress` (`user_id` ASC, `progress` ASC, `deleted_at` ASC) VISIBLE,
  INDEX `idx_tasks_reminder` (`completed` ASC, `deleted_at` ASC, `due_date` ASC, `reminder_sent_at` ASC) VISIBLE,
  CONSTRAINT `fk_task_list`
    FOREIGN KEY (`list_id`)
    REFERENCES `lists` (`id`)
    ON DELETE SET NULL,
  CONSTRAINT `fk_user_task`
    FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`)
    ON DELETE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `job_queue` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` VARCHAR(80) NOT NULL,
  `payload` JSON NOT NULL,
  `status` ENUM('pending', 'processing', 'completed', 'failed') NOT NULL DEFAULT 'pending',
  `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `available_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reserved_at` TIMESTAMP NULL DEFAULT NULL,
  `completed_at` TIMESTAMP NULL DEFAULT NULL,
  `error_message` VARCHAR(1000) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_job_queue_next` (`type`, `status`, `available_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table `activity_logs`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `activity_logs` ;

CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `action` VARCHAR(50) NOT NULL,
  `entity_type` VARCHAR(50) NOT NULL,
  `entity_id` INT NULL DEFAULT NULL,
  `message` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_activity_user_date` (`user_id` ASC, `created_at` ASC) VISIBLE,
  CONSTRAINT `fk_activity_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`)
    ON DELETE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;

-- Bảng lưu thông tin Nhóm
CREATE TABLE IF NOT EXISTS `teams` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `description` VARCHAR(255) NULL DEFAULT NULL,
  `owner_id` INT NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_teams_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng thành viên và phân quyền trong nhóm (owner, admin, member)
CREATE TABLE IF NOT EXISTS `team_members` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `team_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `role` VARCHAR(20) NOT NULL DEFAULT 'member',
  `joined_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uq_team_user` (`team_id` ASC, `user_id` ASC),
  CONSTRAINT `fk_tm_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tm_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bổ sung cột team_id vào bảng tasks
ALTER TABLE `tasks` 
  ADD COLUMN `team_id` INT NULL DEFAULT NULL AFTER `list_id`,
  ADD INDEX `idx_tasks_team_id` (`team_id` ASC),
  ADD CONSTRAINT `fk_tasks_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE SET NULL;

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
