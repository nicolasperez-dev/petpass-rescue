<?php
declare(strict_types=1);

final class AdminController
{
    public function dashboard(): void
    {
        $stats = [
            'users' => (int) Database::fetch('SELECT COUNT(*) c FROM users')['c'],
            'pets' => (int) Database::fetch('SELECT COUNT(*) c FROM pets WHERE status="ACTIVE"')['c'],
            'lost' => (int) Database::fetch('SELECT COUNT(*) c FROM lost_cases WHERE status="ACTIVE"')['c'],
            'partners' => (int) Database::fetch('SELECT COUNT(*) c FROM partners')['c'],
            'tags' => (int) Database::fetch('SELECT COUNT(*) c FROM physical_tags')['c'],
            'scans' => (int) Database::fetch('SELECT COUNT(*) c FROM tag_scans')['c'],
        ];
        $recentPets = Database::fetchAll('SELECT p.*, u.name owner_name, u.email owner_email FROM pets p JOIN users u ON u.id=p.owner_user_id ORDER BY p.created_at DESC LIMIT 8');
        $pendingPartners = Database::fetchAll('SELECT * FROM partners WHERE status="PENDING" ORDER BY created_at DESC LIMIT 8');
        $logs = Database::fetchAll('SELECT a.*, u.name actor_name FROM audit_logs a LEFT JOIN users u ON u.id=a.actor_user_id ORDER BY a.created_at DESC LIMIT 15');
        view('admin/dashboard', compact('stats','recentPets','pendingPartners','logs'), 'layouts/admin');
    }

