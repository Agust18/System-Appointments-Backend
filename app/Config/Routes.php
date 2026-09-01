<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

//login
$routes->post('login', 'User\UserLoginController::login');

//quick
$routes->post('patients/quick-create', 'Patient\PatientQuickPostController::create', ['filter' => ['auth', 'receptionUserCreate']]);
$routes->post('medicals/quick-create', 'Medical\MedicalQuickPostController::create', ['filter' => ['auth', 'receptionUserCreate']]);

//Users
$routes->post("users", 'User\UserPostController::create', ['filter' => ['auth', 'adminOnly']]);

$routes->get("users/(:num)", 'User\UserGetController::find/$1', ['filter' => ['auth', 'adminOnly']]);

$routes->put("users/(:num)", "User\UserPutController::do/$1", ['filter' => ['auth', 'adminOnly']]);

$routes->delete("users/(:num)", "User\UserDeleteController::do/$1", ['filter' => ['auth', 'adminOnly']]);

$routes->get("users", "User\UsersGetController::search", ['filter' => 'auth']);


//Medicals
$routes->post('medicals', 'Medical\MedicalPostController::create',  ['filter' => ['auth', 'adminOnly']]);

$routes->get('medicals/(:num)', 'Medical\MedicalGetController::find/$1',  ['filter' => ['auth', 'adminOnly']]);

$routes->put('medicals/(:num)', 'Medical\MedicalPutController::do/$1', ['filter' => ['auth', 'adminOnly']]);

$routes->delete('medicals/(:num)', 'Medical\MedicalDeleteController::do/$1',  ['filter' => ['auth', 'adminOnly']]);

$routes->get('medicals', 'Medical\MedicalsGetController::search');

$routes->get('medicals/user/(:num)', 'Medical\MedicalGetController::findByUser/$1', ['filter' => 'auth']);


//Patients
$routes->post('patients/register', 'Patient\PatientPublicPostController::create');

$routes->post('patients', 'Patient\PatientPostController::create', ['filter' => 'auth']);

$routes->get('patients/(:num)', 'Patient\PatientGetController::find/$1', ['filter' => 'auth']);

$routes->get('patients/user/(:num)', 'Patient\PatientGetController::findByUser/$1', ['filter' => 'auth']);

$routes->put('patients/(:num)', 'Patient\PatientPutController::do/$1', ['filter' => 'auth']);

$routes->delete('patients/(:num)', 'Patient\PatientDeleteController::do/$1', ['filter' => 'auth']);

$routes->get('patients', 'Patient\PatientsGetController::search', ['filter' => 'auth']);



//Specialty
$routes->post('specialties', 'Specialty\SpecialtyPostController::create', ['filter' => ['auth', 'adminOnly']]);

$routes->get('specialties/(:num)', 'Specialty\SpecialtyGetController::find/$1', ['filter' => 'auth']);

$routes->put('specialties/(:num)', 'Specialty\SpecialtyPutController::do/$1', ['filter' => ['auth', 'adminOnly']]);

$routes->delete('specialties/(:num)', 'Specialty\SpecialtyDeleteController::do/$1', ['filter' => ['auth', 'adminOnly']]);

$routes->get('specialties', 'Specialty\SpecialtiesGetController::search', ['filter' => 'auth']);


//appointment
$routes->post('appointments', 'Appointment\AppointmentPostController::create', ['filter' => ['auth', 'appointmentCreatePermission']]);

$routes->get('appointments/(:num)', 'Appointment\AppointmentGetController::find/$1', ['filter' => 'auth']);

$routes->get('appointments/medical/(:num)', 'Appointment\AppointmentGetController::findByMedical/$1', ['filter' => 'auth']);

$routes->get('appointments/patient/(:num)', 'Appointment\AppointmentGetController::findByPatient/$1', ['filter' => 'auth']);

$routes->put('appointments/(:num)', 'Appointment\AppointmentPutController::do/$1', ['filter' => ['auth', 'appointmentOwnerOnly']]);

$routes->delete('appointments/(:num)', 'Appointment\AppointmentDeleteController::do/$1', ['filter' => ['auth', 'appointmentOwnerOnly']]);

$routes->get('appointments', 'Appointment\AppointmentsGetController::search', ['filter' => 'auth']);


//roles
$routes->post('roles', 'Role\RolePostController::create', ['filter' => ['auth', 'adminOnly']]);

$routes->get('roles/(:num)', 'Role\RoleGetController::find/$1', ['filter' => ['auth', 'adminOnly']]);

$routes->put('roles/(:num)', 'Role\RolePutController::do/$1', ['filter' => ['auth', 'adminOnly']]);

$routes->delete('roles/(:num)', 'Role\RoleDeleteController::do/$1', ['filter' => ['auth', 'adminOnly']]);

$routes->get('roles', 'Role\RolesGetController::search', ['filter' => ['auth']]);


// Medical Schedules
$routes->post('medical-schedules', 'MedicalSchedule\MedicalSchedulePostController::create', ['filter' => ['auth', 'adminOnly']]);

$routes->get('medical-schedules/medical/(:num)', 'MedicalSchedule\MedicalScheduleGetController::findByMedical/$1', ['filter' => 'auth']);

$routes->get('medical-schedules/(:num)', 'MedicalSchedule\MedicalScheduleGetController::find/$1', ['filter' => 'auth']);

$routes->put('medical-schedules/(:num)', 'MedicalSchedule\MedicalSchedulePutController::do/$1', ['filter' => ['auth', 'adminOnly']]);

$routes->delete('medical-schedules/(:num)', 'MedicalSchedule\MedicalScheduleDeleteController::do/$1', ['filter' => ['auth', 'adminOnly']]);

$routes->get('medical-schedules', 'MedicalSchedule\MedicalsScheduleGetController::search', ['filter' => 'auth']);


// Services
$routes->get('services', 'Service\ServicesGetController::search', ['filter' => 'auth']);
$routes->get('services/(:num)', 'Service\ServiceGetController::find/$1', ['filter' => 'auth']);
$routes->post('services', 'Service\ServicePostController::create', ['filter' => ['auth', 'adminOnly']]);
$routes->put('services/(:num)', 'Service\ServicePutController::do/$1', ['filter' => ['auth', 'adminOnly']]);
$routes->delete('services/(:num)', 'Service\ServiceDeleteController::do/$1', ['filter' => ['auth', 'adminOnly']]);

// Medical Services
$routes->get('medicals/(:num)/services', 'MedicalService\MedicalServiceGetController::findByMedical/$1', ['filter' => 'auth']);
$routes->post('medicals/(:num)/services', 'MedicalService\MedicalServicePostController::assignService/$1', ['filter' => ['auth', 'adminOnly']]);
$routes->delete('medicals/(:num)/services/(:num)', 'MedicalService\MedicalServiceDeleteController::do/$1/$2', ['filter' => ['auth', 'adminOnly']]);

// Availability
$routes->get('appointments/availability', 'Appointment\AppointmentAvailabilityGetController::getAvailability', ['filter' => 'auth']);
