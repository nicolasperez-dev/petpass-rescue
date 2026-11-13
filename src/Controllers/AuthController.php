<?php
declare(strict_types=1);

final class AuthController
{
    public function loginForm(): void { view('auth/login', ['title' => 'Entrar']); }

    public function login(): void
    {
        Security::validateCsrf();
        if (!Auth::attempt((string) $_POST['email'], (string) $_POST['password'])) {
            flash('error', 'E-mail ou senha incorretos.');
            redirect('/login');
        }
        flash('success', 'Bem-vindo ao PetPass Rescue.');
        $role = Auth::user()['role_code'];
        redirect($role === 'ADMIN' ? '/admin' : ($role === 'PARTNER' ? '/partner' : '/owner'));
    }

    public function registerForm(): void { view('auth/register', ['title' => 'Criar conta']); }

    public function register(): void
    {
        Security::validateCsrf();
        $email = strtolower(trim((string) $_POST['email']));
        $name = trim((string) $_POST['name']);
        $password = (string) $_POST['password'];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($name) < 2 || mb_strlen($password) < 8) {
            flash('error', 'Confira o nome, o e-mail e use uma senha com pelo menos 8 caracteres.');
            redirect('/register');
        }
        if (Database::fetch('SELECT id FROM users WHERE email = ?', [$email])) {
            flash('error', 'Este e-mail já está cadastrado.'); redirect('/register');
        }
        $role = Database::fetch('SELECT id FROM roles WHERE code = "OWNER"');
        $plan = Database::fetch('SELECT id FROM plans WHERE code = "FREE"');
        $userId = Database::insert(
            'INSERT INTO users (role_id, plan_id, email, password_hash, name, phone, locale, status, email_verified_at) VALUES (?, ?, ?, ?, ?, ?, ?, "ACTIVE", NOW())',
            [$role['id'], $plan['id'], $email, password_hash($password, PASSWORD_DEFAULT), $name, Security::normalizePhone($_POST['phone'] ?? ''), I18n::locale()]
        );
        Database::execute('INSERT INTO user_profiles (user_id, city, state, lgpd_consent) VALUES (?, ?, ?, ?)', [$userId, trim((string) $_POST['city']), trim((string) $_POST['state']), !empty($_POST['lgpd']) ? 1 : 0]);
        Auth::attempt($email, $password);
        flash('success', 'Sua família PetPass foi criada. Agora cadastre seu primeiro filho de quatro patas.');
        redirect('/owner/pets/create');
    }

    public function partnerRegisterForm(): void { view('auth/partner-register', ['title' => 'Cadastrar loja sócia']); }

    public function partnerRegister(): void
    {
        Security::validateCsrf();
        $email = strtolower(trim((string) $_POST['email']));
        $password = (string) $_POST['password'];
        $partnerType = strtoupper((string) $_POST['partner_type']);
        $allowed = ['VETERINARY','PETSHOP','HOTEL','RESTAURANT'];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($password) < 8 || !in_array($partnerType, $allowed, true)) {
            flash('error', 'Preencha corretamente os dados da empresa.'); redirect('/partner/register');
        }
        if (Database::fetch('SELECT id FROM users WHERE email = ?', [$email])) { flash('error', 'E-mail já cadastrado.'); redirect('/partner/register'); }
        $role = Database::fetch('SELECT id FROM roles WHERE code = "PARTNER"');
        $plan = Database::fetch('SELECT id FROM plans WHERE code = "PARTNER_BASIC"');
        $name = trim((string) $_POST['responsible_name']);
        $company = trim((string) $_POST['company_name']);
        $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', iconv('UTF-8', 'ASCII//TRANSLIT', $company) ?: $company), '-')) . '-' . substr(bin2hex(random_bytes(3)), 0, 6);
        Database::transaction(function () use ($role, $plan, $email, $password, $name, $company, $partnerType, $slug): void {
            $userId = Database::insert(
                'INSERT INTO users (role_id, plan_id, email, password_hash, name, phone, locale, status, email_verified_at) VALUES (?, ?, ?, ?, ?, ?, ?, "ACTIVE", NOW())',
                [$role['id'], $plan['id'], $email, password_hash($password, PASSWORD_DEFAULT), $name, Security::normalizePhone($_POST['phone'] ?? ''), I18n::locale()]
            );
            Database::insert(
                'INSERT INTO partners (owner_user_id, slug, partner_type, name, description, phone, whatsapp, email, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, "PENDING")',
                [$userId, $slug, $partnerType, $company, trim((string) $_POST['description']), Security::normalizePhone($_POST['phone'] ?? ''), Security::normalizePhone($_POST['whatsapp'] ?? ''), $email]
            );
        });
        Auth::attempt($email, $password);
        flash('success', 'Cadastro enviado. O administrador poderá verificar e ativar sua loja.');
        redirect('/partner');
    }

    public function logout(): void { Security::validateCsrf(); Auth::logout(); redirect('/'); }
}
