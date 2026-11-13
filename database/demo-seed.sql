-- Synthetic portfolio fixtures. No real contacts or clients.
SET NAMES utf8mb4;
START TRANSACTION;

INSERT INTO roles (id, code, name, description) VALUES
(1,'OWNER','Tutor','Responsável por pets'),
(2,'ADMIN','Administrador','Backend completo do sistema'),
(3,'PARTNER','Loja Sócia','Empresa parceira'),
(4,'VETERINARIAN','Veterinário','Profissional veterinário'),
(5,'RESCUER','Resgatista','Participante da rede de resgate');

INSERT INTO plans (id, code, name, audience, price_cents, description, highlighted, sort_order) VALUES
(1,'FREE','Gratuito','OWNER',0,'Identidade essencial, QR/NFC e contato de emergência',0,1),
(2,'PLUS','Plus','OWNER',1490,'Mais memória, saúde e personalização',0,2),
(3,'RESCUE','Rescue','OWNER',2990,'Alertas, rede de resgate e histórico avançado',1,3),
(4,'RESCUE_PRO','Rescue Pro','OWNER',5990,'Integração futura com GPS e colar inteligente',0,4),
(5,'FAMILY','Família','OWNER',7990,'Várias mascotas e familiares',0,5),
(6,'PARTNER_BASIC','Sócio Básico','PARTNER',0,'Perfil público e mapa',0,1),
(7,'PARTNER_PRO','Sócio Pro','PARTNER',6990,'Catálogo, agenda, promoções e métricas',1,2);

INSERT INTO plan_features (plan_id, feature_code, label, enabled, feature_limit) VALUES
(1,'PUBLIC_PROFILE','Perfil público',1,NULL),(1,'QR_NFC','QR e NFC',1,NULL),(1,'PHOTO_UPLOAD','Fotos',1,3),(1,'AUDIO_PUBLIC','Áudio público',0,0),(1,'LOST_MODE','Modo perdido básico',1,NULL),(1,'SCAN_HISTORY','Histórico de escaneos',0,0),(1,'HEALTH_RECORDS','Histórico de saúde',0,0),(1,'MULTIPLE_CONTACTS','Contatos extras',0,1),
(2,'PUBLIC_PROFILE','Perfil público',1,NULL),(2,'QR_NFC','QR e NFC',1,NULL),(2,'PHOTO_UPLOAD','Fotos',1,15),(2,'AUDIO_PUBLIC','Áudio público',1,3),(2,'LOST_MODE','Modo perdido básico',1,NULL),(2,'SCAN_HISTORY','Histórico de escaneos',1,90),(2,'HEALTH_RECORDS','Histórico de saúde',1,NULL),(2,'MULTIPLE_CONTACTS','Contatos extras',1,5),
(3,'PUBLIC_PROFILE','Perfil público',1,NULL),(3,'QR_NFC','QR e NFC',1,NULL),(3,'PHOTO_UPLOAD','Fotos',1,30),(3,'AUDIO_PUBLIC','Áudio público',1,5),(3,'LOST_MODE','Modo perdido avançado',1,NULL),(3,'SCAN_HISTORY','Histórico de escaneos',1,365),(3,'HEALTH_RECORDS','Histórico de saúde',1,NULL),(3,'MULTIPLE_CONTACTS','Contatos extras',1,10),(3,'RESCUE_NETWORK','Rede de resgate',1,NULL),
(4,'PUBLIC_PROFILE','Perfil público',1,NULL),(4,'QR_NFC','QR e NFC',1,NULL),(4,'PHOTO_UPLOAD','Fotos',1,50),(4,'AUDIO_PUBLIC','Áudio público',1,10),(4,'LOST_MODE','Modo perdido avançado',1,NULL),(4,'SCAN_HISTORY','Histórico de escaneos',1,NULL),(4,'HEALTH_RECORDS','Histórico de saúde',1,NULL),(4,'MULTIPLE_CONTACTS','Contatos extras',1,15),(4,'RESCUE_NETWORK','Rede de resgate',1,NULL),(4,'GPS_DEVICE','Collar GPS futuro',1,NULL),
(5,'PUBLIC_PROFILE','Perfil público',1,NULL),(5,'QR_NFC','QR e NFC',1,NULL),(5,'PHOTO_UPLOAD','Fotos',1,NULL),(5,'AUDIO_PUBLIC','Áudio público',1,NULL),(5,'LOST_MODE','Modo perdido avançado',1,NULL),(5,'SCAN_HISTORY','Histórico de escaneos',1,NULL),(5,'HEALTH_RECORDS','Histórico de saúde',1,NULL),(5,'MULTIPLE_CONTACTS','Contatos extras',1,NULL),(5,'RESCUE_NETWORK','Rede de resgate',1,NULL),(5,'GPS_DEVICE','Collar GPS futuro',1,NULL),
(6,'PARTNER_PROFILE','Perfil no mapa',1,NULL),(6,'PARTNER_CATALOG','Catálogo',0,0),(6,'PARTNER_AGENDA','Agenda',0,0),
(7,'PARTNER_PROFILE','Perfil no mapa',1,NULL),(7,'PARTNER_CATALOG','Catálogo',1,NULL),(7,'PARTNER_AGENDA','Agenda',1,NULL),(7,'PARTNER_ADS','Publicidade',1,NULL);

