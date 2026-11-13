<?php
declare(strict_types=1);

final class OwnerController
{
    public function dashboard(): void
    {
        $user = Auth::user();
        $pets = Database::fetchAll(
            'SELECT p.*, s.name_pt AS species_name, b.name AS breed_name,
                (SELECT COUNT(*) FROM tag_scans ts WHERE ts.pet_id = p.id) AS scan_count,
                (SELECT COUNT(*) FROM physical_tags pt WHERE pt.pet_id = p.id AND pt.status = "ACTIVE") AS tag_count
             FROM pets p LEFT JOIN species s ON s.id = p.species_id LEFT JOIN breeds b ON b.id = p.breed_id
             WHERE p.owner_user_id = ? AND p.status = "ACTIVE" ORDER BY p.created_at DESC',
            [Auth::id()]
        );
        $notifications = Database::fetchAll('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 8', [Auth::id()]);
        $ads = Database::fetchAll(
            'SELECT a.*, p.name AS partner_name FROM ad_campaigns a JOIN partners p ON p.id = a.partner_id
             WHERE a.status = "APPROVED" AND a.placement = "OWNER_DASHBOARD"
             AND (a.starts_at IS NULL OR a.starts_at <= NOW()) AND (a.ends_at IS NULL OR a.ends_at >= NOW()) LIMIT 3'
        );
        $plans = Database::fetchAll('SELECT * FROM plans WHERE audience = "OWNER" AND active = 1 ORDER BY sort_order');
        view('owner/dashboard', compact('user', 'pets', 'notifications', 'ads', 'plans'), 'layouts/owner');
    }

    public function pets(): void
    {
        $pets = Database::fetchAll(
            'SELECT p.*, s.name_pt AS species_name, b.name AS breed_name FROM pets p
             LEFT JOIN species s ON s.id = p.species_id LEFT JOIN breeds b ON b.id = p.breed_id
             WHERE p.owner_user_id = ? AND p.status = "ACTIVE" ORDER BY p.name', [Auth::id()]
        );
        view('owner/pets', compact('pets'), 'layouts/owner');
    }

    public function createPet(): void
    {
        $species = Database::fetchAll('SELECT * FROM species WHERE active = 1 ORDER BY sort_order, name_pt');
        $breeds = Database::fetchAll('SELECT * FROM breeds WHERE active = 1 ORDER BY name');
        $pet = null; $contacts = []; $allergies = []; $medications = []; $vaccinations = [];
        view('owner/pet-form', compact('pet', 'species', 'breeds', 'contacts', 'allergies', 'medications', 'vaccinations'), 'layouts/owner');
    }

    public function editPet(string $id): void
    {
        $pet = require_owner_pet((int) $id);
        $species = Database::fetchAll('SELECT * FROM species WHERE active = 1 ORDER BY sort_order, name_pt');
        $breeds = Database::fetchAll('SELECT * FROM breeds WHERE active = 1 ORDER BY name');
        $contacts = Database::fetchAll('SELECT * FROM emergency_contacts WHERE pet_id = ? ORDER BY priority', [$pet['id']]);
        $allergies = Database::fetchAll('SELECT * FROM allergies WHERE pet_id = ? ORDER BY created_at DESC', [$pet['id']]);
        $medications = Database::fetchAll('SELECT * FROM medications WHERE pet_id = ? ORDER BY active DESC, created_at DESC', [$pet['id']]);
        $vaccinations = Database::fetchAll('SELECT * FROM vaccinations WHERE pet_id = ? ORDER BY next_dose_at DESC', [$pet['id']]);
        view('owner/pet-form', compact('pet', 'species', 'breeds', 'contacts', 'allergies', 'medications', 'vaccinations'), 'layouts/owner');
    }

