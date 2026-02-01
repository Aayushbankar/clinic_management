<?php
declare(strict_types=1);

// CMS JSON API entrypoint.

require __DIR__ . '/../src/core/Request.php';
require __DIR__ . '/../src/core/Response.php';
require __DIR__ . '/../src/core/Router.php';
require __DIR__ . '/../src/core/Security.php';
require __DIR__ . '/../src/core/Database.php';
require __DIR__ . '/../src/middleware/Auth.php';
require __DIR__ . '/../src/middleware/Csrf.php';
require __DIR__ . '/../src/models/UserModel.php';
require __DIR__ . '/../src/controllers/AuthController.php';
require __DIR__ . '/../src/models/DepartmentModel.php';
require __DIR__ . '/../src/models/MedicineModel.php';
require __DIR__ . '/../src/models/DoctorModel.php';
require __DIR__ . '/../src/models/StaffModel.php';
require __DIR__ . '/../src/models/PatientModel.php';
require __DIR__ . '/../src/models/DoctorScheduleModel.php';
require __DIR__ . '/../src/models/AppointmentModel.php';
require __DIR__ . '/../src/models/BillingModel.php';
require __DIR__ . '/../src/models/BillingItemModel.php';
require __DIR__ . '/../src/models/PaymentModel.php';
require __DIR__ . '/../src/controllers/DepartmentController.php';
require __DIR__ . '/../src/controllers/MedicineController.php';
require __DIR__ . '/../src/controllers/UserController.php';
require __DIR__ . '/../src/controllers/DoctorController.php';
require __DIR__ . '/../src/controllers/StaffController.php';
require __DIR__ . '/../src/controllers/PatientController.php';
require __DIR__ . '/../src/controllers/DoctorScheduleController.php';
require __DIR__ . '/../src/controllers/AppointmentController.php';
require __DIR__ . '/../src/controllers/BillingController.php';
require __DIR__ . '/../src/controllers/ReportController.php';
require __DIR__ . '/../src/controllers/PatientHistoryController.php';
require __DIR__ . '/../src/controllers/SettingsController.php';
require __DIR__ . '/../src/controllers/FeedbackController.php';

$config = require __DIR__ . '/../src/config/config.php';

Security::setDefaultHeaders();
Security::maybeCors($config);

// Session
$sess = $config['session'] ?? [];
session_name((string)($sess['name'] ?? 'CMSSESSID'));
session_set_cookie_params([
    'lifetime' => (int)($sess['cookie_lifetime'] ?? 0),
    'path' => (string)($sess['cookie_path'] ?? '/'),
    'secure' => (bool)($sess['cookie_secure'] ?? false),
    'httponly' => (bool)($sess['cookie_httponly'] ?? true),
    'samesite' => (string)($sess['cookie_samesite'] ?? 'Lax'),
]);
session_start();

Csrf::requireForStateChangingRequests();

