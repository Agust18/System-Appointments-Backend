<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->post('login', 'Auth\UserLoginController::login');

$publicRead = ['filter' => ['cors', 'throttle:60,60']];
$publicBooking = ['filter' => ['cors', 'throttle:5,60']];

$routes->group('public', static function ($routes) use ($publicRead, $publicBooking) {
    $routes->options('(:any)', static function () {
    }, ['filter' => 'cors']);

    $routes->get('specialties', 'Specialty\PublicSpecialtiesGetController::search', $publicRead);
    $routes->get('services', 'Service\PublicServicesGetController::search', $publicRead);
    $routes->get('medicals/available', 'Medical\PublicMedicalsGetController::search', $publicRead);
    $routes->get('medicals/(:num)/services', 'MedicalService\PublicMedicalServicesGetController::findByMedical/$1', $publicRead);
    $routes->get('appointments/availability', 'Appointment\PublicAppointmentAvailabilityGetController::getAvailability', $publicRead);
    $routes->post('appointments', 'Appointment\PublicAppointmentPostController::create', $publicBooking);
});


$routes->group('tenant', static function ($routes) use ($publicRead) {
    $routes->options('(:any)', static function () {
    }, ['filter' => 'cors']);

    $routes->get('config', 'Tenant\TenantConfigGetController::find', $publicRead);
});


$platformLogin = ['filter' => ['cors:platform', 'throttle:10,60']];
$platformPrivate = ['filter' => ['cors:platform', 'platformAdminOnly']];

$routes->group('platform', static function ($routes) use ($platformLogin, $platformPrivate) {
    $routes->options('(:any)', static function () {
    }, ['filter' => 'cors:platform']);

    $routes->post('login', 'Platform\PlatformLoginController::login', $platformLogin);

    $routes->get('tenants', 'Platform\TenantsGetController::search', $platformPrivate);
    $routes->post('tenants', 'Platform\TenantPostController::create', $platformPrivate);
    $routes->get('tenants/(:num)', 'Platform\TenantGetController::find/$1', $platformPrivate);
    $routes->put('tenants/(:num)', 'Platform\TenantPutController::do/$1', $platformPrivate);

    // Configuración de cada Tenant (emails, URL del frontend). Solo la edita el panel: no hay PUT /tenant/config.
    $routes->get('tenants/(:num)/settings', 'Platform\TenantSettingsGetController::find/$1', $platformPrivate);
    $routes->put('tenants/(:num)/settings', 'Platform\TenantSettingsPutController::do/$1', $platformPrivate);
});





//Users
$routes->post("users", 'User\UserPostController::create', ['filter' => ['auth', 'adminOnly']]);
$routes->get("users/(:num)", 'User\UserGetController::find/$1', ['filter' => ['auth', 'adminOnly']]);
$routes->put("users/(:num)", "User\UserPutController::do/$1", ['filter' => ['auth', 'adminOnly']]);
$routes->delete("users/(:num)", "User\UserDeleteController::do/$1", ['filter' => ['auth', 'adminOnly']]);
$routes->get("users", "User\UsersGetController::search", ['filter' => ['auth', 'adminOnly']]);

//Medicals
$routes->post('medicals', 'Medical\MedicalPostController::create',  ['filter' => ['auth', 'adminOrReception']]);
$routes->get('medicals/available', 'Medical\MedicalAvailableGetController::search', ['filter' => 'auth']);
$routes->get('medicals/(:num)', 'Medical\MedicalGetController::find/$1',  ['filter' => ['auth', 'medicalViewPermission']]);
$routes->put('medicals/(:num)', 'Medical\MedicalPutController::do/$1', ['filter' => ['auth', 'adminOrReception']]);
$routes->delete('medicals/(:num)', 'Medical\MedicalDeleteController::do/$1',  ['filter' => ['auth', 'adminOnly']]);
$routes->get('medicals', 'Medical\MedicalsGetController::search', ['filter' => ['auth', 'adminOrReception']]);
$routes->get('medicals/user/(:num)', 'Medical\MedicalGetController::findByUser/$1', ['filter' => ['auth', 'medicalViewPermission']]);

//Patients
$routes->post('patients', 'Patient\PatientPostController::create', ['filter' => ['auth', 'adminOrReception']]);
$routes->get('patients/(:num)', 'Patient\PatientGetController::find/$1', ['filter' => ['auth', 'patientViewPermission']]);
$routes->put('patients/(:num)', 'Patient\PatientPutController::do/$1', ['filter' => ['auth', 'adminOrReception']]);
$routes->delete('patients/(:num)', 'Patient\PatientDeleteController::do/$1', ['filter' => ['auth', 'adminOrReception']]);
$routes->get('patients', 'Patient\PatientsGetController::search', ['filter' => ['auth', 'adminReceptionOrMedical']]);

