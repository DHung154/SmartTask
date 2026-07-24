<?php
/**
 * Entry Point (Front Controller)
 *
 * This is the ONLY file that gets executed directly.
 * All requests go through this file thanks to .htaccess
 *
 * MVC Flow:
 * 1. User visits URL (e.g., /tasks/create)
 * 2. .htaccess redirects to this file
 * 3. This file loads Composer autoloader
 * 4. This file creates Router and defines routes
 * 5. Router dispatches to appropriate Controller
 * 6. Controller uses Model (if needed) and loads View
 * 7. HTML is rendered to browser
 */

// No need to call session_start() here!
// The Session class handles it automatically with proper checks
// This prevents "headers already sent" errors

// Load Composer's autoloader
// This allows us to use classes without manually requiring files
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Logger;
use App\Core\Controller;

set_exception_handler(function (\Throwable $exception): void {
    Logger::error('php.fatal', ['exception' => get_class($exception), 'message' => $exception->getMessage()]);
    Controller::abort('Da xay ra loi he thong', $exception->getMessage());
});

// Import classes we need
use App\Core\Router;
use App\Controllers\AuthController;
use App\Controllers\TaskController;
use App\Controllers\ListController;
use App\Controllers\UserController;
use App\Controllers\ReportController;
use App\Controllers\ApiTaskController;
use App\Controllers\HealthController;
use App\Middleware\ApiAdminMiddleware;
use App\Middleware\ApiTokenMiddleware;

// Create a new Router instance
$router = new Router();

/**
 * Define Routes
 *
 * Format: $router->method('path', ControllerClass, 'methodName')
 *
 * GET routes  : Hi?n th? trang (kh�ng l�m thay d?i d? li?u)
 * POST routes : G?i form / thay d?i d? li?u
 *
 * QUY T?C QUAN TR?NG:
 * M?i h�nh d?ng l�m THAY �?I d? li?u (th�m, s?a, x�a, d?i tr?ng th�i)
 * d?u ph?i l� POST v� c� CSRF token.
 *
 * Tru?c d�y x�a task d�ng GET (/tasks/delete?id=5), d?n t?i 2 r?i ro:
 *   1. Ch? c?n d? ngu?i d�ng m? m?t trang c� <img src="/tasks/delete?id=5">
 *      l� task b? x�a m� h? kh�ng h? b?m g� (t?n c�ng CSRF).
 *   2. Tr�nh duy?t / ti?n �ch m? r?ng t? t?i tru?c link (prefetch)
 *      cung c� th? v� t�nh k�ch ho?t vi?c x�a.
 */

// --- Authentication Routes ---
$router->get('/login', AuthController::class, 'login');
$router->post('/login', AuthController::class, 'login');
$router->get('/register', AuthController::class, 'register');
$router->post('/register', AuthController::class, 'register');
$router->post('/logout', AuthController::class, 'logout');

// --- User / Profile Routes ---
$router->get('/profile', UserController::class, 'profile');
$router->post('/profile/update', UserController::class, 'updateProfile');
$router->post('/profile/password', UserController::class, 'changePassword');

// --- Task Routes (hi?n th?) ---
$router->get('/', TaskController::class, 'index');  // Home page (task list)
$router->get('/tasks', TaskController::class, 'index');
$router->get('/tasks/create', TaskController::class, 'create');
$router->get('/tasks/edit', TaskController::class, 'edit');  // Uses ?id=X query param
$router->get('/tasks/search', TaskController::class, 'search');
$router->get('/calendar', ReportController::class, 'calendar');
$router->get('/report', ReportController::class, 'report');
$router->get('/activity', ReportController::class, 'activity');
$router->get('/kanban', TaskController::class, 'kanban');
$router->get('/report/export.csv', ReportController::class, 'exportCsv');

// --- JSON API (Bearer token required) ---
$router->get('/health', HealthController::class, 'index');
$router->get('/api/v1/tasks', ApiTaskController::class, 'index', [ApiTokenMiddleware::class]);
$router->post('/api/v1/tasks', ApiTaskController::class, 'create', [ApiTokenMiddleware::class]);
$router->get('/api/v1/summary', ApiTaskController::class, 'summary', [ApiTokenMiddleware::class]);
$router->get('/api/v1/admin/summary', ApiTaskController::class, 'adminSummary', [ApiTokenMiddleware::class, ApiAdminMiddleware::class]);

// --- Task Routes (thay d?i d? li?u - ch? POST) ---
$router->post('/tasks/create', TaskController::class, 'create');
$router->post('/tasks/quick-add', TaskController::class, 'quickAdd');
$router->post('/tasks/edit', TaskController::class, 'edit');
$router->post('/tasks/toggle', TaskController::class, 'toggle');        // ��nh d?u ho�n th�nh
$router->post('/tasks/star', TaskController::class, 'star');            // ��nh d?u quan tr?ng
$router->post('/tasks/delete', TaskController::class, 'delete');        // X�a m?m -> th�ng r�c
$router->post('/tasks/restore', TaskController::class, 'restore');      // Kh�i ph?c t? th�ng r�c
$router->post('/tasks/force-delete', TaskController::class, 'forceDelete'); // X�a vinh vi?n
$router->post('/tasks/empty-trash', TaskController::class, 'emptyTrash');   // D?n s?ch th�ng r�c

$router->post('/tasks/progress', TaskController::class, 'progress');

// --- List Routes ---
$router->get('/lists/create', ListController::class, 'create');
$router->post('/lists/create', ListController::class, 'create');
$router->get('/lists/edit', ListController::class, 'edit');
$router->post('/lists/edit', ListController::class, 'edit');
$router->post('/lists/delete', ListController::class, 'delete');

// Dispatch the request
// This will call the appropriate controller method based on the URL
$router->dispatch();