    public function pets(): void
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        $params = [];
        $where = '1=1';
        if ($q !== '') {
            $where = '(p.public_id LIKE ? OR p.name LIKE ? OR u.email LIKE ? OR u.name LIKE ?)';
            $like = '%' . $q . '%';
            $params = [$like,$like,$like,$like];
        }
        $pets = Database::fetchAll(
            'SELECT p.*, u.name owner_name, u.email owner_email, s.name_pt species_name, b.name breed_name
             FROM pets p JOIN users u ON u.id=p.owner_user_id
             LEFT JOIN species s ON s.id=p.species_id LEFT JOIN breeds b ON b.id=p.breed_id
             WHERE ' . $where . ' ORDER BY p.created_at DESC LIMIT 150', $params
        );
        view('admin/pets', compact('pets','q'), 'layouts/admin');
    }

    public function pet(string $id): void
    {
        $pet = Database::fetch('SELECT p.*,u.name owner_name,u.email owner_email,pl.code plan_code FROM pets p JOIN users u ON u.id=p.owner_user_id LEFT JOIN plans pl ON pl.id=u.plan_id WHERE p.id=?', [(int)$id]);
        if (!$pet) { http_response_code(404); exit('Pet não encontrado'); }
        $overrides = Database::fetchAll('SELECT * FROM pet_feature_overrides WHERE pet_id=? ORDER BY feature_code', [$pet['id']]);
        $features = Database::fetchAll('SELECT DISTINCT feature_code,label FROM plan_features ORDER BY label');
        $tags = Database::fetchAll('SELECT * FROM physical_tags WHERE pet_id=? ORDER BY created_at DESC', [$pet['id']]);
        $scans = Database::fetchAll('SELECT * FROM tag_scans WHERE pet_id=? ORDER BY scanned_at DESC LIMIT 30', [$pet['id']]);
        view('admin/pet', compact('pet','overrides','features','tags','scans'), 'layouts/admin');
    }

    public function featureOverride(string $id): void
    {
        Security::validateCsrf();
        $petId = (int)$id;
        $feature = trim((string)$_POST['feature_code']);
        $enabled = !empty($_POST['enabled']) ? 1 : 0;
        Database::execute(
            'INSERT INTO pet_feature_overrides (pet_id,feature_code,enabled,reason,granted_by_user_id,expires_at)
             VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE enabled=VALUES(enabled),reason=VALUES(reason),granted_by_user_id=VALUES(granted_by_user_id),expires_at=VALUES(expires_at)',
            [$petId,$feature,$enabled,trim((string)($_POST['reason']??'')),Auth::id(),$_POST['expires_at'] ?: null]
        );
        audit('PET_FEATURE_OVERRIDE','pets',(string)$petId,['feature'=>$feature,'enabled'=>$enabled]);
        flash('success','Exceção de funcionalidade atualizada.');
        redirect('/admin/pets/'.$petId);
    }

    public function users(): void
    {
        $q = trim((string)($_GET['q']??''));
        $params=[]; $where='1=1';
        if($q!==''){ $where='(u.name LIKE ? OR u.email LIKE ?)'; $like='%'.$q.'%'; $params=[$like,$like]; }
        $users=Database::fetchAll('SELECT u.*,r.code role_code,r.name role_name,p.code plan_code,p.name plan_name FROM users u JOIN roles r ON r.id=u.role_id LEFT JOIN plans p ON p.id=u.plan_id WHERE '.$where.' ORDER BY u.created_at DESC LIMIT 200',$params);
        $plans=Database::fetchAll('SELECT * FROM plans WHERE active=1 ORDER BY audience,sort_order');
        view('admin/users',compact('users','plans','q'),'layouts/admin');
    }

    public function updateUser(string $id): void
    {
        Security::validateCsrf();
        Database::execute('UPDATE users SET status=?,plan_id=? WHERE id=?',[in_array($_POST['status']??'',['ACTIVE','SUSPENDED','PENDING'],true)?$_POST['status']:'ACTIVE',(int)$_POST['plan_id'],(int)$id]);
        audit('USER_UPDATED','users',$id);
        flash('success','Conta atualizada.'); redirect('/admin/users');
    }

    public function tags(): void
    {
        $tags=Database::fetchAll('SELECT t.*,p.name pet_name,p.public_id,u.name claimed_name FROM physical_tags t LEFT JOIN pets p ON p.id=t.pet_id LEFT JOIN users u ON u.id=t.claimed_by_user_id ORDER BY t.created_at DESC LIMIT 250');
        $newTag=$_SESSION['new_tag']??null; unset($_SESSION['new_tag']);
        view('admin/tags',compact('tags','newTag'),'layouts/admin');
    }

    public function createTag(): void
    {
        Security::validateCsrf();
        $publicCode='TAG-'.substr(Security::randomCode(10),0,14);
        $activation=(string)random_int(10000000,99999999);
        Database::execute('INSERT INTO physical_tags (public_code,activation_code_hash,label,status) VALUES (?,?,?,"UNCLAIMED")',[$publicCode,password_hash($activation,PASSWORD_DEFAULT),trim((string)($_POST['label']??'Tag física'))]);
        $_SESSION['new_tag']=['public_code'=>$publicCode,'activation_code'=>$activation,'qr_url'=>url('/tag/'.$publicCode.'?src=qr'),'nfc_url'=>url('/tag/'.$publicCode.'?src=nfc')];
        audit('TAG_CREATED','physical_tags',$publicCode);
        flash('success','Tag criada. O código secreto é exibido uma única vez.'); redirect('/admin/tags');
    }

    public function updateTag(string $id): void
    {
        Security::validateCsrf();
        $status=in_array($_POST['status']??'',['UNCLAIMED','ACTIVE','SUSPENDED','REVOKED'],true)?$_POST['status']:'SUSPENDED';
        Database::execute('UPDATE physical_tags SET status=? WHERE id=?',[$status,(int)$id]);
        audit('TAG_STATUS_UPDATED','physical_tags',$id,['status'=>$status]);
        flash('success','Status da tag atualizado.'); redirect('/admin/tags');
    }

    public function partners(): void
    {
        $partners=Database::fetchAll('SELECT p.*,u.name owner_name,u.email owner_email,l.city,l.state FROM partners p JOIN users u ON u.id=p.owner_user_id LEFT JOIN partner_locations l ON l.partner_id=p.id AND l.is_primary=1 ORDER BY p.created_at DESC');
        view('admin/partners',compact('partners'),'layouts/admin');
    }

    public function updatePartner(string $id): void
    {
        Security::validateCsrf();
        $status=in_array($_POST['status']??'',['PENDING','ACTIVE','SUSPENDED'],true)?$_POST['status']:'PENDING';
        Database::execute('UPDATE partners SET status=?,verified=?,sponsored=? WHERE id=?',[$status,!empty($_POST['verified'])?1:0,!empty($_POST['sponsored'])?1:0,(int)$id]);
        audit('PARTNER_UPDATED','partners',$id,['status'=>$status]);
        flash('success','Loja sócia atualizada.'); redirect('/admin/partners');
    }

    public function settings(): void
    {
        $settings=Database::fetchAll('SELECT * FROM app_settings ORDER BY setting_key');
        $plans=Database::fetchAll('SELECT * FROM plans ORDER BY audience,sort_order');
        view('admin/settings',compact('settings','plans'),'layouts/admin');
    }

    public function saveSetting(): void
    {
        Security::validateCsrf();
        $key=preg_replace('/[^a-z0-9_.-]/i','',(string)$_POST['setting_key']);
        Database::execute('INSERT INTO app_settings (setting_key,setting_value,updated_by_user_id) VALUES (?,?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),updated_by_user_id=VALUES(updated_by_user_id)',[$key,(string)$_POST['setting_value'],Auth::id()]);
        flash('success','Configuração salva.'); redirect('/admin/settings');
    }
}
