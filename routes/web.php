<?php

use App\Http\Controllers\API\Service\ServiceController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/admin/dashboard/get-cancellation-request', [DashboardController::class, 'getCancellationRequest'])->name('admin.dashboard.getCancellationRequest');
Route::get('/admin/dashboard/get-pending-order-count', [DashboardController::class, 'getPendingOrderCount'])->name('admin.dashboard.getPendingOrderCount');
Route::get('/admin/dashboard/get-support-awaiting-reply', [DashboardController::class, 'getSupportAwaitingReply'])->name('admin.dashboard.getSupportAwaitingReply');

// define('COVERAGE_CACHE_KEY', 'coverage_data');

Route::prefix('api/addons')->namespace('API\Addons')->group(function () {
	Route::post('/update-client-addon', 'AddonsController@UpdateClientAddon')->name('UpdateClientAddon');
});

Route::prefix('api/affiliates')->namespace('API\Affiliates')->group(function () {
	Route::post('GetAffiliates', 'AffiliatesController@GetAffiliates')->name('GetAffiliates')->middleware('permissionapi:GetAffiliates,admin');
	Route::post('AffiliateActivate', 'AffiliatesController@AffiliateActivate')->name('AffiliateActivate')->middleware('permissionapi:AffiliateActivate,admin');
});

Route::prefix('api/authentication')->namespace('API\Authentication')->group(function () {
	Route::post('ListOAuthCredentials', 'AuthenticationController@ListOAuthCredentials')->name('ListOAuthCredentials');
	Route::post('DeleteOAuthCredential', 'AuthenticationController@DeleteOAuthCredential')->name('DeleteOAuthCredential');
	Route::post('ValidateLogin', 'AuthenticationController@ValidateLogin')->name('ValidateLogin');
    Route::post('login', 'AuthenticationController@login')->name('login');
    Route::post('register', 'AuthenticationController@register')->name('register');
    Route::post('forgot-password', 'AuthenticationController@forgot-password')->name('forgot-password');
    Route::post('whoami', 'AuthenticationController@whoami')->name('whoami');
});

Route::prefix('api/billing')->namespace('API\Billing')->group(function () {
	Route::post('GetCredits', 'BillingController@GetCredits')->name('GetCredits')->middleware('permissionapi:GetCredits,admin');
	Route::post('GetInvoice', 'BillingController@GetInvoice')->name('GetInvoice')->middleware('permissionapi:GetInvoice,admin');
	Route::post('GetInvoices', 'BillingController@GetInvoices')->name('GetInvoices')->middleware('permissionapi:GetInvoices,admin');
	Route::post('GetPayMethods', 'BillingController@GetPayMethods')->name('GetPayMethods')->middleware('permissionapi:GetPayMethods,admin');
	Route::post('GetQuotes', 'BillingController@GetQuotes')->name('GetQuotes')->middleware('permissionapi:GetQuotes,admin');
	Route::post('GetTransactions', 'BillingController@GetTransactions')->name('GetTransactions')->middleware('permissionapi:GetTransactions,admin');
	Route::post('AddBillableItem', 'BillingController@AddBillableItem')->name('AddBillableItem')->middleware('permissionapi:AddBillableItem,admin');
	Route::post('AddCredit', 'BillingController@AddCredit')->name('AddCredit')->middleware('permissionapi:AddCredit,admin');
	Route::post('AddInvoicePayment', 'BillingController@AddInvoicePayment')->name('AddInvoicePayment')->middleware('permissionapi:AddInvoicePayment,admin');
	Route::post('DeleteQuote', 'BillingController@DeleteQuote')->name('DeleteQuote')->middleware('permissionapi:DeleteQuote,admin');
	Route::post('UpdateTransaction', 'BillingController@UpdateTransaction')->name('UpdateTransaction')->middleware('permissionapi:UpdateTransaction,admin');
	Route::post('UpdateQuote', 'BillingController@UpdateQuote')->name('UpdateQuote')->middleware('permissionapi:UpdateQuote,admin');
	Route::post('AddPayMethod', 'BillingController@AddPayMethod')->name('AddPayMethod')->middleware('permissionapi:AddPayMethod,admin');
	Route::post('AddTransaction', 'BillingController@AddTransaction')->name('AddTransaction')->middleware('permissionapi:AddTransaction,admin');
	Route::post('CreateInvoice', 'BillingController@CreateInvoice')->name('CreateInvoice')->middleware('permissionapi:CreateInvoice,admin');
	Route::post('DeletePayMethod', 'BillingController@DeletePayMethod')->name('DeletePayMethod')->middleware('permissionapi:DeletePayMethod,admin');
	Route::post('ApplyCredit', 'BillingController@ApplyCredit')->name('ApplyCredit')->middleware('permissionapi:ApplyCredit,admin');
	Route::post('UpdateInvoice', 'BillingController@UpdateInvoice')->name('UpdateInvoice')->middleware('permissionapi:UpdateInvoice,admin');
	Route::post('AcceptQuote', 'BillingController@AcceptQuote')->name('AcceptQuote')->middleware('permissionapi:AcceptQuote,admin');
});

