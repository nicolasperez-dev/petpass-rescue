<?php
declare(strict_types=1);

final class PublicController
{
    public function home(): void
    {
        $plans = Database::fetchAll('SELECT * FROM plans WHERE active = 1 AND audience IN ("OWNER","ALL") ORDER BY sort_order');
        $partners = Database::fetchAll(
            'SELECT p.*, l.city, l.state, l.emergency_24h, l.latitude, l.longitude
             FROM partners p LEFT JOIN partner_locations l ON l.partner_id = p.id AND l.is_primary = 1
             WHERE p.status = "ACTIVE" ORDER BY p.sponsored DESC, p.verified DESC, p.rating DESC LIMIT 8'
        );
        $lostPets = Database::fetchAll(
            'SELECT p.public_id, p.name, p.main_photo_path, lc.last_seen_city, lc.activated_at
             FROM pets p JOIN lost_cases lc ON lc.pet_id = p.id AND lc.status = "ACTIVE"
             ORDER BY lc.activated_at DESC LIMIT 6'
        );
        view('public/home', compact('plans', 'partners', 'lostPets'), 'layouts/app');
    }

    public function pet(string $publicId): void
    {
        $pet = Database::fetch(
            'SELECT p.*, s.name_pt AS species_name, b.name AS breed_name, u.name AS owner_name
             FROM pets p
             LEFT JOIN species s ON s.id = p.species_id
             LEFT JOIN breeds b ON b.id = p.breed_id
             JOIN users u ON u.id = p.owner_user_id
             WHERE p.public_id = ? AND p.status = "ACTIVE" LIMIT 1',
            [$publicId]
        );
        if (!$pet) {
            http_response_code(404);
            view('public/404', ['title' => 'Pet não encontrado']);
            return;
        }
        $contacts = Database::fetchAll('SELECT * FROM emergency_contacts WHERE pet_id = ? AND is_public = 1 ORDER BY priority', [$pet['id']]);
        $media = Database::fetchAll('SELECT * FROM pet_media WHERE pet_id = ? AND is_public = 1 ORDER BY sort_order, created_at DESC', [$pet['id']]);
        $allergies = Database::fetchAll('SELECT * FROM allergies WHERE pet_id = ? AND is_public_emergency = 1', [$pet['id']]);
        $medications = Database::fetchAll('SELECT * FROM medications WHERE pet_id = ? AND active = 1 AND is_public_emergency = 1', [$pet['id']]);
        $lostCase = Database::fetch('SELECT * FROM lost_cases WHERE pet_id = ? AND status = "ACTIVE" ORDER BY activated_at DESC LIMIT 1', [$pet['id']]);
        $ads = Database::fetchAll(
            'SELECT a.*, p.name AS partner_name FROM ad_campaigns a JOIN partners p ON p.id = a.partner_id
             WHERE a.status = "APPROVED" AND a.placement = "PUBLIC_PROFILE" AND (a.target_city IS NULL OR a.target_city = ?) LIMIT 2',
            [$pet['city']]
        );
        view('public/pet', compact('pet', 'contacts', 'media', 'allergies', 'medications', 'lostCase', 'ads'), 'layouts/public');
    }