INSERT INTO species (id, code, name_pt, name_es, sort_order) VALUES
(1,'DOG','Cachorro','Perro',1),(2,'CAT','Gato','Gato',2),(3,'BIRD','Ave','Ave',3),(4,'RABBIT','Coelho','Conejo',4),(5,'OTHER','Outro','Otro',99);

INSERT INTO breeds (species_id, name) VALUES
(1,'Sem raça definida'),(1,'Border Collie'),(1,'Labrador'),(1,'Golden Retriever'),(1,'Shih-tzu'),(1,'Yorkshire'),(1,'Pinscher'),(1,'Poodle'),
(2,'Sem raça definida'),(2,'Siamês'),(2,'Persa'),(2,'Maine Coon'),(2,'Ragdoll');

INSERT INTO users (id, role_id, plan_id, email, password_hash, name, phone, locale, status, email_verified_at) VALUES
(1,1,4,'tutor@example.com','__DEMO_PASSWORD_HASH__','Demo Owner','','pt-BR','ACTIVE',NOW()),
(2,2,5,'admin@example.com','__DEMO_PASSWORD_HASH__','Demo Admin','','pt-BR','ACTIVE',NOW()),
(3,3,7,'petshop@example.com','__DEMO_PASSWORD_HASH__','Demo Store Manager','','pt-BR','ACTIVE',NOW()),
(4,3,7,'veterinaria@example.com','__DEMO_PASSWORD_HASH__','Demo Veterinarian','','pt-BR','ACTIVE',NOW()),
(5,3,7,'hotel@example.com','__DEMO_PASSWORD_HASH__','Demo Hotel Manager','','pt-BR','ACTIVE',NOW()),
(6,3,7,'restaurante@example.com','__DEMO_PASSWORD_HASH__','Demo Restaurant Manager','','pt-BR','ACTIVE',NOW());

INSERT INTO user_profiles (user_id, city, state, lgpd_consent, marketing_consent) VALUES
(1,'Demo City','SC',1,1),(2,'Demo City','SC',1,0),(3,'Demo City','SC',1,1),(4,'Demo City','SC',1,0),(5,'Demo City','SC',1,1),(6,'Demo City','SC',1,1);