Route::prefix('api/client')->namespace('API\Client')->group(function () {
	Route::post('/AddClient', 'ClientV2Controller@AddClient')->name('AddClient')->middleware('permissionapi:AddClient,admin', 'api');
	Route::post('/UpdateClient', 'ClientController@UpdateClient')->name('UpdateClient')->middleware('permissionapi:UpdateClient,admin');
	Route::post('/AddContact', 'ClientController@AddContact')->name('AddContact')->middleware('permissionapi:AddContact,admin');
	Route::post('/CloseClient', 'ClientController@CloseClient')->name('CloseClient')->middleware('permissionapi:CloseClient,admin');
	Route::post('/DeleteClient', 'ClientController@DeleteClient')->name('DeleteClient')->middleware('permissionapi:DeleteClient,admin');
	Route::post('/DeleteContact', 'ClientController@DeleteContact')->name('DeleteContact')->middleware('permissionapi:DeleteContact,admin');
	Route::post('/GetClientPassword', 'ClientV2Controller@GetClientPassword')->name('GetClientPassword')->middleware('permissionapi:GetClientPassword,admin');
	Route::post('/GetClients', 'ClientV2Controller@GetClients')->name('GetClients')->middleware('permissionapi:GetClients,admin');
	Route::post('/GetClientGroups', 'ClientV2Controller@GetClientGroups')->name('GetClientGroups')->middleware('permissionapi:GetClientGroups,admin');
	Route::post('GetClientsDetails', 'ClientV2Controller@GetClientsDetails')->name('GetClientsDetails')->middleware('permissionapi:GetClientsDetails,admin');
	Route::post('/GetClientsAddons', 'ClientV2Controller@GetClientsAddons')->name('GetClientsAddons')->middleware('permissionapi:GetClientsAddons,admin');
	Route::post('GetClientsDomains', 'ClientV2Controller@GetClientsDomains')->name('GetClientsDomains')->middleware('permissionapi:GetClientsDomains,admin')->middleware('permissionapi:GetClientsDomains,admin');
	Route::post('/GetContacts', 'ClientV2Controller@GetContacts')->name('GetContacts')->middleware('permissionapi:GetContacts,admin');
	Route::post('/GetClientsProducts', 'ClientV2Controller@GetClientsProducts')->name('GetClientsProducts')->middleware('permissionapi:GetClientsProducts,admin');
	Route::post('/GetEmails', 'ClientV2Controller@GetEmails')->name('GetEmails')->middleware('permissionapi:GetEmails,admin');
	Route::post('/UpdateContact', 'ClientController@UpdateContact')->name('UpdateContact')->middleware('permissionapi:UpdateContact,admin');
	Route::post('/GetCancelledPackages', 'ClientV2Controller@GetCancelledPackages')->name('GetCancelledPackages')->middleware('permissionapi:GetCancelledPackages,admin');
});

Route::prefix('api/Domains')->namespace('API\Domains')->group(function () {
	Route::post('DomainWhois', 'DomainsController@DomainWhois')->name('DomainWhois');
	Route::post('GetTLDPricing', 'DomainsController@GetTLDPricing')->name('GetTLDPricing');
	Route::post('GetPromotions', 'OrdersController@GetPromotions')->name('GetPromotions');
	Route::post('PendingOrder', 'OrdersController@PendingOrder')->name('PendingOrder');
	Route::post('GetProducts', 'OrdersController@GetProducts')->name('GetProducts');
	Route::post('CancelOrder', 'OrdersController@CancelOrder')->name('CancelOrder');
	Route::post('DeleteOrder', 'OrdersController@DeleteOrder')->name('DeleteOrder');
	Route::post('FraudOrder', 'OrdersController@FraudOrder')->name('FraudOrder');
	Route::post('AcceptOrder', 'OrdersController@AcceptOrder')->name('AcceptOrder');
	Route::post('AddOrder', 'OrdersController@AddOrder')->name('AddOrder');
	Route::post('OrderFraudCheck', 'OrdersController@OrderFraudCheck')->name('OrderFraudCheck');
});

Route::prefix('api/module')->namespace('API\Module')->group(function () {
	Route::post('ActivateModule', 'ModuleController@ActivateModule')->name('ActivateModule')->middleware('permissionapi:ActivateModule,admin');
	Route::post('DeactivateModule', 'ModuleController@DeactivateModule')->name('DeactivateModule')->middleware('permissionapi:DeactivateModule,admin');
	Route::post('GetModuleConfigurationParameters', 'ModuleController@GetModuleConfigurationParameters')->name('GetModuleConfigurationParameters')->middleware('permissionapi:GetModuleConfigurationParameters,admin');
	Route::post('UpdateModuleConfiguration', 'ModuleController@UpdateModuleConfiguration')->name('UpdateModuleConfiguration')->middleware('permissionapi:UpdateModuleConfiguration,admin');
});

Route::prefix('api/orders')->namespace('API\Orders')->group(function () {
	Route::post('GetOrders', 'OrdersController@GetOrders')->name('GetOrders');
	Route::post('GetOrderStatuses', 'OrdersController@GetOrderStatuses')->name('GetOrderStatuses');
	Route::post('GetPromotions', 'OrdersController@GetPromotions')->name('GetPromotions');
	Route::post('PendingOrder', 'OrdersController@PendingOrder')->name('PendingOrder');
	Route::post('GetProducts', 'OrdersController@GetProducts')->name('GetProducts');
	Route::post('CancelOrder', 'OrdersController@CancelOrder')->name('CancelOrder');
	Route::post('DeleteOrder', 'OrdersController@DeleteOrder')->name('DeleteOrder');
	Route::post('FraudOrder', 'OrdersController@FraudOrder')->name('FraudOrder');
	Route::post('AcceptOrder', 'OrdersController@AcceptOrder')->name('AcceptOrder');
	Route::post('AddOrder', 'OrdersController@AddOrder')->name('AddOrder');
	Route::post('OrderFraudCheck', 'OrdersController@OrderFraudCheck')->name('OrderFraudCheck');
});

Route::prefix('api/products')->namespace('API\Products')->group(function () {
	Route::post('AddProduct', 'ProductsController@AddProduct')->name('AddProduct')->middleware('permissionapi:AddProduct,admin');
});

