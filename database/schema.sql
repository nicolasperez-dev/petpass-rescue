SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS ad_clicks;
DROP TABLE IF EXISTS ad_impressions;
DROP TABLE IF EXISTS ad_campaigns;
DROP TABLE IF EXISTS restaurant_reservations;
DROP TABLE IF EXISTS hotel_bookings;
DROP TABLE IF EXISTS hotel_units;
DROP TABLE IF EXISTS offers;
DROP TABLE IF EXISTS product_media;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS appointments;
DROP TABLE IF EXISTS services;
DROP TABLE IF EXISTS partner_staff;
DROP TABLE IF EXISTS partner_hours;
DROP TABLE IF EXISTS directory_places;
DROP TABLE IF EXISTS partner_locations;
DROP TABLE IF EXISTS partners;
DROP TABLE IF EXISTS device_locations;
DROP TABLE IF EXISTS device_commands;
DROP TABLE IF EXISTS devices;
DROP TABLE IF EXISTS notification_deliveries;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS rescue_participants;
DROP TABLE IF EXISTS sightings;
DROP TABLE IF EXISTS lost_cases;
DROP TABLE IF EXISTS tag_scans;
DROP TABLE IF EXISTS physical_tags;
DROP TABLE IF EXISTS health_documents;
DROP TABLE IF EXISTS medical_records;
DROP TABLE IF EXISTS vaccinations;
DROP TABLE IF EXISTS medications;
DROP TABLE IF EXISTS allergies;
DROP TABLE IF EXISTS emergency_contacts;
DROP TABLE IF EXISTS pet_media;
DROP TABLE IF EXISTS pet_field_visibility;
DROP TABLE IF EXISTS pet_feature_overrides;
DROP TABLE IF EXISTS pets;
DROP TABLE IF EXISTS breeds;
DROP TABLE IF EXISTS species;
DROP TABLE IF EXISTS user_profiles;
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS plan_features;
DROP TABLE IF EXISTS plans;
DROP TABLE IF EXISTS roles;
DROP TABLE IF EXISTS bot_faqs;
DROP TABLE IF EXISTS app_settings;

