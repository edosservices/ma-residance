-- Ma Résidence — schéma complet MySQL / MariaDB
-- Produit en exécutant les six migrations Laravel sur une base vide :
--   0001_01_01_000000_create_users_table
--   0001_01_01_000001_create_cache_table
--   0001_01_01_000002_create_jobs_table
--   2026_10_08_100000_create_residence_domain
--   2026_10_09_220000_add_handled_fields_to_maintenance_requests
--   2026_10_09_230000_add_guarantee_and_recognition_deeds
--
-- Import Hostinger : base vide, encodage utf8mb4 / utf8mb4_unicode_ci, moteur InnoDB.
-- Aucune donnée applicative. La table migrations ne contient que le registre des six fichiers,
-- pour que php artisan migrate ne tente pas de recréer les tables.
--
-- Les colonnes JSON de Laravel sont rendues comme sur MariaDB 10.11 :
-- LONGTEXT utf8mb4_bin + contrainte CHECK (json_valid(...)).
-- Cela s'importe sur MariaDB 10.4.3+ et MySQL 8.0.16+.
-- Les noms d'index et de clés étrangères restent sous la limite MySQL de 64 caractères
-- (le plus long fait 53). Les deux index qui dépasseraient cette limite sont déjà
-- nommés dans les migrations : exchange_rates_org_currency_idx et
-- utility_charge_org_prop_type_period_uq.
-- ON DELETE RESTRICT est le comportement par défaut d'InnoDB : il n'est pas répété.
-- ON DELETE SET NULL et ON DELETE CASCADE sont écrits quand la migration les demande.