Route::prefix('api/project-management')->namespace('API\ProjectManagement')->group(function () {
	Route::post('/AddProjectMessage', 'ProjectManagementController@AddProjectMessage')->name('AddProjectMessage')->middleware('permissionapi:AddProjectMessage,admin');
	Route::post('/AddProjectTask', 'ProjectManagementController@AddProjectTask')->name('AddProjectTask')->middleware('permissionapi:AddProjectTask,admin');
	Route::post('/CreateProject', 'ProjectManagementController@CreateProject')->name('CreateProject')->middleware('permissionapi:CreateProject,admin');
	Route::post('/DeleteProjectTask', 'ProjectManagementController@DeleteProjectTask')->name('DeleteProjectTask')->middleware('permissionapi:DeleteProjectTask,admin');
	Route::post('/StartTaskTimer', 'ProjectManagementController@StartTaskTimer')->name('StartTaskTimer')->middleware('permissionapi:StartTaskTimer,admin');
	Route::post('/EndTaskTimer', 'ProjectManagementController@EndTaskTimer')->name('EndTaskTimer')->middleware('permissionapi:EndTaskTimer,admin');
	Route::post('/GetProject', 'ProjectManagementController@GetProject')->name('GetProject')->middleware('permissionapi:GetProject,admin');
	Route::post('/GetProjects', 'ProjectManagementController@GetProjects')->name('GetProjects')->middleware('permissionapi:GetProjects,admin');
	Route::post('/UpdateProject', 'ProjectManagementController@UpdateProject')->name('UpdateProject')->middleware('permissionapi:UpdateProject,admin');
	Route::post('/UpdateProjectTask', 'ProjectManagementController@UpdateProjectTask')->name('UpdateProjectTask')->middleware('permissionapi:UpdateProjectTask,admin');
});

Route::prefix('api/service')->namespace('API\Service')->group(function () {
	Route::post('ModuleCreate', 'ServiceController@ModuleCreate')->name('ModuleCreate')->middleware('permissionapi:ModuleCreate,admin');
	Route::post('ModuleTerminate', 'ServiceController@ModuleTerminate')->name('ModuleTerminate')->middleware('permissionapi:ModuleTerminate,admin');
	Route::post('ModuleSuspend', 'ServiceController@ModuleSuspend')->name('ModuleSuspend')->middleware('permissionapi:ModuleSuspend,admin');
	Route::post('ModuleUnsuspend', 'ServiceController@ModuleUnsuspend')->name('ModuleUnsuspend')->middleware('permissionapi:ModuleUnsuspend,admin');
	Route::post('ModuleCustom', 'ServiceController@ModuleCustom')->name('ModuleCustom')->middleware('permissionapi:ModuleCustom,admin');
	Route::post('ModuleChangePackage', 'ServiceController@ModuleChangePackage')->name('ModuleChangePackage')->middleware('permissionapi:ModuleChangePackage,admin');
	Route::post('ModuleChangePw', 'ServiceController@ModuleChangePw')->name('ModuleChangePw')->middleware('permissionapi:ModuleChangePw,admin');
	Route::post('UpgradeProduct', 'ServiceController@UpgradeProduct')->name('UpgradeProduct')->middleware('permissionapi:UpgradeProduct,admin', 'api');
	Route::post('UpdateClientProduct', 'ServiceController@UpdateClientProduct')->name('UpdateClientProduct')->middleware('permissionapi:UpdateClientProduct,admin');
});

Route::prefix('api/support')->namespace('API\Support')->group(function () {
	Route::post('AddAnnouncement', 'SupportController@AddAnnouncement')->name('AddAnnouncement')->middleware('permissionapi:AddAnnouncement,admin');
	Route::post('DeleteAnnouncement', 'SupportController@DeleteAnnouncement')->name('DeleteAnnouncement')->middleware('permissionapi:DeleteAnnouncement,admin');
	Route::post('AddClientNote', 'SupportController@AddClientNote')->name('AddClientNote')->middleware('permissionapi:AddClientNote,admin');
	Route::post('GetAnnouncements', 'SupportController@GetAnnouncements')->name('GetAnnouncements')->middleware('permissionapi:GetAnnouncements,admin');
	Route::post('DeleteTicketNote', 'SupportController@DeleteTicketNote')->name('DeleteTicketNote')->middleware('permissionapi:DeleteTicketNote,admin');
	Route::post('OpenTicket', 'SupportController@OpenTicket')->name('OpenTicket')->middleware('permissionapi:OpenTicket,admin');
	Route::post('AddTicketNote', 'SupportController@AddTicketNote')->name('AddTicketNote')->middleware('permissionapi:AddTicketNote,admin');
	Route::post('AddTicketReply', 'SupportController@AddTicketReply')->name('AddTicketReply')->middleware('permissionapi:AddTicketReply,admin');
	Route::post('DeleteTicket', 'SupportController@DeleteTicket')->name('DeleteTicket')->middleware('permissionapi:DeleteTicket,admin');
	Route::post('DeleteTicketReply', 'SupportController@DeleteTicketReply')->name('DeleteTicketReply')->middleware('permissionapi:DeleteTicketReply,admin');
	Route::post('UpdateTicketReply', 'SupportController@UpdateTicketReply')->name('UpdateTicketReply')->middleware('permissionapi:UpdateTicketReply,admin');
	Route::post('UpdateTicket', 'SupportController@UpdateTicket')->name('UpdateTicket')->middleware('permissionapi:UpdateTicket,admin');
	Route::post('AddCancelRequest', 'SupportController@AddCancelRequest')->name('AddCancelRequest')->middleware('permissionapi:AddCancelRequest,admin');
	Route::post('BlockTicketSender', 'SupportController@BlockTicketSender')->name('BlockTicketSender')->middleware('permissionapi:BlockTicketSender,admin');
	Route::post('MergeTicket', 'SupportController@MergeTicket')->name('MergeTicket')->middleware('permissionapi:MergeTicket,admin');
});