    public function savePet(): void
    {
        Security::validateCsrf();
        $id = (int) ($_POST['id'] ?? 0);
        $returnPath = $id ? '/owner/pets/' . $id . '/edit' : '/owner/pets/create';

        try {
            $existing = $id ? require_owner_pet($id) : null;
            $name = trim((string) ($_POST['name'] ?? ''));
            if ($name === '') throw new RuntimeException('O nome do seu pet é obrigatório.');

            $photoPath = $existing['main_photo_path'] ?? null;
            if ($existing && !empty($_POST['remove_main_photo'])) {
                $this->deleteOwnedUpload($photoPath);
                $photoPath = null;
            }
            $mainPhotoFile = null;
            foreach (['main_photo', 'main_photo_camera'] as $photoField) {
                if (!empty($_FILES[$photoField]['name']) || (isset($_FILES[$photoField]['error']) && (int) $_FILES[$photoField]['error'] !== UPLOAD_ERR_NO_FILE)) {
                    $mainPhotoFile = $_FILES[$photoField];
                    break;
                }
            }
            if ($mainPhotoFile) {
                $uploaded = Security::upload(
                    $mainPhotoFile,
                    'pets/' . (Auth::id() ?? 'guest'),
                    ['image/jpeg','image/png','image/webp','image/gif'],
                    (int) config('app.max_upload_mb') * 1024 * 1024
                );
                if ($existing && $photoPath && $photoPath !== $uploaded['path']) $this->deleteOwnedUpload($photoPath);
                $photoPath = $uploaded['path'];
            }

            $audioPath = $existing['public_audio_path'] ?? null;
            if ($existing && !empty($_POST['remove_public_audio'])) {
                $this->deleteOwnedUpload($audioPath);
                $audioPath = null;
            }
            if (!empty($_FILES['public_audio']['name']) || (isset($_FILES['public_audio']['error']) && (int) $_FILES['public_audio']['error'] !== UPLOAD_ERR_NO_FILE)) {
                $petFeatureId = $existing ? (int) $existing['id'] : null;
                if (!user_has_feature(Auth::user(), 'AUDIO_PUBLIC', $petFeatureId)) {
                    throw new RuntimeException('A mensagem de áudio faz parte do plano Plus ou superior. O administrador também pode liberar esse recurso gratuitamente para este pet.');
                }
                $uploaded = Security::upload(
                    $_FILES['public_audio'],
                    'audio/' . (Auth::id() ?? 'guest'),
                    ['audio/mpeg','audio/mp4','audio/webm','audio/ogg','audio/wav','audio/x-wav','audio/x-m4a'],
                    15 * 1024 * 1024
                );
                if ($existing && $audioPath && $audioPath !== $uploaded['path']) $this->deleteOwnedUpload($audioPath);
                $audioPath = $uploaded['path'];
            }

            $speciesId = ($_POST['species_id'] ?? '') !== '' ? (int) $_POST['species_id'] : null;
            $breedId = ($_POST['breed_id'] ?? '') !== '' ? (int) $_POST['breed_id'] : null;
            if ($breedId) {
                $validBreed = Database::fetch('SELECT id FROM breeds WHERE id=? AND (? IS NULL OR species_id=?) AND active=1', [$breedId, $speciesId, $speciesId]);
                if (!$validBreed) $breedId = null;
            }

            $values = [
                $speciesId,
                $breedId,
                trim((string) ($_POST['custom_species'] ?? '')) ?: null,
                trim((string) ($_POST['custom_breed'] ?? '')) ?: null,
                $name,
                trim((string) ($_POST['nickname'] ?? '')) ?: null,
                in_array($_POST['sex'] ?? '', ['MALE','FEMALE','UNKNOWN'], true) ? $_POST['sex'] : 'UNKNOWN',
                !empty($_POST['birth_date']) ? $_POST['birth_date'] : null,
                trim((string) ($_POST['approximate_age'] ?? '')) ?: null,
                ($_POST['weight_kg'] ?? '') !== '' ? (float) $_POST['weight_kg'] : null,
                trim((string) ($_POST['color'] ?? '')) ?: null,
                in_array($_POST['size'] ?? '', ['TINY','SMALL','MEDIUM','LARGE','GIANT','UNKNOWN'], true) ? $_POST['size'] : 'UNKNOWN',
                trim((string) ($_POST['microchip'] ?? '')) ?: null,
                isset($_POST['neutered']) ? 1 : null,
                trim((string) ($_POST['temperament'] ?? '')) ?: null,
                trim((string) ($_POST['approach_instructions'] ?? '')) ?: null,
                trim((string) ($_POST['favorite_things'] ?? '')) ?: null,
                trim((string) ($_POST['fears'] ?? '')) ?: null,
                trim((string) ($_POST['general_notes'] ?? '')) ?: null,
                trim((string) ($_POST['city'] ?? '')) ?: null,
                trim((string) ($_POST['state'] ?? 'SC')) ?: 'SC',
                $photoPath,
                $audioPath,
                max(0, (int) round(((float) ($_POST['reward'] ?? 0)) * 100)),
            ];

            if ($existing) {
                Database::execute(
                    'UPDATE pets SET species_id=?, breed_id=?, custom_species=?, custom_breed=?, name=?, nickname=?, sex=?, birth_date=?, approximate_age=?, weight_kg=?, color=?, size=?, microchip=?, neutered=?, temperament=?, approach_instructions=?, favorite_things=?, fears=?, general_notes=?, city=?, state=?, main_photo_path=?, public_audio_path=?, reward_cents=? WHERE id=? AND owner_user_id=?',
                    [...$values, $id, Auth::id()]
                );
                $petId = $id;
                audit('PET_UPDATED', 'pets', (string) $petId);
            } else {
                do {
                    $publicId = Security::petPublicId();
                } while (Database::fetch('SELECT id FROM pets WHERE public_id=?', [$publicId]));
                $petId = Database::insert(
                    'INSERT INTO pets (public_id, owner_user_id, species_id, breed_id, custom_species, custom_breed, name, nickname, sex, birth_date, approximate_age, weight_kg, color, size, microchip, neutered, temperament, approach_instructions, favorite_things, fears, general_notes, city, state, main_photo_path, public_audio_path, reward_cents) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [$publicId, Auth::id(), ...$values]
                );
                $this->ensureDigitalTag(['id' => $petId, 'public_id' => $publicId]);
                audit('PET_CREATED', 'pets', (string) $petId);
            }

            $this->replacePrimaryContact($petId);
            $this->addHealthQuickItems($petId);
            flash('success', $existing ? 'Dados atualizados com carinho.' : 'Seu pet ganhou uma identidade PetPass única e um QR funcional.');
            redirect('/owner/pets/' . $petId . '/edit');
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            redirect($returnPath);
        }
    }

