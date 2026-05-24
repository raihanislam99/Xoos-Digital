CREATE TABLE IF NOT EXISTS `tasks` (
  `id` VARCHAR(36) NOT NULL,
  `title` VARCHAR(100) NOT NULL,
  `description` VARCHAR(500) DEFAULT NULL,
  `priority` ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
  `status` ENUM('todo', 'in-progress', 'done') NOT NULL DEFAULT 'todo',
  `due_date` DATE DEFAULT NULL,
  `assignee_type` ENUM('person', 'company') DEFAULT NULL,
  `assignee_name` VARCHAR(100) DEFAULT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` BIGINT NOT NULL,
  `updated_at` BIGINT NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_priority` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
