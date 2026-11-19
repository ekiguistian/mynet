<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Auth::index');
$routes->post('/login', 'Auth::login');
$routes->get('/auth/password', 'Auth::changePassword', ['filter' => 'auth']);
$routes->post('/auth/password', 'Auth::savePassword', ['filter' => 'auth']);

$routes->group('', ['filter' => 'auth'], static function ($routes): void {
    $routes->post('/logout', 'Auth::logout');
    $routes->get('/dashboard', 'Dashboard::index');
    $routes->get('/area/(:segment)', 'Workspace::index/$1');
    $routes->get('/area/(:segment)/baru', 'Workspace::newForm/$1');
    $routes->post('/area/(:segment)/simpan', 'Workspace::store/$1');
    $routes->post('/area/(:segment)/aksi', 'Workspace::action/$1');
    $routes->post('/billing/generate', 'Billing::generate');
    $routes->post('/billing/pay/(:num)', 'Billing::pay/$1');
    $routes->post('/attendance/toggle', 'Attendance::toggle');
    $routes->post('/vouchers/generate', 'Vouchers::generate');
    $routes->post('/work-orders/create', 'WorkOrders::create');
    $routes->post('/work-orders/(:num)/status', 'WorkOrders::changeStatus/$1');
    $routes->post('/earnings/(:num)/pay', 'WorkOrders::payEarning/$1');
    $routes->post('/notifications/bill/(:num)', 'Notifications::bill/$1');
});

$routes->post('/payments/webhook/(:segment)', 'PaymentWebhook::handle/$1');