    private function deleteOwnedUpload(?string $relativePath): void
    {
        if (!$relativePath || !str_starts_with($relativePath, 'uploads/')) return;
        $root = realpath(PUBLIC_PATH . '/uploads');
        $path = realpath(PUBLIC_PATH . '/' . $relativePath);
        if ($root && $path && str_starts_with($path, $root) && is_file($path)) @unlink($path);
    }

    private function ensureDigitalTag(array $pet): array
    {
        $tag = Database::fetch(
            'SELECT * FROM physical_tags WHERE pet_id=? AND status="ACTIVE" ORDER BY (label="QR digital") DESC, created_at ASC LIMIT 1',
            [(int) $pet['id']]
        );
        if ($tag) return $tag;

        do {
            $code = 'WEB-' . substr(Security::randomCode(10), 0, 14);
        } while (Database::fetch('SELECT id FROM physical_tags WHERE public_code=?', [$code]));

        $id = Database::insert(
            'INSERT INTO physical_tags (public_code, pet_id, claimed_by_user_id, label, status, claimed_at) VALUES (?, ?, ?, "QR digital", "ACTIVE", NOW())',
            [$code, (int) $pet['id'], Auth::id()]
        );
        return Database::fetch('SELECT * FROM physical_tags WHERE id=?', [$id]);
    }

    private function replacePrimaryContact(int $petId): void
    {
        $contactName = trim((string) ($_POST['contact_name'] ?? ''));
        $contactPhone = Security::normalizePhone($_POST['contact_phone'] ?? '');
        if ($contactName === '' || $contactPhone === '') return;
        $existing = Database::fetch('SELECT id FROM emergency_contacts WHERE pet_id = ? AND priority = 1 LIMIT 1', [$petId]);
        if ($existing) {
            Database::execute('UPDATE emergency_contacts SET name=?, phone=?, whatsapp=?, relationship=?, is_public=1, receives_alerts=1 WHERE id=?', [$contactName, $contactPhone, $contactPhone, trim((string) ($_POST['contact_relationship'] ?? 'Tutor')), $existing['id']]);
        } else {
            Database::execute('INSERT INTO emergency_contacts (pet_id, name, phone, whatsapp, relationship, priority, is_public, receives_alerts) VALUES (?, ?, ?, ?, ?, 1, 1, 1)', [$petId, $contactName, $contactPhone, $contactPhone, trim((string) ($_POST['contact_relationship'] ?? 'Tutor'))]);
        }
    }