Route::prefix('api/system')->namespace('API\System')->group(function () {
	Route::post('/AddBannedIp', 'SystemController@AddBannedIp')->name('AddBannedIp')->middleware('permissionapi:AddBannedIp,admin');
	Route::post('/DecryptPassword', 'SystemController@DecryptPassword')->name('DecryptPassword')->middleware('permissionapi:DecryptPassword,admin');
	Route::post('/EncryptPassword', 'SystemController@EncryptPassword')->name('EncryptPassword')->middleware('permissionapi:EncryptPassword,admin');
	Route::post('/GetActivityLog', 'SystemController@GetActivityLog')->name('GetActivityLog')->middleware('permissionapi:GetActivityLog,admin');
	Route::post('/GetAdminDetails', 'SystemController@GetAdminDetails')->name('GetAdminDetails')->middleware('permissionapi:GetAdminDetails,admin');
	Route::post('/GetAdminUsers', 'SystemController@GetAdminUsers')->name('GetAdminUsers')->middleware('permissionapi:GetAdminUsers,admin');
	Route::post('/GetAutomationLog', 'SystemController@GetAutomationLog')->name('GetAutomationLog')->middleware('permissionapi:GetAutomationLog,admin');
	Route::post('/GetConfigurationValue', 'SystemController@GetConfigurationValue')->name('GetConfigurationValue')->middleware('permissionapi:GetConfigurationValue,admin');
	Route::post('/GetCurrencies', 'SystemController@GetCurrencies')->name('GetCurrencies')->middleware('permissionapi:GetCurrencies,admin');
	Route::post('/GetEmailTemplates', 'SystemController@GetEmailTemplates')->name('GetEmailTemplates')->middleware('permissionapi:GetEmailTemplates,admin');
	Route::post('/GetPaymentMethods', 'SystemController@GetPaymentMethods')->name('GetPaymentMethods')->middleware('permissionapi:GetPaymentMethods,admin');
	Route::post('/GetStaffOnline', 'SystemController@GetStaffOnline')->name('GetStaffOnline')->middleware('permissionapi:GetStaffOnline,admin');
	Route::post('/GetStats', 'SystemController@GetStats')->name('GetStats')->middleware('permissionapi:GetStats,admin');
	Route::post('/GetToDoItems', 'SystemController@GetToDoItems')->name('GetToDoItems')->middleware('permissionapi:GetToDoItems,admin');
	Route::post('/GetToDoItemStatuses', 'SystemController@GetToDoItemStatuses')->name('GetToDoItemStatuses')->middleware('permissionapi:GetToDoItemStatuses,admin');
	Route::post('/GetToDoItemStatuses', 'SystemController@GetToDoItemStatuses')->name('GetToDoItemStatuses')->middleware('permissionapi:GetToDoItemStatuses,admin');
	Route::post('/LogActivity', 'SystemController@LogActivity')->name('LogActivity')->middleware('permissionapi:LogActivity,admin');
	Route::post('/SendAdminEmail', 'SystemController@SendAdminEmail')->name('SendAdminEmail')->middleware('permissionapi:SendAdminEmail,admin');
	Route::post('/SendEmail', 'SystemController@SendEmail')->name('SendEmail')->middleware('permissionapi:SendEmail,admin');
	Route::post('/SetConfigurationValue', 'SystemController@SetConfigurationValue')->name('SetConfigurationValue')->middleware('permissionapi:SetConfigurationValue,admin');
	Route::post('/TriggerNotificationEvent', 'SystemController@TriggerNotificationEvent')->name('TriggerNotificationEvent')->middleware('permissionapi:TriggerNotificationEvent,admin');
	Route::post('/UpdateAnnouncement', 'SystemController@UpdateAnnouncement')->name('UpdateAnnouncement')->middleware('permissionapi:UpdateAnnouncement,admin');
	Route::post('/UpdateToDoItem', 'SystemController@UpdateToDoItem')->name('UpdateToDoItem')->middleware('permissionapi:UpdateToDoItem,admin');
	Route::post('/UpdateAdminNotes', 'SystemController@UpdateAdminNotes')->name('UpdateAdminNotes')->middleware('permissionapi:UpdateAdminNotes,admin');
});

Route::prefix('api/tickets')->namespace('API\Tickets')->group(function () {
	Route::post('GetSupportDepartments', 'TicketsController@GetSupportDepartments')->name('GetSupportDepartments')->middleware('permissionapi:GetSupportDepartments,admin');
	Route::post('GetSupportStatuses', 'TicketsController@GetSupportStatuses')->name('GetSupportStatuses')->middleware('permissionapi:GetSupportStatuses,admin');
	Route::post('GetTicketNotes', 'TicketsController@GetTicketNotes')->name('GetTicketNotes')->middleware('permissionapi:GetTicketNotes,admin');
	Route::post('GetTicket', 'TicketsController@GetTicket')->name('GetTicket')->middleware('permissionapi:GetTicket,admin');
	Route::post('GetTicketPredefinedReplies', 'TicketsController@GetTicketPredefinedReplies')->name('GetTicketPredefinedReplies')->middleware('permissionapi:GetTicketPredefinedReplies,admin');
	Route::post('GetTicketPredefinedCats', 'TicketsController@GetTicketPredefinedCats')->name('GetTicketPredefinedCats')->middleware('permissionapi:GetTicketPredefinedCats,admin');
	Route::post('GetTicketAttachment', 'TicketsController@GetTicketAttachment')->name('GetTicketAttachment')->middleware('permissionapi:GetTicketAttachment,admin');
	Route::post('GetTickets', 'TicketsController@GetTickets')->name('GetTickets')->middleware('permissionapi:GetTickets,admin');
	Route::post('GetTicketCounts', 'TicketsController@GetTicketCounts')->name('GetTicketCounts')->middleware('permissionapi:GetTicketCounts,admin');
});

