/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.8.3-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: crm_db_portal
-- ------------------------------------------------------
-- Server version	11.8.3-MariaDB-0+deb13u1 from Debian

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `allowed_ips`
--

DROP TABLE IF EXISTS `allowed_ips`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `allowed_ips` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ip_prefix` varchar(15) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `applicant_notes`
--

DROP TABLE IF EXISTS `applicant_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `applicant_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `note_uid` varchar(255) DEFAULT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `applicant_id` bigint(20) unsigned NOT NULL,
  `details` longtext NOT NULL,
  `moved_tab_to` varchar(50) DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `applicant_notes_user_id_index` (`user_id`),
  KEY `applicant_notes_applicant_id_index` (`applicant_id`),
  KEY `applicant_notes_moved_tab_to_index` (`moved_tab_to`),
  KEY `applicant_notes_user_id_applicant_id_moved_tab_to_index` (`user_id`,`applicant_id`,`moved_tab_to`)
) ENGINE=InnoDB AUTO_INCREMENT=48712 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `applicants`
--

DROP TABLE IF EXISTS `applicants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `applicants` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `applicant_uid` varchar(255) DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `job_source_id` bigint(20) unsigned DEFAULT NULL,
  `job_category_id` bigint(20) unsigned DEFAULT NULL,
  `job_title_id` bigint(20) unsigned DEFAULT NULL,
  `job_type` varchar(255) DEFAULT NULL,
  `applicant_name` varchar(255) NOT NULL,
  `applicant_email` varchar(255) DEFAULT NULL,
  `applicant_email_secondary` varchar(255) DEFAULT NULL,
  `gender` enum('m','f','u') DEFAULT 'u',
  `dob` date DEFAULT NULL,
  `applicant_postcode` varchar(50) DEFAULT NULL,
  `applicant_phone` varchar(50) DEFAULT NULL,
  `applicant_phone_secondary` varchar(50) DEFAULT NULL,
  `applicant_landline` varchar(50) DEFAULT NULL,
  `applicant_cv` longtext DEFAULT NULL,
  `updated_cv` longtext DEFAULT NULL,
  `applicant_notes` longtext DEFAULT NULL,
  `applicant_experience` longtext DEFAULT NULL,
  `lat` float DEFAULT NULL,
  `lng` float DEFAULT NULL,
  `is_blocked` tinyint(1) NOT NULL DEFAULT 0,
  `is_temp_not_interested` tinyint(1) NOT NULL DEFAULT 0,
  `is_callback_enable` tinyint(1) NOT NULL DEFAULT 0,
  `is_no_job` tinyint(1) NOT NULL DEFAULT 0,
  `is_no_response` tinyint(1) NOT NULL DEFAULT 0,
  `is_in_nurse_home` tinyint(1) NOT NULL DEFAULT 0,
  `is_circuit_busy` tinyint(1) NOT NULL DEFAULT 0,
  `is_cv_in_quality` tinyint(1) NOT NULL DEFAULT 0,
  `is_cv_in_quality_clear` tinyint(1) NOT NULL DEFAULT 0,
  `is_cv_sent` tinyint(1) NOT NULL DEFAULT 0,
  `is_cv_in_quality_reject` tinyint(1) NOT NULL DEFAULT 0,
  `is_interview_confirm` tinyint(1) NOT NULL DEFAULT 0,
  `is_interview_attend` tinyint(1) NOT NULL DEFAULT 0,
  `is_in_crm_request` tinyint(1) NOT NULL DEFAULT 0,
  `is_in_crm_reject` tinyint(1) NOT NULL DEFAULT 0,
  `is_in_crm_request_reject` tinyint(1) NOT NULL DEFAULT 0,
  `is_crm_request_confirm` tinyint(1) NOT NULL DEFAULT 0,
  `is_crm_interview_attended` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=not,1=yes,2=pending',
  `is_in_crm_start_date` tinyint(1) NOT NULL DEFAULT 0,
  `is_in_crm_invoice` tinyint(1) NOT NULL DEFAULT 0,
  `is_in_crm_invoice_sent` tinyint(1) NOT NULL DEFAULT 0,
  `is_in_crm_start_date_hold` tinyint(1) NOT NULL DEFAULT 0,
  `is_in_crm_paid` tinyint(1) NOT NULL DEFAULT 0,
  `is_in_crm_dispute` tinyint(1) NOT NULL DEFAULT 0,
  `is_job_within_radius` tinyint(1) NOT NULL DEFAULT 1,
  `have_nursing_home_experience` tinyint(4) DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `paid_status` varchar(20) NOT NULL DEFAULT 'pending',
  `paid_timestamp` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_applicant_phone_secondary` (`applicant_phone_secondary`),
  KEY `idx_status_deleted_at` (`status`,`deleted_at`),
  KEY `idx_job_title` (`job_title_id`),
  KEY `idx_job_category` (`job_category_id`),
  KEY `idx_job_source` (`job_source_id`),
  KEY `idx_status_deleted` (`status`,`deleted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=244492 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `applicants_pivot_sales`