// Central exception handler
set_exception_handler(function (Throwable $e) use ($config): void {
    $debug = (bool)($config['app']['debug'] ?? false);
    if ($debug) {
        Response::error('Server error', 500, [
            'type' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);
    }
    Response::error('Server error', 500);
});

$router = new Router();

// Health
$router->add('GET', '/', function (array $params = []): void {
    Response::ok(['service' => 'cms-api', 'status' => 'ok']);
});

// Basic DB health
$router->add('GET', '/health/db', function (array $params = []) use ($config): void {
    $pdo = Database::pdo($config);
    $stmt = $pdo->query('SELECT 1 AS ok');
    $row = $stmt->fetch();
    Response::ok(['db' => $row]);
});

// Placeholder route group
$router->add('GET', '/version', function (array $params = []): void {
    Response::ok(['version' => '0.1.0']);
});

$router->add('GET', '/auth/csrf', function (array $params = []): void {
    AuthController::csrf();
});

$router->add('POST', '/auth/login', function (array $params = []) use ($config): void {
    AuthController::login($config);
});

$router->add('POST', '/auth/logout', function (array $params = []): void {
    AuthController::logout();
});

$router->add('GET', '/auth/me', function (array $params = []) use ($config): void {
    AuthController::me($config);
});

// Users (admin)
$router->add('GET', '/users', function (array $params = []) use ($config): void {
    UserController::index($config);
});
$router->add('GET', '/users/{id}', function (array $params) use ($config): void {
    UserController::show($config, (int)($params['id'] ?? 0));
});
$router->add('POST', '/users', function (array $params = []) use ($config): void {
    UserController::create($config);
});
$router->add('PUT', '/users/{id}', function (array $params) use ($config): void {
    UserController::update($config, (int)($params['id'] ?? 0));
});
$router->add('PATCH', '/users/{id}', function (array $params) use ($config): void {
    UserController::update($config, (int)($params['id'] ?? 0));
});
$router->add('DELETE', '/users/{id}', function (array $params) use ($config): void {
    UserController::delete($config, (int)($params['id'] ?? 0));
});

// Departments
$router->add('GET', '/departments', function (array $params = []) use ($config): void {
    DepartmentController::index($config);
});
$router->add('GET', '/departments/{id}', function (array $params) use ($config): void {
    DepartmentController::show($config, (int)($params['id'] ?? 0));
});
$router->add('POST', '/departments', function (array $params = []) use ($config): void {
    DepartmentController::create($config);
});
$router->add('PUT', '/departments/{id}', function (array $params) use ($config): void {
    DepartmentController::update($config, (int)($params['id'] ?? 0));
});
$router->add('PATCH', '/departments/{id}', function (array $params) use ($config): void {
    DepartmentController::update($config, (int)($params['id'] ?? 0));
});
$router->add('DELETE', '/departments/{id}', function (array $params) use ($config): void {
    DepartmentController::delete($config, (int)($params['id'] ?? 0));
});

// Medicines
$router->add('GET', '/medicines', function (array $params = []) use ($config): void {
    MedicineController::index($config);
});
$router->add('GET', '/medicines/{id}', function (array $params) use ($config): void {
    MedicineController::show($config, (int)($params['id'] ?? 0));
});
$router->add('POST', '/medicines', function (array $params = []) use ($config): void {
    MedicineController::create($config);
});
$router->add('PUT', '/medicines/{id}', function (array $params) use ($config): void {
    MedicineController::update($config, (int)($params['id'] ?? 0));
});
$router->add('PATCH', '/medicines/{id}', function (array $params) use ($config): void {
    MedicineController::update($config, (int)($params['id'] ?? 0));
});
$router->add('DELETE', '/medicines/{id}', function (array $params) use ($config): void {
    MedicineController::delete($config, (int)($params['id'] ?? 0));
});

// Doctors
$router->add('GET', '/doctors', function (array $params = []) use ($config): void {
    DoctorController::index($config);
});
$router->add('GET', '/doctors/me', function (array $params = []) use ($config): void {
    DoctorController::me($config);
});
$router->add('GET', '/doctors/{id}', function (array $params) use ($config): void {
    DoctorController::show($config, (int)($params['id'] ?? 0));
});
$router->add('POST', '/doctors', function (array $params = []) use ($config): void {
    DoctorController::create($config);
});
$router->add('PUT', '/doctors/{id}', function (array $params) use ($config): void {
    DoctorController::update($config, (int)($params['id'] ?? 0));
});
$router->add('PATCH', '/doctors/{id}', function (array $params) use ($config): void {
    DoctorController::update($config, (int)($params['id'] ?? 0));
});
$router->add('DELETE', '/doctors/{id}', function (array $params) use ($config): void {
    DoctorController::delete($config, (int)($params['id'] ?? 0));
});

// Staff (admin)
$router->add('GET', '/staff', function (array $params = []) use ($config): void {
    StaffController::index($config);
});
$router->add('GET', '/staff/{id}', function (array $params) use ($config): void {
    StaffController::show($config, (int)($params['id'] ?? 0));
});
$router->add('POST', '/staff', function (array $params = []) use ($config): void {
    StaffController::create($config);
});
$router->add('PUT', '/staff/{id}', function (array $params) use ($config): void {
    StaffController::update($config, (int)($params['id'] ?? 0));
});
$router->add('PATCH', '/staff/{id}', function (array $params) use ($config): void {
    StaffController::update($config, (int)($params['id'] ?? 0));
});
$router->add('DELETE', '/staff/{id}', function (array $params) use ($config): void {
    StaffController::delete($config, (int)($params['id'] ?? 0));
});

// Patients
$router->add('GET', '/patients', function (array $params = []) use ($config): void {
    PatientController::index($config);
});
$router->add('GET', '/patients/me', function (array $params = []) use ($config): void {
    PatientController::me($config);
});
$router->add('GET', '/patients/{id}', function (array $params) use ($config): void {
    PatientController::show($config, (int)($params['id'] ?? 0));
});
$router->add('POST', '/patients', function (array $params = []) use ($config): void {
    PatientController::create($config);
});
$router->add('PUT', '/patients/{id}', function (array $params) use ($config): void {
    PatientController::update($config, (int)($params['id'] ?? 0));
});
$router->add('PATCH', '/patients/{id}', function (array $params) use ($config): void {
    PatientController::update($config, (int)($params['id'] ?? 0));
});
$router->add('DELETE', '/patients/{id}', function (array $params) use ($config): void {
    PatientController::delete($config, (int)($params['id'] ?? 0));
});

// Doctor schedule
$router->add('GET', '/doctor-schedule', function (array $params = []) use ($config): void {
    DoctorScheduleController::index($config);
});
$router->add('POST', '/doctor-schedule', function (array $params = []) use ($config): void {
    DoctorScheduleController::create($config);
});
$router->add('PUT', '/doctor-schedule/{id}', function (array $params) use ($config): void {
    DoctorScheduleController::update($config, (int)($params['id'] ?? 0));
});
$router->add('PATCH', '/doctor-schedule/{id}', function (array $params) use ($config): void {
    DoctorScheduleController::update($config, (int)($params['id'] ?? 0));
});
$router->add('DELETE', '/doctor-schedule/{id}', function (array $params) use ($config): void {
    DoctorScheduleController::delete($config, (int)($params['id'] ?? 0));
});

// Appointments
$router->add('GET', '/appointments', function (array $params = []) use ($config): void {
    AppointmentController::index($config);
});
$router->add('GET', '/appointments/{id}', function (array $params) use ($config): void {
    AppointmentController::show($config, (int)($params['id'] ?? 0));
});
$router->add('POST', '/appointments', function (array $params = []) use ($config): void {
    AppointmentController::create($config);
});
$router->add('PUT', '/appointments/{id}', function (array $params) use ($config): void {
    AppointmentController::update($config, (int)($params['id'] ?? 0));
});
$router->add('PATCH', '/appointments/{id}', function (array $params) use ($config): void {
    AppointmentController::update($config, (int)($params['id'] ?? 0));
});

// Billing / Payments
$router->add('GET', '/billing', function (array $params = []) use ($config): void {
    BillingController::index($config);
});
$router->add('GET', '/billing/{id}', function (array $params) use ($config): void {
    BillingController::show($config, (int)($params['id'] ?? 0));
});
$router->add('POST', '/billing', function (array $params = []) use ($config): void {
    BillingController::create($config);
});
$router->add('PUT', '/billing/{id}', function (array $params) use ($config): void {
    BillingController::update($config, (int)($params['id'] ?? 0));
});
$router->add('PATCH', '/billing/{id}', function (array $params) use ($config): void {
    BillingController::update($config, (int)($params['id'] ?? 0));
});
$router->add('DELETE', '/billing/{id}', function (array $params) use ($config): void {
    BillingController::delete($config, (int)($params['id'] ?? 0));
});
$router->add('POST', '/billing/{id}/payments', function (array $params) use ($config): void {
    BillingController::addPayment($config, (int)($params['id'] ?? 0));
});

// Reports
$router->add('GET', '/reports/dashboard', function (array $params = []) use ($config): void {
    ReportController::dashboard($config);
});
$router->add('GET', '/reports/appointments-per-doctor', function (array $params = []) use ($config): void {
    ReportController::appointmentsPerDoctor($config);
});
$router->add('GET', '/reports/revenue', function (array $params = []) use ($config): void {
    ReportController::revenue($config);
});
$router->add('GET', '/reports/patient-history', function (array $params = []) use ($config): void {
    ReportController::patientHistory($config);
});

// Patient history (doctor write)
$router->add('POST', '/patient-history', function (array $params = []) use ($config): void {
    PatientHistoryController::create($config);
});

// Settings
$router->add('GET', '/settings/clinic', function (array $params = []) use ($config): void {
    SettingsController::clinic($config);
});
$router->add('PUT', '/settings/clinic', function (array $params = []) use ($config): void {
    SettingsController::updateClinic($config);
});
$router->add('PATCH', '/settings/clinic', function (array $params = []) use ($config): void {
    SettingsController::updateClinic($config);
});

// Feedback
$router->add('GET', '/feedback', function (array $params = []) use ($config): void {
    FeedbackController::index($config);
});
$router->add('POST', '/feedback', function (array $params = []) use ($config): void {
    FeedbackController::create($config);
});

$router->dispatch(Request::method(), Request::route());