Route::prefix('api/users')->namespace('API\Users')->group(function () {
	Route::post('ResetPassword', 'UsersController@ResetPassword')->name('ResetPassword')->middleware('permissionapi:ResetPassword,admin');
});

// Route::get('/coverage', function () {
//    try {
//       // Check if coverage data exists in cache
//       $coverageData = Cache::get(COVERAGE_CACHE_KEY);

//       if (!$coverageData) {
//          // Retrieve the list of coverage data from the database
//          $coverageData = DB::table("tblpackage_coverage")->get();

//          // Cache the data for future requests (assuming it doesn't change frequently)
//          Cache::put(COVERAGE_CACHE_KEY, $coverageData, now()->addHours(1)); // Cache for 1 hour
//       }

//       return response()->json($coverageData);
//    } catch (\Exception $e) {
//       // Handle potential errors such as database connection issues
//       return response()->json(['message' => 'Server error'], 500);
//    }
// });

Route::get('/product-coverage', function (Request $request) {
   try {
      $productIds = $request->input('product_id');

      // Convert comma-separated product IDs string to an array
      $productIdsArray = array_map('intval', explode(',', $productIds));

      // Fetch product coverage data from the cache if available
      $productCoverageData = Cache::remember('product_coverage_' . $productIds, now()->addMinutes(10), function () use ($productIdsArray) {
         return DB::table('tblproducts')
            ->join('tblpricing', 'tblproducts.id', '=', 'tblpricing.relid')
            ->select('tblproducts.id', 'tblproducts.type', 'tblproducts.gid', 'tblproducts.name', 'tblpricing.monthly', 'tblpricing.quarterly', 'tblpricing.semiannually', 'tblpricing.annually')
            ->whereIn('tblproducts.id', $productIdsArray)
            ->groupBy('tblpricing.relid')
            ->get()
            ->toArray();
      });

      if (empty($productCoverageData)) {
         return response()->json(['message' => 'Data not found'], 404);
      }

      return response()->json($productCoverageData);
   } catch (\Exception $e) {
      // Handle potential errors such as database connection issues
      return response()->json(['message' => 'Server error'], 500);
   }
});

Route::get('/', 'IndexController@index');
Route::match(['get', 'post'], '/index.php', 'IndexController@index');

Route::get('/install', 'InstallController@index')
   ->name('page.install');


