<?php
declare(strict_types=1);

final class PartnerController
{
    private function partner(): array
    {
        $partner=Database::fetch('SELECT * FROM partners WHERE owner_user_id=? LIMIT 1',[Auth::id()]);
        if(!$partner){ http_response_code(404); exit('Nenhuma loja sócia vinculada.'); }
        return $partner;
    }

    public function dashboard(): void
    {
        $partner=$this->partner();
        $stats=[
            'services'=>(int)Database::fetch('SELECT COUNT(*) c FROM services WHERE partner_id=?',[$partner['id']])['c'],
            'products'=>(int)Database::fetch('SELECT COUNT(*) c FROM products WHERE partner_id=?',[$partner['id']])['c'],
            'appointments'=>(int)Database::fetch('SELECT COUNT(*) c FROM appointments WHERE partner_id=? AND scheduled_at>=CURDATE()',[$partner['id']])['c'],
        ];
        $appointments=Database::fetchAll('SELECT a.*,p.name pet_name,u.name owner_name,s.name service_name FROM appointments a JOIN pets p ON p.id=a.pet_id JOIN users u ON u.id=a.owner_user_id LEFT JOIN services s ON s.id=a.service_id WHERE a.partner_id=? ORDER BY a.scheduled_at DESC LIMIT 12',[$partner['id']]);
        view('partner/dashboard',compact('partner','stats','appointments'),'layouts/partner');
    }

    public function profile(): void
    {
        $partner=$this->partner();
        $location=Database::fetch('SELECT * FROM partner_locations WHERE partner_id=? AND is_primary=1 LIMIT 1',[$partner['id']]);
        view('partner/profile',compact('partner','location'),'layouts/partner');
    }

    public function saveProfile(): void
    {
        Security::validateCsrf(); $partner=$this->partner();
        $logo=$partner['logo_path']; $cover=$partner['cover_path'];
        if(!empty($_FILES['logo']['name'])) $logo=Security::upload($_FILES['logo'],'partners',['image/jpeg','image/png','image/webp'],8*1024*1024)['path'];
        if(!empty($_FILES['cover']['name'])) $cover=Security::upload($_FILES['cover'],'partners',['image/jpeg','image/png','image/webp'],12*1024*1024)['path'];
        Database::execute('UPDATE partners SET name=?,description=?,phone=?,whatsapp=?,website=?,logo_path=?,cover_path=? WHERE id=?',[trim((string)$_POST['name']),trim((string)$_POST['description']),Security::normalizePhone($_POST['phone']??''),Security::normalizePhone($_POST['whatsapp']??''),trim((string)($_POST['website']??'')),$logo,$cover,$partner['id']]);
        $loc=Database::fetch('SELECT id FROM partner_locations WHERE partner_id=? AND is_primary=1',[$partner['id']]);
        $data=[trim((string)$_POST['address']),trim((string)($_POST['neighborhood']??'')),trim((string)$_POST['city']),trim((string)$_POST['state']),trim((string)($_POST['postal_code']??'')),$_POST['latitude']?:null,$_POST['longitude']?:null,!empty($_POST['emergency_24h'])?1:0];
        if($loc) Database::execute('UPDATE partner_locations SET address=?,neighborhood=?,city=?,state=?,postal_code=?,latitude=?,longitude=?,emergency_24h=? WHERE id=?',[...$data,$loc['id']]);
        else Database::execute('INSERT INTO partner_locations (partner_id,address,neighborhood,city,state,postal_code,latitude,longitude,emergency_24h,is_primary) VALUES (?,?,?,?,?,?,?,?,?,1)',[$partner['id'],...$data]);
        flash('success','Perfil da loja atualizado.'); redirect('/partner/profile');
    }

    public function services(): void
    {
        $partner=$this->partner();
        $services=Database::fetchAll('SELECT * FROM services WHERE partner_id=? ORDER BY active DESC,name',[$partner['id']]);
        view('partner/services',compact('partner','services'),'layouts/partner');
    }

    public function saveService(): void
    {
        Security::validateCsrf(); $partner=$this->partner(); $id=(int)($_POST['id']??0);
        $params=[trim((string)$_POST['name']),trim((string)$_POST['category']),trim((string)($_POST['description']??'')),(int)round(((float)($_POST['price']??0))*100),(int)($_POST['duration_minutes']??0),!empty($_POST['active'])?1:0];
        if($id) Database::execute('UPDATE services SET name=?,category=?,description=?,price_cents=?,duration_minutes=?,active=? WHERE id=? AND partner_id=?',[...$params,$id,$partner['id']]);
        else Database::execute('INSERT INTO services (partner_id,name,category,description,price_cents,duration_minutes,active) VALUES (?,?,?,?,?,?,?)',[$partner['id'],...$params]);
        flash('success','Serviço salvo.'); redirect('/partner/services');
    }