SET NAMES utf8mb4;
SET TIME_ZONE = '+00:00';
SET FOREIGN_KEY_CHECKS = 0;
SET UNIQUE_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone_verified_at` timestamp NULL DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `timezone` varchar(255) NOT NULL DEFAULT 'Africa/Kinshasa',
  `locale` varchar(8) NOT NULL DEFAULT 'fr',
  `is_super_admin` tinyint(1) NOT NULL DEFAULT 0,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_phone_unique` (`phone`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` smallint(5) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` varchar(255) NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `organizations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `timezone` varchar(255) NOT NULL DEFAULT 'Africa/Kinshasa',
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`settings`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `organizations_slug_unique` (`slug`),
  KEY `organizations_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `organization_members` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `role` varchar(255) NOT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `organization_members_organization_id_user_id_unique` (`organization_id`,`user_id`),
  KEY `organization_members_user_id_foreign` (`user_id`),
  KEY `organization_members_organization_id_status_index` (`organization_id`,`status`),
  CONSTRAINT `organization_members_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
  CONSTRAINT `organization_members_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `platform_settings` (
  `key` varchar(255) NOT NULL,
  `value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`value`)),
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`key`),
  KEY `platform_settings_updated_by_foreign` (`updated_by`),
  CONSTRAINT `platform_settings_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `organization_counters` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint(20) unsigned NOT NULL,
  `key` varchar(255) NOT NULL,
  `value` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `organization_counters_organization_id_key_unique` (`organization_id`,`key`),
  CONSTRAINT `organization_counters_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `exchange_rates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint(20) unsigned NOT NULL,
  `base_currency` char(3) NOT NULL,
  `quote_currency` char(3) NOT NULL,
  `rate` decimal(20,8) NOT NULL,
  `effective_at` timestamp NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `exchange_rates_created_by_foreign` (`created_by`),
  KEY `exchange_rates_org_currency_idx` (`organization_id`,`base_currency`,`quote_currency`,`effective_at`),
  CONSTRAINT `exchange_rates_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `exchange_rates_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `expense_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `expense_categories_organization_id_slug_unique` (`organization_id`,`slug`),
  CONSTRAINT `expense_categories_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `properties` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `properties_organization_id_status_index` (`organization_id`,`status`),
  CONSTRAINT `properties_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `units` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint(20) unsigned NOT NULL,
  `property_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `reference` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `bedrooms` smallint(5) unsigned DEFAULT NULL,
  `features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`features`)),
  `price_minor` bigint(20) unsigned NOT NULL,
  `currency` char(3) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'available',
  `photo_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `units_organization_id_reference_unique` (`organization_id`,`reference`),
  KEY `units_organization_id_status_index` (`organization_id`,`status`),
  KEY `units_property_id_status_index` (`property_id`,`status`),
  CONSTRAINT `units_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
  CONSTRAINT `units_property_id_foreign` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `unit_price_histories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `unit_id` bigint(20) unsigned NOT NULL,
  `price_minor` bigint(20) unsigned NOT NULL,
  `currency` char(3) NOT NULL,
  `effective_at` timestamp NOT NULL,
  `changed_by` bigint(20) unsigned DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `unit_price_histories_changed_by_foreign` (`changed_by`),
  KEY `unit_price_histories_unit_id_effective_at_index` (`unit_id`,`effective_at`),
  CONSTRAINT `unit_price_histories_changed_by_foreign` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `unit_price_histories_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `tenants` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `occupants` smallint(5) unsigned NOT NULL DEFAULT 1,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tenants_organization_id_phone_unique` (`organization_id`,`phone`),
  KEY `tenants_organization_id_status_index` (`organization_id`,`status`),
  KEY `tenants_user_id_index` (`user_id`),
  CONSTRAINT `tenants_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
  CONSTRAINT `tenants_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `rental_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint(20) unsigned NOT NULL,
  `unit_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `tenant_id` bigint(20) unsigned DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `decided_by` bigint(20) unsigned DEFAULT NULL,
  `decided_at` timestamp NULL DEFAULT NULL,
  `decision_note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rental_requests_user_id_foreign` (`user_id`),
  KEY `rental_requests_tenant_id_foreign` (`tenant_id`),
  KEY `rental_requests_decided_by_foreign` (`decided_by`),
  KEY `rental_requests_organization_id_status_index` (`organization_id`,`status`),
  KEY `rental_requests_unit_id_status_index` (`unit_id`,`status`),
  CONSTRAINT `rental_requests_decided_by_foreign` FOREIGN KEY (`decided_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `rental_requests_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
  CONSTRAINT `rental_requests_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE SET NULL,
  CONSTRAINT `rental_requests_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`),
  CONSTRAINT `rental_requests_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `contracts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint(20) unsigned NOT NULL,
  `property_id` bigint(20) unsigned NOT NULL,
  `unit_id` bigint(20) unsigned NOT NULL,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `reference` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `rent_minor` bigint(20) unsigned NOT NULL,
  `currency` char(3) NOT NULL,
  `exchange_rate_id` bigint(20) unsigned DEFAULT NULL,
  `fx_base_currency` char(3) DEFAULT NULL,
  `fx_quote_currency` char(3) DEFAULT NULL,
  `fx_rate` decimal(20,8) DEFAULT NULL,
  `equivalent_minor` bigint(20) unsigned DEFAULT NULL,
  `equivalent_currency` char(3) DEFAULT NULL,
  `billing_cycle` varchar(255) NOT NULL DEFAULT 'monthly',
  `generation_day` tinyint(3) unsigned NOT NULL,
  `due_day` tinyint(3) unsigned NOT NULL,
  `grace_until_day` tinyint(3) unsigned NOT NULL,
  `prorata_method` varchar(255) NOT NULL,
  `conditions` text DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `guarantee_deposit_months` tinyint(3) unsigned DEFAULT NULL,
  `guarantee_advance_months` tinyint(3) unsigned DEFAULT NULL,
  `status` varchar(255) NOT NULL,
  `activated_at` timestamp NULL DEFAULT NULL,
  `terminated_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `contracts_organization_id_reference_unique` (`organization_id`,`reference`),
  KEY `contracts_property_id_foreign` (`property_id`),
  KEY `contracts_exchange_rate_id_foreign` (`exchange_rate_id`),
  KEY `contracts_created_by_foreign` (`created_by`),
  KEY `contracts_organization_id_status_index` (`organization_id`,`status`),
  KEY `contracts_unit_id_status_index` (`unit_id`,`status`),
  KEY `contracts_tenant_id_status_index` (`tenant_id`,`status`),
  CONSTRAINT `contracts_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `contracts_exchange_rate_id_foreign` FOREIGN KEY (`exchange_rate_id`) REFERENCES `exchange_rates` (`id`) ON DELETE SET NULL,
  CONSTRAINT `contracts_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
  CONSTRAINT `contracts_property_id_foreign` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`),
  CONSTRAINT `contracts_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`),
  CONSTRAINT `contracts_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `utility_charges` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint(20) unsigned NOT NULL,
  `property_id` bigint(20) unsigned NOT NULL,
  `type` varchar(255) NOT NULL,
  `period_key` char(7) NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `total_minor` bigint(20) unsigned NOT NULL,
  `currency` char(3) NOT NULL,
  `method` varchar(255) NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `utility_charge_org_prop_type_period_uq` (`organization_id`,`property_id`,`type`,`period_key`),
  KEY `utility_charges_property_id_foreign` (`property_id`),
  KEY `utility_charges_created_by_foreign` (`created_by`),
  CONSTRAINT `utility_charges_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `utility_charges_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
  CONSTRAINT `utility_charges_property_id_foreign` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint(20) unsigned NOT NULL,
  `contract_id` bigint(20) unsigned DEFAULT NULL,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `unit_id` bigint(20) unsigned DEFAULT NULL,
  `property_id` bigint(20) unsigned DEFAULT NULL,
  `utility_charge_id` bigint(20) unsigned DEFAULT NULL,
  `number` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `period_key` char(7) NOT NULL,
  `dedupe_key` varchar(255) DEFAULT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `amount_minor` bigint(20) unsigned NOT NULL,
  `currency` char(3) NOT NULL,
  `fx_rate` decimal(20,8) DEFAULT NULL,
  `due_on` date NOT NULL,
  `status` varchar(255) NOT NULL,
  `issued_at` timestamp NOT NULL,
  `notes` text DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancel_reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoices_organization_id_number_unique` (`organization_id`,`number`),
  UNIQUE KEY `invoices_contract_id_dedupe_key_unique` (`contract_id`,`dedupe_key`),
  KEY `invoices_unit_id_foreign` (`unit_id`),
  KEY `invoices_property_id_foreign` (`property_id`),
  KEY `invoices_utility_charge_id_foreign` (`utility_charge_id`),
  KEY `invoices_organization_id_status_index` (`organization_id`,`status`),
  KEY `invoices_organization_id_period_start_index` (`organization_id`,`period_start`),
  KEY `invoices_tenant_id_status_index` (`tenant_id`,`status`),
  KEY `invoices_due_on_index` (`due_on`),
  CONSTRAINT `invoices_contract_id_foreign` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invoices_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
  CONSTRAINT `invoices_property_id_foreign` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invoices_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`),
  CONSTRAINT `invoices_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invoices_utility_charge_id_foreign` FOREIGN KEY (`utility_charge_id`) REFERENCES `utility_charges` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `invoice_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint(20) unsigned NOT NULL,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `label` varchar(255) NOT NULL,
  `quantity` decimal(12,2) NOT NULL DEFAULT 1.00,
  `unit_price_minor` bigint(20) unsigned NOT NULL,
  `amount_minor` bigint(20) unsigned NOT NULL,
  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invoice_items_organization_id_foreign` (`organization_id`),
  KEY `invoice_items_invoice_id_index` (`invoice_id`),
  CONSTRAINT `invoice_items_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`),
  CONSTRAINT `invoice_items_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint(20) unsigned NOT NULL,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `contract_id` bigint(20) unsigned DEFAULT NULL,
  `invoice_id` bigint(20) unsigned DEFAULT NULL,
  `reference` varchar(255) NOT NULL,
  `provider` varchar(255) NOT NULL DEFAULT 'manual',
  `provider_reference` varchar(255) DEFAULT NULL,
  `kind` varchar(255) NOT NULL,
  `reverses_payment_id` bigint(20) unsigned DEFAULT NULL,
  `amount_minor` bigint(20) unsigned NOT NULL,
  `currency` char(3) NOT NULL,
  `method` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL,
  `proof_path` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `rejection_reason` varchar(255) DEFAULT NULL,
  `declared_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payments_organization_id_reference_unique` (`organization_id`,`reference`),
  KEY `payments_contract_id_foreign` (`contract_id`),
  KEY `payments_reverses_payment_id_foreign` (`reverses_payment_id`),
  KEY `payments_declared_by_foreign` (`declared_by`),
  KEY `payments_reviewed_by_foreign` (`reviewed_by`),
  KEY `payments_organization_id_status_index` (`organization_id`,`status`),
  KEY `payments_tenant_id_status_index` (`tenant_id`,`status`),
  KEY `payments_invoice_id_index` (`invoice_id`),
  CONSTRAINT `payments_contract_id_foreign` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payments_declared_by_foreign` FOREIGN KEY (`declared_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payments_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payments_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
  CONSTRAINT `payments_reverses_payment_id_foreign` FOREIGN KEY (`reverses_payment_id`) REFERENCES `payments` (`id`),
  CONSTRAINT `payments_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payments_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `payment_allocations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint(20) unsigned NOT NULL,
  `payment_id` bigint(20) unsigned NOT NULL,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `amount_minor` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_allocations_payment_id_invoice_id_unique` (`payment_id`,`invoice_id`),
  KEY `payment_allocations_organization_id_foreign` (`organization_id`),
  KEY `payment_allocations_invoice_id_payment_id_index` (`invoice_id`,`payment_id`),
  CONSTRAINT `payment_allocations_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`),
  CONSTRAINT `payment_allocations_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
  CONSTRAINT `payment_allocations_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cash_collections` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint(20) unsigned NOT NULL,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `agent_id` bigint(20) unsigned NOT NULL,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `payment_id` bigint(20) unsigned DEFAULT NULL,
  `reference` varchar(255) NOT NULL,
  `code` varchar(12) NOT NULL,
  `amount_minor` bigint(20) unsigned NOT NULL,
  `currency` char(3) NOT NULL,
  `status` varchar(255) NOT NULL,
  `tenant_confirmed_at` timestamp NULL DEFAULT NULL,
  `agent_confirmed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cash_collections_organization_id_reference_unique` (`organization_id`,`reference`),
  UNIQUE KEY `cash_collections_code_unique` (`code`),
  KEY `cash_collections_tenant_id_foreign` (`tenant_id`),
  KEY `cash_collections_invoice_id_foreign` (`invoice_id`),
  KEY `cash_collections_payment_id_foreign` (`payment_id`),
  KEY `cash_collections_organization_id_status_index` (`organization_id`,`status`),
  KEY `cash_collections_agent_id_status_index` (`agent_id`,`status`),
  CONSTRAINT `cash_collections_agent_id_foreign` FOREIGN KEY (`agent_id`) REFERENCES `users` (`id`),
  CONSTRAINT `cash_collections_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`),
  CONSTRAINT `cash_collections_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
  CONSTRAINT `cash_collections_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cash_collections_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cash_remittances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint(20) unsigned NOT NULL,
  `agent_id` bigint(20) unsigned NOT NULL,
  `reference` varchar(255) NOT NULL,
  `amount_minor` bigint(20) unsigned NOT NULL,
  `currency` char(3) NOT NULL,
  `status` varchar(255) NOT NULL,
  `note` text DEFAULT NULL,
  `confirmed_by` bigint(20) unsigned DEFAULT NULL,
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cash_remittances_organization_id_reference_unique` (`organization_id`,`reference`),
  KEY `cash_remittances_confirmed_by_foreign` (`confirmed_by`),
  KEY `cash_remittances_organization_id_status_index` (`organization_id`,`status`),
  KEY `cash_remittances_agent_id_status_index` (`agent_id`,`status`),
  CONSTRAINT `cash_remittances_agent_id_foreign` FOREIGN KEY (`agent_id`) REFERENCES `users` (`id`),
  CONSTRAINT `cash_remittances_confirmed_by_foreign` FOREIGN KEY (`confirmed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cash_remittances_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cash_remittance_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint(20) unsigned NOT NULL,
  `cash_remittance_id` bigint(20) unsigned NOT NULL,
  `cash_collection_id` bigint(20) unsigned NOT NULL,
  `amount_minor` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cash_remittance_items_cash_collection_id_unique` (`cash_collection_id`),
  KEY `cash_remittance_items_organization_id_foreign` (`organization_id`),
  KEY `cash_remittance_items_cash_remittance_id_index` (`cash_remittance_id`),
  CONSTRAINT `cash_remittance_items_cash_collection_id_foreign` FOREIGN KEY (`cash_collection_id`) REFERENCES `cash_collections` (`id`),
  CONSTRAINT `cash_remittance_items_cash_remittance_id_foreign` FOREIGN KEY (`cash_remittance_id`) REFERENCES `cash_remittances` (`id`),
  CONSTRAINT `cash_remittance_items_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `maintenance_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint(20) unsigned NOT NULL,
  `unit_id` bigint(20) unsigned NOT NULL,
  `tenant_id` bigint(20) unsigned DEFAULT NULL,
  `reported_by` bigint(20) unsigned DEFAULT NULL,
  `assigned_to` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `urgency` varchar(255) NOT NULL DEFAULT 'normal',
  `status` varchar(255) NOT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `estimated_cost_minor` bigint(20) unsigned DEFAULT NULL,
  `currency` char(3) DEFAULT NULL,
  `quote_note` text DEFAULT NULL,
  `handled_at` timestamp NULL DEFAULT NULL,
  `handled_by` bigint(20) unsigned DEFAULT NULL,
  `handled_note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `maintenance_requests_tenant_id_foreign` (`tenant_id`),
  KEY `maintenance_requests_reported_by_foreign` (`reported_by`),
  KEY `maintenance_requests_assigned_to_foreign` (`assigned_to`),
  KEY `maintenance_requests_organization_id_status_index` (`organization_id`,`status`),
  KEY `maintenance_requests_unit_id_status_index` (`unit_id`,`status`),
  KEY `maintenance_requests_handled_by_foreign` (`handled_by`),
  CONSTRAINT `maintenance_requests_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `maintenance_requests_handled_by_foreign` FOREIGN KEY (`handled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `maintenance_requests_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
  CONSTRAINT `maintenance_requests_reported_by_foreign` FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `maintenance_requests_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE SET NULL,
  CONSTRAINT `maintenance_requests_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `expenses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint(20) unsigned NOT NULL,
  `expense_category_id` bigint(20) unsigned NOT NULL,
  `property_id` bigint(20) unsigned DEFAULT NULL,
  `unit_id` bigint(20) unsigned DEFAULT NULL,
  `tenant_id` bigint(20) unsigned DEFAULT NULL,
  `maintenance_request_id` bigint(20) unsigned DEFAULT NULL,
  `amount_minor` bigint(20) unsigned NOT NULL,
  `currency` char(3) NOT NULL,
  `spent_on` date NOT NULL,
  `payee` varchar(255) DEFAULT NULL,
  `motif` varchar(255) NOT NULL,
  `comment` text DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'recorded',
  `voided_at` timestamp NULL DEFAULT NULL,
  `void_reason` varchar(255) DEFAULT NULL,
  `recorded_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expenses_expense_category_id_foreign` (`expense_category_id`),
  KEY `expenses_property_id_foreign` (`property_id`),
  KEY `expenses_tenant_id_foreign` (`tenant_id`),
  KEY `expenses_maintenance_request_id_foreign` (`maintenance_request_id`),
  KEY `expenses_recorded_by_foreign` (`recorded_by`),
  KEY `expenses_organization_id_spent_on_index` (`organization_id`,`spent_on`),
  KEY `expenses_organization_id_status_index` (`organization_id`,`status`),
  KEY `expenses_unit_id_spent_on_index` (`unit_id`,`spent_on`),
  CONSTRAINT `expenses_expense_category_id_foreign` FOREIGN KEY (`expense_category_id`) REFERENCES `expense_categories` (`id`),
  CONSTRAINT `expenses_maintenance_request_id_foreign` FOREIGN KEY (`maintenance_request_id`) REFERENCES `maintenance_requests` (`id`) ON DELETE SET NULL,
  CONSTRAINT `expenses_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
  CONSTRAINT `expenses_property_id_foreign` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE SET NULL,
  CONSTRAINT `expenses_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `expenses_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE SET NULL,
  CONSTRAINT `expenses_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `maintenance_updates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint(20) unsigned NOT NULL,
  `maintenance_request_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `status_to` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `expense_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `maintenance_updates_organization_id_foreign` (`organization_id`),
  KEY `maintenance_updates_user_id_foreign` (`user_id`),
  KEY `maintenance_updates_expense_id_foreign` (`expense_id`),
  KEY `maintenance_updates_maintenance_request_id_index` (`maintenance_request_id`),
  CONSTRAINT `maintenance_updates_expense_id_foreign` FOREIGN KEY (`expense_id`) REFERENCES `expenses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `maintenance_updates_maintenance_request_id_foreign` FOREIGN KEY (`maintenance_request_id`) REFERENCES `maintenance_requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `maintenance_updates_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
  CONSTRAINT `maintenance_updates_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `move_out_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint(20) unsigned NOT NULL,
  `contract_id` bigint(20) unsigned NOT NULL,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `requested_by` bigint(20) unsigned DEFAULT NULL,
  `requested_on` date NOT NULL,
  `planned_on` date NOT NULL,
  `reason` text DEFAULT NULL,
  `status` varchar(255) NOT NULL,
  `checklist` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`checklist`)),
  `photo_path` varchar(255) DEFAULT NULL,
  `review_note` text DEFAULT NULL,
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `reminder_sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `move_out_requests_tenant_id_foreign` (`tenant_id`),
  KEY `move_out_requests_requested_by_foreign` (`requested_by`),
  KEY `move_out_requests_reviewed_by_foreign` (`reviewed_by`),
  KEY `move_out_requests_organization_id_status_index` (`organization_id`,`status`),
  KEY `move_out_requests_contract_id_status_index` (`contract_id`,`status`),
  CONSTRAINT `move_out_requests_contract_id_foreign` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`),
  CONSTRAINT `move_out_requests_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
  CONSTRAINT `move_out_requests_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `move_out_requests_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `move_out_requests_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `message_threads` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint(20) unsigned NOT NULL,
  `pair_key` varchar(255) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `message_threads_organization_id_pair_key_unique` (`organization_id`,`pair_key`),
  CONSTRAINT `message_threads_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `message_participants` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `message_thread_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `last_read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `message_participants_message_thread_id_user_id_unique` (`message_thread_id`,`user_id`),
  KEY `message_participants_user_id_foreign` (`user_id`),
  CONSTRAINT `message_participants_message_thread_id_foreign` FOREIGN KEY (`message_thread_id`) REFERENCES `message_threads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `message_participants_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint(20) unsigned NOT NULL,
  `message_thread_id` bigint(20) unsigned NOT NULL,
  `sender_id` bigint(20) unsigned DEFAULT NULL,
  `body` text NOT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `messages_organization_id_foreign` (`organization_id`),
  KEY `messages_sender_id_foreign` (`sender_id`),
  KEY `messages_message_thread_id_created_at_index` (`message_thread_id`,`created_at`),
  CONSTRAINT `messages_message_thread_id_foreign` FOREIGN KEY (`message_thread_id`) REFERENCES `message_threads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `messages_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
  CONSTRAINT `messages_sender_id_foreign` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `notifications` (
  `id` char(36) NOT NULL,
  `type` varchar(255) NOT NULL,
  `notifiable_type` varchar(255) NOT NULL,
  `notifiable_id` bigint(20) unsigned NOT NULL,
  `data` text NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `subject_type` varchar(255) DEFAULT NULL,
  `subject_id` bigint(20) unsigned DEFAULT NULL,
  `description` text NOT NULL,
  `properties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`properties`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `audit_logs_organization_id_created_at_index` (`organization_id`,`created_at`),
  KEY `audit_logs_subject_type_subject_id_index` (`subject_type`,`subject_id`),
  KEY `audit_logs_user_id_index` (`user_id`),
  CONSTRAINT `audit_logs_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `invoice_reminders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `kind` varchar(255) NOT NULL,
  `sent_on` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_reminders_invoice_id_kind_sent_on_unique` (`invoice_id`,`kind`,`sent_on`),
  CONSTRAINT `invoice_reminders_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `recognition_deeds` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint(20) unsigned NOT NULL,
  `contract_id` bigint(20) unsigned NOT NULL,
  `reference` varchar(255) NOT NULL,
  `payee_name` varchar(255) NOT NULL,
  `deposit_months` tinyint(3) unsigned NOT NULL,
  `advance_months` tinyint(3) unsigned NOT NULL,
  `amount_minor` bigint(20) unsigned NOT NULL,
  `currency` char(3) NOT NULL,
  `identity_document` varchar(255) DEFAULT NULL,
  `identity_path` varchar(255) DEFAULT NULL,
  `origin` varchar(255) DEFAULT NULL,
  `premises` varchar(255) NOT NULL,
  `landlord_witnesses` text DEFAULT NULL,
  `tenant_witnesses` text DEFAULT NULL,
  `certified_at` timestamp NULL DEFAULT NULL,
  `certificate_code` varchar(255) DEFAULT NULL,
  `certificate_holder` varchar(255) DEFAULT NULL,
  `certificate_path` varchar(255) DEFAULT NULL,
  `content_hash` varchar(64) DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `recognition_deeds_organization_id_reference_unique` (`organization_id`,`reference`),
  UNIQUE KEY `recognition_deeds_contract_id_unique` (`contract_id`),
  KEY `recognition_deeds_created_by_foreign` (`created_by`),
  CONSTRAINT `recognition_deeds_contract_id_foreign` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`),
  CONSTRAINT `recognition_deeds_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `recognition_deeds_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`migration`, `batch`) VALUES
('0001_01_01_000000_create_users_table', 1),
('0001_01_01_000001_create_cache_table', 1),
('0001_01_01_000002_create_jobs_table', 1),
('2026_10_08_100000_create_residence_domain', 1),
('2026_10_09_220000_add_handled_fields_to_maintenance_requests', 1),
('2026_10_09_230000_add_guarantee_and_recognition_deeds', 1);

SET UNIQUE_CHECKS = 1;
SET FOREIGN_KEY_CHECKS = 1;
