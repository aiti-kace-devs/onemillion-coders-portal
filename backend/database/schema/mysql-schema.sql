/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `admin_course`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_course` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` bigint unsigned NOT NULL,
  `course_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admin_course_admin_id_course_id_unique` (`admin_id`,`course_id`),
  KEY `admin_course_admin_id_index` (`admin_id`),
  KEY `admin_course_course_id_index` (`course_id`),
  CONSTRAINT `admin_course_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`),
  CONSTRAINT `admin_course_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admins` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_super` tinyint(1) NOT NULL DEFAULT '0',
  `super` tinyint(1) NOT NULL DEFAULT '0',
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `preferences` json DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admins_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `admission_rejections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admission_rejections` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `course_id` bigint unsigned NOT NULL,
  `rejected_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `admission_rejections_user_id_index` (`user_id`),
  KEY `admission_rejections_course_id_index` (`course_id`),
  KEY `admission_rejections_rejected_at_index` (`rejected_at`),
  CONSTRAINT `admission_rejections_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `admission_rejections_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`userId`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `admittedstudents`;
/*!50001 DROP VIEW IF EXISTS `admittedstudents`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `admittedstudents` AS SELECT 
 1 AS `id`,
 1 AS `fullname`,
 1 AS `email`,
 1 AS `gender`,
 1 AS `mobile_no`,
 1 AS `age`,
 1 AS `exam`,
 1 AS `registered_course`,
 1 AS `shortlist`,
 1 AS `user_status`,
 1 AS `user_registration_date`,
 1 AS `user_updated_at`,
 1 AS `userId`,
 1 AS `card_type`,
 1 AS `ghcard`,
 1 AS `verification_date`,
 1 AS `verified_by`,
 1 AS `network_type`,
 1 AS `exam_score`,
 1 AS `exam_set`,
 1 AS `confirmed`,
 1 AS `submitted`,
 1 AS `email_sent`,
 1 AS `location`,
 1 AS `session_id`,
 1 AS `user_admission_status`,
 1 AS `course_name`,
 1 AS `course_location`,
 1 AS `course_duration`,
 1 AS `course_start_date`,
 1 AS `course_end_date`,
 1 AS `course_status`,
 1 AS `centre_id`,
 1 AS `programme_id`,
 1 AS `session_name`,
 1 AS `session_limit`,
 1 AS `course_time`,
 1 AS `session`,
 1 AS `session_status`,
 1 AS `whatsapp_link`,
 1 AS `programme_name`,
 1 AS `pprogramme_duration`,
 1 AS `programme_start_date`,
 1 AS `programme_end_date`,
 1 AS `programme_status`,
 1 AS `centre_name`,
 1 AS `branch_id`,
 1 AS `centre_status`,
 1 AS `region_name`,
 1 AS `branch_status`,
 1 AS `admission_date`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `app_configs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `app_configs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'string',
  `is_cached` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `app_configs_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `attendances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attendances` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `course_id` bigint unsigned NOT NULL,
  `date` date NOT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_attendance_search` (`user_id`,`course_id`,`date`),
  KEY `attendances_course_id_foreign` (`course_id`),
  CONSTRAINT `attendances_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `attendances_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`userId`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `branches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `branches_title_unique` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `centres`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `centres` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `branch_id` bigint unsigned NOT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `centres_title_unique` (`title`),
  KEY `centres_branch_id_foreign` (`branch_id`),
  CONSTRAINT `centres_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `course_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `course_sessions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `course_id` bigint unsigned NOT NULL,
  `limit` int NOT NULL DEFAULT '100',
  `course_time` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `session` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `link` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `course_sessions_uuid_unique` (`uuid`),
  KEY `course_sessions_course_id_session_index` (`course_id`,`session`),
  CONSTRAINT `course_sessions_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `courses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `courses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `course_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `course` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `duration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `centre_id` bigint unsigned NOT NULL,
  `programme_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `courses_course_name_unique` (`course_name`),
  UNIQUE KEY `courses_centre_id_programme_id_unique` (`centre_id`,`programme_id`),
  KEY `courses_id_index` (`id`),
  KEY `courses_programme_id_foreign` (`programme_id`),
  CONSTRAINT `courses_centre_id_foreign` FOREIGN KEY (`centre_id`) REFERENCES `centres` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `courses_programme_id_foreign` FOREIGN KEY (`programme_id`) REFERENCES `programmes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dataprotection_officers_cohort2`;
/*!50001 DROP VIEW IF EXISTS `dataprotection_officers_cohort2`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `dataprotection_officers_cohort2` AS SELECT 
 1 AS `fullname`,
 1 AS `mobile_no`,
 1 AS `user_id`,
 1 AS `email`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `dataprotectionofficerswithoutsession`;
/*!50001 DROP VIEW IF EXISTS `dataprotectionofficerswithoutsession`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `dataprotectionofficerswithoutsession` AS SELECT 
 1 AS `fullname`,
 1 AS `email`,
 1 AS `mobile_no`,
 1 AS `userId`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `email_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_templates_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `form_responses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `form_responses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `form_id` bigint unsigned NOT NULL,
  `response_data` json NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `form_responses_uuid_unique` (`uuid`),
  KEY `form_responses_form_id_foreign` (`form_id`),
  CONSTRAINT `form_responses_form_id_foreign` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `forms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `forms` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `schema` json NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'L4eVD9',
  `message_after_registration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Thank you for your submission',
  `message_when_inactive` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'The form is not accepting submissions at this moment',
  `active` tinyint NOT NULL DEFAULT '1',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `forms_uuid_unique` (`uuid`),
  UNIQUE KEY `forms_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `group_admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `group_admin` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `group_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `group_admin_user_id_foreign` (`user_id`),
  CONSTRAINT `group_admin_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `oex_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `oex_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `oex_categories_name_unique` (`name`),
  KEY `oex_categories_id_index` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `oex_exam_masters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `oex_exam_masters` (
  `id` bigint unsigned NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` bigint unsigned NOT NULL,
  `passmark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `exam_date` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `exam_duration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `oex_exam_masters_title_unique` (`title`),
  KEY `oex_exam_masters_category_foreign` (`category`),
  CONSTRAINT `oex_exam_masters_category_foreign` FOREIGN KEY (`category`) REFERENCES `oex_categories` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `oex_question_masters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `oex_question_masters` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `exam_set_id` bigint unsigned NOT NULL,
  `questions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `ans` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `exam_id` bigint unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `oex_question_masters_exam_id_foreign` (`exam_id`),
  CONSTRAINT `oex_question_masters_exam_id_foreign` FOREIGN KEY (`exam_id`) REFERENCES `oex_exam_masters` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `oex_results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `oex_results` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `exam_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `yes_ans` int NOT NULL,
  `no_ans` int NOT NULL,
  `result_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `exam_set` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `oex_results_exam_id_user_id_index` (`exam_id`,`user_id`),
  KEY `oex_results_yes_ans_index` (`yes_ans`),
  KEY `oex_results_no_ans_index` (`no_ans`),
  KEY `oex_results_created_at_index` (`created_at`),
  KEY `oex_results_id_index` (`id`),
  KEY `oex_results_user_id_foreign` (`user_id`),
  CONSTRAINT `oex_results_exam_id_foreign` FOREIGN KEY (`exam_id`) REFERENCES `oex_exam_masters` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `oex_results_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pagebuilder__page_translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pagebuilder__page_translations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `page_id` int unsigned NOT NULL,
  `locale` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `meta_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `meta_description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `route` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pagebuilder__page_translations_page_id_locale_unique` (`page_id`,`locale`),
  CONSTRAINT `pagebuilder__page_translations_page_id_foreign` FOREIGN KEY (`page_id`) REFERENCES `pagebuilder__pages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pagebuilder__pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pagebuilder__pages` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `layout` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pagebuilder__settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pagebuilder__settings` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `setting` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_array` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pagebuilder__settings_setting_unique` (`setting`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pagebuilder__uploads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pagebuilder__uploads` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `public_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_file` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `server_file` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pagebuilder__uploads_public_id_unique` (`public_id`),
  UNIQUE KEY `pagebuilder__uploads_server_file_unique` (`server_file`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_activation_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_activation_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  KEY `password_activation_tokens_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_resets` (
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  KEY `password_resets_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `periods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `periods` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `starts_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `programmes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `programmes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `duration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `programmes_title_unique` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `role_admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_admin` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `role_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `role_admin_user_id_foreign` (`user_id`),
  CONSTRAINT `role_admin_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sms_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sms_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sms_templates_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `studentswithoutsession`;
/*!50001 DROP VIEW IF EXISTS `studentswithoutsession`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `studentswithoutsession` AS SELECT 
 1 AS `id`,
 1 AS `fullname`,
 1 AS `email`,
 1 AS `gender`,
 1 AS `mobile_no`,
 1 AS `age`,
 1 AS `exam`,
 1 AS `registered_course`,
 1 AS `shortlist`,
 1 AS `user_status`,
 1 AS `user_registration_date`,
 1 AS `user_updated_at`,
 1 AS `userId`,
 1 AS `card_type`,
 1 AS `ghcard`,
 1 AS `verification_date`,
 1 AS `verified_by`,
 1 AS `network_type`,
 1 AS `exam_score`,
 1 AS `exam_set`,
 1 AS `confirmed`,
 1 AS `submitted`,
 1 AS `email_sent`,
 1 AS `location`,
 1 AS `session_id`,
 1 AS `user_admission_status`,
 1 AS `course_name`,
 1 AS `course_location`,
 1 AS `course_duration`,
 1 AS `course_start_date`,
 1 AS `course_end_date`,
 1 AS `course_status`,
 1 AS `centre_id`,
 1 AS `programme_id`,
 1 AS `session_name`,
 1 AS `session_limit`,
 1 AS `course_time`,
 1 AS `session`,
 1 AS `session_status`,
 1 AS `whatsapp_link`,
 1 AS `programme_name`,
 1 AS `pprogramme_duration`,
 1 AS `programme_start_date`,
 1 AS `programme_end_date`,
 1 AS `programme_status`,
 1 AS `centre_name`,
 1 AS `branch_id`,
 1 AS `centre_status`,
 1 AS `region_name`,
 1 AS `branch_status`,
 1 AS `admission_date`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `studentswithsession`;
/*!50001 DROP VIEW IF EXISTS `studentswithsession`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `studentswithsession` AS SELECT 
 1 AS `id`,
 1 AS `fullname`,
 1 AS `email`,
 1 AS `gender`,
 1 AS `mobile_no`,
 1 AS `age`,
 1 AS `exam`,
 1 AS `registered_course`,
 1 AS `shortlist`,
 1 AS `user_status`,
 1 AS `user_registration_date`,
 1 AS `user_updated_at`,
 1 AS `userId`,
 1 AS `card_type`,
 1 AS `ghcard`,
 1 AS `verification_date`,
 1 AS `verified_by`,
 1 AS `network_type`,
 1 AS `exam_score`,
 1 AS `exam_set`,
 1 AS `confirmed`,
 1 AS `submitted`,
 1 AS `email_sent`,
 1 AS `location`,
 1 AS `session_id`,
 1 AS `user_admission_status`,
 1 AS `course_name`,
 1 AS `course_location`,
 1 AS `course_duration`,
 1 AS `course_start_date`,
 1 AS `course_end_date`,
 1 AS `course_status`,
 1 AS `centre_id`,
 1 AS `programme_id`,
 1 AS `session_name`,
 1 AS `session_limit`,
 1 AS `course_time`,
 1 AS `session`,
 1 AS `session_status`,
 1 AS `whatsapp_link`,
 1 AS `programme_name`,
 1 AS `pprogramme_duration`,
 1 AS `programme_start_date`,
 1 AS `programme_end_date`,
 1 AS `programme_status`,
 1 AS `centre_name`,
 1 AS `branch_id`,
 1 AS `centre_status`,
 1 AS `region_name`,
 1 AS `branch_status`,
 1 AS `admission_date`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `user_admission`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_admission` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `course_id` bigint unsigned NOT NULL,
  `confirmed` datetime DEFAULT NULL,
  `submitted` datetime DEFAULT NULL,
  `email_sent` datetime DEFAULT NULL,
  `location` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `session` bigint unsigned DEFAULT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_admission_user_id_unique` (`user_id`),
  KEY `user_admission_user_id_course_id_index` (`user_id`,`course_id`),
  KEY `user_admission_email_sent_index` (`email_sent`),
  KEY `user_admission_submitted_index` (`submitted`),
  KEY `user_admission_confirmed_index` (`confirmed`),
  KEY `user_admission_session_foreign` (`session`),
  KEY `user_admission_course_id_foreign` (`course_id`),
  CONSTRAINT `user_admission_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `user_admission_session_foreign` FOREIGN KEY (`session`) REFERENCES `course_sessions` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `user_admission_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`userId`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_exams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_exams` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `exam_id` bigint unsigned NOT NULL,
  `std_status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `exam_joined` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `started` datetime DEFAULT NULL,
  `submitted` datetime DEFAULT NULL,
  `user_feedback` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_exams_user_id_index` (`user_id`),
  KEY `user_exams_exam_id_index` (`exam_id`),
  KEY `user_exams_user_id_exam_id_index` (`user_id`,`exam_id`),
  KEY `user_exams_exam_joined_index` (`exam_joined`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `previous_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `gender` enum('male','female') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `mobile_no` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `age` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `exam` bigint unsigned NOT NULL,
  `registered_course` bigint unsigned NOT NULL,
  `shortlist` int DEFAULT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `userId` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `card_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ghcard` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `verification_date` datetime DEFAULT NULL,
  `verified_by` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `details_updated_at` datetime DEFAULT NULL,
  `contact` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `network_type` enum('mtn','telecel','airteltigo') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `form_response_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `idx_email` (`email`),
  UNIQUE KEY `users_userid_unique` (`userId`),
  UNIQUE KEY `idx_mobile_no` (`mobile_no`),
  KEY `users_form_response_id_foreign` (`form_response_id`),
  KEY `users_id_index` (`id`),
  KEY `users_exam_foreign` (`exam`),
  KEY `users_registered_course_foreign` (`registered_course`),
  CONSTRAINT `users_exam_foreign` FOREIGN KEY (`exam`) REFERENCES `oex_exam_masters` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `users_form_response_id_foreign` FOREIGN KEY (`form_response_id`) REFERENCES `form_responses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_registered_course_foreign` FOREIGN KEY (`registered_course`) REFERENCES `courses` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `vDailyCourseAttendance`;
/*!50001 DROP VIEW IF EXISTS `vDailyCourseAttendance`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `vDailyCourseAttendance` AS SELECT 
 1 AS `course_name`,
 1 AS `attendance_date`,
 1 AS `total`,
 1 AS `course_id`,
 1 AS `session_name`,
 1 AS `branch_name`,
 1 AS `programme_name`,
 1 AS `centre_name`,
 1 AS `session_id`,
 1 AS `branch_id`,
 1 AS `programme_id`,
 1 AS `centre_id`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `v_daily_course_attendance`;
/*!50001 DROP VIEW IF EXISTS `v_daily_course_attendance`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_daily_course_attendance` AS SELECT 
 1 AS `course_name`,
 1 AS `attendance_date`,
 1 AS `total`,
 1 AS `course_id`,
 1 AS `session_name`,
 1 AS `branch_name`,
 1 AS `programme_name`,
 1 AS `centre_name`,
 1 AS `session_id`,
 1 AS `branch_id`,
 1 AS `programme_id`,
 1 AS `centre_id`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `v_daily_course_session_attendance`;
/*!50001 DROP VIEW IF EXISTS `v_daily_course_session_attendance`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_daily_course_session_attendance` AS SELECT 
 1 AS `course_name`,
 1 AS `attendance_date`,
 1 AS `total`,
 1 AS `course_id`,
 1 AS `session_name`,
 1 AS `branch_name`,
 1 AS `programme_name`,
 1 AS `centre_name`,
 1 AS `session_id`,
 1 AS `branch_id`,
 1 AS `programme_id`,
 1 AS `centre_id`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `vw_admitted_student_contact`;
/*!50001 DROP VIEW IF EXISTS `vw_admitted_student_contact`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `vw_admitted_student_contact` AS SELECT 
 1 AS `mobile_no`,
 1 AS `name`,
 1 AS `email`,
 1 AS `course_name`,
 1 AS `course_session_name`,
 1 AS `session`,
 1 AS `centre_id`,
 1 AS `programme_id`,
 1 AS `centre_name`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `vw_session_metrics`;
/*!50001 DROP VIEW IF EXISTS `vw_session_metrics`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `vw_session_metrics` AS SELECT 
 1 AS `id`,
 1 AS `name`,
 1 AS `limit`,
 1 AS `remaining`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `vwstudentwithresultslist`;
/*!50001 DROP VIEW IF EXISTS `vwstudentwithresultslist`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `vwstudentwithresultslist` AS SELECT 
 1 AS `id`,
 1 AS `fullname`,
 1 AS `email`,
 1 AS `gender`,
 1 AS `mobile_no`,
 1 AS `age`,
 1 AS `exam`,
 1 AS `registered_course`,
 1 AS `shortlist`,
 1 AS `user_status`,
 1 AS `user_registration_date`,
 1 AS `user_updated_at`,
 1 AS `userId`,
 1 AS `card_type`,
 1 AS `ghcard`,
 1 AS `verification_date`,
 1 AS `verified_by`,
 1 AS `network_type`,
 1 AS `exam_score`,
 1 AS `exam_set`,
 1 AS `confirmed`,
 1 AS `submitted`,
 1 AS `email_sent`,
 1 AS `location`,
 1 AS `session_id`,
 1 AS `user_admission_status`,
 1 AS `course_name`,
 1 AS `course_location`,
 1 AS `course_duration`,
 1 AS `course_start_date`,
 1 AS `course_end_date`,
 1 AS `course_status`,
 1 AS `centre_id`,
 1 AS `programme_id`,
 1 AS `session_name`,
 1 AS `session_limit`,
 1 AS `course_time`,
 1 AS `session`,
 1 AS `session_status`,
 1 AS `whatsapp_link`,
 1 AS `programme_name`,
 1 AS `pprogramme_duration`,
 1 AS `programme_start_date`,
 1 AS `programme_end_date`,
 1 AS `programme_status`,
 1 AS `centre_name`,
 1 AS `branch_id`,
 1 AS `centre_status`,
 1 AS `region_name`,
 1 AS `branch_status`,
 1 AS `admission_date`*/;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `⁠ vw_test ⁠`;