--

DROP TABLE IF EXISTS `applicants_pivot_sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `applicants_pivot_sales` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `pivot_uid` varchar(255) DEFAULT NULL,
  `applicant_id` bigint(20) unsigned NOT NULL,
  `sale_id` bigint(20) unsigned NOT NULL,
  `is_interested` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `applicants_pivot_sales_sale_id_foreign` (`sale_id`),
  KEY `applicants_pivot_sales_applicant_id_sale_id_index` (`applicant_id`,`sale_id`),
  KEY `idx_pivot_applicant_sale` (`applicant_id`,`sale_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3878 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `audits`
--

DROP TABLE IF EXISTS `audits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `audits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `data` longtext NOT NULL,
  `message` varchar(255) NOT NULL,
  `auditable_id` bigint(20) NOT NULL,
  `auditable_type` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_auditable` (`auditable_type`,`auditable_id`),
  KEY `idx_audit_msg` (`auditable_type`,`message`),
  KEY `idx_message` (`message`),
  KEY `idx_auditable_type` (`auditable_type`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=807829 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `contacts`
--

DROP TABLE IF EXISTS `contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contacts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `contact_name` varchar(255) NOT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(50) DEFAULT NULL,
  `contact_landline` varchar(50) DEFAULT NULL,
  `contact_note` varchar(255) DEFAULT NULL,
  `contactable_id` varchar(255) NOT NULL,
  `contactable_type` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `contacts_contact_name_index` (`contact_name`),
  KEY `contacts_contact_email_index` (`contact_email`),
  KEY `contacts_contact_phone_index` (`contact_phone`),
  KEY `contacts_contact_landline_index` (`contact_landline`),
  KEY `contacts_contactable_id_index` (`contactable_id`),
  KEY `contacts_contactable_type_index` (`contactable_type`),
  KEY `idx_contacts_unit_search` (`contactable_id`,`contactable_type`),
  KEY `idx_contactable` (`contactable_type`,`contactable_id`),
  KEY `idx_contact_email` (`contact_email`),
  KEY `idx_contacts_lookup` (`contactable_id`,`contactable_type`),
  KEY `contacts_contactable_idx` (`contactable_type`,`contactable_id`),
  KEY `idx_contact_phone` (`contact_phone`),
  KEY `idx_contact_landline` (`contact_landline`),
  FULLTEXT KEY `contact_email` (`contact_email`,`contact_phone`,`contact_landline`)
) ENGINE=InnoDB AUTO_INCREMENT=107986 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `crm_notes`
--