    public function products(): void
    {
        $partner=$this->partner();
        $products=Database::fetchAll('SELECT * FROM products WHERE partner_id=? ORDER BY active DESC,featured DESC,name',[$partner['id']]);
        $offers=Database::fetchAll('SELECT * FROM offers WHERE partner_id=? ORDER BY created_at DESC',[$partner['id']]);
        view('partner/products',compact('partner','products','offers'),'layouts/partner');
    }

    public function saveProduct(): void
    {
        Security::validateCsrf(); $partner=$this->partner();
        if($partner['partner_type']!=='PETSHOP'){ flash('error','Catálogo de produtos é exclusivo para pet shops.'); redirect('/partner'); }
        $id=(int)($_POST['id']??0); $image=null;
        if($id){ $existing=Database::fetch('SELECT * FROM products WHERE id=? AND partner_id=?',[$id,$partner['id']]); $image=$existing['main_image_path']??null; }
        if(!empty($_FILES['image']['name'])) $image=Security::upload($_FILES['image'],'partners',['image/jpeg','image/png','image/webp'],10*1024*1024)['path'];
        $params=[trim((string)$_POST['sku'])?:null,trim((string)$_POST['name']),trim((string)$_POST['category']),trim((string)$_POST['description']),(int)round(((float)$_POST['price'])*100),$_POST['promotional_price']!==''?(int)round(((float)$_POST['promotional_price'])*100):null,(int)$_POST['stock'],$image,!empty($_POST['active'])?1:0,!empty($_POST['featured'])?1:0];
        if($id) Database::execute('UPDATE products SET sku=?,name=?,category=?,description=?,price_cents=?,promotional_price_cents=?,stock=?,main_image_path=?,active=?,featured=? WHERE id=? AND partner_id=?',[...$params,$id,$partner['id']]);
        else Database::execute('INSERT INTO products (partner_id,sku,name,category,description,price_cents,promotional_price_cents,stock,main_image_path,active,featured) VALUES (?,?,?,?,?,?,?,?,?,?,?)',[$partner['id'],...$params]);
        flash('success','Produto salvo no catálogo.'); redirect('/partner/products');
    }

    public function saveOffer(): void
    {
        Security::validateCsrf();
        $partner = $this->partner();
        $title = trim((string) ($_POST['title'] ?? ''));
        if ($title === '') { flash('error','Informe um título para a oferta.'); redirect('/partner/products'); }
        $discount = $_POST['discount_percent'] !== '' ? max(0,min(100,(int)$_POST['discount_percent'])) : null;
        Database::execute(
            'INSERT INTO offers (partner_id,title,description,discount_percent,coupon_code,starts_at,ends_at,active) VALUES (?,?,?,?,?,?,?,?)',
            [$partner['id'],$title,trim((string)($_POST['description']??'')),$discount,strtoupper(trim((string)($_POST['coupon_code']??'')))?:null,$_POST['starts_at']?:null,$_POST['ends_at']?:null,!empty($_POST['active'])?1:0]
        );
        flash('success','Oferta publicada e conectada ao perfil público da loja.');
        redirect('/partner/products');
    }

    public function appointments(): void
    {
        $partner=$this->partner();
        $appointments=Database::fetchAll('SELECT a.*,p.name pet_name,p.public_id,u.name owner_name,u.phone owner_phone,s.name service_name FROM appointments a JOIN pets p ON p.id=a.pet_id JOIN users u ON u.id=a.owner_user_id LEFT JOIN services s ON s.id=a.service_id WHERE a.partner_id=? ORDER BY a.scheduled_at DESC',[$partner['id']]);
        view('partner/appointments',compact('partner','appointments'),'layouts/partner');
    }

    public function updateAppointment(string $id): void
    {
        Security::validateCsrf(); $partner=$this->partner();
        $status=in_array($_POST['status']??'',['PENDING','CONFIRMED','IN_PROGRESS','COMPLETED','CANCELLED'],true)?$_POST['status']:'PENDING';
        Database::execute('UPDATE appointments SET status=?,notes=? WHERE id=? AND partner_id=?',[$status,trim((string)($_POST['notes']??'')),(int)$id,$partner['id']]);
        flash('success','Atendimento atualizado.'); redirect('/partner/appointments');
    }

    public function veterinary(): void
    {
        $partner=$this->partner();
        if($partner['partner_type']!=='VETERINARY'){ http_response_code(403); exit('Área exclusiva de veterinárias.'); }
        $patients=Database::fetchAll('SELECT DISTINCT p.*,u.name owner_name,u.phone owner_phone FROM appointments a JOIN pets p ON p.id=a.pet_id JOIN users u ON u.id=p.owner_user_id WHERE a.partner_id=? ORDER BY p.name',[$partner['id']]);
        $records=Database::fetchAll('SELECT mr.*,p.name pet_name FROM medical_records mr JOIN pets p ON p.id=mr.pet_id WHERE mr.partner_id=? ORDER BY mr.record_date DESC LIMIT 50',[$partner['id']]);
        view('partner/veterinary',compact('partner','patients','records'),'layouts/partner');
    }