    private function addHealthQuickItems(int $petId): void
    {
        $allergy = trim((string) ($_POST['new_allergy'] ?? ''));
        if ($allergy !== '') Database::execute('INSERT INTO allergies (pet_id, allergen, severity, notes, is_public_emergency) VALUES (?, ?, ?, ?, ?)', [$petId, $allergy, $_POST['allergy_severity'] ?? 'MEDIUM', trim((string) ($_POST['allergy_notes'] ?? '')), !empty($_POST['allergy_public']) ? 1 : 0]);
        $med = trim((string) ($_POST['new_medication'] ?? ''));
        if ($med !== '') Database::execute('INSERT INTO medications (pet_id, name, dosage, frequency, starts_at, active, is_public_emergency) VALUES (?, ?, ?, ?, CURDATE(), 1, ?)', [$petId, $med, trim((string) ($_POST['medication_dosage'] ?? '')), trim((string) ($_POST['medication_frequency'] ?? '')), !empty($_POST['medication_public']) ? 1 : 0]);
        $vaccine = trim((string) ($_POST['new_vaccine'] ?? ''));
        if ($vaccine !== '') Database::execute('INSERT INTO vaccinations (pet_id, vaccine_name, applied_at, next_dose_at, veterinarian_name, status) VALUES (?, ?, ?, ?, ?, "CURRENT")', [$petId, $vaccine, $_POST['vaccine_date'] ?: null, $_POST['vaccine_next'] ?: null, trim((string) ($_POST['veterinarian_name'] ?? ''))]);
    }

    public function media(string $id): void
    {
        $pet = require_owner_pet((int) $id);
        $media = Database::fetchAll('SELECT * FROM pet_media WHERE pet_id = ? ORDER BY media_type, sort_order, created_at DESC', [$pet['id']]);
        view('owner/media', compact('pet', 'media'), 'layouts/owner');
    }

    public function uploadMedia(string $id): void
    {
        Security::validateCsrf();
        $returnPath = '/owner/pets/' . $id . '/media';
        try {
            $pet = require_owner_pet((int) $id);
            $type = strtoupper((string) ($_POST['media_type'] ?? 'PHOTO'));
            $map = [
                'PHOTO' => [['image/jpeg','image/png','image/webp','image/gif'], 'pets/' . Auth::id(), 12],
                'AUDIO' => [['audio/mpeg','audio/mp4','audio/webm','audio/ogg','audio/wav','audio/x-wav','audio/x-m4a'], 'audio/' . Auth::id(), 15],
                'VIDEO' => [['video/mp4','video/webm'], 'pets/' . Auth::id(), 40],
                'DOCUMENT' => [['application/pdf','image/jpeg','image/png'], 'documents/' . Auth::id(), 15],
            ];
            if (!isset($map[$type])) throw new RuntimeException('Tipo de arquivo inválido.');
            if (!isset($_FILES['media_file'])) {
                throw new RuntimeException('O servidor não recebeu o arquivo. Talvez ele ultrapasse o limite post_max_size do Ferozo.');
            }
            [$mimes, $folder, $mb] = $map[$type];
            $requiredFeature = match ($type) {
                'PHOTO' => 'PHOTO_UPLOAD',
                'AUDIO' => 'AUDIO_PUBLIC',
                default => 'HEALTH_RECORDS',
            };
            if (!user_has_feature(Auth::user(), $requiredFeature, $pet['id'])) {
                throw new RuntimeException('Esse recurso faz parte de um plano superior. O administrador também pode liberá-lo individualmente.');
            }
            $limit = plan_feature_limit(Auth::user(), $requiredFeature);
            if ($limit !== null) {
                $count = (int) (Database::fetch('SELECT COUNT(*) c FROM pet_media WHERE pet_id=? AND media_type=?', [$pet['id'], $type])['c'] ?? 0);
                if ($count >= $limit) throw new RuntimeException('Você atingiu o limite deste tipo de arquivo no plano atual.');
            }
            $upload = Security::upload($_FILES['media_file'], $folder, $mimes, $mb * 1024 * 1024);
            Database::execute(
                'INSERT INTO pet_media (pet_id, media_type, file_path, original_name, mime_type, file_size, caption, is_public) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [$pet['id'], $type, $upload['path'], $upload['original'], $upload['mime'], $upload['size'], trim((string) ($_POST['caption'] ?? '')), !empty($_POST['is_public']) ? 1 : 0]
            );
            if ($type === 'PHOTO' && empty($pet['main_photo_path']) && !empty($_POST['use_as_main'])) {
                Database::execute('UPDATE pets SET main_photo_path=? WHERE id=? AND owner_user_id=?', [$upload['path'], $pet['id'], Auth::id()]);
            }
            audit('PET_MEDIA_UPLOADED', 'pet_media', null, ['pet_id' => $pet['id'], 'type' => $type]);
            flash('success', 'Arquivo enviado e guardado corretamente.');
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
        }
        redirect($returnPath);
    }

