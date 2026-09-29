<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

//login
$routes->post('login', 'Auth\UserLoginController::login');

$routes->post('patients/register', 'Patient\PatientPublicPostController::create');

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

$routes->put('medicals/(:num)', 'Medical\MedicalPutController::do/$1', ['filter' => ['auth', 'adminOnly']]);

$routes->delete('medicals/(:num)', 'Medical\MedicalDeleteController::do/$1',  ['filter' => ['auth', 'adminOnly']]);

$routes->get('medicals', 'Medical\MedicalsGetController::search', ['filter' => ['auth', 'adminOrReception']]);

$routes->get('medicals/user/(:num)', 'Medical\MedicalGetController::findByUser/$1', ['filter' => ['auth', 'medicalViewPermission']]);


//Patients


$routes->post('patients', 'Patient\PatientPostController::create', ['filter' => ['auth', 'receptionUserCreate']]);

$routes->get('patients/(:num)', 'Patient\PatientGetController::find/$1', ['filter' => ['auth', 'patientViewPermission']]);

$routes->get('patients/user/(:num)', 'Patient\PatientGetController::findByUser/$1', ['filter' => ['auth', 'patientViewPermission']]);

$routes->put('patients/(:num)', 'Patient\PatientPutController::do/$1', ['filter' => ['auth', 'adminOrReception']]);

$routes->delete('patients/(:num)', 'Patient\PatientDeleteController::do/$1', ['filter' => ['auth', 'adminOrReception']]);

$routes->get('patients', 'Patient\PatientsGetController::search', ['filter' => ['auth', 'adminOrReception']]);



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
$routes->get('metrics/schedule-heatmap', 'Metrics\MetricsScheduleHeatmapGetController::get', ['filter' => ['auth', 'adminOrReception']]);

//roles
$routes->post('roles', 'Role\RolePostController::create', ['filter' => ['auth', 'adminOnly']]);

$routes->get('roles/(:num)', 'Role\RoleGetController::find/$1', ['filter' => ['auth', 'adminOnly']]);

$routes->put('roles/(:num)', 'Role\RolePutController::do/$1', ['filter' => ['auth', 'adminOnly']]);

$routes->delete('roles/(:num)', 'Role\RoleDeleteController::do/$1', ['filter' => ['auth', 'adminOnly']]);

$routes->get('roles', 'Role\RolesGetController::search', ['filter' => ['auth', 'adminOnly']]);


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


//verificacion de email 

$routes->get('verify-email', 'Auth\VerifyEmailGetController::verify');
$routes->post('resend-verification', 'Auth\ResendVerificationPostController::resend');

//passwords 
$routes->post('forgot-password','Auth\ForgotPasswordPostController::requestReset');
$routes->post('reset-password','Auth\ResetPasswordPostController::reset');

