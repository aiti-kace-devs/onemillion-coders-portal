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
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_super` tinyint(1) NOT NULL DEFAULT '0',
  `super` tinyint(1) NOT NULL DEFAULT '0',
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `preferences` json DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admins_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `admission_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admission_batches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `year` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `completed` tinyint(1) NOT NULL DEFAULT '1',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
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
  KEY `attendances_course_id_date_index` (`course_id`,`date`),
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
  `status` tinyint DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `branches_title_unique` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `centres`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `centres` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `branch_id` bigint unsigned NOT NULL,
  `constituency_id` bigint unsigned DEFAULT NULL,
  `status` tinyint DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `gps_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gps_location` json DEFAULT NULL,
  `is_pwd_friendly` tinyint(1) NOT NULL DEFAULT '0',
  `wheelchair_accessible` tinyint(1) NOT NULL DEFAULT '0',
  `has_access_ramp` tinyint(1) NOT NULL DEFAULT '0',
  `has_accessible_toilet` tinyint(1) NOT NULL DEFAULT '0',
  `has_elevator` tinyint(1) NOT NULL DEFAULT '0',
  `supports_hearing_impaired` tinyint(1) NOT NULL DEFAULT '0',
  `supports_visually_impaired` tinyint(1) NOT NULL DEFAULT '0',
  `staff_trained_for_pwd` tinyint(1) NOT NULL DEFAULT '0',
  `accessibility_rating` tinyint DEFAULT NULL,
  `pwd_notes` text COLLATE utf8mb4_unicode_ci,
  `images` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `centres_title_unique` (`title`),
  KEY `centres_branch_id_foreign` (`branch_id`),
  KEY `centres_constituency_id_foreign` (`constituency_id`),
  CONSTRAINT `centres_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `centres_constituency_id_foreign` FOREIGN KEY (`constituency_id`) REFERENCES `constituencies` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cms_asset_containers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cms_asset_containers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `handle` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `disk` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `settings` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cms_asset_containers_handle_unique` (`handle`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cms_assets_meta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cms_assets_meta` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `container` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `folder` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `basename` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `filename` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `extension` char(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `meta` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cms_assets_meta_container_folder_basename_unique` (`container`,`folder`,`basename`),
  KEY `cms_assets_meta_container_index` (`container`),
  KEY `cms_assets_meta_folder_index` (`folder`),
  KEY `cms_assets_meta_basename_index` (`basename`),
  KEY `cms_assets_meta_filename_index` (`filename`),
  KEY `cms_assets_meta_extension_index` (`extension`),
  KEY `cms_assets_meta_path_index` (`path`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cms_blueprints`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cms_blueprints` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `namespace` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `handle` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` json NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cms_blueprints_handle_namespace_unique` (`handle`,`namespace`),
  KEY `cms_blueprints_namespace_index` (`namespace`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cms_collections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cms_collections` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `handle` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `settings` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cms_collections_handle_unique` (`handle`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cms_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cms_entries` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `site` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `origin_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `published` tinyint(1) NOT NULL DEFAULT '1',
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uri` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order` int DEFAULT NULL,
  `collection` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `blueprint` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data` json NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cms_entries_site_index` (`site`),
  KEY `cms_entries_origin_id_index` (`origin_id`),
  KEY `cms_entries_uri_index` (`uri`),
  KEY `cms_entries_date_index` (`date`),
  KEY `cms_entries_order_index` (`order`),
  KEY `cms_entries_collection_index` (`collection`),
  KEY `cms_entries_blueprint_index` (`blueprint`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cms_fieldsets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cms_fieldsets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `handle` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` json NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cms_fieldsets_handle_unique` (`handle`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cms_form_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cms_form_submissions` (
  `id` decimal(14,4) NOT NULL,
  `form` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data` json DEFAULT NULL,
  `created_at` timestamp(6) NULL DEFAULT NULL,
  `updated_at` timestamp(6) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cms_form_submissions_id_unique` (`id`),
  UNIQUE KEY `cms_form_submissions_form_created_at_unique` (`form`,`created_at`),
  KEY `cms_form_submissions_form_index` (`form`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cms_forms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cms_forms` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `handle` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `settings` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cms_forms_handle_unique` (`handle`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cms_global_set_variables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cms_global_set_variables` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `handle` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `locale` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `origin` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data` json NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cms_global_set_variables_handle_index` (`handle`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cms_global_sets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cms_global_sets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `handle` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `settings` json NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cms_global_sets_handle_unique` (`handle`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cms_navigations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cms_navigations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `handle` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `settings` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cms_navigations_handle_unique` (`handle`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cms_sites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cms_sites` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `handle` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `locale` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `lang` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attributes` json NOT NULL,
  `order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cms_sites_handle_unique` (`handle`),
  KEY `cms_sites_order_index` (`order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cms_taxonomies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cms_taxonomies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `handle` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sites` json DEFAULT NULL,
  `settings` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cms_taxonomies_handle_unique` (`handle`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cms_taxonomy_terms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cms_taxonomy_terms` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `site` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `uri` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `taxonomy` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` json NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cms_taxonomy_terms_slug_taxonomy_site_unique` (`slug`,`taxonomy`,`site`),
  KEY `cms_taxonomy_terms_site_index` (`site`),
  KEY `cms_taxonomy_terms_uri_index` (`uri`),
  KEY `cms_taxonomy_terms_taxonomy_index` (`taxonomy`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cms_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cms_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `handler` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` json NOT NULL,
  `expire_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cms_tokens_token_unique` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cms_trees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cms_trees` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `handle` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `locale` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tree` json DEFAULT NULL,
  `settings` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cms_trees_handle_type_locale_unique` (`handle`,`type`,`locale`),
  KEY `cms_trees_type_index` (`type`),
  KEY `cms_trees_locale_index` (`locale`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `constituencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `constituencies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `branch_id` bigint unsigned NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `constituencies_branch_id_foreign` (`branch_id`),
  CONSTRAINT `constituencies_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `course_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `course_batches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `course_id` bigint unsigned DEFAULT NULL,
  `batch_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `course_batches_course_id_foreign` (`course_id`),
  KEY `course_batches_batch_id_foreign` (`batch_id`),
  CONSTRAINT `course_batches_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `course_batches_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `course_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `course_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `course_certifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `course_certifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `programme_id` bigint unsigned DEFAULT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `course_certifications_programme_id_foreign` (`programme_id`),
  CONSTRAINT `course_certifications_programme_id_foreign` FOREIGN KEY (`programme_id`) REFERENCES `programmes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `course_match`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `course_match` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tag` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `question` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_multiple_select` tinyint(1) NOT NULL DEFAULT '0',
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_source` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `icon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order` int DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `course_match_tag_unique` (`tag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `course_match_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `course_match_options` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `value` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `answer` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `icon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order` int DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `course_match_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `course_match_options_course_match_id_index` (`course_match_id`),
  CONSTRAINT `course_match_options_course_match_id_foreign` FOREIGN KEY (`course_match_id`) REFERENCES `course_match` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `course_modules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `course_modules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `programme_id` bigint unsigned DEFAULT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `course_modules_programme_id_foreign` (`programme_id`),
  CONSTRAINT `course_modules_programme_id_foreign` FOREIGN KEY (`programme_id`) REFERENCES `programmes` (`id`) ON DELETE SET NULL
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
  `status` tinyint DEFAULT NULL,
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
  `duration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` tinyint DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `centre_id` bigint unsigned NOT NULL,
  `programme_id` bigint unsigned NOT NULL,
  `batch_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `courses_centre_programme_batch_unique` (`centre_id`,`programme_id`,`batch_id`),
  KEY `courses_id_index` (`id`),
  KEY `courses_programme_id_foreign` (`programme_id`),
  KEY `courses_batch_id_foreign` (`batch_id`),
  KEY `courses_centre_id_index` (`centre_id`),
  KEY `courses_programme_id_index` (`programme_id`),
  CONSTRAINT `courses_centre_id_foreign` FOREIGN KEY (`centre_id`) REFERENCES `centres` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `courses_programme_id_foreign` FOREIGN KEY (`programme_id`) REFERENCES `programmes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_courses_batch` FOREIGN KEY (`batch_id`) REFERENCES `admission_batches` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `district_centre`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `district_centre` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `district_id` bigint unsigned NOT NULL,
  `centre_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `district_centre_district_id_centre_id_unique` (`district_id`,`centre_id`),
  KEY `district_centre_centre_id_foreign` (`centre_id`),
  CONSTRAINT `district_centre_centre_id_foreign` FOREIGN KEY (`centre_id`) REFERENCES `centres` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `district_centre_district_id_foreign` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `districts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `districts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `branch_id` bigint unsigned NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `districts_branch_id_foreign` (`branch_id`),
  CONSTRAINT `districts_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  `group_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `group_admin_user_id_foreign` (`user_id`),
  CONSTRAINT `group_admin_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `groups` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `handle` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` json DEFAULT NULL,
  `roles` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `groups_handle_unique` (`handle`)
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
DROP TABLE IF EXISTS `media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `media` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `file` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `media_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `storage_backend` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` bigint DEFAULT NULL,
  `mime_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `width` int DEFAULT NULL,
  `height` int DEFAULT NULL,
  `duration` int DEFAULT NULL,
  `thumbnail_small` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `thumbnail_medium` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `thumbnail_large` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `thumbnails_generated` tinyint(1) NOT NULL DEFAULT '0',
  `is_reusable` tinyint(1) NOT NULL DEFAULT '0',
  `uploaded_by_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `media_uploaded_by_id_foreign` (`uploaded_by_id`),
  CONSTRAINT `media_uploaded_by_id_foreign` FOREIGN KEY (`uploaded_by_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL
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
DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `priority` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_user_id_read_at_index` (`user_id`,`read_at`),
  KEY `notifications_created_at_index` (`created_at`),
  KEY `notifications_type_index` (`type`),
  CONSTRAINT `notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `oex_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `oex_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint NOT NULL DEFAULT '1',
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
  `passmark` tinyint DEFAULT NULL,
  `exam_date` datetime DEFAULT NULL,
  `exam_duration` tinyint DEFAULT NULL,
  `number_of_questions` tinyint unsigned DEFAULT NULL,
  `status` tinyint DEFAULT NULL,
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
  `status` tinyint DEFAULT NULL,
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
  KEY `oex_results_user_id_created_at_index` (`user_id`,`created_at`),
  CONSTRAINT `oex_results_exam_id_foreign` FOREIGN KEY (`exam_id`) REFERENCES `oex_exam_masters` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `oex_results_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `old_admissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `old_admissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `course_id` bigint unsigned NOT NULL,
  `confirmed` datetime DEFAULT NULL,
  `email_sent` datetime DEFAULT NULL,
  `session` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `old_admissions_session_foreign` (`session`),
  KEY `old_admissions_course_id_foreign` (`course_id`),
  KEY `old_admissions_user_id_foreign` (`user_id`),
  CONSTRAINT `old_admissions_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `old_admissions_session_foreign` FOREIGN KEY (`session`) REFERENCES `course_sessions` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `old_admissions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`userId`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `otp_verified_emails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `otp_verified_emails` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `otp_code_hash` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `otp_verified_emails_email_unique` (`email`),
  KEY `otp_verified_emails_email_verified_at_index` (`email`,`verified_at`),
  KEY `otp_verified_emails_used_at_index` (`used_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_activation_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_activation_tokens` (
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
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
DROP TABLE IF EXISTS `programme_course_match_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `programme_course_match_options` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `programme_id` bigint unsigned NOT NULL,
  `course_match_option_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `programme_course_match_option_unq` (`programme_id`,`course_match_option_id`),
  KEY `programme_course_match_options_programme_id_index` (`programme_id`),
  KEY `programme_course_match_options_course_match_option_id_index` (`course_match_option_id`),
  CONSTRAINT `programme_course_match_options_course_match_option_id_foreign` FOREIGN KEY (`course_match_option_id`) REFERENCES `course_match_options` (`id`),
  CONSTRAINT `programme_course_match_options_programme_id_foreign` FOREIGN KEY (`programme_id`) REFERENCES `programmes` (`id`)
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
  `status` tinyint DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `course_category_id` bigint unsigned DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sub_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `level` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mode_of_delivery` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `job_responsible` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `overview` json DEFAULT NULL,
  `prerequisites` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `programmes_title_unique` (`title`),
  KEY `programmes_course_category_id_foreign` (`course_category_id`),
  CONSTRAINT `programmes_course_category_id_foreign` FOREIGN KEY (`course_category_id`) REFERENCES `course_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `questionnaire_responses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `questionnaire_responses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `questionnaire_id` bigint unsigned NOT NULL,
  `response_data` json NOT NULL,
  `is_submitted` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `questionnaire_responses_uuid_unique` (`uuid`),
  KEY `questionnaire_responses_questionnaire_id_foreign` (`questionnaire_id`),
  CONSTRAINT `questionnaire_responses_questionnaire_id_foreign` FOREIGN KEY (`questionnaire_id`) REFERENCES `questionnaires` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `questionnaires`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `questionnaires` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '7vpDzK',
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `schema` json NOT NULL,
  `message_after_submission` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Thank you for your submission',
  `message_when_inactive` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'The form is not accepting submissions at this moment',
  `active` tinyint NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `questionnaires_uuid_unique` (`uuid`),
  UNIQUE KEY `questionnaires_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `role_admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_admin` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `role_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
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
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guard_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'admin',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `handle` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `permissions` json DEFAULT NULL,
  `preferences` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`),
  UNIQUE KEY `roles_name_unique` (`name`),
  UNIQUE KEY `roles_handle_unique` (`handle`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
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
DROP TABLE IF EXISTS `tag_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tag_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_models` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tag_types_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `taggables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `taggables` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tag_id` bigint unsigned NOT NULL,
  `taggable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `taggable_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `taggables_tag_id_taggable_id_taggable_type_unique` (`tag_id`,`taggable_id`,`taggable_type`),
  KEY `taggables_taggable_type_taggable_id_index` (`taggable_type`,`taggable_id`),
  CONSTRAINT `taggables_tag_id_foreign` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tags` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `tag_type_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tags_name_unique` (`name`),
  KEY `tags_tag_type_id_foreign` (`tag_type_id`),
  CONSTRAINT `tags_tag_type_id_foreign` FOREIGN KEY (`tag_type_id`) REFERENCES `tag_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_admission`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_admission` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `course_id` bigint unsigned NOT NULL,
  `confirmed` datetime DEFAULT NULL,
  `email_sent` datetime DEFAULT NULL,
  `session` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_admission_user_id_unique` (`user_id`),
  KEY `user_admission_user_id_course_id_index` (`user_id`,`course_id`),
  KEY `user_admission_email_sent_index` (`email_sent`),
  KEY `user_admission_confirmed_index` (`confirmed`),
  KEY `user_admission_session_foreign` (`session`),
  KEY `user_admission_course_id_foreign` (`course_id`),
  KEY `user_admission_course_id_confirmed_index` (`course_id`,`confirmed`),
  KEY `user_admission_course_id_session_index` (`course_id`,`session`),
  CONSTRAINT `user_admission_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `user_admission_session_foreign` FOREIGN KEY (`session`) REFERENCES `course_sessions` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `user_admission_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`userId`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_assessments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_assessments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `current_level` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'beginner',
  `questions_answered` int NOT NULL DEFAULT '0',
  `correct_answers` int NOT NULL DEFAULT '0',
  `wrong_answers` int NOT NULL DEFAULT '0',
  `answered_question_ids` json DEFAULT NULL,
  `violation_count` tinyint unsigned NOT NULL DEFAULT '0',
  `level_started_at` timestamp NULL DEFAULT NULL,
  `completed` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_assessments_user_id_unique` (`user_id`),
  CONSTRAINT `user_assessments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_course_recommendations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_course_recommendations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `course_id` bigint unsigned DEFAULT NULL,
  `centre_id` bigint unsigned DEFAULT NULL,
  `rank` smallint unsigned DEFAULT NULL,
  `match_percentage` smallint unsigned DEFAULT NULL,
  `option_ids` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_course_recommendations_course_id_foreign` (`course_id`),
  KEY `user_course_recommendations_user_id_index` (`user_id`),
  KEY `user_course_recommendations_centre_id_foreign` (`centre_id`),
  CONSTRAINT `user_course_recommendations_centre_id_foreign` FOREIGN KEY (`centre_id`) REFERENCES `centres` (`id`) ON DELETE SET NULL,
  CONSTRAINT `user_course_recommendations_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_exams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_exams` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `exam_id` bigint unsigned NOT NULL,
  `std_status` tinyint NOT NULL,
  `exam_joined` tinyint NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `started` datetime DEFAULT NULL,
  `submitted` datetime DEFAULT NULL,
  `user_feedback` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_exams_user_id_index` (`user_id`),
  KEY `user_exams_exam_id_index` (`exam_id`),
  KEY `user_exams_user_id_exam_id_index` (`user_id`,`exam_id`),
  KEY `user_exams_exam_joined_index` (`exam_joined`),
  CONSTRAINT `user_exams_exam_id_foreign` FOREIGN KEY (`exam_id`) REFERENCES `oex_exam_masters` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `user_exams_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `middle_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `previous_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `gender` enum('male','female') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pwd` tinyint(1) NOT NULL DEFAULT '0',
  `student_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `support` tinyint(1) DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `mobile_no` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `age` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `exam` bigint unsigned NOT NULL,
  `registered_course` bigint unsigned DEFAULT NULL,
  `data` json DEFAULT NULL,
  `shortlist` tinyint DEFAULT NULL,
  `status` tinyint DEFAULT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `userId` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `card_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ghcard` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `verification_date` datetime DEFAULT NULL,
  `verified_by` bigint unsigned DEFAULT NULL,
  `details_updated_at` datetime DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `network_type` enum('mtn','telecel','airteltigo') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `form_response_id` bigint unsigned DEFAULT NULL,
  `student_level` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `idx_email` (`email`),
  UNIQUE KEY `users_userid_unique` (`userId`),
  UNIQUE KEY `idx_mobile_no` (`mobile_no`),
  UNIQUE KEY `users_student_id_unique` (`student_id`),
  UNIQUE KEY `users_ghcard_unique` (`ghcard`),
  KEY `users_form_response_id_foreign` (`form_response_id`),
  KEY `users_id_index` (`id`),
  KEY `users_exam_foreign` (`exam`),
  KEY `users_registered_course_foreign` (`registered_course`),
  KEY `users_verified_by_foreign` (`verified_by`),
  CONSTRAINT `users_exam_foreign` FOREIGN KEY (`exam`) REFERENCES `oex_exam_masters` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `users_form_response_id_foreign` FOREIGN KEY (`form_response_id`) REFERENCES `form_responses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_registered_course_foreign` FOREIGN KEY (`registered_course`) REFERENCES `courses` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `users_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
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
DROP TABLE IF EXISTS `versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `versions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `version` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `versions_version_unique` (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
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
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
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
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (59,'2020_03_31_114745_remove_backpackuser_model',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (60,'2021_05_04_162552_create_runway_uris_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (61,'2024_03_07_100000_create_asset_containers_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (62,'2024_03_07_100000_create_asset_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (63,'2024_03_07_100000_create_blueprints_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (64,'2024_03_07_100000_create_collections_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (65,'2024_03_07_100000_create_entries_table_with_string_ids',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (66,'2024_03_07_100000_create_fieldsets_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (67,'2024_03_07_100000_create_form_submissions_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (68,'2024_03_07_100000_create_forms_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (69,'2024_03_07_100000_create_global_variables_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (70,'2024_03_07_100000_create_globals_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (71,'2024_03_07_100000_create_navigation_trees_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (72,'2024_03_07_100000_create_navigations_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (73,'2024_03_07_100000_create_taxonomies_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (74,'2024_03_07_100000_create_terms_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (75,'2024_03_07_100000_create_tokens_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (76,'2024_05_15_100000_modify_form_submissions_id',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (77,'2024_07_16_100000_create_sites_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (78,'2025_05_06_144122_create_questionnaires_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (79,'2025_05_08_171055_create_questionnaire_responses_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (80,'2025_05_09_210137_create_report_views',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (81,'2025_05_21_183218_create_attendance_views',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (82,'2025_07_01_223832_statamic_auth_tables',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (83,'2025_07_02_144044_creat_course_category_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (84,'2025_07_02_191137_add_version_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (85,'2025_07_02_191138_add_last_login_to_users_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (86,'2025_07_11_131457_modify_id_column_on_oex_exam_masters',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (87,'2025_07_14_145053_add_extra_columns_to_programmes_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (88,'2025_07_14_151416_creat_course_module_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (89,'2025_07_14_151849_creat_course_certification_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (90,'2025_07_15_115732_create_sessions_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (91,'2025_07_15_145513_create_media_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (92,'2025_07_16_195304_create_cache_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (93,'2025_07_19_201533_add_level_job_responsible_to_programmes_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (94,'2025_07_20_213218_add_no_of_days_column_to_course_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (95,'2025_07_21_094248_create_admission_batch_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (96,'2025_07_21_214728_create_course_match_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (97,'2025_07_21_214736_create_course_match_options_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (98,'2025_07_21_214802_create_programme_course_match_options_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (99,'2025_08_05_101703_create_course_batches_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (100,'2025_08_26_104315_add_name_fields_to_users_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (101,'2026_01_30_180254_statamic_groups_table',16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (104,'2026_01_30_180254_statamic_roles_table',17);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (105,'2026_02_03_140817_add_pwd_fields_to_centres_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (106,'2026_02_07_125205_add_has_disability_to_users_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (107,'2026_02_10_213605_create_notifications_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (108,'2026_02_11_000000_create_cache_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (109,'2026_02_11_000001_create_otp_verified_emails_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (110,'2026_02_11_000002_add_otp_verification_email_template',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (111,'2026_02_11_000003_add_otp_config_to_app_configs',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (112,'2026_02_11_084514_drop_unique_course_name_from_courses_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (113,'2026_02_12_000000_add_lifecycle_columns_to_otp_verified_emails',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (114,'2026_02_12_133800_create_oex_question_master_programme_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (115,'2026_02_12_180000_update_courses_batch_fk_set_null',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (116,'2026_02_13_090512_update_courses_unique_constraint',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (117,'2026_02_13_101500_add_indexes_for_course_batch_metrics',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (118,'2026_02_15_161733_add_number_of_questions_to_oex_exam_masters_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (119,'2026_02_15_200000_remove_link_from_otp_email_template',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (120,'2026_02_24_130000_create_districts_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (121,'2026_02_24_130100_create_district_centre_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (122,'2026_02_25_100637_create_tags_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (123,'2026_02_25_100646_create_taggables_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (124,'2026_03_02_140711_create_constituencies_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (125,'2026_03_02_160000_add_constituency_id_to_centres_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (126,'2026_03_03_210907_drop_oex_question_master_programme_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (127,'2026_03_03_212406_create_tag_types_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (128,'2026_03_03_212650_add_tag_type_id_to_tags_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (129,'2026_03_10_221757_add_level_columns_for_assessment',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (130,'2026_03_10_221802_create_user_assessments_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (131,'2026_03_11_000000_update_users_registered_course_and_add_data',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (132,'2026_03_11_000001_add_mode_of_delivery_and_provider_to_programmes_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (133,'2026_03_11_000002_add_is_multiple_select_and_type_to_course_match_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (134,'2026_03_12_000000_create_user_course_recommendations_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (135,'2026_03_12_000010_add_reference_source_to_course_match_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (136,'2026_03_13_000000_add_need_support_to_users_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (137,'2026_03_13_000001_add_centre_id_to_user_course_recommendations_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (138,'2026_03_13_115030_add_images_to_centres',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (139,'2026_03_13_181956_data_cleaning',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (140,'2026_03_14_000001_add_gps_location_to_centres_table',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (141,'2026_03_14_000002_change_centre_gps_address_to_string',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (142,'2026_03_14_030519_create_old_admissions_table',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (143,'2026_03_14_114731_add_violation_count_to_user_assessments_table',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (144,'2026_03_17_000001_add_icon_to_course_categories_table',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (145,'2026_03_17_000001_add_unique_index_to_users_ghcard',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (146,'2026_03_18_172613_add_pwd_to_users_table',19);