Auth::routes(['verify' => true]);
Route::get('/home', 'HomeController@index')->name('home');
Route::post('/logout', [App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logoutClient');
Route::match(['get', 'post'], '/addfunds', 'HomeController@AddDepositFunds')->name('deposit.add');
Route::post('/generateinvoice', 'HomeController@GenerateInvoice')->name('generate.invoice');
Route::get('/testingmap', function () {
   return view('pages.dashboard.testingmap');
});
Route::get('/domain/check', 'DomainCheckerController@index')->name('domain.check');
Route::get('/checkcoverage', function () {
   return view('pages.dashboard.checkcoverage');
})->name('checkCoverage');

Route::prefix('')->namespace('Client')->group(function () {
   Route::match(['get', 'post'], '/cart', 'CartController@index')->name('cart');
   // Route::get('/cart', 'CartController@index')->name('cart');
   // Route::post('/cart', 'CartController@index')->name('cart.post');

   Route::match(['get', 'post'], '/dl.php', 'DownloadController@index')->name('dl');
   Route::match(['get', 'post'], 'submitticket.php', 'SupportController@Support_OpenTicket')->name('submitticket')->middleware('auth:web');
   Route::get('viewinvoice.php', 'BillingController@Billing_ViewInvoiceWeb_existing');
   Route::match(['get', 'post'], 'creditcard.php', 'CreditCardController@index');
   Route::post('paymentmethods/remote-input', 'PaymentMethodsController@remoteInput')->name('remoteInput');

   // Profile Controller
   Route::get('detailprofile', 'ProfileController@EditAccountDetails')
      ->name('pages.profile.editaccountdetails.index');
   Route::post('updateprofile', 'ProfileController@UpdateAccountDetails')
      ->name('pages.profile.editaccountdetails.update');
   Route::post('updatepw', 'ProfileController@UpdatePassword')
      ->name('pages.profile.editaccountdetails.updatepw');
   Route::get('emailnotes', 'ProfileController@EmailNotes')
      ->name('pages.profile.emailnotes.index');

   Route::match(['get', 'post'], 'securitysettings', 'ProfileController@SecuritySettings')
      ->name('pages.profile.securitysettings.index');

      Route::get('securitysettings/2fa/setup', 'ProfileController@setupTwoFactor')
    ->name('2fa.setup')
    ->middleware('auth:web');
      
      Route::get('account-security-two-factor-enable', 'ProfileController@enableTwoFactor')
      ->name('account-security-two-factor-enable')
      ->middleware('auth:web');
      
  Route::get('account-security-two-factor-disable', 'ProfileController@disableTwoFactor')
      ->name('account-security-two-factor-disable')
      ->middleware('auth:web');

   //     Route::get('2fa/verify', 'TwoFactorController@showVerification')
   //      ->name('2fa.verify');
   //  Route::post('2fa/verify', 'TwoFactorController@verify')
   //      ->name('2fa.verify.post');

  // 2FA Verification Routes, ini yang kepakai
   //  Route::get('2fa/verify', 'TwoFactorController@showVerification')
   //      ->name('2fa.verify');
   //  Route::post('2fa/verify', 'TwoFactorController@verify')
   //      ->name('2fa.verify.post');
   
   //    Route::get('/2fa/backup', 'TwoFactorController@showBackupCodeForm')->name('2fa.backup.form');
   // Route::post('/2fa/backup', 'TwoFactorController@loginWithBackupCode')->name('2fa.backup');

   Route::get('2fa/backup', 'TwoFactorController@showBackupCodeForm')->name('2fa.backup.form');
   Route::post('2fa/backup', 'TwoFactorController@loginWithBackupCode')->name('2fa.backup');

   // Two Factor Authentication Routes
   Route::prefix('securitysettings/2fa')->group(function () {
      Route::get('setup', 'ProfileController@setupTwoFactor')->name('2fa.setup');
      Route::post('enable', 'ProfileController@enableTwoFactor')->name('2fa.enable');
      Route::post('disable', 'ProfileController@disableTwoFactor')->name('2fa.disable');
   });

   //  Route::post('2fa/enable', 'TwoFactorController@enable')
   //      ->name('2fa.enable')
   //      ->middleware('auth:web');
        
   //  Route::post('2fa/disable', 'TwoFactorController@disable')
   //      ->name('2fa.disable')
   //      ->middleware('auth:web');

       

      
});
// Upload Account Terms
Route::get('uploadterms', 'HomeController@UploadAccountTerms')
   ->name('pages.profile.uploadaccountterms.index');
// Contact/Sub Account
Route::get('contactsub', 'HomeController@ContactSub')
   ->name('pages.profile.contactsub.index');
Route::get('contactsub.json', 'HomeController@ContactSub_dtJson')
   ->name('dt_Contacts');
Route::post('addcontact', 'HomeController@ContactSub_CreateNew')
   ->name('pages.profile.contactsub.create');
Route::get('contactsub/details/{id}', 'HomeController@ContactSub_Details')
   ->name('pages.profile.contactsub.details');
Route::post('contactsub/update/{id}', 'HomeController@ContactSub_Update')
   ->name('pages.profile.contactsub.update');
Route::delete('delete', 'HomeController@ContactSub_Delete')
   ->name('pages.profile.contactsub.delete');
//Update Password
Route::get('updatepassword', 'HomeController@UpdatePassword')
   ->name('pages.profile.changepassword.index');
//Logout
Route::get('logout', 'HomeController@logout')
   ->name('logout');

Route::prefix('services')->namespace('Client')->group(function () {
   //Service Controller
   Route::match(['get', 'post'], 'upgrade', 'ServicesController@Services_Upgrade')
      ->name('pages.services.upgrade')->middleware('auth:web');
   Route::get('myservices', 'ServicesController@Services_myservices')
      ->name('pages.services.myservices.index')->middleware('auth:web');
   Route::get('dt_myServices', 'ServicesController@dt_myServices')
      ->name('dt_myServices');
   Route::match(['get', 'post'], 'servicedetails/{id}', 'ServicesController@Services_DetailServices')
      ->name('pages.services.myservices.servicedetails')->middleware('auth:web');

      // Host Child Nameservers
    // Last Updated : 06/11/2024 
    // Author : Anggi
    Route::get('mydomains/childnameservers', 'ServicesController@Domain_Childnameservers')
    ->name('pages.domain.mydomains.childnameservers');
    Route::post('mydomains/childnameservers/get', 'ServicesController@Domain_Childnameservers_Get')
    ->name('pages.domain.mydomains.childnameservers.get');
    Route::post('mydomains/childnameservers/create', 'ServicesController@Domain_Childnameservers_Create')
    ->name('pages.domain.mydomains.childnameservers.create');
    Route::post('mydomains/childnameservers/update', 'ServicesController@Domain_Childnameservers_Update')
    ->name('pages.domain.mydomains.childnameservers.update');
    Route::post('mydomains/childnameservers/delete', 'ServicesController@Domain_Childnameservers_Delete')
    ->name('pages.domain.mydomains.childnameservers.delete');

    // Details Domain
    // Last Updated : 11/11/2024
    // Author : Anggi
    Route::get('servicedetails', 'ServicesController@Domain_Details')
    ->name('pages.domain.mydomains.details');
    Route::get('documentdomain', 'ServicesController@Domain_Document_Upload')
   ->name('pages.domain.mydomains.details.document');

   Route::get('details/requirement', 'DomainsController@Domain_Document_Requirement')
   ->name('pages.domain.mydomains.details.requirement');

   // Route::get('documentdomain', 'ServicesController@Domain_Document_Upload')
   //  ->name('pages.domain.mydomains.document');
   
    Route::post('details/upload', 'ServicesController@uploadDocuments')
    ->name('pages.domain.mydomains.details.upload');
    Route::get('details/document/update/{userid}', 'ServicesController@updateListDocuments')
    ->name('pages.domain.mydomains.details.update');
    Route::post('details/document/delete', 'ServicesController@deleteFile')
    ->name('pages.domain.mydomains.details.delete');

    /*
    * Author: Fajar Habib Zaelani
    * Last Updated: 19/11/2024
    * DNS Manager
    */
    Route::get('mydomains/details/dnsmanager', 'ServicesController@DNSManager')
    ->name('pages.domain.mydomains.details.dnsmanager');

    /*
    * Author: Anggi
    * Last Updated: 21/11/2024
    * Upload Document
    */
    Route::post('details/requirement/detail', 'DomainsController@Domain_Document_Requirement_Detail')
  ->name('pages.domain.mydomains.details.requirement.detail');
    Route::post('details/document/tldlookup', 'ServicesController@tldLookup')
    ->name('pages.domain.mydomains.details.tldlookup');
    Route::post('details/document/setdocument', 'ServicesController@setDocument')
    ->name('pages.domain.mydomains.details.setdocument');   

    // Route::match(['get', 'post'], 'jalanpintascpanel/{id}', 'ServicesController@Services_DetailJalanPintasCpanel')
    // ->name('pages.services.myservices.jalanPintasCpanel')->middleware('auth:web');

    // cPanel Routes Group
    // cPanel Shortcuts Group
    Route::prefix('jalanpintascpanel')->middleware('auth:web')->group(function () {
        // Main cPanel page
        Route::match(['get',
            'post'
        ], '{id}', 'ServicesController@Services_DetailJalanPintasCpanel')
        ->name('pages.services.myservices.jalanPintasCpanel');

        // Direct cPanel shortcuts
        Route::get('{id}/email', 'ServicesController@Services_RedirectToEmailManager')
        ->name('cpanel.email');
        Route::get('{id}/ftp', 'ServicesController@Services_RedirectToFileManager')
        ->name('cpanel.ftp');
        Route::get('{id}/database', 'ServicesController@Services_RedirectToDatabase')
        ->name('cpanel.database');
        Route::get('{id}/subdomain', 'ServicesController@Services_RedirectToSubdomain')
        ->name('cpanel.subdomain');
        Route::get('{id}/backup', 'ServicesController@Services_RedirectToBackup')
        ->name('cpanel.backup');
        Route::get('{id}/phpmyadmin', 'ServicesController@Services_RedirectToPhpMyAdmin')
        ->name('cpanel.phpmyadmin');
        Route::get('{id}/awstats', 'ServicesController@Services_RedirectToAwstats')
        ->name('cpanel.awstats');
    });
    
   Route::get('cancelservice', 'ServicesController@Services_cancelservice')
      ->name('pages.services.cancelservice.index')->middleware('auth:web');
   Route::get('cartservice', 'ServicesController@Services_CartServices')
      ->name('pages.services.cartservices.index');
   Route::post('getproduct', 'ServicesController@Services_ProductList')
      ->name('pages.services.cartservices.getlist');
   Route::get('confproduct/{pid}', 'ServicesController@Services_ProductList_Configure')
      ->name('pages.services.cartservices.configure');
   Route::post('whoisdomainchecker', 'ServicesController@Service_ProductList_DomainChecker')
      ->name('domaincheck.json');
   Route::post('domainstatus', 'ServicesController@Service_ProductList_DomainStatus')
      ->name('domainstatus.json');
   Route::post('confproduct/orderpost/{id}', 'ServicesController@Service_Order_Post')
      ->name('postDataOrder');
   Route::get('confproduct/orderget/{id}', 'ServicesController@Service_OrderSummary')
      ->name('pages.services.order.config');
   Route::post('configProductOption', 'ServicesController@commandFunction')
      ->name('commandAJAX');
   Route::get('viewcart/{id}', 'ServicesController@Services_ViewCart')
      ->name('pages.services.order.viewchart')->middleware('auth:web');
   Route::post('checkout/{id}', 'ServicesController@Services_CheckOut')
      ->name('checkout');
   Route::post('checkoutapi/{id}', 'ServicesController@Checkout_API')
      ->name('checkoutAPI');
   Route::get('/outofstock/{id}', 'ServicesController@Services_OutOfStock')
      ->name('outofstock');
   Route::get('viewaddons', 'ServicesController@Services_ViewAddons')
      ->name('pages.services.viewaddons.index');
   Route::get('/services/services/cpanel/login/{id}', [
       'as' => 'pages.services.myservices.cpanellogin',
       'uses' => 'ServicesController@Services_LoginCpanel'
   ]);
});

Route::prefix('domain')->namespace('Client')->group(function () {
   Route::get('mydomains', 'DomainsController@Domains_MyDomains')
      ->name('pages.domain.mydomains.index')->middleware('auth:web');
   //Auction
   Route::get('lelangdomains', 'DomainsController@Domains_LelangDomain')
      ->name('pages.domain.lelangdomains.index')->middleware('auth:web');
   Route::post('/lelangdomains/action', 'DomainsController@Domains_LelangDomainAction')
      ->name('pages.domain.lelangdomains.action');
   //Sell Domain
   Route::get('selldomains', 'DomainsController@Domains_SellDomain')
      ->name('pages.domain.selldomains.index')->middleware('auth:web');
   Route::post('/selldomains/action', 'DomainsController@Domains_SellDomainAction')
      ->name('pages.domain.selldomains.action');

   Route::get('transferdomain', 'DomainsController@Domains_TransferDomain')
      ->name('pages.domain.transferdomain.index')->middleware('auth:web');
   Route::get('generatecertificate', 'DomainsController@Generate_Domain_Certificate')
      ->name('generate.domain.certificate');
   Route::get('dt_myDomains', 'DomainsController@dt_myDomains')
      ->name('dt_myDomains')->middleware('auth:web');;
   Route::match(['get', 'post'], 'domaindetails/{id}', 'DomainsController@Domains_DetailDomain')
      ->name('pages.domain.mydomains.domaindetails');

   Route::match(['get', 'post'], '/', 'DomainsController@Domains_DetailDomain2')
      ->name('pages.domain.mydomains.domaindetails2');
   Route::post('update/nameservers', 'DomainsController@Domain_Nameservers_Update')
      ->name('pages.domain.update.nameservers');
   Route::post('update/autorenew', 'DomainsController@Domain_AutoRenew_Update')
      ->name('pages.domain.update.autorenew');
   Route::post('getDomainStat.json', 'DomainsController@DomainStatJson')
      ->name('domainstatjson');
   Route::post('setupdomain', 'DomainsController@Domain_SetupTransfer')
      ->name('pages.domain.domain.setup');
   // Host Child Nameservers
   // Last Updated : 06/11/2024 
   // Author : Anggi
   Route::get('mydomains/childnameservers', 'DomainsController@Domain_Childnameservers')
   ->name('pages.domain.mydomains.childnameservers');
   Route::post('mydomains/childnameservers/get', 'DomainsController@Domain_Childnameservers_Get')
   ->name('pages.domain.mydomains.childnameservers.get');
   Route::post('mydomains/childnameservers/create', 'DomainsController@Domain_Childnameservers_Create')
   ->name('pages.domain.mydomains.childnameservers.create');
   Route::post('mydomains/childnameservers/update', 'DomainsController@Domain_Childnameservers_Update')
   ->name('pages.domain.mydomains.childnameservers.update');
   Route::post('mydomains/childnameservers/delete', 'DomainsController@Domain_Childnameservers_Delete')
   ->name('pages.domain.mydomains.childnameservers.delete');
   
});

Route::prefix('billinginfo')->namespace('Client')->group(function () {
   Route::get('myinvoices', 'BillingController@Billing_MyInvoices')
      ->name('pages.billing.myinvoices.index')->middleware('auth:web');
   Route::get('dt_myInvoices', 'BillingController@dt_myInvoices')
      ->name('dt_myInvoices');
   Route::get('viewinvoice/pdf/{id}', 'BillingController@Billing_ViewInvoice')
      ->name('pages.services.mydomains.viewinvoice')->middleware('auth:web');
   Route::match(['get', 'post'], 'viewinvoice/web/{id}', 'BillingController@Billing_ViewInvoiceWeb')
      ->name('pages.services.mydomains.viewinvoiceweb')->middleware('auth:web');
   Route::post('viewinvoice/applycredit/{id}', 'BillingController@BillingInvoice_ApplyCredit')
      ->name('pages.services.mydomains.viewinvoiceweb.applycredit')->middleware('auth:web');
   Route::post('updatepayment', 'BillingController@BillingInvoice_UpdatePayment')
      ->name('pages.services.mydomains.viewinvoiceweb.updatepayment');
   Route::get('manualrequest', 'BillingController@Billing_ManualRequest')
      ->name('pages.billing.manualbillingrequest.index')->middleware('auth:web');
   Route::get('taxinvoice', 'BillingController@Billing_TaxRequest')
      ->name('pages.billing.requesttaxinvoice.index')->middleware('auth:web');
   Route::get('refund', 'BillingController@Billing_Refund')
      ->name('pages.billing.refund.index')->middleware('auth:web');
   Route::get('offerforme', 'BillingController@Billing_Offer')
      ->name('pages.billing.offer.index')->middleware('auth:web');
   Route::get('/loadinvimage', function () {
      return Theme::asset('assets/images/WHMCEPS-dark.png');
   })->name('invoiceimage.url');
});

Route::prefix('support')->namespace('Client')->group(function () {
   Route::get('openticket', 'SupportController@Support_OpenTicket')
       ->name('pages.support.openticket.index')->middleware('auth:web');
   Route::match(['get', 'post'], 'openticket', 'SupportController@Support_OpenTicket')
      ->name('pages.support.openticket.index')->middleware('auth:web');
   Route::get('submitticket/{id}', 'SupportController@Support_SubmitTicket')
      ->name('pages.support.openticket.submitticket')->middleware('auth:web');
   Route::post('postticket', 'SupportController@Support_PostTicket')
      ->name('pages.support.openticket.postticket')->middleware('auth:web');
   Route::get('mytickets', 'SupportController@Support_MyTickets')
      ->name('pages.support.mytickets.index')->middleware('auth:web');
   Route::get('dt_myTickets', 'SupportController@dt_myTickets')
      ->name('dt_myTickets');
   Route::get('viewtickets/{id}', 'SupportController@Support_TicketDetails')
       ->name('pages.support.mytickets.ticketdetails')->middleware('auth:web');
   Route::match(['get', 'post'], 'viewtickets', 'SupportController@Support_TicketDetails')
      ->name('pages.support.mytickets.ticketdetails')->middleware('auth:web');
   Route::get('networkstatus', 'SupportController@Support_NetworkStatus')
      ->name('pages.support.networkstatus.index');
});

Route::prefix('affiliate')->middleware('auth:web')->namespace('Client')->group(function () {
   Route::get('/', 'AffiliateController@Affiliate')
      ->name('pages.affiliate.index');
   Route::get('dtAffiliate', 'AffiliateController@dtAffiliate')
      ->name('dtAffiliate.json');
   Route::post('activateaccount', 'AffiliateController@ActivateAffiliateAccount')
      ->name('pages.affiliate.activateaccount');
   Route::post('withdrawrequest', 'AffiliateController@WithdrawRequest')
      ->name('pages.affiliate.withdrawrequest');
});

Route::prefix("modules/gateways/callback")->group(function () {
   Route::match(['get', 'post'], "{controller}/{method}", "CallbackController@register")->name('modules.gateways.callback');
});

Route::fallback(function () {
    return response()->view('error.404', [], 404);
});


use App\Http\Controllers\Callback\Nicepay;

Route::prefix('api/nicepay')->group(function () {
    Route::post('/va', [Nicepay::class, 'va'])->name('nicepay.va');
    Route::post('/getva', [Nicepay::class, 'va'])->name('nicepay.va');
    Route::post('/getVa', [Nicepay::class, 'va'])->name('nicepay.va');
    Route::post('/va-created', [Nicepay::class, 'vacreated'])->name('nicepay.vacreated');
    Route::post('/ewallet', [Nicepay::class, 'ewallet'])->name('nicepay.ewallet');
    Route::post('/retail', [Nicepay::class, 'retail'])->name('nicepay.retail');
});

use App\Http\Controllers\Callback\NicepayController;

Route::prefix('api/nicepaynew')->group(function () {
    Route::post('/va', [NicepayController::class, 'va'])->name('nicepay.va');
    Route::post('/va-created', [NicepayController::class, 'vacreated'])->name('nicepay.vacreated');
    Route::post('/ewallet', [NicepayController::class, 'ewallet'])->name('nicepay.ewallet');
    Route::post('/retail', [NicepayController::class, 'retail'])->name('nicepay.retail');
});

Route::prefix('api/testing')->group(function () {
   return("testing");
});

// API Public (tanpa auth) - riwayat kerja staff
Route::prefix('api/')->group(function () {
    Route::get('staffgssl', [\App\Http\Controllers\API\StaffWorkLogController::class, 'staffGssl']);
});