DROP TABLE IF EXISTS `crm_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `crm_notes_uid` varchar(255) DEFAULT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `applicant_id` bigint(20) unsigned NOT NULL,
  `sale_id` bigint(20) unsigned NOT NULL,
  `details` longtext NOT NULL,
  `moved_tab_to` varchar(50) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `crm_notes_applicant_id_index` (`applicant_id`),
  KEY `crm_notes_user_id_index` (`user_id`),
  KEY `crm_notes_sale_id_index` (`sale_id`),
  KEY `crm_notes_moved_tab_to_index` (`moved_tab_to`),
  KEY `idx_applicant_sale_id` (`applicant_id`,`sale_id`,`id`),
  KEY `idx_applicant_sale` (`applicant_id`,`sale_id`),
  KEY `idx_applicant_sale_max` (`applicant_id`,`sale_id`,`id`),
  KEY `crm_notes_fast_lookup` (`applicant_id`,`sale_id`,`moved_tab_to`,`created_at`),
  KEY `idx_crm_notes_app_sale_status` (`applicant_id`,`sale_id`,`status`,`moved_tab_to`,`id`)
) ENGINE=InnoDB AUTO_INCREMENT=429266 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `crm_rejected_cv`
--

DROP TABLE IF EXISTS `crm_rejected_cv`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_rejected_cv` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `crm_rejected_cv_uid` varchar(255) DEFAULT NULL,
  `applicant_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `crm_note_id` bigint(20) unsigned NOT NULL,
  `sale_id` bigint(20) unsigned NOT NULL,
  `reason` longtext NOT NULL,
  `crm_rejected_cv_note` longtext NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `crm_rejected_cv_applicant_id_index` (`applicant_id`),
  KEY `crm_rejected_cv_user_id_index` (`user_id`),
  KEY `crm_rejected_cv_crm_note_id_index` (`crm_note_id`),
  KEY `crm_rejected_cv_sale_id_index` (`sale_id`)
) ENGINE=InnoDB AUTO_INCREMENT=40083 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cv_notes`
--