    public function saveMedicalRecord(): void
    {
        Security::validateCsrf(); $partner=$this->partner();
        if($partner['partner_type']!=='VETERINARY') exit('Não autorizado');
        $pet=Database::fetch('SELECT id FROM pets WHERE public_id=?',[trim((string)$_POST['pet_public_id'])]);
        if(!$pet){ flash('error','ID do pet não encontrado.'); redirect('/partner/veterinary'); }
        Database::execute('INSERT INTO medical_records (pet_id,partner_id,professional_user_id,record_type,title,description,record_date,next_check_at,privacy) VALUES (?,?,?,?,?,?,?,?,?)',[$pet['id'],$partner['id'],Auth::id(),$_POST['record_type'],trim((string)$_POST['title']),trim((string)$_POST['description']),$_POST['record_date']?:date('Y-m-d H:i:s'),$_POST['next_check_at']?:null,$_POST['privacy']]);
        flash('success','Registro clínico adicionado ao histórico autorizado.'); redirect('/partner/veterinary');
    }

    public function hotel(): void
    {
        $partner=$this->partner(); if($partner['partner_type']!=='HOTEL'){http_response_code(403);exit('Área exclusiva de hotéis.');}
        $units=Database::fetchAll('SELECT * FROM hotel_units WHERE partner_id=? ORDER BY active DESC,name',[$partner['id']]);
        $bookings=Database::fetchAll('SELECT hb.*,hu.name unit_name,p.name pet_name,u.name owner_name FROM hotel_bookings hb JOIN hotel_units hu ON hu.id=hb.hotel_unit_id JOIN pets p ON p.id=hb.pet_id JOIN users u ON u.id=hb.owner_user_id WHERE hu.partner_id=? ORDER BY hb.check_in DESC',[$partner['id']]);
        view('partner/hotel',compact('partner','units','bookings'),'layouts/partner');
    }

    public function saveHotelUnit(): void
    {
        Security::validateCsrf(); $partner=$this->partner(); if($partner['partner_type']!=='HOTEL') exit('Não autorizado');
        Database::execute('INSERT INTO hotel_units (partner_id,name,capacity,pet_size,daily_price_cents,description,active) VALUES (?,?,?,?,?,?,1)',[$partner['id'],trim((string)$_POST['name']),(int)$_POST['capacity'],trim((string)$_POST['pet_size']),(int)round(((float)$_POST['daily_price'])*100),trim((string)$_POST['description'])]);
        flash('success','Espaço de hospedagem criado.'); redirect('/partner/hotel');
    }

    public function updateHotelBooking(string $id): void
    {
        Security::validateCsrf();
        $partner = $this->partner();
        if ($partner['partner_type'] !== 'HOTEL') { http_response_code(403); exit('Não autorizado'); }
        $status = in_array($_POST['status'] ?? '', ['PENDING','CONFIRMED','CHECKED_IN','COMPLETED','CANCELLED'], true) ? $_POST['status'] : 'PENDING';
        Database::execute('UPDATE hotel_bookings hb JOIN hotel_units hu ON hu.id=hb.hotel_unit_id SET hb.status=?,hb.requirements_checked=? WHERE hb.id=? AND hu.partner_id=?', [$status,!empty($_POST['requirements_checked'])?1:0,(int)$id,$partner['id']]);
        flash('success','Hospedagem atualizada.');
        redirect('/partner/hotel');
    }

    public function restaurant(): void
    {
        $partner=$this->partner(); if($partner['partner_type']!=='RESTAURANT'){http_response_code(403);exit('Área exclusiva de restaurantes.');}
        $reservations=Database::fetchAll('SELECT rr.*,u.name user_name,p.name pet_name FROM restaurant_reservations rr JOIN users u ON u.id=rr.user_id LEFT JOIN pets p ON p.id=rr.pet_id WHERE rr.partner_id=? ORDER BY rr.reserved_at DESC',[$partner['id']]);
        view('partner/restaurant',compact('partner','reservations'),'layouts/partner');
    }

    public function updateRestaurantReservation(string $id): void
    {
        Security::validateCsrf();
        $partner = $this->partner();
        if ($partner['partner_type'] !== 'RESTAURANT') { http_response_code(403); exit('Não autorizado'); }
        $status = in_array($_POST['status'] ?? '', ['PENDING','CONFIRMED','COMPLETED','CANCELLED'], true) ? $_POST['status'] : 'PENDING';
        Database::execute('UPDATE restaurant_reservations SET status=? WHERE id=? AND partner_id=?', [$status,(int)$id,$partner['id']]);
        flash('success','Reserva atualizada.');
        redirect('/partner/restaurant');
    }
}