INSERT INTO pets (id, public_id, owner_user_id, species_id, breed_id, name, nickname, sex, birth_date, weight_kg, color, size, microchip, neutered, temperament, approach_instructions, favorite_things, fears, city, state, reward_cents, is_lost, main_photo_path) VALUES
(1,'PET-LUNA2026',1,1,2,'Luna','Luninha','FEMALE','2022-03-14',18.40,'Preta e branca','MEDIUM','DEMO-CHIP-001',1,'Amigável e carinhosa; pode se assustar com barulho de motos.','Aproxime-se devagar, agache e chame Luna pelo nome. Não corra atrás dela.','Bola azul, praia e carinho na barriga','Motos e fogos','Demo City','SC',50000,0,'assets/img/petpass-hero.svg'),
(2,'PET-THOR2026',1,1,3,'Thor',NULL,'MALE','2021-07-08',27.20,'Caramelo','LARGE',NULL,1,'Dócil e sociável.','Ofereça água e fale com calma.','Passeios longos','Trovões','Demo City','SC',0,0,NULL);

INSERT INTO emergency_contacts (pet_id, name, phone, whatsapp, relationship, priority, is_public, receives_alerts) VALUES
(1,'Demo Owner','','','Tutora',1,1,1),
(1,'Demo Contact','','','Familiar',2,1,1);

INSERT INTO allergies (pet_id, allergen, severity, notes, is_public_emergency) VALUES
(1,'Frango','HIGH','Evitar alimentos e petiscos com proteína de frango.',1);
INSERT INTO medications (pet_id, name, dosage, frequency, starts_at, active, is_public_emergency) VALUES
(1,'Apoquel','5,4 mg','1 vez ao dia','2026-06-01',1,1);
INSERT INTO vaccinations (pet_id, vaccine_name, applied_at, next_dose_at, veterinarian_name, status) VALUES
(1,'V10','2026-02-10','2027-02-10','Demo Veterinarian','CURRENT');
INSERT INTO medical_records (pet_id, professional_user_id, record_type, title, description, record_date, next_check_at, privacy) VALUES
(1,4,'CONSULTATION','Controle dermatológico','Evolução estável. Manter cuidados e medicação conforme prescrição.','2026-06-18 14:30:00','2026-09-18 14:30:00','AUTHORIZED_VETS');

INSERT INTO physical_tags (id, public_code, activation_code_hash, pet_id, claimed_by_user_id, label, status, nfc_programmed_at, claimed_at) VALUES
(1,'TAG-LUNA-9K4P7M',NULL,1,1,'Coleira principal','ACTIVE',NOW(),NOW()),
(2,'TAG-NOVA-7F2A9Q','__DEMO_PASSWORD_HASH__',NULL,NULL,'Tag sem ativar','UNCLAIMED',NULL,NULL);

INSERT INTO tag_scans (physical_tag_id, pet_id, source, latitude, longitude, ip_hash, scanned_at) VALUES
(1,1,'QR',-27.1432000,-48.5138000,SHA2('demo-1',256),DATE_SUB(NOW(), INTERVAL 2 DAY)),
(1,1,'NFC',-27.1398000,-48.5181000,SHA2('demo-2',256),DATE_SUB(NOW(), INTERVAL 1 DAY));

INSERT INTO partners (id, owner_user_id, slug, partner_type, name, description, phone, whatsapp, email, status, verified, sponsored, rating) VALUES
(1,4,'guarda-pet-24h','VETERINARY','Demo Veterinary','Veterinária demonstrativa com agenda, pacientes, prontuário, vacinas e atendimento de emergência.','','','veterinaria@example.com','ACTIVE',1,0,4.90),
(2,3,'onda-pet-shop','PETSHOP','Demo Pet Store','Pet shop demonstrativo com produtos, fotos, vídeos, estoque e ofertas editáveis.','','','petshop@example.com','ACTIVE',1,1,4.80),
(3,5,'hotel-cauda-feliz','HOTEL','Demo Pet Hotel','Hospedagem demonstrativa conectada aos dados autorizados da mascota.','','','hotel@example.com','ACTIVE',1,0,4.70),
(4,6,'mar-e-patas-bistro','RESTAURANT','Demo Pet Cafe','Restaurante demonstrativo com área pet friendly, reservas e benefícios PetPass.','','','restaurante@example.com','ACTIVE',1,0,4.60);