    public function deleteMedia(string $id, string $mediaId): void
    {
        Security::validateCsrf();
        $pet = require_owner_pet((int) $id);
        $media = Database::fetch('SELECT * FROM pet_media WHERE id = ? AND pet_id = ?', [(int) $mediaId, $pet['id']]);
        if ($media) {
            $path = PUBLIC_PATH . '/' . $media['file_path'];
            if (is_file($path) && str_starts_with(realpath($path) ?: '', realpath(PUBLIC_PATH . '/uploads') ?: '')) @unlink($path);
            Database::execute('DELETE FROM pet_media WHERE id = ?', [$media['id']]);
        }
        flash('success', 'Arquivo removido.'); redirect('/owner/pets/' . $id . '/media');
    }

    public function tags(string $id): void
    {
        $pet = require_owner_pet((int) $id);
        $primaryTag = $this->ensureDigitalTag($pet);
        $tags = Database::fetchAll('SELECT *, (SELECT COUNT(*) FROM tag_scans WHERE physical_tag_id = physical_tags.id) AS scans FROM physical_tags WHERE pet_id = ? ORDER BY (label="QR digital") DESC, created_at DESC', [$pet['id']]);
        $recentScans = Database::fetchAll('SELECT ts.*, pt.label FROM tag_scans ts JOIN physical_tags pt ON pt.id = ts.physical_tag_id WHERE ts.pet_id = ? ORDER BY ts.scanned_at DESC LIMIT 20', [$pet['id']]);
        view('owner/tags', compact('pet', 'primaryTag', 'tags', 'recentScans'), 'layouts/owner');
    }

    public function claimTag(string $id): void
    {
        Security::validateCsrf();
        $pet = require_owner_pet((int) $id);
        $code = strtoupper(trim((string) $_POST['public_code']));
        $activation = trim((string) $_POST['activation_code']);
        $tag = Database::fetch('SELECT * FROM physical_tags WHERE public_code = ? LIMIT 1', [$code]);
        if (!$tag || $tag['status'] !== 'UNCLAIMED' || !$tag['activation_code_hash'] || !password_verify($activation, $tag['activation_code_hash'])) {
            flash('error', 'Código público ou código secreto inválido.'); redirect('/owner/pets/' . $id . '/tags');
        }
        Database::execute('UPDATE physical_tags SET pet_id=?, claimed_by_user_id=?, status="ACTIVE", activation_code_hash=NULL, claimed_at=NOW() WHERE id=? AND status="UNCLAIMED"', [$pet['id'], Auth::id(), $tag['id']]);
        audit('TAG_CLAIMED', 'physical_tags', (string) $tag['id'], ['pet_id' => $pet['id']]);
        flash('success', 'Tag vinculada. QR e NFC agora levam ao perfil de ' . $pet['name'] . '.'); redirect('/owner/pets/' . $id . '/tags');
    }