DROP TABLE IF EXISTS `cv_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cv_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cv_uid` varchar(255) DEFAULT 'Null',
  `user_id` bigint(20) unsigned NOT NULL,
  `sale_id` bigint(20) unsigned NOT NULL,
  `applicant_id` bigint(20) unsigned NOT NULL,
  `details` longtext NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '0=Inactive, 1=Active, 2=Paid, 3=Open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `cv_notes_applicant_id_index` (`applicant_id`),
  KEY `cv_notes_user_id_index` (`user_id`),
  KEY `cv_notes_sale_id_index` (`sale_id`),
  KEY `idx_cv_notes_applicant_sale` (`applicant_id`,`sale_id`,`id`,`status`),
  KEY `idx_cv_notes_sale_status` (`sale_id`,`status`),
  KEY `idx_cv_notes_app_sale` (`applicant_id`,`sale_id`,`id`),
  KEY `idx_applicant_sale_id` (`applicant_id`,`sale_id`,`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_applicant_sale_status` (`id`,`applicant_id`,`sale_id`,`status`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=84802 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `email_templates`
--

DROP TABLE IF EXISTS `email_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `template` longtext DEFAULT 'Null',
  `from_email` varchar(255) DEFAULT 'Null',
  `subject` varchar(255) DEFAULT 'Null',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_templates_title_unique` (`title`),
  UNIQUE KEY `email_templates_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `history`
--

DROP TABLE IF EXISTS `history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `history` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `history_uid` varchar(255) DEFAULT 'NULL',
  `applicant_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `sale_id` bigint(20) unsigned NOT NULL,
  `stage` varchar(50) NOT NULL,
  `sub_stage` varchar(255) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `history_applicant_id_index` (`applicant_id`),
  KEY `history_user_id_index` (`user_id`),
  KEY `history_sale_id_index` (`sale_id`),
  KEY `history_sub_stage_index` (`sub_stage`),
  KEY `idx_history_sub_stage_status_created` (`sub_stage`,`status`,`created_at`),
  KEY `idx_history_applicant_sale_status` (`applicant_id`,`sale_id`,`status`),
  KEY `idx_history_applicant_sale` (`applicant_id`,`sale_id`),
  KEY `idx_history_status_stage` (`status`,`sub_stage`),
  KEY `idx_history_app_sale_stage` (`applicant_id`,`sale_id`,`sub_stage`,`status`),
  KEY `idx_applicant_sale_substage` (`applicant_id`,`sale_id`,`sub_stage`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=579375 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `interviews`
--

DROP TABLE IF EXISTS `interviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `interviews` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `interview_uid` varchar(255) DEFAULT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `applicant_id` bigint(20) unsigned NOT NULL,
  `sale_id` bigint(20) unsigned NOT NULL,
  `schedule_time` varchar(50) NOT NULL,
  `schedule_date` varchar(50) NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `interviews_applicant_id_foreign` (`applicant_id`),
  KEY `interviews_sale_id_foreign` (`sale_id`),
  KEY `interviews_interview_uid_index` (`interview_uid`),
  KEY `interviews_user_id_applicant_id_sale_id_index` (`user_id`,`applicant_id`,`sale_id`),
  KEY `schedule_time` (`schedule_time`,`schedule_date`),
  KEY `idx_interviews_app_sale_status` (`applicant_id`,`sale_id`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=13828 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ip_addresses`
--

DROP TABLE IF EXISTS `ip_addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ip_addresses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `ip_address` varchar(100) NOT NULL,
  `mac_address` varchar(100) DEFAULT 'Null',
  `device_type` varchar(50) DEFAULT 'Null',
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ip_addresses_user_id_foreign` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=150 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `job_categories`
--

DROP TABLE IF EXISTS `job_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `job_categories_name_index` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `job_sources`
--

DROP TABLE IF EXISTS `job_sources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_sources` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `job_sources_name_index` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `job_titles`
--

DROP TABLE IF EXISTS `job_titles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_titles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `related_titles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`related_titles`)),
  `type` varchar(50) NOT NULL,
  `job_category_id` bigint(20) unsigned DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `job_titles_name_index` (`name`),
  KEY `job_titles_job_category_id_index` (`job_category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=279 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `login_details`
--

DROP TABLE IF EXISTS `login_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `login_details` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `login_at` time NOT NULL,
  `logout_at` time DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `login_details_user_id_foreign` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4813 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `msg_id` varchar(255) DEFAULT NULL,
  `module_id` bigint(20) DEFAULT NULL,
  `module_type` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `message` longtext NOT NULL,
  `phone_number` varchar(50) NOT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `status` varchar(50) NOT NULL,
  `is_sent` tinyint(4) NOT NULL DEFAULT 0,
  `is_read` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `messages_module_id_index` (`module_id`),
  KEY `messages_module_type_index` (`module_type`),
  KEY `messages_user_id_index` (`user_id`),
  KEY `messages_msg_id_index` (`msg_id`),
  KEY `phone_number` (`phone_number`,`date`,`time`,`status`,`is_sent`,`is_read`),
  KEY `date` (`date`,`time`),
  KEY `idx_messages_lookup` (`status`,`module_type`,`is_read`,`created_at`),
  KEY `phone_number_2` (`phone_number`),
  KEY `idx_module_stats` (`module_type`,`module_id`,`status`,`is_read`,`created_at`),
  KEY `idx_messages_module` (`module_type`,`module_id`,`is_read`,`created_at`),
  KEY `idx_module_counts` (`module_id`,`module_type`,`status`,`is_read`),
  KEY `idx_messages_module_search` (`module_id`,`module_type`,`status`,`is_read`,`created_at`),
  KEY `idx_messages_status_read` (`status`,`is_read`)
) ENGINE=InnoDB AUTO_INCREMENT=128732 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `model_has_permissions`
--

DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `model_has_roles`
--

DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `module_notes`
--

DROP TABLE IF EXISTS `module_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `module_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `module_note_uid` varchar(255) DEFAULT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `module_noteable_id` bigint(20) unsigned NOT NULL,
  `module_noteable_type` varchar(50) NOT NULL,
  `details` longtext NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `module_notes_user_id_index` (`user_id`),
  KEY `module_notes_module_noteable_id_index` (`module_noteable_id`),
  KEY `module_notes_module_noteable_type_index` (`module_noteable_type`),
  KEY `idx_module_notes_type_id_created` (`module_noteable_type`,`module_noteable_id`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=897731 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `notes_for_range_applicants`
--

DROP TABLE IF EXISTS `notes_for_range_applicants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notes_for_range_applicants` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `range_uid` varchar(255) DEFAULT NULL,
  `applicants_pivot_sales_id` bigint(20) unsigned NOT NULL,
  `reason` longtext NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `notes_for_range_applicants_applicants_pivot_sales_id_foreign` (`applicants_pivot_sales_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3878 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `sale_id` bigint(20) unsigned DEFAULT NULL,
  `applicant_id` bigint(20) unsigned DEFAULT NULL,
  `message` text DEFAULT NULL,
  `type` text DEFAULT NULL,
  `is_read` varchar(255) NOT NULL DEFAULT '0',
  `notify_by` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_user_id_index` (`user_id`),
  KEY `notifications_sale_id_index` (`sale_id`),
  KEY `notifications_applicant_id_index` (`applicant_id`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `offices`
--

DROP TABLE IF EXISTS `offices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `offices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `office_uid` varchar(255) DEFAULT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `office_name` varchar(255) NOT NULL,
  `office_type` varchar(50) DEFAULT NULL,
  `office_postcode` varchar(50) NOT NULL,
  `office_website` varchar(255) DEFAULT NULL,
  `office_notes` longtext NOT NULL,
  `office_lat` float DEFAULT NULL,
  `office_lng` float DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `offices_user_id_index` (`user_id`),
  KEY `offices_office_name_index` (`office_name`),
  KEY `offices_office_postcode_index` (`office_postcode`),
  KEY `offices_office_type_index` (`office_type`),
  KEY `idx_offices_name` (`office_name`)
) ENGINE=InnoDB AUTO_INCREMENT=2268 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `outcodepostcodes`
--

DROP TABLE IF EXISTS `outcodepostcodes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `outcodepostcodes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `outcode` varchar(10) NOT NULL,
  `lat` double NOT NULL,
  `lng` double NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `outcodepostcodes_outcode_index` (`outcode`)
) ENGINE=InnoDB AUTO_INCREMENT=4098 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=354 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `postcodes`
--

DROP TABLE IF EXISTS `postcodes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `postcodes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `postcode` varchar(10) NOT NULL,
  `lat` double DEFAULT NULL,
  `lng` double DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `postcodes_postcode_index` (`postcode`)
) ENGINE=InnoDB AUTO_INCREMENT=1816333 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `quality_notes`
--

DROP TABLE IF EXISTS `quality_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `quality_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `quality_notes_uid` varchar(255) DEFAULT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `applicant_id` bigint(20) unsigned NOT NULL,
  `sale_id` bigint(20) unsigned NOT NULL,
  `details` longtext NOT NULL,
  `moved_tab_to` varchar(50) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `quality_notes_applicant_id_index` (`applicant_id`),
  KEY `quality_notes_user_id_index` (`user_id`),
  KEY `quality_notes_sale_id_index` (`sale_id`),
  KEY `quality_notes_moved_tab_to_index` (`moved_tab_to`),
  KEY `idx_moved_tab_applicant_sale` (`moved_tab_to`,`applicant_id`,`sale_id`,`id`,`created_at`),
  KEY `idx_applicant_sale_status` (`id`,`applicant_id`,`sale_id`,`moved_tab_to`,`status`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=114033 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `regions`
--

DROP TABLE IF EXISTS `regions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `regions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `districts_code` varchar(191) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `regions_districts_code_index` (`districts_code`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `revert_stages`
--

DROP TABLE IF EXISTS `revert_stages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `revert_stages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `applicant_id` bigint(20) unsigned NOT NULL,
  `sale_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `notes` longtext DEFAULT NULL,
  `stage` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `revert_stages_sale_id_foreign` (`sale_id`),
  KEY `revert_stages_user_id_foreign` (`user_id`),
  KEY `revert_stages_applicant_id_sale_id_user_id_index` (`applicant_id`,`sale_id`,`user_id`),
  KEY `idx_applicant_sale_stage` (`applicant_id`,`sale_id`,`stage`,`id`)
) ENGINE=InnoDB AUTO_INCREMENT=38770 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `role_has_permissions`
--

DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` varchar(255) DEFAULT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sale_documents`
--

DROP TABLE IF EXISTS `sale_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sale_documents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sale_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `document_name` varchar(255) NOT NULL,
  `document_path` varchar(255) NOT NULL,
  `document_size` varchar(10) DEFAULT NULL,
  `document_extension` varchar(10) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `sale_documents_sale_id_index` (`sale_id`),
  KEY `sale_documents_user_id_index` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=124 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sale_notes`
--

DROP TABLE IF EXISTS `sale_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sale_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sales_notes_uid` varchar(255) DEFAULT NULL,
  `sale_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `sale_note` longtext NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `sale_notes_user_id_index` (`user_id`),
  KEY `sale_notes_sale_id_index` (`sale_id`),
  KEY `idx_sale_id` (`sale_id`),
  KEY `idx_sale_latest` (`sale_id`,`id`),
  KEY `idx_sale_notes_sale_created` (`sale_id`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=146466 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sales`
--

DROP TABLE IF EXISTS `sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sale_uid` varchar(255) DEFAULT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `office_id` bigint(20) unsigned NOT NULL,
  `unit_id` bigint(20) unsigned NOT NULL,
  `job_category_id` bigint(20) unsigned NOT NULL,
  `job_title_id` bigint(20) unsigned NOT NULL,
  `sale_postcode` varchar(50) NOT NULL,
  `position_type` varchar(250) NOT NULL,
  `job_type` varchar(50) NOT NULL,
  `timing` longtext DEFAULT NULL,
  `salary` longtext DEFAULT NULL,
  `experience` longtext DEFAULT NULL,
  `qualification` longtext DEFAULT NULL,
  `benefits` longtext DEFAULT NULL,
  `lat` float DEFAULT NULL,
  `lng` float DEFAULT NULL,
  `job_description` longtext DEFAULT NULL,
  `is_on_hold` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0=Not On Hold, 1=On Hold, 2=Pending',
  `is_re_open` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0=No, 1=Yes, 2=Requested',
  `cv_limit` tinyint(4) NOT NULL DEFAULT 8,
  `sale_notes` longtext DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 2 COMMENT '0=Inactive/deleted, 1=Active, 2=Pending, 3=Rejected',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sales_office_id_index` (`office_id`),
  KEY `sales_user_id_index` (`user_id`),
  KEY `sales_unit_id_index` (`unit_id`),
  KEY `sales_job_category_id_index` (`job_category_id`),
  KEY `sales_job_title_id_index` (`job_title_id`),
  KEY `sales_sale_postcode_index` (`sale_postcode`),
  KEY `idx_status` (`status`),
  KEY `idx_is_on_hold` (`is_on_hold`),
  KEY `idx_deleted_at` (`deleted_at`),
  KEY `idx_updated_at` (`updated_at`),
  KEY `idx_job_title_id` (`job_title_id`),
  KEY `idx_job_category_id` (`job_category_id`),
  KEY `idx_office_id` (`office_id`),
  KEY `idx_unit_id` (`unit_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_sales_status_hold_updated` (`status`,`is_on_hold`,`updated_at`),
  KEY `idx_sales_deleted_updated` (`deleted_at`,`updated_at`),
  KEY `idx_sales_office_unit` (`office_id`,`unit_id`),
  KEY `idx_sales_office` (`office_id`,`unit_id`),
  KEY `idx_status_is_on_hold` (`status`,`is_on_hold`),
  KEY `idx_office` (`office_id`),
  KEY `idx_unit` (`unit_id`),
  KEY `idx_office_unit` (`office_id`,`unit_id`)
) ENGINE=InnoDB AUTO_INCREMENT=17401 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sent_emails`
--

DROP TABLE IF EXISTS `sent_emails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sent_emails` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `applicant_id` bigint(20) unsigned DEFAULT NULL,
  `sale_id` bigint(20) unsigned DEFAULT NULL,
  `action_name` varchar(191) NOT NULL,
  `sent_from` varchar(191) NOT NULL,
  `sent_to` varchar(191) NOT NULL,
  `cc_emails` varchar(191) DEFAULT NULL,
  `subject` varchar(191) NOT NULL,
  `title` varchar(191) NOT NULL,
  `template` longtext NOT NULL,
  `status` enum('0','1','2') NOT NULL DEFAULT '0' COMMENT '0=unsent, 1=sent, 2=failed',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sent_emails_applicant_id_foreign` (`applicant_id`),
  KEY `sent_emails_sale_id_foreign` (`sale_id`),
  KEY `sent_emails_user_id_applicant_id_sale_id_index` (`user_id`,`applicant_id`,`sale_id`),
  KEY `sent_from` (`sent_from`,`sent_to`),
  KEY `subject` (`subject`,`title`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=68312 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'string',
  `group` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_key_unique` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sms_templates`
--

DROP TABLE IF EXISTS `sms_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sms_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `template` longtext NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0 = inactive, 1 = active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `sms_templates_title_unique` (`title`),
  UNIQUE KEY `sms_templates_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `smtp_settings`
--

DROP TABLE IF EXISTS `smtp_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `smtp_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `from_name` varchar(255) DEFAULT NULL,
  `from_address` varchar(255) DEFAULT NULL,
  `mailer` varchar(255) NOT NULL,
  `host` varchar(255) NOT NULL,
  `port` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `encryption` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `telescope_entries`
--

DROP TABLE IF EXISTS `telescope_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `telescope_entries` (
  `sequence` bigint(20) unsigned NOT NULL,
  `uuid` char(36) NOT NULL,
  `batch_id` char(36) NOT NULL,
  `family_hash` varchar(255) DEFAULT NULL,
  `should_display_on_index` tinyint(1) NOT NULL DEFAULT 1,
  `type` varchar(20) NOT NULL,
  `content` longtext NOT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`sequence`),
  UNIQUE KEY `telescope_entries_uuid_unique` (`uuid`),
  KEY `telescope_entries_batch_id_index` (`batch_id`),
  KEY `telescope_entries_family_hash_index` (`family_hash`),
  KEY `telescope_entries_created_at_index` (`created_at`),
  KEY `telescope_entries_type_should_display_on_index_index` (`type`,`should_display_on_index`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `telescope_entries_tags`
--

DROP TABLE IF EXISTS `telescope_entries_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `telescope_entries_tags` (
  `entry_uuid` char(36) NOT NULL,
  `tag` varchar(255) NOT NULL,
  PRIMARY KEY (`entry_uuid`,`tag`),
  KEY `telescope_entries_tags_tag_index` (`tag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `telescope_monitoring`
--

DROP TABLE IF EXISTS `telescope_monitoring`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `telescope_monitoring` (
  `tag` varchar(255) NOT NULL,
  PRIMARY KEY (`tag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `units`
--

DROP TABLE IF EXISTS `units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `units` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `unit_uid` varchar(255) DEFAULT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `office_id` bigint(20) unsigned NOT NULL,
  `unit_name` varchar(255) NOT NULL,
  `unit_postcode` varchar(50) NOT NULL,
  `unit_website` varchar(255) DEFAULT NULL,
  `unit_notes` longtext DEFAULT NULL,
  `lat` float DEFAULT NULL,
  `lng` float DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `units_user_id_index` (`user_id`),
  KEY `units_office_id_index` (`office_id`),
  KEY `idx_units_name_postcode` (`unit_postcode`,`unit_name`),
  KEY `idx_units_deleted` (`deleted_at`),
  KEY `idx_units_created` (`created_at`),
  KEY `status` (`status`),
  FULLTEXT KEY `idx_units_search` (`unit_name`,`unit_postcode`,`unit_website`,`unit_notes`)
) ENGINE=InnoDB AUTO_INCREMENT=6746 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_name_index` (`name`),
  KEY `users_email_index` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=187 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-05-27  9:51:02
