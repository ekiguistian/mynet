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
    $routes->get('/version', 'Version::index');
    $routes->post('/version/migrate', 'Version::migrate');
    $routes->get('/area/(:segment)', 'Workspace::index/$1');
    $routes->get('/area/customers/generate-id', 'Workspace::generateCustomerId');
    $routes->get('/area/(:segment)/baru', 'Workspace::newForm/$1');
    $routes->post('/area/(:segment)/simpan', 'Workspace::store/$1');
    $routes->post('/area/(:segment)/aksi', 'Workspace::action/$1');
    $routes->post('/billing/generate', 'Billing::generate');
    $routes->post('/billing/pay/(:num)', 'Billing::pay/$1');
    $routes->post('/attendance/toggle', 'Attendance::toggle');
    $routes->post('/vouchers/generate', 'Vouchers::generate');
    $routes->get('/vouchers/profiles/(:num)', 'Vouchers::profiles/$1');
    $routes->post('/work-orders/create', 'WorkOrders::create');
    $routes->post('/work-orders/(:num)/status', 'WorkOrders::changeStatus/$1');
    $routes->post('/earnings/(:num)/pay', 'WorkOrders::payEarning/$1');
    $routes->post('/notifications/bill/(:num)', 'Notifications::bill/$1');
    $routes->get('/settings', 'SystemSettings::index');
    $routes->post('/settings/save', 'SystemSettings::save');
    $routes->get('/settings/api-keys', 'SystemSettings::apiKeys');
    $routes->post('/settings/api-keys/create', 'SystemSettings::createApiKey');
    $routes->post('/settings/api-keys/revoke', 'SystemSettings::revokeApiKey');
    $routes->get('/integrations', 'Integrations::index');
    $routes->post('/integrations/save/(:segment)', 'Integrations::save/$1');
    $routes->post('/integrations/save-branch-whatsapp', 'Integrations::saveBranchWhatsapp');
    $routes->post('/integrations/test-whatsapp', 'Integrations::testWhatsApp');
    $routes->post('/integrations/test-payment', 'Integrations::testPayment');
    $routes->post('/integrations/router-test/(:num)', 'Integrations::testRouter/$1');
    $routes->get('/integrations/templates', 'Integrations::templates');
    $routes->post('/integrations/templates/save', 'Integrations::saveTemplate');
    $routes->get('/notifications/queue', 'WhatsappQueue::index');
    $routes->post('/notifications/queue/schedule', 'WhatsappQueue::schedule');
    $routes->post('/notifications/queue/(:num)/retry', 'WhatsappQueue::retry/$1');
    $routes->get('/tickets', 'MaintenanceTickets::index');
    $routes->post('/tickets/create', 'MaintenanceTickets::create');
    $routes->post('/tickets/(:num)/assign', 'MaintenanceTickets::assign/$1');
    $routes->post('/tickets/(:num)/status', 'MaintenanceTickets::updateStatus/$1');
    $routes->get('/invoices/(:num)/print/(:segment)', 'InvoiceDocuments::printView/$1/$2');
    $routes->get('/invoices/(:num)/pdf', 'InvoiceDocuments::pdf/$1');
});

$routes->post('/payments/webhook/(:segment)', 'PaymentWebhook::handle/$1');
$routes->get('/api/v1/ping', 'ExternalApi::ping', ['filter' => 'apiKey']);
$routes->get('/api/v1/customers', 'ExternalApi::customers', ['filter' => 'apiKey:customers_read']);
$routes->get('/api/v1/invoices', 'ExternalApi::invoices', ['filter' => 'apiKey:invoices_read']);
$routes->get('/api/v1/tickets', 'ExternalApi::tickets', ['filter' => 'apiKey:tickets_read']);
$routes->post('/api/v1/tickets', 'ExternalApi::createTicket', ['filter' => 'apiKey:tickets_write']);