INSERT INTO partner_locations (partner_id, label, address, neighborhood, city, state, postal_code, latitude, longitude, emergency_24h, is_primary) VALUES
(1,'Unidade Demo City','Demo Street 100','Demo District','Demo City','SC','00000-000',-27.0915000,-48.6126000,1,1),
(2,'Loja Demo City','Demo Street 200','Demo District','Demo City','SC','00000-000',-27.1462000,-48.5069000,0,1),
(3,'Hotel BC','Demo Street 300','Demo District','Demo City','SC','00000-000',-26.9926000,-48.6352000,0,1),
(4,'Bistrô Demo City','Demo Street 400','Demo District','Demo City','SC','00000-000',-27.1383000,-48.5146000,0,1);

INSERT INTO partner_hours (partner_id, weekday, opens_at, closes_at, open_24h, closed) VALUES
(1,1,NULL,NULL,1,0),(1,2,NULL,NULL,1,0),(1,3,NULL,NULL,1,0),(1,4,NULL,NULL,1,0),(1,5,NULL,NULL,1,0),(1,6,NULL,NULL,1,0),(1,0,NULL,NULL,1,0),
(2,1,'08:00:00','20:00:00',0,0),(3,1,'07:00:00','22:00:00',0,0),(4,1,'11:30:00','23:00:00',0,0);

INSERT INTO partner_staff (partner_id, user_id, name, role_title, license_number, permissions_json) VALUES
(1,4,'Demo Veterinarian','Médica veterinária','CRMV-SC 00000',JSON_OBJECT('medical_records',true,'vaccines',true,'appointments',true));

INSERT INTO services (id, partner_id, name, category, description, price_cents, duration_minutes) VALUES
(1,1,'Consulta veterinária','VETERINARY','Consulta clínica completa para pets cadastrados.',16000,50),
(2,2,'Banho e tosa','GROOMING','Cuidados com carinho e horário marcado.',8500,90),
(3,3,'Diária de hospedagem','HOTEL','Hospedagem com acompanhamento e atualizações.',12000,1440),
(4,4,'Reserva área pet','RESTAURANT','Mesa na área pet friendly.',0,120);

INSERT INTO products (partner_id, sku, name, category, description, price_cents, promotional_price_cents, stock, main_image_path, active, featured) VALUES
(2,'OP-001','Tag QR Turquesa','Segurança','Tag demonstrativa resistente com acesso ao perfil PetPass.',4990,3990,30,'assets/img/favicon.svg',1,1),
(2,'OP-002','Peitoral Refletivo','Acessórios','Peitoral confortável para passeios seguros.',8990,NULL,14,'assets/img/favicon.svg',1,0),
(2,'OP-003','Kit Viagem Pet','Viagem','Kit demonstrativo para viagens com seu filho de quatro patas.',14990,12990,8,'assets/img/favicon.svg',1,1);

INSERT INTO offers (partner_id, title, description, discount_percent, coupon_code, starts_at, ends_at) VALUES
(2,'Boas-vindas PetPass','10% de desconto na primeira compra demonstrativa.',10,'PETPASS10',NOW(),DATE_ADD(NOW(), INTERVAL 60 DAY));

INSERT INTO hotel_units (partner_id, name, capacity, pet_size, daily_price_cents, description) VALUES
(3,'Suíte Carinho P/M',1,'SMALL_MEDIUM',12000,'Suíte individual com atualizações para a família.'),
(3,'Suíte Família G',2,'LARGE',19000,'Espaço amplo para pets maiores ou irmãos.');

INSERT INTO ad_campaigns (partner_id, title, description, target_url, placement, target_city, status, starts_at, ends_at) VALUES
(2,'Proteção também é carinho','Conheça a Tag QR Turquesa e mantenha os dados de quem você ama sempre acessíveis.','/partners/onda-pet-shop','OWNER_DASHBOARD','Demo City','APPROVED',NOW(),DATE_ADD(NOW(), INTERVAL 90 DAY));