//Specialty
$routes->post('specialties', 'Specialty\SpecialtyPostController::create', ['filter' => ['auth', 'adminOnly']]);
$routes->get('specialties/(:num)', 'Specialty\SpecialtyGetController::find/$1', ['filter' => 'auth']);
$routes->put('specialties/(:num)', 'Specialty\SpecialtyPutController::do/$1', ['filter' => ['auth', 'adminOnly']]);
$routes->delete('specialties/(:num)', 'Specialty\SpecialtyDeleteController::do/$1', ['filter' => ['auth', 'adminOnly']]);
$routes->get('specialties', 'Specialty\SpecialtiesGetController::search', ['filter' => 'auth']);

//appointment
$routes->post('appointments', 'Appointment\AppointmentPostController::create', ['filter' => ['auth', 'appointmentCreatePermission']]);
$routes->get('appointments/availability', 'Appointment\AppointmentAvailabilityGetController::getAvailability', ['filter' => 'auth']);
$routes->get('appointments/(:num)', 'Appointment\AppointmentGetController::find/$1', ['filter' => ['auth', 'appointmentViewPermission']]);
$routes->put('appointments/(:num)', 'Appointment\AppointmentPutController::do/$1', ['filter' => ['auth', 'appointmentPermission']]);
$routes->delete('appointments/(:num)', 'Appointment\AppointmentDeleteController::do/$1', ['filter' => ['auth', 'appointmentPermission']]);
$routes->get('appointments', 'Appointment\AppointmentsGetController::search', ['filter' => 'auth']);

//stats
$routes->get('appointments/stats', 'Appointment\AppointmentStatsGetController::get', ['filter' => 'auth']);
//metrics
$routes->get('metrics/kpis', 'Metrics\MetricsKpisGetController::get', ['filter' => ['auth', 'adminOrReception']]);
$routes->get('metrics/by-medical', 'Metrics\MetricsByMedicalGetController::get', ['filter' => ['auth', 'adminOrReception']]);
$routes->get('metrics/monthly-trend', 'Metrics\MetricsMonthlyTrendGetController::get', ['filter' => ['auth', 'adminOrReception']]);
$routes->get('metrics/top-services', 'Metrics\MetricsTopServicesGetController::get', ['filter' => ['auth', 'adminOrReception']]);
$routes->get('metrics/top-specialties', 'Metrics\MetricsTopSpecialtiesGetController::get', ['filter' => ['auth', 'adminOrReception']]);
$routes->get('metrics/schedule-heatmap', 'Metrics\MetricsScheduleHeatmapGetController::get', ['filter' => ['auth', 'adminOrReception']]);
$routes->get('metrics/recent-appointments', 'Metrics\MetricsRecentAppointmentsGetController::get', ['filter' => ['auth', 'adminOrReception']]);


// Medical Schedules
$routes->post('medical-schedules', 'MedicalSchedule\MedicalSchedulePostController::create', ['filter' => ['auth', 'adminOrReception']]);
$routes->get('medical-schedules/medical/(:num)', 'MedicalSchedule\MedicalScheduleGetController::findByMedical/$1', ['filter' => 'auth']);
$routes->get('medical-schedules/(:num)', 'MedicalSchedule\MedicalScheduleGetController::find/$1', ['filter' => 'auth']);
$routes->put('medical-schedules/(:num)', 'MedicalSchedule\MedicalSchedulePutController::do/$1', ['filter' => ['auth', 'adminOrReception']]);
$routes->delete('medical-schedules/(:num)', 'MedicalSchedule\MedicalScheduleDeleteController::do/$1', ['filter' => ['auth', 'adminOrReception']]);
$routes->get('medical-schedules', 'MedicalSchedule\MedicalSchedulesGetController::search', ['filter' => 'auth']);

// Services
$routes->get('services', 'Service\ServicesGetController::search', ['filter' => 'auth']);
$routes->get('services/(:num)', 'Service\ServiceGetController::find/$1', ['filter' => 'auth']);
$routes->post('services', 'Service\ServicePostController::create', ['filter' => ['auth', 'adminOnly']]);
$routes->put('services/(:num)', 'Service\ServicePutController::do/$1', ['filter' => ['auth', 'adminOnly']]);
$routes->delete('services/(:num)', 'Service\ServiceDeleteController::do/$1', ['filter' => ['auth', 'adminOnly']]);

// Medical Services
$routes->get('medicals/(:num)/services', 'MedicalService\MedicalServiceGetController::findByMedical/$1', ['filter' => 'auth']);
$routes->post('medicals/(:num)/services', 'MedicalService\MedicalServicePostController::assignService/$1', ['filter' => ['auth', 'adminOrReception']]);
$routes->delete('medicals/(:num)/services/(:num)', 'MedicalService\MedicalServiceDeleteController::do/$1/$2', ['filter' => ['auth', 'adminOrReception']]);

//passwords 
$routes->post('forgot-password','Auth\ForgotPasswordPostController::requestReset');
$routes->post('reset-password','Auth\ResetPasswordPostController::reset');