    public function markNfcProgrammed(string $id, string $tagId): void
    {
        Security::validateCsrf();
        $pet = require_owner_pet((int) $id);
        Database::execute('UPDATE physical_tags SET nfc_programmed_at=NOW() WHERE id=? AND pet_id=?', [(int) $tagId, $pet['id']]);
        flash('success', 'NFC marcado como programado e verificado.'); redirect('/owner/pets/' . $id . '/tags');
    }

    public function lost(string $id): void
    {
        $pet = require_owner_pet((int) $id);
        $case = Database::fetch('SELECT * FROM lost_cases WHERE pet_id = ? AND status = "ACTIVE" ORDER BY activated_at DESC LIMIT 1', [$pet['id']]);
        $sightings = $case ? Database::fetchAll('SELECT * FROM sightings WHERE lost_case_id = ? ORDER BY created_at DESC', [$case['id']]) : [];
        view('owner/lost', compact('pet', 'case', 'sightings'), 'layouts/owner');
    }

    public function activateLost(string $id): void
    {
        Security::validateCsrf();
        $pet = require_owner_pet((int) $id);
        $active = Database::fetch('SELECT id FROM lost_cases WHERE pet_id = ? AND status = "ACTIVE"', [$pet['id']]);
        if (!$active) {
            $caseId = Database::insert(
                'INSERT INTO lost_cases (pet_id, status, last_seen_at, last_seen_address, last_seen_city, latitude, longitude, description, public_message, radius_km, reward_cents) VALUES (?, "ACTIVE", ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$pet['id'], $_POST['last_seen_at'] ?: date('Y-m-d H:i:s'), trim((string) $_POST['last_seen_address']), trim((string) $_POST['last_seen_city']), $_POST['latitude'] ?: null, $_POST['longitude'] ?: null, trim((string) $_POST['description']), trim((string) $_POST['public_message']), (int) ($_POST['radius_km'] ?? 5), (int) round(((float) ($_POST['reward'] ?? 0)) * 100)]
            );
            Database::execute('UPDATE pets SET is_lost=1 WHERE id=?', [$pet['id']]);
            $contacts = Database::fetchAll('SELECT * FROM emergency_contacts WHERE pet_id=? AND receives_alerts=1', [$pet['id']]);
            $notificationId = Database::insert('INSERT INTO notifications (user_id,type,title,message,link) VALUES (?,"LOST_MODE",?,?,?)', [Auth::id(), 'Modo perdido ativado para ' . $pet['name'], 'O perfil público agora está em modo emergência.', '/owner/pets/' . $pet['id'] . '/lost']);
            foreach ($contacts as $contact) Database::execute('INSERT INTO notification_deliveries (notification_id,channel,recipient,status,sent_at) VALUES (?,"WHATSAPP",?,"SIMULATED",NOW())', [$notificationId, $contact['whatsapp'] ?: $contact['phone']]);
            audit('LOST_CASE_ACTIVATED', 'lost_cases', (string) $caseId);
        }
        flash('success', 'Modo perdido ativado. A rede de cuidado está pronta para ajudar.'); redirect('/owner/pets/' . $id . '/lost');
    }

    public function resolveLost(string $id): void
    {
        Security::validateCsrf();
        $pet = require_owner_pet((int) $id);
        Database::execute('UPDATE lost_cases SET status="RESOLVED", resolved_at=NOW() WHERE pet_id=? AND status="ACTIVE"', [$pet['id']]);
        Database::execute('UPDATE pets SET is_lost=0 WHERE id=?', [$pet['id']]);
        audit('LOST_CASE_RESOLVED', 'pets', (string) $pet['id']);
        flash('success', 'Que alegria! ' . $pet['name'] . ' foi marcado como encontrado.'); redirect('/owner/pets/' . $id . '/lost');
    }