    public function tag(string $code): void
    {
        $source = strtoupper((string) ($_GET['src'] ?? 'UNKNOWN'));
        if (!in_array($source, ['QR', 'NFC'], true)) $source = 'UNKNOWN';
        $tag = Database::fetch('SELECT * FROM physical_tags WHERE public_code = ? LIMIT 1', [$code]);
        if (!$tag || $tag['status'] === 'REVOKED') {
            http_response_code(404);
            view('public/tag-invalid', ['title' => 'Tag não encontrada'], 'layouts/public');
            return;
        }
        Database::execute(
            'INSERT INTO tag_scans (physical_tag_id, pet_id, source, user_agent, ip_hash) VALUES (?, ?, ?, ?, ?)',
            [$tag['id'], $tag['pet_id'], $source, mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255), Security::ipHash()]
        );
        if ($tag['status'] === 'UNCLAIMED' || !$tag['pet_id']) {
            redirect('/activate/' . rawurlencode($tag['public_code']));
        }
        if ($tag['status'] !== 'ACTIVE') {
            view('public/tag-invalid', ['title' => 'Tag temporariamente indisponível'], 'layouts/public');
            return;
        }
        $pet = Database::fetch('SELECT public_id FROM pets WHERE id = ? LIMIT 1', [$tag['pet_id']]);
        redirect('/pet/' . $pet['public_id'] . '?src=' . strtolower($source));
    }

    public function activate(string $code): void
    {
        $tag = Database::fetch('SELECT * FROM physical_tags WHERE public_code = ? LIMIT 1', [$code]);
        if (!$tag) {
            http_response_code(404);
            view('public/tag-invalid', ['title' => 'Tag não encontrada'], 'layouts/public');
            return;
        }
        if ($tag['status'] === 'ACTIVE' && $tag['pet_id']) {
            $pet = Database::fetch('SELECT public_id FROM pets WHERE id = ?', [$tag['pet_id']]);
            redirect('/pet/' . $pet['public_id']);
        }
        $pets = [];
        if (Auth::user() && Auth::user()['role_code'] === 'OWNER') {
            $pets = Database::fetchAll('SELECT id, public_id, name FROM pets WHERE owner_user_id = ? AND status = "ACTIVE" ORDER BY name', [Auth::id()]);
        }
        view('public/activate', compact('tag', 'pets'), 'layouts/public');
    }

    public function map(): void
    {
        $type = strtoupper(trim((string) ($_GET['type'] ?? '')));
        $city = trim((string) ($_GET['city'] ?? ''));
        $query = trim((string) ($_GET['q'] ?? ''));
        $only24h = !empty($_GET['emergency_24h']);
        $allowedTypes = ['VETERINARY','PETSHOP','HOTEL','RESTAURANT','OTHER'];
        if (!in_array($type, $allowedTypes, true)) $type = '';

        $where = ['data_status="ACTIVE"'];
        $params = [];
        if ($type !== '') { $where[]='place_type=?'; $params[]=$type; }
        if ($city !== '') { $where[]='city=?'; $params[]=$city; }
        if ($query !== '') {
            $where[]='(name LIKE ? OR subtype LIKE ? OR services_text LIKE ? OR neighborhood LIKE ?)';
            for ($i=0;$i<4;$i++) $params[]='%'.$query.'%';
        }
        if ($only24h) $where[]='emergency_24h=1';
        $places = Database::fetchAll(
            'SELECT id,slug,place_type AS partner_type,subtype,name,description,phone,whatsapp,website,source_url,address,neighborhood,city,state,postal_code,latitude,longitude,coordinate_precision,emergency_24h,pet_friendly,services_text,verified_at,0 AS is_partner,0 AS sponsored,1 AS verified,0 AS rating FROM directory_places WHERE '.implode(' AND ',$where).' ORDER BY emergency_24h DESC,city,place_type,name',
            $params
        );

        $partnerWhere=['p.status="ACTIVE"']; $partnerParams=[];
        if($type!==''){ $partnerWhere[]='p.partner_type=?'; $partnerParams[]=$type; }
        if($city!==''){ $partnerWhere[]='l.city=?'; $partnerParams[]=$city; }
        if($query!==''){ $partnerWhere[]='(p.name LIKE ? OR p.description LIKE ? OR l.neighborhood LIKE ?)'; $partnerParams[]='%'.$query.'%';$partnerParams[]='%'.$query.'%';$partnerParams[]='%'.$query.'%'; }
        if($only24h) $partnerWhere[]='l.emergency_24h=1';
        $partners=Database::fetchAll(
            'SELECT p.id,p.slug,p.partner_type,NULL AS subtype,p.name,p.description,p.phone,p.whatsapp,p.website,NULL AS source_url,l.address,l.neighborhood,l.city,l.state,l.postal_code,l.latitude,l.longitude,"EXACT" AS coordinate_precision,l.emergency_24h,1 AS pet_friendly,NULL AS services_text,CURDATE() AS verified_at,1 AS is_partner,p.sponsored,p.verified,p.rating FROM partners p JOIN partner_locations l ON l.partner_id=p.id AND l.is_primary=1 WHERE '.implode(' AND ',$partnerWhere),
            $partnerParams
        );
        $places=array_merge($partners,$places);
        usort($places,static function(array $a,array $b):int{
            $v=(int)$b['emergency_24h']<=>(int)$a['emergency_24h']; if($v!==0)return $v;
            $v=(int)$b['is_partner']<=>(int)$a['is_partner']; if($v!==0)return $v;
            $v=strcasecmp((string)$a['city'],(string)$b['city']); return $v!==0?$v:strcasecmp((string)$a['name'],(string)$b['name']);
        });
        $cities=Database::fetchAll('SELECT DISTINCT city FROM directory_places WHERE data_status="ACTIVE" UNION SELECT DISTINCT city FROM partner_locations ORDER BY city');
        view('public/map', compact('places','type','city','query','only24h','cities'));
    }

    public function partner(string $slug): void
    {
        $partner = Database::fetch(
            'SELECT p.*, l.address, l.city, l.state, l.latitude, l.longitude, l.emergency_24h
             FROM partners p LEFT JOIN partner_locations l ON l.partner_id = p.id AND l.is_primary = 1
             WHERE p.slug = ? AND p.status = "ACTIVE" LIMIT 1', [$slug]
        );
        if (!$partner) { http_response_code(404); view('public/404', ['title' => 'Loja não encontrada']); return; }
        $services = Database::fetchAll('SELECT * FROM services WHERE partner_id = ? AND active = 1 ORDER BY name', [$partner['id']]);
        $products = Database::fetchAll('SELECT * FROM products WHERE partner_id = ? AND active = 1 ORDER BY featured DESC, name LIMIT 24', [$partner['id']]);
        $offers = Database::fetchAll('SELECT * FROM offers WHERE partner_id = ? AND active = 1 AND (starts_at IS NULL OR starts_at <= NOW()) AND (ends_at IS NULL OR ends_at >= NOW())', [$partner['id']]);
        $ownerPets = (Auth::user() && Auth::user()['role_code'] === 'OWNER') ? Database::fetchAll('SELECT id,name,public_id FROM pets WHERE owner_user_id=? AND status="ACTIVE" ORDER BY name',[Auth::id()]) : [];
        $hotelUnits = $partner['partner_type'] === 'HOTEL' ? Database::fetchAll('SELECT * FROM hotel_units WHERE partner_id=? AND active=1 ORDER BY daily_price_cents,name', [$partner['id']]) : [];
        view('public/partner', compact('partner', 'services', 'products', 'offers', 'ownerPets', 'hotelUnits'));
    }

    public function reportSighting(string $publicId): void
    {
        Security::validateCsrf();
        try {
            $pet = Database::fetch('SELECT id, owner_user_id, name FROM pets WHERE public_id = ? LIMIT 1', [$publicId]);
            if (!$pet) throw new RuntimeException('Pet não encontrado.');

            $photo = null;
            if (!empty($_FILES['photo']['name']) || (isset($_FILES['photo']['error']) && (int) $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE)) {
                $photo = Security::upload($_FILES['photo'], 'sightings', ['image/jpeg','image/png','image/webp','image/gif'], 8 * 1024 * 1024)['path'];
            }

            $lostCase = Database::fetch('SELECT id FROM lost_cases WHERE pet_id = ? AND status = "ACTIVE" ORDER BY activated_at DESC LIMIT 1', [$pet['id']]);
            if (!$lostCase) {
                $lostCaseId = Database::insert(
                    'INSERT INTO lost_cases (pet_id, status, description, public_message, activated_at) VALUES (?, "ACTIVE", ?, ?, NOW())',
                    [$pet['id'], 'Avistamento informado pelo perfil público.', 'Entre em contato com a família.']
                );
                Database::execute('UPDATE pets SET is_lost = 1 WHERE id = ?', [$pet['id']]);
            } else {
                $lostCaseId = (int) $lostCase['id'];
            }
            Database::execute(
                'INSERT INTO sightings (lost_case_id, reporter_name, reporter_phone, message, latitude, longitude, photo_path, location_consent) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [$lostCaseId, trim((string) ($_POST['name'] ?? '')), Security::normalizePhone($_POST['phone'] ?? ''), trim((string) ($_POST['message'] ?? '')), ($_POST['latitude'] ?? '') ?: null, ($_POST['longitude'] ?? '') ?: null, $photo, !empty($_POST['location_consent']) ? 1 : 0]
            );
            $notificationId = Database::insert(
                'INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, "SIGHTING", ?, ?, ?)',
                [$pet['owner_user_id'], 'Novo avistamento de ' . $pet['name'], 'Uma pessoa enviou localização ou mensagem pelo perfil público.', '/owner/pets/' . $pet['id'] . '/lost']
            );
            foreach (['INTERNAL','EMAIL','WHATSAPP'] as $channel) {
                Database::execute('INSERT INTO notification_deliveries (notification_id, channel, status, sent_at) VALUES (?, ?, "SIMULATED", NOW())', [$notificationId, $channel]);
            }
            flash('success', 'Obrigado! A família recebeu o aviso. Você fez parte da rede de cuidado.');
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
        }
        redirect('/pet/' . $publicId);
    }

}