INSERT INTO bot_faqs (language, keywords, question, answer, sort_order) VALUES
('pt-BR','cadastrar pet mascota registrar','Como cadastrar uma mascota?','Entre em Meus pets e toque em Adicionar pet. Somente o nome é obrigatório; os demais dados podem ser completados aos poucos.',1),
('pt-BR','qr nfc ativar tag chapita','Como ativar meu QR ou NFC?','Abra a seção QR e NFC do pet, escaneie a tag ainda não ativada, informe o código secreto e confirme a mascota. Depois disso QR e NFC abrirão sempre o mesmo perfil.',2),
('pt-BR','perdido modo alerta','Como ativar o modo perdido?','No painel do pet, abra Modo perdido, informe a última localização e confirme. O perfil público muda para emergência e os contatos recebem alertas internos simulados.',3),
('pt-BR','plano gratis premium pagar','O que é grátis e o que é premium?','O plano Gratuito mantém identificação, QR/NFC, contato e modo encontrado. Áudios, histórico completo, rede de resgate e GPS aparecem nos planos pagos.',4),
('es','registrar mascota','¿Cómo registro una mascota?','Entrá en Mis mascotas y elegí Agregar mascota. Solo el nombre es obligatorio y podés completar el resto después.',1);


INSERT INTO appointments (id, partner_id, service_id, pet_id, owner_user_id, staff_id, scheduled_at, status, notes, price_cents) VALUES
(1,1,1,1,1,1,DATE_ADD(NOW(), INTERVAL 2 DAY),'CONFIRMED','Controle dermatológico',16000),
(2,2,2,2,1,NULL,DATE_ADD(NOW(), INTERVAL 4 DAY),'PENDING','Banho hipoalergênico',8500);

INSERT INTO hotel_bookings (hotel_unit_id, pet_id, owner_user_id, check_in, check_out, status, requirements_checked, notes, total_cents) VALUES
(1,1,1,DATE_ADD(CURDATE(),INTERVAL 15 DAY),DATE_ADD(CURDATE(),INTERVAL 18 DAY),'CONFIRMED',1,'Enviar foto diária para a família.',36000);

INSERT INTO restaurant_reservations (partner_id,user_id,pet_id,reserved_at,guests,pet_area_required,status,notes) VALUES
(4,1,1,DATE_ADD(NOW(),INTERVAL 7 DAY),2,1,'CONFIRMED','Mesa tranquila, longe do som alto.');

INSERT INTO lost_cases (pet_id,status,last_seen_at,last_seen_address,last_seen_city,latitude,longitude,description,public_message,radius_km,reward_cents,activated_at,resolved_at) VALUES
(1,'RESOLVED',DATE_SUB(NOW(),INTERVAL 60 DAY),'Demo Park','Demo City',-27.1759000,-48.4982000,'Caso demonstrativo resolvido em 42 minutos.','Aproxime-se devagar e ligue para a família.',5,50000,DATE_SUB(NOW(),INTERVAL 60 DAY),DATE_ADD(DATE_SUB(NOW(), INTERVAL 60 DAY), INTERVAL 42 MINUTE));


INSERT INTO directory_places (slug,place_type,name,description,address,city,state,latitude,longitude,coordinate_precision,emergency_24h,pet_friendly,services_text,data_status) VALUES
('demo-clinic','VETERINARY','Demo Clinic','Fictional portfolio fixture. Not an actual emergency service.','Demo Street 100','Demo City','SC',-27.10,-48.60,'APPROXIMATE',1,1,'Demo veterinary services','ACTIVE'),
('demo-store','PETSHOP','Demo Pet Store','Fictional portfolio fixture.','Demo Street 200','Demo City','SC',-27.11,-48.61,'APPROXIMATE',0,1,'Demo pet supplies','ACTIVE');

INSERT INTO app_settings (setting_key, setting_value) VALUES
('brand_values','Cuidado, Amor, Carinho, Proteção, Felicidade, Presença, Confiança'),
('support_whatsapp',''),
('default_country','BR'),
('allow_partner_registration','1');

COMMIT;