    public function bookService(string $serviceId): void
    {
        Security::validateCsrf();
        $service = Database::fetch('SELECT s.*, p.status partner_status FROM services s JOIN partners p ON p.id=s.partner_id WHERE s.id=? AND s.active=1 LIMIT 1', [(int)$serviceId]);
        if (!$service || $service['partner_status'] !== 'ACTIVE') { flash('error','Serviço indisponível.'); redirect('/owner/map'); }
        $pet = require_owner_pet((int)$_POST['pet_id']);
        $scheduledAt = trim((string)($_POST['scheduled_at'] ?? ''));
        if ($scheduledAt === '') { flash('error','Escolha uma data e horário.'); redirect('/owner/map'); }
        Database::execute('INSERT INTO appointments (partner_id,service_id,pet_id,owner_user_id,scheduled_at,status,notes,price_cents) VALUES (?,?,?,?,?,"PENDING",?,?)',[$service['partner_id'],$service['id'],$pet['id'],Auth::id(),str_replace('T',' ',$scheduledAt),trim((string)($_POST['notes']??'')),$service['price_cents']]);
        $notificationId=Database::insert('INSERT INTO notifications (user_id,type,title,message,link) VALUES (?,"BOOKING",?,?,?)',[Auth::id(),'Reserva solicitada para '.$pet['name'],'A loja recebeu sua solicitação.','/owner']);
        Database::execute('INSERT INTO notification_deliveries (notification_id,channel,status,sent_at) VALUES (?,"INTERNAL","SIMULATED",NOW())',[$notificationId]);
        flash('success','Reserva solicitada. A loja poderá confirmar pelo painel de agenda.');
        redirect('/owner');
    }

    public function bookHotel(string $unitId): void
    {
        Security::validateCsrf();
        $unit = Database::fetch('SELECT hu.*,p.status partner_status FROM hotel_units hu JOIN partners p ON p.id=hu.partner_id WHERE hu.id=? AND hu.active=1 LIMIT 1', [(int) $unitId]);
        if (!$unit || $unit['partner_status'] !== 'ACTIVE') { flash('error','Hospedagem indisponível.'); redirect('/owner/map'); }
        $pet = require_owner_pet((int) ($_POST['pet_id'] ?? 0));
        $checkIn = (string) ($_POST['check_in'] ?? '');
        $checkOut = (string) ($_POST['check_out'] ?? '');
        if ($checkIn === '' || $checkOut === '' || $checkOut <= $checkIn) { flash('error','Confira as datas da hospedagem.'); redirect('/owner/map'); }
        $days = max(1, (int) ((strtotime($checkOut) - strtotime($checkIn)) / 86400));
        Database::execute('INSERT INTO hotel_bookings (hotel_unit_id,pet_id,owner_user_id,check_in,check_out,status,requirements_checked,notes,total_cents) VALUES (?,?,?,?,?,"PENDING",0,?,?)', [$unit['id'],$pet['id'],Auth::id(),$checkIn,$checkOut,trim((string)($_POST['notes']??'')),$days*(int)$unit['daily_price_cents']]);
        flash('success','Solicitação de hospedagem enviada ao hotel.');
        redirect('/owner');
    }

    public function bookRestaurant(string $partnerId): void
    {
        Security::validateCsrf();
        $partner = Database::fetch('SELECT id FROM partners WHERE id=? AND partner_type="RESTAURANT" AND status="ACTIVE"', [(int) $partnerId]);
        if (!$partner) { flash('error','Restaurante indisponível.'); redirect('/owner/map'); }
        $petId = !empty($_POST['pet_id']) ? (int) $_POST['pet_id'] : null;
        if ($petId) require_owner_pet($petId);
        $reservedAt = str_replace('T',' ',trim((string)($_POST['reserved_at']??'')));
        if ($reservedAt === '') { flash('error','Escolha uma data e horário.'); redirect('/owner/map'); }
        Database::execute('INSERT INTO restaurant_reservations (partner_id,user_id,pet_id,reserved_at,guests,pet_area_required,status,notes) VALUES (?,?,?,?,?,1,"PENDING",?)', [(int)$partnerId,Auth::id(),$petId,$reservedAt,max(1,(int)($_POST['guests']??2)),trim((string)($_POST['notes']??''))]);
        flash('success','Reserva enviada ao restaurante pet friendly.');
        redirect('/owner');
    }