/*!50001 DROP VIEW IF EXISTS `⁠ vw_test ⁠`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `⁠ vw_test ⁠` AS SELECT 
 1 AS `email`,
 1 AS `mobile_no`,
 1 AS `fullname`*/;
SET character_set_client = @saved_cs_client;
/*!50001 DROP VIEW IF EXISTS `admittedstudents`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `admittedstudents` AS select `vwstudentwithresultslist`.`id` AS `id`,`vwstudentwithresultslist`.`fullname` AS `fullname`,`vwstudentwithresultslist`.`email` AS `email`,`vwstudentwithresultslist`.`gender` AS `gender`,`vwstudentwithresultslist`.`mobile_no` AS `mobile_no`,`vwstudentwithresultslist`.`age` AS `age`,`vwstudentwithresultslist`.`exam` AS `exam`,`vwstudentwithresultslist`.`registered_course` AS `registered_course`,`vwstudentwithresultslist`.`shortlist` AS `shortlist`,`vwstudentwithresultslist`.`user_status` AS `user_status`,`vwstudentwithresultslist`.`user_registration_date` AS `user_registration_date`,`vwstudentwithresultslist`.`user_updated_at` AS `user_updated_at`,`vwstudentwithresultslist`.`userId` AS `userId`,`vwstudentwithresultslist`.`card_type` AS `card_type`,`vwstudentwithresultslist`.`ghcard` AS `ghcard`,`vwstudentwithresultslist`.`verification_date` AS `verification_date`,`vwstudentwithresultslist`.`verified_by` AS `verified_by`,`vwstudentwithresultslist`.`network_type` AS `network_type`,`vwstudentwithresultslist`.`exam_score` AS `exam_score`,`vwstudentwithresultslist`.`exam_set` AS `exam_set`,`vwstudentwithresultslist`.`confirmed` AS `confirmed`,`vwstudentwithresultslist`.`submitted` AS `submitted`,`vwstudentwithresultslist`.`email_sent` AS `email_sent`,`vwstudentwithresultslist`.`location` AS `location`,`vwstudentwithresultslist`.`session_id` AS `session_id`,`vwstudentwithresultslist`.`user_admission_status` AS `user_admission_status`,`vwstudentwithresultslist`.`course_name` AS `course_name`,`vwstudentwithresultslist`.`course_location` AS `course_location`,`vwstudentwithresultslist`.`course_duration` AS `course_duration`,`vwstudentwithresultslist`.`course_start_date` AS `course_start_date`,`vwstudentwithresultslist`.`course_end_date` AS `course_end_date`,`vwstudentwithresultslist`.`course_status` AS `course_status`,`vwstudentwithresultslist`.`centre_id` AS `centre_id`,`vwstudentwithresultslist`.`programme_id` AS `programme_id`,`vwstudentwithresultslist`.`session_name` AS `session_name`,`vwstudentwithresultslist`.`session_limit` AS `session_limit`,`vwstudentwithresultslist`.`course_time` AS `course_time`,`vwstudentwithresultslist`.`session` AS `session`,`vwstudentwithresultslist`.`session_status` AS `session_status`,`vwstudentwithresultslist`.`whatsapp_link` AS `whatsapp_link`,`vwstudentwithresultslist`.`programme_name` AS `programme_name`,`vwstudentwithresultslist`.`pprogramme_duration` AS `pprogramme_duration`,`vwstudentwithresultslist`.`programme_start_date` AS `programme_start_date`,`vwstudentwithresultslist`.`programme_end_date` AS `programme_end_date`,`vwstudentwithresultslist`.`programme_status` AS `programme_status`,`vwstudentwithresultslist`.`centre_name` AS `centre_name`,`vwstudentwithresultslist`.`branch_id` AS `branch_id`,`vwstudentwithresultslist`.`centre_status` AS `centre_status`,`vwstudentwithresultslist`.`region_name` AS `region_name`,`vwstudentwithresultslist`.`branch_status` AS `branch_status`,`vwstudentwithresultslist`.`admission_date` AS `admission_date` from `vwstudentwithresultslist` where (`vwstudentwithresultslist`.`admission_date` is not null) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `dataprotection_officers_cohort2`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `dataprotection_officers_cohort2` AS select `admittedstudents`.`fullname` AS `fullname`,`admittedstudents`.`mobile_no` AS `mobile_no`,`admittedstudents`.`userId` AS `user_id`,`admittedstudents`.`email` AS `email` from `admittedstudents` where (`admittedstudents`.`session_id` = 18) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `dataprotectionofficerswithoutsession`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `dataprotectionofficerswithoutsession` AS select `studentswithoutsession`.`fullname` AS `fullname`,`studentswithoutsession`.`email` AS `email`,`studentswithoutsession`.`mobile_no` AS `mobile_no`,`studentswithoutsession`.`userId` AS `userId` from `studentswithoutsession` where (`studentswithoutsession`.`registered_course` = 25) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `studentswithoutsession`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `studentswithoutsession` AS select `admittedstudents`.`id` AS `id`,`admittedstudents`.`fullname` AS `fullname`,`admittedstudents`.`email` AS `email`,`admittedstudents`.`gender` AS `gender`,`admittedstudents`.`mobile_no` AS `mobile_no`,`admittedstudents`.`age` AS `age`,`admittedstudents`.`exam` AS `exam`,`admittedstudents`.`registered_course` AS `registered_course`,`admittedstudents`.`shortlist` AS `shortlist`,`admittedstudents`.`user_status` AS `user_status`,`admittedstudents`.`user_registration_date` AS `user_registration_date`,`admittedstudents`.`user_updated_at` AS `user_updated_at`,`admittedstudents`.`userId` AS `userId`,`admittedstudents`.`card_type` AS `card_type`,`admittedstudents`.`ghcard` AS `ghcard`,`admittedstudents`.`verification_date` AS `verification_date`,`admittedstudents`.`verified_by` AS `verified_by`,`admittedstudents`.`network_type` AS `network_type`,`admittedstudents`.`exam_score` AS `exam_score`,`admittedstudents`.`exam_set` AS `exam_set`,`admittedstudents`.`confirmed` AS `confirmed`,`admittedstudents`.`submitted` AS `submitted`,`admittedstudents`.`email_sent` AS `email_sent`,`admittedstudents`.`location` AS `location`,`admittedstudents`.`session_id` AS `session_id`,`admittedstudents`.`user_admission_status` AS `user_admission_status`,`admittedstudents`.`course_name` AS `course_name`,`admittedstudents`.`course_location` AS `course_location`,`admittedstudents`.`course_duration` AS `course_duration`,`admittedstudents`.`course_start_date` AS `course_start_date`,`admittedstudents`.`course_end_date` AS `course_end_date`,`admittedstudents`.`course_status` AS `course_status`,`admittedstudents`.`centre_id` AS `centre_id`,`admittedstudents`.`programme_id` AS `programme_id`,`admittedstudents`.`session_name` AS `session_name`,`admittedstudents`.`session_limit` AS `session_limit`,`admittedstudents`.`course_time` AS `course_time`,`admittedstudents`.`session` AS `session`,`admittedstudents`.`session_status` AS `session_status`,`admittedstudents`.`whatsapp_link` AS `whatsapp_link`,`admittedstudents`.`programme_name` AS `programme_name`,`admittedstudents`.`pprogramme_duration` AS `pprogramme_duration`,`admittedstudents`.`programme_start_date` AS `programme_start_date`,`admittedstudents`.`programme_end_date` AS `programme_end_date`,`admittedstudents`.`programme_status` AS `programme_status`,`admittedstudents`.`centre_name` AS `centre_name`,`admittedstudents`.`branch_id` AS `branch_id`,`admittedstudents`.`centre_status` AS `centre_status`,`admittedstudents`.`region_name` AS `region_name`,`admittedstudents`.`branch_status` AS `branch_status`,`admittedstudents`.`admission_date` AS `admission_date` from `admittedstudents` where (`admittedstudents`.`session_id` is null) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `studentswithsession`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `studentswithsession` AS select `admittedstudents`.`id` AS `id`,`admittedstudents`.`fullname` AS `fullname`,`admittedstudents`.`email` AS `email`,`admittedstudents`.`gender` AS `gender`,`admittedstudents`.`mobile_no` AS `mobile_no`,`admittedstudents`.`age` AS `age`,`admittedstudents`.`exam` AS `exam`,`admittedstudents`.`registered_course` AS `registered_course`,`admittedstudents`.`shortlist` AS `shortlist`,`admittedstudents`.`user_status` AS `user_status`,`admittedstudents`.`user_registration_date` AS `user_registration_date`,`admittedstudents`.`user_updated_at` AS `user_updated_at`,`admittedstudents`.`userId` AS `userId`,`admittedstudents`.`card_type` AS `card_type`,`admittedstudents`.`ghcard` AS `ghcard`,`admittedstudents`.`verification_date` AS `verification_date`,`admittedstudents`.`verified_by` AS `verified_by`,`admittedstudents`.`network_type` AS `network_type`,`admittedstudents`.`exam_score` AS `exam_score`,`admittedstudents`.`exam_set` AS `exam_set`,`admittedstudents`.`confirmed` AS `confirmed`,`admittedstudents`.`submitted` AS `submitted`,`admittedstudents`.`email_sent` AS `email_sent`,`admittedstudents`.`location` AS `location`,`admittedstudents`.`session_id` AS `session_id`,`admittedstudents`.`user_admission_status` AS `user_admission_status`,`admittedstudents`.`course_name` AS `course_name`,`admittedstudents`.`course_location` AS `course_location`,`admittedstudents`.`course_duration` AS `course_duration`,`admittedstudents`.`course_start_date` AS `course_start_date`,`admittedstudents`.`course_end_date` AS `course_end_date`,`admittedstudents`.`course_status` AS `course_status`,`admittedstudents`.`centre_id` AS `centre_id`,`admittedstudents`.`programme_id` AS `programme_id`,`admittedstudents`.`session_name` AS `session_name`,`admittedstudents`.`session_limit` AS `session_limit`,`admittedstudents`.`course_time` AS `course_time`,`admittedstudents`.`session` AS `session`,`admittedstudents`.`session_status` AS `session_status`,`admittedstudents`.`whatsapp_link` AS `whatsapp_link`,`admittedstudents`.`programme_name` AS `programme_name`,`admittedstudents`.`pprogramme_duration` AS `pprogramme_duration`,`admittedstudents`.`programme_start_date` AS `programme_start_date`,`admittedstudents`.`programme_end_date` AS `programme_end_date`,`admittedstudents`.`programme_status` AS `programme_status`,`admittedstudents`.`centre_name` AS `centre_name`,`admittedstudents`.`branch_id` AS `branch_id`,`admittedstudents`.`centre_status` AS `centre_status`,`admittedstudents`.`region_name` AS `region_name`,`admittedstudents`.`branch_status` AS `branch_status`,`admittedstudents`.`admission_date` AS `admission_date` from `admittedstudents` where (`admittedstudents`.`session_id` is not null) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `vDailyCourseAttendance`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vDailyCourseAttendance` AS select `s`.`course_name` AS `course_name`,`at`.`attendance_date` AS `attendance_date`,`at`.`total` AS `total`,`at`.`course_id` AS `course_id`,`cs`.`name` AS `session_name`,`b`.`title` AS `branch_name`,`p`.`title` AS `programme_name`,`c`.`title` AS `centre_name`,`cs`.`id` AS `session_id`,`b`.`id` AS `branch_id`,`p`.`id` AS `programme_id`,`c`.`id` AS `centre_id` from ((((((`user_admission` `ua` left join `courses` `s` on((`s`.`id` = `ua`.`course_id`))) left join `programmes` `p` on((`p`.`id` = `s`.`programme_id`))) left join `centres` `c` on((`c`.`id` = `s`.`centre_id`))) left join `branches` `b` on((`b`.`id` = `c`.`branch_id`))) left join `course_sessions` `cs` on((`cs`.`id` = `ua`.`session`))) left join (select date_format(`a`.`date`,'%Y-%m-%d') AS `attendance_date`,count(0) AS `total`,max(`a`.`course_id`) AS `course_id` from `attendances` `a` group by `a`.`course_id`,`attendance_date` order by `a`.`course_id`,`attendance_date`) `at` on((`at`.`course_id` = `s`.`id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `v_daily_course_attendance`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `v_daily_course_attendance` AS select `s`.`course_name` AS `course_name`,`at`.`attendance_date` AS `attendance_date`,`at`.`total` AS `total`,`s`.`id` AS `course_id`,`cs`.`name` AS `session_name`,`b`.`title` AS `branch_name`,`p`.`title` AS `programme_name`,`c`.`title` AS `centre_name`,`cs`.`id` AS `session_id`,`b`.`id` AS `branch_id`,`p`.`id` AS `programme_id`,`c`.`id` AS `centre_id` from (((((`courses` `s` left join `course_sessions` `cs` on((`cs`.`course_id` = `s`.`id`))) left join `programmes` `p` on((`p`.`id` = `s`.`programme_id`))) left join `centres` `c` on((`c`.`id` = `s`.`centre_id`))) left join `branches` `b` on((`b`.`id` = `c`.`branch_id`))) left join (select date_format(`a`.`date`,'%Y-%m-%d') AS `attendance_date`,count(distinct `a`.`user_id`) AS `total`,`ua`.`course_id` AS `course_id` from (`attendances` `a` left join `user_admission` `ua` on(((`ua`.`user_id` = `a`.`user_id`) and (`ua`.`course_id` = `a`.`course_id`)))) group by `ua`.`course_id`,date_format(`a`.`date`,'%Y-%m-%d')) `at` on((`at`.`course_id` = `s`.`id`))) order by `s`.`course_name`,`cs`.`name`,`at`.`attendance_date` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `v_daily_course_session_attendance`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `v_daily_course_session_attendance` AS select `s`.`course_name` AS `course_name`,`at`.`attendance_date` AS `attendance_date`,`at`.`total` AS `total`,`s`.`id` AS `course_id`,`cs`.`name` AS `session_name`,`b`.`title` AS `branch_name`,`p`.`title` AS `programme_name`,`c`.`title` AS `centre_name`,`cs`.`id` AS `session_id`,`b`.`id` AS `branch_id`,`p`.`id` AS `programme_id`,`c`.`id` AS `centre_id` from (((((`course_sessions` `cs` left join `courses` `s` on((`s`.`id` = `cs`.`course_id`))) left join `programmes` `p` on((`p`.`id` = `s`.`programme_id`))) left join `centres` `c` on((`c`.`id` = `s`.`centre_id`))) left join `branches` `b` on((`b`.`id` = `c`.`branch_id`))) left join (select date_format(`a`.`date`,'%Y-%m-%d') AS `attendance_date`,count(distinct `a`.`user_id`) AS `total`,`ua`.`session` AS `session_id`,`ua`.`course_id` AS `course_id` from (`attendances` `a` left join `user_admission` `ua` on(((`ua`.`user_id` = `a`.`user_id`) and (`ua`.`course_id` = `a`.`course_id`)))) group by `ua`.`session`,date_format(`a`.`date`,'%Y-%m-%d'),`ua`.`course_id`) `at` on(((`at`.`session_id` = `cs`.`id`) and (`at`.`course_id` = `s`.`id`)))) order by `s`.`course_name`,`cs`.`name`,`at`.`attendance_date` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `vw_admitted_student_contact`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_admitted_student_contact` AS select `u`.`mobile_no` AS `mobile_no`,`u`.`name` AS `name`,`u`.`email` AS `email`,`c`.`course_name` AS `course_name`,`cs`.`name` AS `course_session_name`,`cs`.`session` AS `session`,`c`.`centre_id` AS `centre_id`,`c`.`programme_id` AS `programme_id`,`ct`.`title` AS `centre_name` from ((`user_admission` `ua` left join ((`users` `u` join `courses` `c` on((`u`.`registered_course` = `c`.`id`))) join `centres` `ct` on((`ct`.`id` = `c`.`centre_id`))) on((`u`.`userId` = `ua`.`user_id`))) join `course_sessions` `cs` on((`cs`.`id` = `ua`.`session`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `vw_session_metrics`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_session_metrics` AS select `cs`.`id` AS `id`,`cs`.`name` AS `name`,`cs`.`limit` AS `limit`,(`cs`.`limit` - (select count(`user_admission`.`id`) from `user_admission` where (`user_admission`.`session` = `cs`.`id`))) AS `remaining` from `course_sessions` `cs` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `vwstudentwithresultslist`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vwstudentwithresultslist` AS select `users`.`id` AS `id`,`users`.`name` AS `fullname`,`users`.`email` AS `email`,`users`.`gender` AS `gender`,`users`.`mobile_no` AS `mobile_no`,`users`.`age` AS `age`,`users`.`exam` AS `exam`,`users`.`registered_course` AS `registered_course`,`users`.`shortlist` AS `shortlist`,`users`.`status` AS `user_status`,`users`.`created_at` AS `user_registration_date`,`users`.`updated_at` AS `user_updated_at`,`users`.`userId` AS `userId`,`users`.`card_type` AS `card_type`,`users`.`ghcard` AS `ghcard`,`users`.`verification_date` AS `verification_date`,`users`.`verified_by` AS `verified_by`,`users`.`network_type` AS `network_type`,`oex_results`.`yes_ans` AS `exam_score`,`oex_results`.`exam_set` AS `exam_set`,`user_admission`.`confirmed` AS `confirmed`,`user_admission`.`submitted` AS `submitted`,`user_admission`.`email_sent` AS `email_sent`,`user_admission`.`location` AS `location`,`user_admission`.`session` AS `session_id`,`user_admission`.`status` AS `user_admission_status`,`courses`.`course_name` AS `course_name`,`courses`.`location` AS `course_location`,`courses`.`duration` AS `course_duration`,`courses`.`start_date` AS `course_start_date`,`courses`.`end_date` AS `course_end_date`,`courses`.`status` AS `course_status`,`courses`.`centre_id` AS `centre_id`,`courses`.`programme_id` AS `programme_id`,`course_sessions`.`name` AS `session_name`,`course_sessions`.`limit` AS `session_limit`,`course_sessions`.`course_time` AS `course_time`,`course_sessions`.`session` AS `session`,`course_sessions`.`status` AS `session_status`,`course_sessions`.`link` AS `whatsapp_link`,`programmes`.`title` AS `programme_name`,`programmes`.`duration` AS `pprogramme_duration`,`programmes`.`start_date` AS `programme_start_date`,`programmes`.`end_date` AS `programme_end_date`,`programmes`.`status` AS `programme_status`,`centres`.`title` AS `centre_name`,`centres`.`branch_id` AS `branch_id`,`centres`.`status` AS `centre_status`,`branches`.`title` AS `region_name`,`branches`.`status` AS `branch_status`,`user_admission`.`created_at` AS `admission_date` from (((((((`users` join `oex_results` on((`oex_results`.`user_id` = `users`.`id`))) left join `user_admission` on((`user_admission`.`user_id` = `users`.`userId`))) left join `courses` on((`courses`.`id` = `users`.`registered_course`))) left join `course_sessions` on((`course_sessions`.`course_id` = `user_admission`.`session`))) left join `programmes` on((`programmes`.`id` = `courses`.`programme_id`))) left join `centres` on((`centres`.`id` = `courses`.`centre_id`))) left join `branches` on((`branches`.`id` = `centres`.`branch_id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `⁠ vw_test ⁠`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `⁠ vw_test ⁠` AS select 'boatseth3@gmail.com' AS `email`,'+233247460450' AS `mobile_no`,'Seth Gyekye-Boateng' AS `fullname` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'2014_10_12_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'2014_10_12_100000_create_password_resets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'2019_08_19_000000_create_failed_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2019_12_14_000001_create_personal_access_tokens_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2021_12_06_091650_create_admins_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2021_12_06_163848_create_oex_categories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2021_12_06_164100_create_oex_exam_masters_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2021_12_06_164245_create_oex_question_masters_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2021_12_06_164519_create_oex_results_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2021_12_07_160154_create_user_exams_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2024_09_26_160641_add-start-column-to-user_exams_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2024_09_27_153425_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2024_10_02_101721_add_user_id_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2024_10_04_185808_add_user_feedback_to_user_exams_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2024_10_10_145502_create_user_admission_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2024_10_12_201147_create_attendance_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2024_10_13_115311_add_meeting_link_column_to_course_sessions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2024_10_19_164126_add_ghana_card_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2024_10_20_073048_add_columns_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2024_10_23_182557_add_super_to_admin',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2024_11_12_085723_create_branches_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2024_11_12_085733_create_centres_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2024_11_12_085745_create_programmes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2024_11_12_085753_create_periods_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2024_11_21_165759_add_gender_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2024_11_21_215725_add_contact_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2024_11_22_232146_add_network_type_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2024_11_23_183718_create-indexes-on-attendance-table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2024_11_24_170210_add_card_type_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2025_01_14_112608_create_forms_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2025_01_14_112611_create_form_responses_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2025_03_29_124144_add_centre_id_to_courses_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2025_03_30_102927_add_unique_code_to_forms_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2025_03_30_234534_add_description_column_to_forms_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2025_03_31_034839_add_registered_course_column_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2025_04_01_154503_create_sms_templates_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2025_04_01_223606_add_uuid_column_to_course_sessions_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2025_04_02_152139_add_form_response_id_to_users_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2025_04_02_223607_add_extra_fields',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2025_04_02_003247_add_age_to_users_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2025_04_03_221054_change_age_column_type_in_users_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2019_11_18_105032_create_pages_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2019_11_18_105615_create_uploads_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2020_04_18_064412_create_page_translations_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2020_04_18_065546_create_settings_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2025_04_05_201740_create_permission_tables',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2025_04_10_160922_add_indexes_to_tables',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2025_04_12_163847_add_question_set_id_to_oex_results_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2025_04_12_182950_remove-unique-index-from-course-session-table',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2025_04_12_201741_add_shortlist_column_to_users_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2025_04_13_015007_make_session_nullable_on_user_admission_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52,'2025_04_14_224330_create_admission_rejection_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2025_04_15_125352_add_email_and_mobile_index_to_users_table',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2025_04_16_100400_create_app_configs_table',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (55,'2025_04_16_143752_remove_location_from_attendances_table',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (56,'2025_04_23_111021_create_admin_course_table',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57,'2025_04_22_162950_create_email_templates_table',13);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (58,'2025_05_02_150654_add_details_updated_column_to_users_table',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (59,'2025_05_09_210137_create_report_views',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (60,'2025_05_21_183218_create_attendance_views',16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (62,'2025_07_01_223832_statamic_auth_tables',17);
