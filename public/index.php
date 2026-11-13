<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

$router = new Router();

$ownerOnly = [static fn() => Auth::requireRole('OWNER')];
$adminOnly = [static fn() => Auth::requireRole('ADMIN')];
$partnerOnly = [static fn() => Auth::requireRole('PARTNER')];

$router->get('', [PublicController::class, 'home']);
$router->get('pet/{publicId}', [PublicController::class, 'pet']);
$router->get('tag/{code}', [PublicController::class, 'tag']);
$router->get('activate/{code}', [PublicController::class, 'activate']);
$router->get('map', [PublicController::class, 'map']);
$router->get('partners/{slug}', [PublicController::class, 'partner']);
$router->post('pet/{publicId}/sighting', [PublicController::class, 'reportSighting']);

$router->get('login', [AuthController::class, 'loginForm']);
$router->post('login', [AuthController::class, 'login']);
$router->get('register', [AuthController::class, 'registerForm']);
$router->post('register', [AuthController::class, 'register']);
$router->get('partner/register', [AuthController::class, 'partnerRegisterForm']);
$router->post('partner/register', [AuthController::class, 'partnerRegister']);
$router->post('logout', [AuthController::class, 'logout']);

$router->get('owner', [OwnerController::class, 'dashboard'], $ownerOnly);
$router->get('owner/pets', [OwnerController::class, 'pets'], $ownerOnly);
$router->get('owner/pets/create', [OwnerController::class, 'createPet'], $ownerOnly);
$router->get('owner/pets/{id}/edit', [OwnerController::class, 'editPet'], $ownerOnly);
$router->post('owner/pets/save', [OwnerController::class, 'savePet'], $ownerOnly);
$router->get('owner/pets/{id}/media', [OwnerController::class, 'media'], $ownerOnly);
$router->post('owner/pets/{id}/media', [OwnerController::class, 'uploadMedia'], $ownerOnly);
$router->post('owner/pets/{id}/media/{mediaId}/delete', [OwnerController::class, 'deleteMedia'], $ownerOnly);
$router->get('owner/pets/{id}/tags', [OwnerController::class, 'tags'], $ownerOnly);
$router->post('owner/pets/{id}/claim-tag', [OwnerController::class, 'claimTag'], $ownerOnly);
$router->post('owner/pets/{id}/tags/{tagId}/nfc-programmed', [OwnerController::class, 'markNfcProgrammed'], $ownerOnly);
$router->get('owner/pets/{id}/lost', [OwnerController::class, 'lost'], $ownerOnly);
$router->post('owner/pets/{id}/lost/activate', [OwnerController::class, 'activateLost'], $ownerOnly);
$router->post('owner/pets/{id}/lost/resolve', [OwnerController::class, 'resolveLost'], $ownerOnly);
$router->post('owner/services/{serviceId}/book', [OwnerController::class, 'bookService'], $ownerOnly);
$router->post('owner/hotel/{unitId}/book', [OwnerController::class, 'bookHotel'], $ownerOnly);
$router->post('owner/restaurant/{partnerId}/book', [OwnerController::class, 'bookRestaurant'], $ownerOnly);
$router->get('owner/map', [OwnerController::class, 'map'], $ownerOnly);
$router->get('owner/plans', [OwnerController::class, 'plans'], $ownerOnly);

$router->get('admin', [AdminController::class, 'dashboard'], $adminOnly);
$router->get('admin/pets', [AdminController::class, 'pets'], $adminOnly);
$router->get('admin/pets/{id}', [AdminController::class, 'pet'], $adminOnly);
$router->post('admin/pets/{id}/feature', [AdminController::class, 'featureOverride'], $adminOnly);
$router->get('admin/users', [AdminController::class, 'users'], $adminOnly);
$router->post('admin/users/{id}', [AdminController::class, 'updateUser'], $adminOnly);
$router->get('admin/tags', [AdminController::class, 'tags'], $adminOnly);
$router->post('admin/tags', [AdminController::class, 'createTag'], $adminOnly);
$router->post('admin/tags/{id}', [AdminController::class, 'updateTag'], $adminOnly);
$router->get('admin/partners', [AdminController::class, 'partners'], $adminOnly);
$router->post('admin/partners/{id}', [AdminController::class, 'updatePartner'], $adminOnly);
$router->get('admin/settings', [AdminController::class, 'settings'], $adminOnly);
$router->post('admin/settings', [AdminController::class, 'saveSetting'], $adminOnly);

$router->get('partner', [PartnerController::class, 'dashboard'], $partnerOnly);
$router->get('partner/profile', [PartnerController::class, 'profile'], $partnerOnly);
$router->post('partner/profile', [PartnerController::class, 'saveProfile'], $partnerOnly);
$router->get('partner/services', [PartnerController::class, 'services'], $partnerOnly);
$router->post('partner/services', [PartnerController::class, 'saveService'], $partnerOnly);
$router->get('partner/products', [PartnerController::class, 'products'], $partnerOnly);
$router->post('partner/products', [PartnerController::class, 'saveProduct'], $partnerOnly);
$router->post('partner/offers', [PartnerController::class, 'saveOffer'], $partnerOnly);
$router->get('partner/appointments', [PartnerController::class, 'appointments'], $partnerOnly);
$router->post('partner/appointments/{id}', [PartnerController::class, 'updateAppointment'], $partnerOnly);
$router->get('partner/veterinary', [PartnerController::class, 'veterinary'], $partnerOnly);
$router->post('partner/veterinary/records', [PartnerController::class, 'saveMedicalRecord'], $partnerOnly);
$router->get('partner/hotel', [PartnerController::class, 'hotel'], $partnerOnly);
$router->post('partner/hotel/units', [PartnerController::class, 'saveHotelUnit'], $partnerOnly);
$router->post('partner/hotel/bookings/{id}', [PartnerController::class, 'updateHotelBooking'], $partnerOnly);
$router->get('partner/restaurant', [PartnerController::class, 'restaurant'], $partnerOnly);
$router->post('partner/restaurant/reservations/{id}', [PartnerController::class, 'updateRestaurantReservation'], $partnerOnly);

$router->post('api/bot', [ApiController::class, 'bot']);
$router->get('api/breeds', [ApiController::class, 'breeds']);

try {
    $path = (string) ($_GET['route'] ?? parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
    $basePath = trim((string) config('app.base_path'), '/');
    $path = trim($path, '/');
    if ($basePath !== '' && ($path === $basePath || str_starts_with($path, $basePath . '/'))) {
        $path = trim(substr($path, strlen($basePath)), '/');
    }
    $router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $path);
} catch (PDOException $e) {
    http_response_code(503);
    $title = 'PetPass precisa ser instalado';
    $databaseError = config('app.debug') ? $e->getMessage() : 'Não foi possível conectar ao banco de dados.';
    view('public/setup', compact('databaseError'), 'layouts/public');
} catch (Throwable $e) {
    http_response_code(500);
    if (config('app.debug')) {
        echo '<pre>' . e($e->getMessage() . "\n" . $e->getTraceAsString()) . '</pre>';
    } else {
        view('public/500', ['title' => 'Algo não saiu como esperado'], 'layouts/public');
    }
}