    public function map(): void
    {
        $type = strtoupper(trim((string) ($_GET['type'] ?? '')));
        $city = trim((string) ($_GET['city'] ?? ''));
        $query = trim((string) ($_GET['q'] ?? ''));
        $only24h = !empty($_GET['emergency_24h']);
        $allowedTypes = ['VETERINARY','PETSHOP','HOTEL','RESTAURANT','OTHER'];
        if (!in_array($type, $allowedTypes, true)) $type = '';

        $where = ['data_status = "ACTIVE"'];
        $params = [];
        if ($type !== '') { $where[] = 'place_type = ?'; $params[] = $type; }
        if ($city !== '') { $where[] = 'city = ?'; $params[] = $city; }
        if ($query !== '') {
            $where[] = '(name LIKE ? OR subtype LIKE ? OR services_text LIKE ? OR neighborhood LIKE ?)';
            for ($i=0; $i<4; $i++) $params[] = '%' . $query . '%';
        }
        if ($only24h) $where[] = 'emergency_24h = 1';

        $places = Database::fetchAll(
            'SELECT id,slug,place_type AS partner_type,subtype,name,description,phone,whatsapp,website,source_url,address,neighborhood,city,state,postal_code,latitude,longitude,coordinate_precision,emergency_24h,pet_friendly,services_text,verified_at,0 AS is_partner,0 AS sponsored,1 AS verified,0 AS rating
             FROM directory_places WHERE ' . implode(' AND ', $where) . '
             ORDER BY emergency_24h DESC, city, place_type, name',
            $params
        );

        $partnerWhere = ['p.status="ACTIVE"'];
        $partnerParams = [];
        if ($type !== '') { $partnerWhere[] = 'p.partner_type=?'; $partnerParams[] = $type; }
        if ($city !== '') { $partnerWhere[] = 'l.city=?'; $partnerParams[] = $city; }
        if ($query !== '') { $partnerWhere[] = '(p.name LIKE ? OR p.description LIKE ? OR l.neighborhood LIKE ?)'; $partnerParams[]='%'.$query.'%'; $partnerParams[]='%'.$query.'%'; $partnerParams[]='%'.$query.'%'; }
        if ($only24h) $partnerWhere[] = 'l.emergency_24h=1';
        $partners = Database::fetchAll(
            'SELECT p.id,p.slug,p.partner_type,NULL AS subtype,p.name,p.description,p.phone,p.whatsapp,p.website,NULL AS source_url,l.address,l.neighborhood,l.city,l.state,l.postal_code,l.latitude,l.longitude,"EXACT" AS coordinate_precision,l.emergency_24h,1 AS pet_friendly,NULL AS services_text,CURDATE() AS verified_at,1 AS is_partner,p.sponsored,p.verified,p.rating
             FROM partners p JOIN partner_locations l ON l.partner_id=p.id AND l.is_primary=1 WHERE ' . implode(' AND ', $partnerWhere),
            $partnerParams
        );
        $places = array_merge($partners, $places);
        usort($places, static function(array $a, array $b): int {
            $by24h = (int) $b['emergency_24h'] <=> (int) $a['emergency_24h'];
            if ($by24h !== 0) return $by24h;
            $byPartner = (int) $b['is_partner'] <=> (int) $a['is_partner'];
            if ($byPartner !== 0) return $byPartner;
            $byCity = strcasecmp((string) $a['city'], (string) $b['city']);
            return $byCity !== 0 ? $byCity : strcasecmp((string) $a['name'], (string) $b['name']);
        });
        $cities = Database::fetchAll('SELECT DISTINCT city FROM directory_places WHERE data_status="ACTIVE" UNION SELECT DISTINCT city FROM partner_locations ORDER BY city');
        view('owner/map', compact('places', 'type', 'city', 'query', 'only24h', 'cities'), 'layouts/owner');
    }

    public function plans(): void
    {
        $plans = Database::fetchAll('SELECT * FROM plans WHERE audience="OWNER" AND active=1 ORDER BY sort_order');
        foreach ($plans as &$plan) $plan['features'] = Database::fetchAll('SELECT * FROM plan_features WHERE plan_id=? AND enabled=1 ORDER BY id', [$plan['id']]);
        unset($plan);
        view('owner/plans', compact('plans'), 'layouts/owner');
    }
}