CREATE TABLE roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(40) NOT NULL UNIQUE,
    name VARCHAR(80) NOT NULL,
    description VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE plans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(40) NOT NULL UNIQUE,
    name VARCHAR(80) NOT NULL,
    audience ENUM('OWNER','PARTNER','ALL') NOT NULL DEFAULT 'OWNER',
    price_cents INT UNSIGNED NOT NULL DEFAULT 0,
    description VARCHAR(255) NULL,
    highlighted TINYINT(1) NOT NULL DEFAULT 0,
    active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order SMALLINT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE plan_features (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plan_id BIGINT UNSIGNED NOT NULL,
    feature_code VARCHAR(80) NOT NULL,
    label VARCHAR(160) NOT NULL,
    enabled TINYINT(1) NOT NULL DEFAULT 1,
    feature_limit INT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_plan_feature (plan_id, feature_code),
    CONSTRAINT fk_plan_features_plan FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id BIGINT UNSIGNED NOT NULL,
    plan_id BIGINT UNSIGNED NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(160) NOT NULL,
    phone VARCHAR(40) NULL,
    locale VARCHAR(10) NOT NULL DEFAULT 'pt-BR',
    status ENUM('PENDING','ACTIVE','SUSPENDED','DELETED') NOT NULL DEFAULT 'ACTIVE',
    email_verified_at DATETIME NULL,
    last_login_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_users_role (role_id),
    KEY idx_users_plan (plan_id),
    KEY idx_users_status (status),
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id),
    CONSTRAINT fk_users_plan FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE password_resets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_password_reset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_profiles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    document_number VARCHAR(40) NULL,
    city VARCHAR(120) NULL,
    state VARCHAR(80) NULL,
    country_code CHAR(2) NOT NULL DEFAULT 'BR',
    avatar_path VARCHAR(255) NULL,
    notification_radius_km SMALLINT UNSIGNED NOT NULL DEFAULT 5,
    lgpd_consent TINYINT(1) NOT NULL DEFAULT 0,
    marketing_consent TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_profiles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE species (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(60) NOT NULL UNIQUE,
    name_pt VARCHAR(100) NOT NULL,
    name_es VARCHAR(100) NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order SMALLINT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE breeds (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    species_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(140) NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_breed_species (species_id, name),
    KEY idx_breeds_species (species_id),
    CONSTRAINT fk_breeds_species FOREIGN KEY (species_id) REFERENCES species(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    public_id VARCHAR(32) NOT NULL UNIQUE,
    owner_user_id BIGINT UNSIGNED NOT NULL,
    species_id BIGINT UNSIGNED NULL,
    breed_id BIGINT UNSIGNED NULL,
    custom_species VARCHAR(120) NULL,
    custom_breed VARCHAR(140) NULL,
    name VARCHAR(120) NOT NULL,
    nickname VARCHAR(120) NULL,
    sex ENUM('MALE','FEMALE','UNKNOWN') NOT NULL DEFAULT 'UNKNOWN',
    birth_date DATE NULL,
    approximate_age VARCHAR(80) NULL,
    weight_kg DECIMAL(7,2) NULL,
    color VARCHAR(160) NULL,
    size ENUM('TINY','SMALL','MEDIUM','LARGE','GIANT','UNKNOWN') NOT NULL DEFAULT 'UNKNOWN',
    microchip VARCHAR(80) NULL,
    neutered TINYINT(1) NULL,
    temperament TEXT NULL,
    approach_instructions TEXT NULL,
    favorite_things TEXT NULL,
    fears TEXT NULL,
    general_notes TEXT NULL,
    city VARCHAR(120) NULL,
    state VARCHAR(80) NULL,
    country_code CHAR(2) NOT NULL DEFAULT 'BR',
    main_photo_path VARCHAR(255) NULL,
    public_audio_path VARCHAR(255) NULL,
    reward_cents INT UNSIGNED NOT NULL DEFAULT 0,
    is_lost TINYINT(1) NOT NULL DEFAULT 0,
    status ENUM('ACTIVE','DECEASED','ARCHIVED') NOT NULL DEFAULT 'ACTIVE',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_pets_owner (owner_user_id),
    KEY idx_pets_lost_city (is_lost, city),
    KEY idx_pets_species (species_id),
    CONSTRAINT fk_pets_owner FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_pets_species FOREIGN KEY (species_id) REFERENCES species(id) ON DELETE SET NULL,
    CONSTRAINT fk_pets_breed FOREIGN KEY (breed_id) REFERENCES breeds(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pet_feature_overrides (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pet_id BIGINT UNSIGNED NOT NULL,
    feature_code VARCHAR(80) NOT NULL,
    enabled TINYINT(1) NOT NULL DEFAULT 1,
    reason VARCHAR(255) NULL,
    granted_by_user_id BIGINT UNSIGNED NULL,
    expires_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_pet_feature (pet_id, feature_code),
    CONSTRAINT fk_pet_feature_pet FOREIGN KEY (pet_id) REFERENCES pets(id) ON DELETE CASCADE,
    CONSTRAINT fk_pet_feature_admin FOREIGN KEY (granted_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pet_field_visibility (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pet_id BIGINT UNSIGNED NOT NULL,
    field_code VARCHAR(80) NOT NULL,
    visibility ENUM('PUBLIC','LOST_ONLY','AUTHORIZED','PRIVATE') NOT NULL DEFAULT 'PRIVATE',
    UNIQUE KEY uq_pet_field_visibility (pet_id, field_code),
    CONSTRAINT fk_pet_visibility_pet FOREIGN KEY (pet_id) REFERENCES pets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pet_media (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pet_id BIGINT UNSIGNED NOT NULL,
    media_type ENUM('PHOTO','AUDIO','VIDEO','DOCUMENT') NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    original_name VARCHAR(190) NULL,
    mime_type VARCHAR(100) NULL,
    file_size INT UNSIGNED NULL,
    caption VARCHAR(255) NULL,
    is_public TINYINT(1) NOT NULL DEFAULT 0,
    sort_order SMALLINT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_pet_media_pet_type (pet_id, media_type),
    CONSTRAINT fk_pet_media_pet FOREIGN KEY (pet_id) REFERENCES pets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE emergency_contacts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pet_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(160) NOT NULL,
    phone VARCHAR(40) NOT NULL,
    whatsapp VARCHAR(40) NULL,
    relationship VARCHAR(100) NULL,
    priority SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    is_public TINYINT(1) NOT NULL DEFAULT 1,
    receives_alerts TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_emergency_pet_priority (pet_id, priority),
    CONSTRAINT fk_emergency_pet FOREIGN KEY (pet_id) REFERENCES pets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE allergies (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pet_id BIGINT UNSIGNED NOT NULL,
    allergen VARCHAR(190) NOT NULL,
    severity ENUM('LOW','MEDIUM','HIGH','CRITICAL') NOT NULL DEFAULT 'MEDIUM',
    notes TEXT NULL,
    is_public_emergency TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_allergies_pet (pet_id),
    CONSTRAINT fk_allergies_pet FOREIGN KEY (pet_id) REFERENCES pets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE medications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pet_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(190) NOT NULL,
    dosage VARCHAR(120) NULL,
    frequency VARCHAR(120) NULL,
    starts_at DATE NULL,
    ends_at DATE NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    is_public_emergency TINYINT(1) NOT NULL DEFAULT 0,
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_medications_pet_active (pet_id, active),
    CONSTRAINT fk_medications_pet FOREIGN KEY (pet_id) REFERENCES pets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE vaccinations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pet_id BIGINT UNSIGNED NOT NULL,
    vaccine_name VARCHAR(190) NOT NULL,
    applied_at DATE NULL,
    next_dose_at DATE NULL,
    veterinarian_name VARCHAR(190) NULL,
    partner_id BIGINT UNSIGNED NULL,
    batch_number VARCHAR(100) NULL,
    status ENUM('PENDING','CURRENT','OVERDUE') NOT NULL DEFAULT 'CURRENT',
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_vaccinations_pet_next (pet_id, next_dose_at),
    CONSTRAINT fk_vaccinations_pet FOREIGN KEY (pet_id) REFERENCES pets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE medical_records (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pet_id BIGINT UNSIGNED NOT NULL,
    partner_id BIGINT UNSIGNED NULL,
    professional_user_id BIGINT UNSIGNED NULL,
    record_type ENUM('CONSULTATION','EXAM','SURGERY','DIAGNOSIS','PRESCRIPTION','NOTE') NOT NULL DEFAULT 'CONSULTATION',
    title VARCHAR(190) NOT NULL,
    description TEXT NULL,
    record_date DATETIME NOT NULL,
    next_check_at DATETIME NULL,
    privacy ENUM('OWNER','AUTHORIZED_VETS','PUBLIC_EMERGENCY') NOT NULL DEFAULT 'OWNER',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_medical_pet_date (pet_id, record_date),
    CONSTRAINT fk_medical_pet FOREIGN KEY (pet_id) REFERENCES pets(id) ON DELETE CASCADE,
    CONSTRAINT fk_medical_professional FOREIGN KEY (professional_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE health_documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pet_id BIGINT UNSIGNED NOT NULL,
    medical_record_id BIGINT UNSIGNED NULL,
    name VARCHAR(190) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NULL,
    privacy ENUM('OWNER','AUTHORIZED_VETS','PUBLIC_EMERGENCY') NOT NULL DEFAULT 'OWNER',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_health_docs_pet (pet_id),
    CONSTRAINT fk_health_docs_pet FOREIGN KEY (pet_id) REFERENCES pets(id) ON DELETE CASCADE,
    CONSTRAINT fk_health_docs_record FOREIGN KEY (medical_record_id) REFERENCES medical_records(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE physical_tags (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    public_code VARCHAR(48) NOT NULL UNIQUE,
    activation_code_hash VARCHAR(255) NULL,
    pet_id BIGINT UNSIGNED NULL,
    claimed_by_user_id BIGINT UNSIGNED NULL,
    label VARCHAR(120) NULL,
    status ENUM('UNCLAIMED','ACTIVE','SUSPENDED','REVOKED') NOT NULL DEFAULT 'UNCLAIMED',
    nfc_programmed_at DATETIME NULL,
    nfc_locked TINYINT(1) NOT NULL DEFAULT 0,
    claimed_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_tags_pet (pet_id),
    KEY idx_tags_status (status),
    CONSTRAINT fk_tags_pet FOREIGN KEY (pet_id) REFERENCES pets(id) ON DELETE SET NULL,
    CONSTRAINT fk_tags_claimed_by FOREIGN KEY (claimed_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tag_scans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    physical_tag_id BIGINT UNSIGNED NOT NULL,
    pet_id BIGINT UNSIGNED NULL,
    source ENUM('QR','NFC','UNKNOWN') NOT NULL DEFAULT 'UNKNOWN',
    latitude DECIMAL(10,7) NULL,
    longitude DECIMAL(10,7) NULL,
    user_agent VARCHAR(255) NULL,
    ip_hash CHAR(64) NULL,
    scanned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_scans_tag_time (physical_tag_id, scanned_at),
    KEY idx_scans_pet_time (pet_id, scanned_at),
    CONSTRAINT fk_scans_tag FOREIGN KEY (physical_tag_id) REFERENCES physical_tags(id) ON DELETE CASCADE,
    CONSTRAINT fk_scans_pet FOREIGN KEY (pet_id) REFERENCES pets(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE lost_cases (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pet_id BIGINT UNSIGNED NOT NULL,
    status ENUM('ACTIVE','RESOLVED','CANCELLED') NOT NULL DEFAULT 'ACTIVE',
    last_seen_at DATETIME NULL,
    last_seen_address VARCHAR(255) NULL,
    last_seen_city VARCHAR(120) NULL,
    latitude DECIMAL(10,7) NULL,
    longitude DECIMAL(10,7) NULL,
    description TEXT NULL,
    public_message TEXT NULL,
    radius_km SMALLINT UNSIGNED NOT NULL DEFAULT 5,
    reward_cents INT UNSIGNED NOT NULL DEFAULT 0,
    activated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_at DATETIME NULL,
    KEY idx_lost_status_city (status, last_seen_city),
    KEY idx_lost_pet (pet_id),
    CONSTRAINT fk_lost_pet FOREIGN KEY (pet_id) REFERENCES pets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sightings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lost_case_id BIGINT UNSIGNED NOT NULL,
    reporter_name VARCHAR(160) NULL,
    reporter_phone VARCHAR(40) NULL,
    message TEXT NULL,
    latitude DECIMAL(10,7) NULL,
    longitude DECIMAL(10,7) NULL,
    photo_path VARCHAR(255) NULL,
    location_consent TINYINT(1) NOT NULL DEFAULT 0,
    status ENUM('NEW','VERIFIED','DISCARDED') NOT NULL DEFAULT 'NEW',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_sightings_case_time (lost_case_id, created_at),
    CONSTRAINT fk_sightings_case FOREIGN KEY (lost_case_id) REFERENCES lost_cases(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE rescue_participants (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lost_case_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    partner_id BIGINT UNSIGNED NULL,
    display_name VARCHAR(160) NOT NULL,
    participant_type VARCHAR(80) NOT NULL,
    joined_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_participants_case (lost_case_id),
    CONSTRAINT fk_participants_case FOREIGN KEY (lost_case_id) REFERENCES lost_cases(id) ON DELETE CASCADE,
    CONSTRAINT fk_participants_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    type VARCHAR(80) NOT NULL,
    title VARCHAR(190) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255) NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_notifications_user_read (user_id, is_read, created_at),
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notification_deliveries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    notification_id BIGINT UNSIGNED NOT NULL,
    channel ENUM('INTERNAL','EMAIL','SMS','WHATSAPP','PUSH') NOT NULL,
    recipient VARCHAR(190) NULL,
    status ENUM('PENDING','SENT','FAILED','SIMULATED') NOT NULL DEFAULT 'SIMULATED',
    external_id VARCHAR(190) NULL,
    sent_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_delivery_notification (notification_id),
    CONSTRAINT fk_delivery_notification FOREIGN KEY (notification_id) REFERENCES notifications(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE partners (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    owner_user_id BIGINT UNSIGNED NOT NULL,
    slug VARCHAR(160) NOT NULL UNIQUE,
    partner_type ENUM('VETERINARY','PETSHOP','HOTEL','RESTAURANT','WALKER','SHELTER','OTHER') NOT NULL,
    name VARCHAR(190) NOT NULL,
    legal_name VARCHAR(190) NULL,
    tax_id VARCHAR(40) NULL,
    description TEXT NULL,
    phone VARCHAR(40) NULL,
    whatsapp VARCHAR(40) NULL,
    email VARCHAR(190) NULL,
    website VARCHAR(255) NULL,
    logo_path VARCHAR(255) NULL,
    cover_path VARCHAR(255) NULL,
    status ENUM('PENDING','ACTIVE','SUSPENDED') NOT NULL DEFAULT 'PENDING',
    verified TINYINT(1) NOT NULL DEFAULT 0,
    sponsored TINYINT(1) NOT NULL DEFAULT 0,
    rating DECIMAL(3,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_partners_type_status (partner_type, status),
    CONSTRAINT fk_partners_owner FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE partner_locations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    partner_id BIGINT UNSIGNED NOT NULL,
    label VARCHAR(120) NULL,
    address VARCHAR(255) NOT NULL,
    neighborhood VARCHAR(120) NULL,
    city VARCHAR(120) NOT NULL,
    state VARCHAR(80) NOT NULL,
    postal_code VARCHAR(20) NULL,
    country_code CHAR(2) NOT NULL DEFAULT 'BR',
    latitude DECIMAL(10,7) NULL,
    longitude DECIMAL(10,7) NULL,
    emergency_24h TINYINT(1) NOT NULL DEFAULT 0,
    is_primary TINYINT(1) NOT NULL DEFAULT 1,
    KEY idx_partner_locations_city (city, partner_id),
    CONSTRAINT fk_partner_locations_partner FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE directory_places (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(180) NOT NULL UNIQUE,
    place_type ENUM('VETERINARY','PETSHOP','HOTEL','RESTAURANT','OTHER') NOT NULL,
    subtype VARCHAR(160) NULL,
    name VARCHAR(190) NOT NULL,
    description TEXT NULL,
    phone VARCHAR(50) NULL,
    whatsapp VARCHAR(50) NULL,
    website VARCHAR(255) NULL,
    source_url VARCHAR(500) NULL,
    address VARCHAR(255) NOT NULL,
    neighborhood VARCHAR(140) NULL,
    city VARCHAR(120) NOT NULL,
    state VARCHAR(80) NOT NULL DEFAULT 'SC',
    postal_code VARCHAR(20) NULL,
    country_code CHAR(2) NOT NULL DEFAULT 'BR',
    latitude DECIMAL(10,7) NULL,
    longitude DECIMAL(10,7) NULL,
    coordinate_precision ENUM('EXACT','APPROXIMATE') NOT NULL DEFAULT 'APPROXIMATE',
    emergency_24h TINYINT(1) NOT NULL DEFAULT 0,
    pet_friendly TINYINT(1) NOT NULL DEFAULT 1,
    services_text VARCHAR(500) NULL,
    verified_at DATE NULL,
    data_status ENUM('ACTIVE','NEEDS_REVIEW','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_directory_type_city (place_type, city, data_status),
    KEY idx_directory_emergency (emergency_24h, city),
    KEY idx_directory_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE partner_hours (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    partner_id BIGINT UNSIGNED NOT NULL,
    weekday TINYINT UNSIGNED NOT NULL,
    opens_at TIME NULL,
    closes_at TIME NULL,
    open_24h TINYINT(1) NOT NULL DEFAULT 0,
    closed TINYINT(1) NOT NULL DEFAULT 0,
    UNIQUE KEY uq_partner_weekday (partner_id, weekday),
    CONSTRAINT fk_partner_hours_partner FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE partner_staff (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    partner_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    name VARCHAR(160) NOT NULL,
    role_title VARCHAR(120) NULL,
    license_number VARCHAR(80) NULL,
    permissions_json JSON NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    KEY idx_staff_partner (partner_id),
    CONSTRAINT fk_staff_partner FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE CASCADE,
    CONSTRAINT fk_staff_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE vaccinations ADD CONSTRAINT fk_vaccinations_partner FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE SET NULL;
ALTER TABLE medical_records ADD CONSTRAINT fk_medical_partner FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE SET NULL;
ALTER TABLE rescue_participants ADD CONSTRAINT fk_participants_partner FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE SET NULL;

CREATE TABLE services (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    partner_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(190) NOT NULL,
    category VARCHAR(100) NOT NULL,
    description TEXT NULL,
    price_cents INT UNSIGNED NOT NULL DEFAULT 0,
    duration_minutes SMALLINT UNSIGNED NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_services_partner_category (partner_id, category),
    CONSTRAINT fk_services_partner FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE appointments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    partner_id BIGINT UNSIGNED NOT NULL,
    service_id BIGINT UNSIGNED NULL,
    pet_id BIGINT UNSIGNED NOT NULL,
    owner_user_id BIGINT UNSIGNED NOT NULL,
    staff_id BIGINT UNSIGNED NULL,
    scheduled_at DATETIME NOT NULL,
    status ENUM('PENDING','CONFIRMED','IN_PROGRESS','COMPLETED','CANCELLED') NOT NULL DEFAULT 'PENDING',
    notes TEXT NULL,
    price_cents INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_appointments_partner_time (partner_id, scheduled_at),
    KEY idx_appointments_owner (owner_user_id, status),
    CONSTRAINT fk_appointments_partner FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE CASCADE,
    CONSTRAINT fk_appointments_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL,
    CONSTRAINT fk_appointments_pet FOREIGN KEY (pet_id) REFERENCES pets(id) ON DELETE CASCADE,
    CONSTRAINT fk_appointments_owner FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_appointments_staff FOREIGN KEY (staff_id) REFERENCES partner_staff(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE products (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    partner_id BIGINT UNSIGNED NOT NULL,
    sku VARCHAR(100) NULL UNIQUE,
    name VARCHAR(190) NOT NULL,
    category VARCHAR(100) NULL,
    description TEXT NULL,
    price_cents INT UNSIGNED NOT NULL DEFAULT 0,
    promotional_price_cents INT UNSIGNED NULL,
    stock INT NOT NULL DEFAULT 0,
    main_image_path VARCHAR(255) NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    featured TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_products_partner_active (partner_id, active),
    CONSTRAINT fk_products_partner FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE product_media (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    media_type ENUM('PHOTO','VIDEO') NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    sort_order SMALLINT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_product_media_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE offers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    partner_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(190) NOT NULL,
    description TEXT NULL,
    discount_percent TINYINT UNSIGNED NULL,
    coupon_code VARCHAR(80) NULL,
    starts_at DATETIME NULL,
    ends_at DATETIME NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_offers_partner_active (partner_id, active),
    CONSTRAINT fk_offers_partner FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hotel_units (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    partner_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(160) NOT NULL,
    capacity SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    pet_size VARCHAR(60) NULL,
    daily_price_cents INT UNSIGNED NOT NULL DEFAULT 0,
    description TEXT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT fk_hotel_units_partner FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hotel_bookings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hotel_unit_id BIGINT UNSIGNED NOT NULL,
    pet_id BIGINT UNSIGNED NOT NULL,
    owner_user_id BIGINT UNSIGNED NOT NULL,
    check_in DATE NOT NULL,
    check_out DATE NOT NULL,
    status ENUM('PENDING','CONFIRMED','CHECKED_IN','COMPLETED','CANCELLED') NOT NULL DEFAULT 'PENDING',
    requirements_checked TINYINT(1) NOT NULL DEFAULT 0,
    notes TEXT NULL,
    total_cents INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_hotel_booking_unit FOREIGN KEY (hotel_unit_id) REFERENCES hotel_units(id) ON DELETE CASCADE,
    CONSTRAINT fk_hotel_booking_pet FOREIGN KEY (pet_id) REFERENCES pets(id) ON DELETE CASCADE,
    CONSTRAINT fk_hotel_booking_owner FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE restaurant_reservations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    partner_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    pet_id BIGINT UNSIGNED NULL,
    reserved_at DATETIME NOT NULL,
    guests SMALLINT UNSIGNED NOT NULL DEFAULT 2,
    pet_area_required TINYINT(1) NOT NULL DEFAULT 1,
    status ENUM('PENDING','CONFIRMED','COMPLETED','CANCELLED') NOT NULL DEFAULT 'PENDING',
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_restaurant_reservation_partner FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE CASCADE,
    CONSTRAINT fk_restaurant_reservation_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_restaurant_reservation_pet FOREIGN KEY (pet_id) REFERENCES pets(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE devices (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pet_id BIGINT UNSIGNED NOT NULL,
    serial_number VARCHAR(100) NOT NULL UNIQUE,
    model VARCHAR(100) NOT NULL,
    status ENUM('ONLINE','OFFLINE','SUSPENDED') NOT NULL DEFAULT 'OFFLINE',
    battery_percent TINYINT UNSIGNED NULL,
    signal_percent TINYINT UNSIGNED NULL,
    firmware_version VARCHAR(80) NULL,
    last_seen_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_devices_pet FOREIGN KEY (pet_id) REFERENCES pets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE device_commands (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    device_id BIGINT UNSIGNED NOT NULL,
    command VARCHAR(80) NOT NULL,
    payload_json JSON NULL,
    status ENUM('QUEUED','SENT','EXECUTED','FAILED','EXPIRED') NOT NULL DEFAULT 'QUEUED',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    executed_at DATETIME NULL,
    KEY idx_device_commands_device_time (device_id, created_at),
    CONSTRAINT fk_device_commands_device FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE device_locations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    device_id BIGINT UNSIGNED NOT NULL,
    latitude DECIMAL(10,7) NOT NULL,
    longitude DECIMAL(10,7) NOT NULL,
    accuracy_meters DECIMAL(8,2) NULL,
    recorded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_device_locations_time (device_id, recorded_at),
    CONSTRAINT fk_device_locations_device FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ad_campaigns (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    partner_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(190) NOT NULL,
    description TEXT NULL,
    image_path VARCHAR(255) NULL,
    target_url VARCHAR(255) NULL,
    placement VARCHAR(80) NOT NULL DEFAULT 'OWNER_DASHBOARD',
    target_city VARCHAR(120) NULL,
    target_species_id BIGINT UNSIGNED NULL,
    status ENUM('DRAFT','PENDING','APPROVED','PAUSED','FINISHED','REJECTED') NOT NULL DEFAULT 'PENDING',
    starts_at DATETIME NULL,
    ends_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ads_partner FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE CASCADE,
    CONSTRAINT fk_ads_species FOREIGN KEY (target_species_id) REFERENCES species(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ad_impressions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_ad_impressions_campaign_time (campaign_id, created_at),
    CONSTRAINT fk_ad_impressions_campaign FOREIGN KEY (campaign_id) REFERENCES ad_campaigns(id) ON DELETE CASCADE,
    CONSTRAINT fk_ad_impressions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ad_clicks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_ad_clicks_campaign_time (campaign_id, created_at),
    CONSTRAINT fk_ad_clicks_campaign FOREIGN KEY (campaign_id) REFERENCES ad_campaigns(id) ON DELETE CASCADE,
    CONSTRAINT fk_ad_clicks_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE bot_faqs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    language VARCHAR(10) NOT NULL DEFAULT 'pt-BR',
    keywords VARCHAR(255) NOT NULL,
    question VARCHAR(255) NOT NULL,
    answer TEXT NOT NULL,
    sort_order SMALLINT NOT NULL DEFAULT 0,
    active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE app_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(120) NOT NULL UNIQUE,
    setting_value TEXT NULL,
    updated_by_user_id BIGINT UNSIGNED NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_settings_user FOREIGN KEY (updated_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    actor_user_id BIGINT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(100) NOT NULL,
    entity_id VARCHAR(100) NULL,
    metadata_json JSON NULL,
    ip_hash CHAR(64) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_audit_entity (entity_type, entity_id),
    KEY idx_audit_time (created_at),
    CONSTRAINT fk_audit_user FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
