<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoutingController;
use App\Http\Controllers\Auth\LoginController;

use App\Http\Controllers\ApplicantController;
use App\Http\Controllers\HeadOfficeController;
use App\Http\Controllers\ModuleNotesController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\IPAddressController;
use App\Http\Controllers\CrmController;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\PostcodeController;
use App\Http\Controllers\CommunicationController;
use App\Http\Controllers\QualityController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\FreePBXController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportController;
use SebastianBergmann\CodeCoverage\Report\Html\Dashboard;
use App\Http\Middleware\IPAddress;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

require __DIR__ . '/auth.php';

// Route::middleware(IPAddress::class)->group(function () {
    Route::get('/', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'login']);
// });

Route::get('logout', [LoginController::class, 'logout'])->name('logout');
Route::get('message_receive', [CommunicationController::class, 'messageReceive']); /**This route is using to retrieve messages from openVox */
Route::group(['prefix' => '/', 'middleware' => 'auth'], function () {
    Route::get('', [RoutingController::class, 'index'])->name('root');
    Route::get('{first}/{second}/{third}', [RoutingController::class, 'thirdLevel'])->name('third');
    Route::get('{first}/{second}', [RoutingController::class, 'secondLevel'])->name('second');

    Route::group(['prefix' => 'dashboard'], function () {
        Route::get('', [DashboardController::class, 'index'])->name('dashboard.index');
    });
    Route::get('getUsersForDashboard', [DashboardController::class, 'getUsersForDashboard'])->name('getUsersForDashboard');
    Route::POST('getUserStatistics', [DashboardController::class, 'getUserStatistics'])->name('getUserStatistics');
    Route::get('/get-weekly-sales', [DashboardController::class, 'getWeeklySales']);
    Route::get('/get-sales-analytic', [DashboardController::class, 'getSalesAnalytic']);
    Route::get('/unread-messages', [DashboardController::class, 'getUnreadMessages'])->name('unread-messages');

    Route::group(['prefix' => 'applicants'], function () {
        Route::get('', [ApplicantController::class, 'index'])->name('applicants.list');
        Route::get('create', [ApplicantController::class, 'create'])->name('applicants.create');
        Route::post('store', [ApplicantController::class, 'store'])->name('applicants.store');
        Route::get('edit', [ApplicantController::class, 'edit'])->name('applicants.edit');
        Route::post('update', [ApplicantController::class, 'update'])->name('applicants.update');
        Route::post('uploadCv', [ApplicantController::class, 'uploadCv'])->name('applicants.uploadCv');
        Route::post('crmuploadCv', [ApplicantController::class, 'crmuploadCv'])->name('applicants.crmuploadCv');
        Route::get('history', [ApplicantController::class, 'history'])->name('applicants.history');
    });
    Route::get('/applicants/available-no-jobs/{id}/{radius?}', [ApplicantController::class, 'availableNoJobsIndex'])->name('applicants.available_no_job');
    Route::get('/applicants/available-jobs/{id}/{radius?}', [ApplicantController::class, 'availableJobsIndex'])->name('applicantsAvailableJobs');
    Route::get('getAvailableJobs', [ApplicantController::class, 'getAvailableJobs'])->name('getAvailableJobs');
    Route::get('getAvailableNoJobs', [ApplicantController::class, 'getAvailableNoJobs'])->name('getAvailableNoJobs');
    Route::get('applicantsExport', [ApplicantController::class, 'export'])->name('applicantsExport');
    Route::post('changeStatus', [ApplicantController::class, 'changeStatus'])->name('changeStatus');
    Route::get('getApplicantsAjaxRequest', [ApplicantController::class, 'getApplicantsAjaxRequest'])->name('getApplicantsAjaxRequest');
    Route::get('getJobTitlesByCategory', [ApplicantController::class, 'getJobTitlesByCategory'])->name('getJobTitlesByCategory');
    Route::post('storeShortNotes', [ApplicantController::class, 'storeShortNotes'])->name('storeShortNotes');
    Route::post('markApplicantNoNursingHome', [ApplicantController::class, 'markApplicantNoNursingHome'])->name('markApplicantNoNursingHome');
    Route::post('sendCVtoQuality', [ApplicantController::class, 'sendCVtoQuality'])->name('sendCVtoQuality');
    Route::get('getApplicantHistoryAjaxRequest', [ApplicantController::class, 'getApplicantHistoryAjaxRequest'])
    ->name('getApplicantHistoryAjaxRequest');
    Route::get('getApplicantNoNursingHomeNotes', [ApplicantController::class, 'getApplicantNoNursingHomeNotes'])
    ->name('getApplicantNoNursingHomeNotes');
    Route::get('getApplicanCallbackNotes', [ApplicantController::class, 'getApplicanCallbackNotes'])
    ->name('getApplicanCallbackNotes');

    Route::group(['prefix' => 'postcode-finder'], function () {
        Route::get('', [PostcodeController::class, 'index'])->name('postcode-finder.index');
    });
    Route::post('getPostcodeResults', [PostcodeController::class, 'getPostcodeResults'])->name('getPostcodeResults');

    Route::group(['prefix' => 'head-offices'], function () {
        Route::get('', [HeadOfficeController::class, 'index'])->name('head-offices.list');
        Route::get('create', [HeadOfficeController::class, 'create'])->name('head-offices.create');
        Route::post('store', [HeadOfficeController::class, 'store'])->name('head-offices.store');
        Route::get('edit', [HeadOfficeController::class, 'edit'])->name('head-offices.edit');
        Route::post('update', [HeadOfficeController::class, 'update'])->name('head-offices.update');
        Route::get('{id}', [HeadOfficeController::class, 'officeDetails'])->name('head-offices.details');
    });
    Route::get('officesExport', [HeadOfficeController::class, 'export'])->name('officesExport');
    Route::get('getHeadOffices', [HeadOfficeController::class, 'getHeadOffices'])->name('getHeadOffices');
    Route::post('storeHeadOfficeShortNotes', [HeadOfficeController::class, 'storeHeadOfficeShortNotes'])->name('storeHeadOfficeShortNotes');
    Route::get('getModuleContacts', [HeadOfficeController::class, 'getModuleContacts'])->name('getModuleContacts');

    Route::group(['prefix' => 'units'], function () {
        Route::get('', [UnitController::class, 'index'])->name('units.list');
        Route::get('create', [UnitController::class, 'create'])->name('units.create');
        Route::post('store', [UnitController::class, 'store'])->name('units.store');
        Route::get('edit', [UnitController::class, 'edit'])->name('units.edit');
        Route::post('update', [UnitController::class, 'update'])->name('units.update');
        Route::get('{id}', [UnitController::class, 'unitDetails'])->name('units.details');
    });
    Route::get('unitsExport', [UnitController::class, 'export'])->name('unitsExport');
    Route::get('getUnits', [UnitController::class, 'getUnits'])->name('getUnits');
    Route::post('storeUnitShortNotes', [UnitController::class, 'storeUnitShortNotes'])->name('storeUnitShortNotes');

    Route::group(['prefix' => 'sales'], function () {
        Route::get('', [SaleController::class, 'index'])->name('sales.list');
        Route::get('create', [SaleController::class, 'create'])->name('sales.create');
        Route::post('store', [SaleController::class, 'store'])->name('sales.store');
        Route::get('edit', [SaleController::class, 'edit'])->name('sales.edit');
        Route::post('update', [SaleController::class, 'update'])->name('sales.update');
        Route::get('history', [SaleController::class, 'saleHistoryIndex'])->name('sales.history');
        Route::get('direct', [SaleController::class, 'directSaleIndex'])->name('sales.direct');
        Route::get('open', [SaleController::class, 'openSaleIndex'])->name('sales.open');
        Route::get('closed', [SaleController::class, 'closeSaleIndex'])->name('sales.closed');
        Route::get('rejected', [SaleController::class, 'rejectedSaleIndex'])->name('sales.rejected');
        Route::get('on-hold', [SaleController::class, 'onHoldSaleIndex'])->name('sales.on-hold');
        Route::get('pending-on-hold', [SaleController::class, 'pendingOnHoldSaleIndex'])->name('sales.pending-on-hold');
    });
    Route::get('salesExport', [SaleController::class, 'export'])->name('salesExport');
    Route::get('/sales/fetch-applicants-by-radius/{id}/{radius?}', [SaleController::class, 'fetchApplicantsWithinSaleRadiusIndex'])
    ->name('fetchApplicantsWithinSaleRadiusIndex');
    Route::get('getSales', [SaleController::class, 'getSales'])->name('getSales');
    Route::get('getDirectSales', [SaleController::class, 'getDirectSales'])->name('getDirectSales');
    Route::get('getRejectedSales', [SaleController::class, 'getRejectedSales'])->name('getRejectedSales');
    Route::get('getClosedSales', [SaleController::class, 'getClosedSales'])->name('getClosedSales');
    Route::get('getOpenSales', [SaleController::class, 'getOpenSales'])->name('getOpenSales');
    Route::get('pendingOnHoldSales', [SaleController::class, 'pendingOnHoldSales'])->name('pendingOnHoldSales');
    Route::get('getOnHoldSales', [SaleController::class, 'getOnHoldSales'])->name('getOnHoldSales');
    Route::delete('delete-document', [SaleController::class, 'removeDocument'])->name('sales.remove_document');
    Route::get('getOfficeUnits', [SaleController::class, 'getOfficeUnits'])->name('getOfficeUnits');
    Route::post('storeSaleNotes', [SaleController::class, 'storeSaleNotes'])->name('storeSaleNotes');
    Route::post('changeSaleStatus', [SaleController::class, 'changeSaleStatus'])->name('changeSaleStatus');
    Route::get('getApplicantsBySaleRadius', [SaleController::class, 'getApplicantsBySaleRadius'])->name('getApplicantsBySaleRadius');
    Route::get('changeSaleHoldStatus', [SaleController::class, 'changeSaleHoldStatus'])->name('changeSaleHoldStatus');
    Route::get('getSaleDocuments', [SaleController::class, 'getSaleDocuments'])->name('getSaleDocuments');
    Route::get('getSaleHistoryAjaxRequest', [SaleController::class, 'getSaleHistoryAjaxRequest'])->name('getSaleHistoryAjaxRequest');

    Route::group(['prefix' => 'resources'], function () {
        Route::get('direct', [ResourceController::class, 'directIndex'])->name('resources.directIndex');
        Route::get('indirect', [ResourceController::class, 'indirectIndex'])->name('resources.indirectIndex');
        Route::get('rejected-applicants', [ResourceController::class, 'rejectedApplicantsIndex'])->name('resources.rejectedIndex');
        Route::get('blocked-applicants', [ResourceController::class, 'blockedApplicantsIndex'])->name('resources.blockedApplicantsIndex');
        Route::get('crm-paid-applicants', [ResourceController::class, 'crmPaidIndex'])->name('resources.crmPaidIndex');
        Route::get('no-job-applicants', [ResourceController::class, 'noJobIndex'])->name('resources.noJobIndex');
        Route::get('category-wise-applicants', [ResourceController::class, 'categoryWiseApplicantIndex'])->name('resources.categoryWiseApplicantIndex');
        Route::post('revertBlockedApplicant', [ResourceController::class, 'revertBlockedApplicant'])->name('resources.revertBlockedApplicant');
        Route::post('revertNoJobApplicant', [ResourceController::class, 'revertNoJobApplicant'])->name('resources.revertNoJobApplicant');
    });
    Route::post('markAsNursingHomeExp', [ResourceController::class, 'markAsNursingHomeExp'])->name('markAsNursingHomeExp');
    Route::post('markAsNoNursingHomeExp', [ResourceController::class, 'markAsNoNursingHomeExp'])->name('markAsNoNursingHomeExp');
    Route::get('getResourcesDirectSales', [ResourceController::class, 'getResourcesDirectSales'])->name('getResourcesDirectSales');
    Route::get('getResourcesIndirectApplicants', [ResourceController::class, 'getResourcesIndirectApplicants'])->name('getResourcesIndirectApplicants');
    Route::get('getResourcesRejectedApplicants', [ResourceController::class, 'getResourcesRejectedApplicants'])->name('getResourcesRejectedApplicants');
    Route::get('getApplicantHistorybyStatus', [ResourceController::class, 'getApplicantHistorybyStatus'])->name('getApplicantHistorybyStatus');
    Route::get('getResourcesBlockedApplicants', [ResourceController::class, 'getResourcesBlockedApplicants'])->name('getResourcesBlockedApplicants');
    Route::get('getResourcesPaidApplicants', [ResourceController::class, 'getResourcesPaidApplicants'])->name('getResourcesPaidApplicants');
    Route::get('getResourcesNoJobApplicants', [ResourceController::class, 'getResourcesNoJobApplicants'])->name('getResourcesNoJobApplicants');
    Route::get('getResourcesCategoryWised', [ResourceController::class, 'getResourcesCategoryWised'])->name('getResourcesCategoryWised');
    Route::post('markApplicantNotInterestedOnSale', [ResourceController::class, 'markApplicantNotInterestedOnSale'])->name('markApplicantNotInterestedOnSale');
    Route::post('markApplicantCallback', [ResourceController::class, 'markApplicantCallback'])->name('markApplicantCallback');
	Route::post('exportDirectApplicantsEmails', [ResourceController::class, 'exportDirectApplicantsEmails'])->name('exportDirectApplicantsEmails');

    Route::group(['prefix' => 'emails'], function () {
        Route::get('compose-email', [CommunicationController::class, 'index'])->name('emails.inbox');
        Route::get('sent-emails', [CommunicationController::class, 'sentEmails'])->name('emails.sent_emails');
    });
    Route::get('send-email-to-applicant', [CommunicationController::class, 'sendEmailsToApplicants'])->name('emails.sendemailstoapplicants');
    Route::post('saveEmailsForApplicants', [CommunicationController::class, 'saveEmailsForApplicants'])->name('emails.saveEmailsForApplicants');
    Route::post('saveComposedEmail', [CommunicationController::class, 'saveComposedEmail'])->name('emails.saveComposedEmail');
    Route::get('getSentEmailsAjaxRequest', [CommunicationController::class, 'getSentEmailsAjaxRequest'])->name('getSentEmailsAjaxRequest');
    Route::post('sendMessageToApplicant', [CommunicationController::class, 'sendMessageToApplicant'])->name('sendMessageToApplicant');
    
    Route::group(['prefix' => 'messages'], function () {
        Route::get('', [CommunicationController::class, 'Messagesindex'])->name('messages.index');
        Route::get('write-message', [CommunicationController::class, 'writeMessageindex'])->name('messages.write');
    });

    Route::post('/getChatBoxMessages', [CommunicationController::class, 'getChatBoxMessages'])->name('getChatBoxMessages');
    Route::post('/sendChatBoxMsg', [CommunicationController::class, 'sendChatBoxMsg'])->name('sendChatBoxMsg');
    Route::get('getUserChats', [CommunicationController::class, 'getUserChats'])->name('getUserChats');
    Route::get('getApplicantsForMessage', [CommunicationController::class, 'getApplicantsForMessage'])->name('getApplicantsForMessage');

    Route::group(['prefix' => 'users'], function () {
        Route::get('', [UserController::class, 'index'])->name('users.list');
        Route::get('create', [UserController::class, 'create'])->name('users.create');
        Route::post('store', [UserController::class, 'store'])->name('users.store');
        Route::get('edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('update', [UserController::class, 'update'])->name('users.update');
        Route::get('{id}', [UserController::class, 'userDetails'])->name('users.details');
        Route::get('activity-logs', [UserController::class, 'activityLogIndex'])->name('users.activity_log');
    });
    Route::get('usersExport', [UserController::class, 'export'])->name('usersExport');
    Route::get('getUsers', [UserController::class, 'getUsers'])->name('getUsers');
    Route::get('getUserActivityLogs', [UserController::class, 'getUserActivityLogs'])->name('getUserActivityLogs');

    Route::get('import', [ImportController::class, 'importIndex'])->name('import.index');
    Route::post('users/import', [ImportController::class, 'usersImport'])->name('users.import');
    Route::post('applicants/import', [ImportController::class, 'applicantsImport'])->name('applicants.import');
    Route::post('offices/import', [ImportController::class, 'officesImport'])->name('offices.import');
    Route::post('units/import', [ImportController::class, 'unitsImport'])->name('units.import');
    Route::post('sales/import', [ImportController::class, 'salesImport'])->name('sales.import');
    Route::post('applicant-message/import', [ImportController::class, 'messagesImport'])->name('messages.import');
    Route::post('applicant-notes/import', [ImportController::class, 'applicantNotesImport'])->name('applicantNotes.import');
    Route::post('applicant-pivot-sales/import', [ImportController::class, 'applicantPivotSaleImport'])->name('applicantPivotSale.import');
    Route::post('notes-pivot-sales/import', [ImportController::class, 'notesRangeForPivotSaleImport'])->name('notesRangeForPivotSale.import');
    Route::post('audits/import', [ImportController::class, 'auditsImport'])->name('audits.import');
    Route::post('crm-notes/import', [ImportController::class, 'crmNotesImport'])->name('crmNotes.import');
    Route::post('crm-rejected-cv/import', [ImportController::class, 'crmRejectedCvImport'])->name('crmRejectedCv.import');
    Route::post('cv-notes/import', [ImportController::class, 'cvNotesImport'])->name('cvNotes.import');
    Route::post('history-data/import', [ImportController::class, 'historyImport'])->name('history.import');//
    Route::post('interview/import', [ImportController::class, 'interviewImport'])->name('interview.import');
    Route::post('ipAddress/import', [ImportController::class, 'ipAddressImport'])->name('ipAddress.import');
    Route::post('module-notes-data/import', [ImportController::class, 'moduleNotesImport'])->name('moduleNotes.import');
    Route::post('quality-notes/import', [ImportController::class, 'qualityNotesImport'])->name('qualityNotes.import');
    Route::post('regions/import', [ImportController::class, 'regionsImport'])->name('regions.import');
    Route::post('revert-stage/import', [ImportController::class, 'revertStageImport'])->name('revertStage.import');
    Route::post('sale-documents/import', [ImportController::class, 'saleDocumentsImport'])->name('saleDocuments.import');
    Route::post('sale-notes/import', [ImportController::class, 'saleNotesImport'])->name('saleNotes.import');
    Route::post('sent-emails-data/import', [ImportController::class, 'sentEmailDataImport'])->name('sentEmailData.import');

    Route::group(['prefix' => 'reports'], function () {
        Route::get('users-login-report', [UserController::class, 'userLogin'])->name('reports.usersLoginReport');
        Route::get('login-history', [UserController::class, 'userLoginHistoryIndex'])->name('reports.userLoginHistory');
    });
    Route::get('getUsersLoginReport', [UserController::class, 'getUsersLoginReport'])->name('getUsersLoginReport');
    Route::get('getUserLoginHistory', [UserController::class, 'getUserLoginHistory'])->name('getUserLoginHistory');
    Route::post('changeUserStatus', [UserController::class, 'changeUserStatus'])->name('changeUserStatus');

    Route::group(['prefix' => 'roles'], function () {
        Route::get('', [RoleController::class, 'index'])->name('roles.list');
        Route::get('create', [RoleController::class, 'create'])->name('roles.create');
        Route::post('store', [RoleController::class, 'store'])->name('roles.store');
        Route::get('edit', [RoleController::class, 'edit'])->name('roles.edit');
        Route::put('update', [RoleController::class, 'update'])->name('roles.update');
        Route::get('{id}', [RoleController::class, 'details'])->name('roles.details');
    });
    Route::get('getRoles', [RoleController::class, 'getRoles'])->name('getRoles');
    Route::get('getPermissions', [RoleController::class, 'getPermissions'])->name('getPermissions');
    Route::post('permissions/list', [RoleController::class, 'permissionIndex'])->name('permissions.list');
    Route::post('permissions/store', [RoleController::class, 'permissionStore'])->name('permissions.store');
    Route::put('permissions/update', [RoleController::class, 'permissionUpdate'])->name('permissions.update');

    // job categories
    Route::get('getJobCategories', [SettingController::class, 'getJobCategories'])->name('getJobCategories');
    Route::post('job-categories/list', [SettingController::class, 'jobCategoriesIndex'])->name('job-categories.list');
    Route::post('job-categories/store', [SettingController::class, 'jobCategoriesStore'])->name('job-categories.store');
    Route::put('job-categories/update', [SettingController::class, 'jobCategoriesUpdate'])->name('job-categories.update');
    
    // job titles
    Route::get('getJobTitles', [SettingController::class, 'getJobTitles'])->name('getJobTitles');
    Route::get('job-titles/list', [SettingController::class, 'jobTitlesIndex'])->name('job-titles.list');
    Route::post('job-titles/store', [SettingController::class, 'jobTitlesStore'])->name('job-titles.store');
    Route::put('job-titles/update', [SettingController::class, 'jobTitlesUpdate'])->name('job-titles.update');
    
    // job sources
    Route::get('getJobSources', [SettingController::class, 'getJobSources'])->name('getJobSources');
    Route::post('job-sources/list', [SettingController::class, 'jobSourceIndex'])->name('job-sources.list');
    Route::post('job-sources/store', [SettingController::class, 'jobSourceStore'])->name('job-sources.store');
    Route::put('job-sources/update', [SettingController::class, 'jobSourceUpdate'])->name('job-sources.update');

    /**Email Templates */
    Route::post('settings/email-templates', [SettingController::class, 'emailTemplatesIndex'])->name('settings.email-templates');
    Route::get('getEmailTemplates', [SettingController::class, 'getEmailTemplates'])->name('getEmailTemplates');
    Route::post('emailEditTemplate', [SettingController::class, 'emailEditTemplate'])->name('emailEditTemplate');
    Route::post('email-templates/store', [SettingController::class, 'emailTemplatesStore'])->name('emailTemplates.store');
    Route::put('email-templates/update', [SettingController::class, 'emailTemplatesUpdate'])->name('emailTemplates.update');
    Route::post('emailTemplateDelete', [SettingController::class, 'emailTemplateDelete'])->name('emailTemplates.delete');

    /**SMS Templates */
    Route::post('settings/sms-templates', [SettingController::class, 'smsTemplatesIndex'])->name('settings.sms-templates');
    Route::get('getSmsTemplates', [SettingController::class, 'getSmsTemplates'])->name('getSmsTemplates');
    Route::post('smsEditTemplate', [SettingController::class, 'smsEditTemplate'])->name('smsEditTemplate');
    Route::post('sms-templates/store', [SettingController::class, 'smsTemplatesStore'])->name('smsTemplates.store');
    Route::put('sms-templates/update', [SettingController::class, 'smsTemplatesUpdate'])->name('smsTemplates.update');
    Route::post('smsTemplateDelete', [SettingController::class, 'smsTemplateDelete'])->name('smsTemplates.delete');

    /** crm */ 
    Route::group(['prefix' => 'crm'], function () {
        Route::get('', [CrmController::class, 'index'])->name('crm.list');
        Route::get('{id}', [CrmController::class, 'changeStatus'])->name('crm.changeStatus');
        Route::post('notes-history', [CrmController::class, 'crmNotesHistoryIndex'])->name('crmNotesHistoryIndex');
    });
    Route::get('getCrmApplicantsAjaxRequest', [CrmController::class, 'getCrmApplicantsAjaxRequest'])->name('getCrmApplicantsAjaxRequest');
    Route::get('getApplicantCrmNotesHistoryAjaxRequest', [CrmController::class, 'getApplicantCrmNotesHistoryAjaxRequest'])->name('getApplicantCrmNotesHistoryAjaxRequest');
    Route::get('getApplicantCrmNotes', [CrmController::class, 'getApplicantCrmNotes'])->name('getApplicantCrmNotes');
	Route::get('openToPaidApplicants', [CrmController::class, 'crmOpenToPaidApplicants'])->name('openToPaidApplicants');

    /** CRM Sent CV */
    Route::post('updateCrmNotes', [CrmController::class, 'updateCrmNotes'])->name('updateCrmNotes');
    Route::post('crmSendRequest', [CrmController::class, 'crmSendRequest'])->name('crmSendRequest');
    Route::post('crmSendRejectedCv', [CrmController::class, 'crmSendRejectedCv'])->name('crmSendRejectedCv');
    Route::post('crmRevertInQuality', [CrmController::class, 'crmRevertInQuality'])->name('crmRevertInQuality');
    
    /** CRM Sent No Job */
    Route::post('updateCrmNoJobNotes', [CrmController::class, 'updateCrmNoJobNotes'])->name('updateCrmNoJobNotes');
    Route::post('crmSendNoJobRequest', [CrmController::class, 'crmSendNoJobRequest'])->name('crmSendNoJobRequest');
    Route::post('crmSendNoJobToRejectedCv', [CrmController::class, 'crmSendNoJobToRejectedCv'])->name('crmSendNoJobToRejectedCv');
    Route::post('crmSentCvNoJobRevertInQuality', [CrmController::class, 'crmSentCvNoJobRevertInQuality'])->name('crmSentCvNoJobRevertInQuality');
    
    /** CRM Rejected CV */
    Route::post('crmRevertRejectedCvToSentCv', [CrmController::class, 'crmRevertRejectedCvToSentCv'])->name('crmRevertRejectedCvToSentCv');
    Route::post('crmRevertRejectedCvToQuality', [CrmController::class, 'crmRevertRejectedCvToQuality'])->name('crmRevertRejectedCvToQuality');

    /** CRM Request */
    Route::post('/crm/request-reject', [CrmController::class, 'crmRequestReject'])->name('crmRequestReject');
    Route::post('crmRequestConfirm', [CrmController::class, 'crmRequestConfirm'])->name('crmRequestConfirm');
    Route::post('crmRequestSave', [CrmController::class, 'crmRequestSave'])->name('crmRequestSave');
    Route::post('crmScheduleInterview', [CrmController::class, 'crmScheduleInterview'])->name('crmScheduleInterview');
    Route::post('crmRevertRequestedCvToSentCv', [CrmController::class, 'crmRevertRequestedCvToSentCv'])->name('crmRevertRequestedCvToSentCv');
    Route::post('crmRevertRequestedCvToQuality', [CrmController::class, 'crmRevertRequestedCvToQuality'])->name('crmRevertRequestedCvToQuality');
    Route::post('crmRequestedInterviewEmailToApplicant', [CrmController::class, 'crmRequestedInterviewEmailToApplicant'])->name('crmRequestedInterviewEmailToApplicant');
    /** CRM Request No Job */

    /** CRM Request Reject */
    Route::post('crmRevertRequestRejectToSentCv', [CrmController::class, 'crmRevertRequestRejectToSentCv'])->name('crmRevertRequestRejectToSentCv');
    Route::post('crmRevertRequestRejectToRequest', [CrmController::class, 'crmRevertRequestRejectToRequest'])->name('crmRevertRequestRejectToRequest');
    Route::post('crmRequestRejectToQuality', [CrmController::class, 'crmRequestRejectToQuality'])->name('crmRequestRejectToQuality');

    /** CRM Confirmation */
    Route::post('crmRevertConfirmToRequest', [CrmController::class, 'crmRevertConfirmToRequest'])->name('crmRevertConfirmToRequest');
    Route::post('crmConfirmInterviewToNotAttend', [CrmController::class, 'crmConfirmInterviewToNotAttend'])->name('crmConfirmInterviewToNotAttend');
    Route::post('crmConfirmInterviewToAttend', [CrmController::class, 'crmConfirmInterviewToAttend'])->name('crmConfirmInterviewToAttend');
    Route::post('crmConfirmInterviewToRebook', [CrmController::class, 'crmConfirmInterviewToRebook'])->name('crmConfirmInterviewToRebook');
    Route::post('crmConfirmSave', [CrmController::class, 'crmConfirmSave'])->name('crmConfirmSave');
    
    /** CRM Rebook */
    Route::post('crmRevertRebookToConfirmation', [CrmController::class, 'crmRevertRebookToConfirmation'])->name('crmRevertRebookToConfirmation');
    Route::post('crmRebookToNotAttended', [CrmController::class, 'crmRebookToNotAttended'])->name('crmRebookToNotAttended');
    Route::post('crmRebookToAttended', [CrmController::class, 'crmRebookToAttended'])->name('crmRebookToAttended');
    Route::post('crmRebookSave', [CrmController::class, 'crmRebookSave'])->name('crmRebookSave');

    /** CRM Attended */
    Route::post('crmRevertAttendedToRebook', [CrmController::class, 'crmRevertAttendedToRebook'])->name('crmRevertAttendedToRebook');
    Route::post('crmAttendedToDecline', [CrmController::class, 'crmAttendedToDecline'])->name('crmAttendedToDecline');
    Route::post('crmAttendedToStartDate', [CrmController::class, 'crmAttendedToStartDate'])->name('crmAttendedToStartDate');
    Route::post('crmAttendedSave', [CrmController::class, 'crmAttendedSave'])->name('crmAttendedSave');
    
    /** CRM Not Attended */
    Route::post('crmNotAttendedToAttended', [CrmController::class, 'crmNotAttendedToAttended'])->name('crmNotAttendedToAttended');
    Route::post('crmNotAttendedToQuality', [CrmController::class, 'crmNotAttendedToQuality'])->name('crmNotAttendedToQuality');
    
    /** CRM Decline */
    Route::post('crmRevertDeclinedToAttended', [CrmController::class, 'crmRevertDeclinedToAttended'])->name('crmRevertDeclinedToAttended');

    /** CRM Start Date */
    Route::post('crmRevertStartDateToAttended', [CrmController::class, 'crmRevertStartDateToAttended'])->name('crmRevertStartDateToAttended');
    Route::post('crmStartDateToInvoice', [CrmController::class, 'crmStartDateToInvoice'])->name('crmStartDateToInvoice');
    Route::post('crmStartDateToHold', [CrmController::class, 'crmStartDateToHold'])->name('crmStartDateToHold');
    Route::post('crmStartDateSave', [CrmController::class, 'crmStartDateSave'])->name('crmStartDateSave');
    
    /** CRM Start Date Hold*/
    Route::post('crmRevertStartDateHoldToStartDate', [CrmController::class, 'crmRevertStartDateHoldToStartDate'])->name('crmRevertStartDateHoldToStartDate');
    Route::post('crmStartDateHoldSave', [CrmController::class, 'crmStartDateHoldSave'])->name('crmStartDateHoldSave');
   
    /** CRM Invoice */
    Route::post('crmSendInvoiceToInvoiceSent', [CrmController::class, 'crmSendInvoiceToInvoiceSent'])->name('crmSendInvoiceToInvoiceSent');
    Route::post('crmRevertInvoiceToStartDate', [CrmController::class, 'crmRevertInvoiceToStartDate'])->name('crmRevertInvoiceToStartDate');
    Route::post('crmInvoiceToDispute', [CrmController::class, 'crmInvoiceToDispute'])->name('crmInvoiceToDispute');
    Route::post('crmInvoiceFinalSave', [CrmController::class, 'crmInvoiceFinalSave'])->name('crmInvoiceFinalSave');
    
    /** CRM Invoice Sent*/
    Route::post('crmInvoiceSentToPaid', [CrmController::class, 'crmInvoiceSentToPaid'])->name('crmInvoiceSentToPaid');
    Route::post('crmInvoiceSentToDispute', [CrmController::class, 'crmInvoiceSentToDispute'])->name('crmInvoiceSentToDispute');
    
    /** CRM Dispute */
    Route::post('crmRevertDisputeToInvoice', [CrmController::class, 'crmRevertDisputeToInvoice'])->name('crmRevertDisputeToInvoice');
    
    /** CRM Paid */
    Route::post('crmChangePaidStatus', [CrmController::class, 'crmChangePaidStatus'])->name('crmChangePaidStatus');

    /** regions */ 
    Route::group(['prefix' => 'regions'], function () {
        Route::get('resources', [RegionController::class, 'resourcesIndex'])->name('regions.resources');
        Route::get('sales', [RegionController::class, 'salesIndex'])->name('regions.sales');
    });
    Route::get('getApplicantsByRegions', [RegionController::class, 'getApplicantsByRegions'])->name('getApplicantsByRegions');
    Route::get('getSalesByRegions', [RegionController::class, 'getSalesByRegions'])->name('getSalesByRegions');
    
    /** Quality */ 
    Route::group(['prefix' => 'quality'], function () {
        Route::get('resources', [QualityController::class, 'resourceIndex'])->name('quality.resources');
        Route::get('sales', [QualityController::class, 'saleIndex'])->name('quality.sales');
    });
    Route::get('getResourcesByTypeAjaxRequest', [QualityController::class, 'getResourcesByTypeAjaxRequest'])->name('getResourcesByTypeAjaxRequest');
    Route::get('getSalesByTypeAjaxRequest', [QualityController::class, 'getSalesByTypeAjaxRequest'])->name('getSalesByTypeAjaxRequest');
    Route::post('clear_reject_Sale', [QualityController::class, 'clearRejectSale'])->name('clear_reject_Sale');
    Route::post('updateApplicantStatusByQuality', [QualityController::class, 'updateApplicantStatusByQuality'])->name('updateApplicantStatusByQuality');
    Route::get('getQualityNotesHistory', [QualityController::class, 'getQualityNotesHistory'])->name('getQualityNotesHistory');


    Route::group(['prefix' => 'ip-address'], function () {
        Route::get('', [IPAddressController::class, 'index'])->name('ip-address.list');
        Route::post('store', [IPAddressController::class, 'store'])->name('ip-address.store');
        Route::put('update', [IPAddressController::class, 'update'])->name('ip-address.update');
        Route::post('delete', [IPAddressController::class, 'destroy'])->name('ip-address.destroy');
    });
    Route::get('ipaddressExport', [IPAddressController::class, 'export'])->name('ipaddressExport');
    Route::get('getIPs', [IPAddressController::class, 'getIPs'])->name('getIPs');

    Route::get('settings', [SettingController::class, 'index'])->name('settings.list');
    Route::get('getSettings', [SettingController::class, 'getSettings'])->name('settings.get');
    Route::get('save-settings', [SettingController::class, ''])->name('settings.save');
    
    Route::post('save-smtp-settings', [SettingController::class, 'saveSmtpSettings'])->name('settings.smtp.save');
    Route::post('delete-smtp-settings', [SettingController::class, 'deleteSmtp'])->name('settings.smtp.delete');

    Route::post('module-notes/store', [ModuleNotesController::class, 'store'])->name('moduleNotes.store');
    Route::get('getModuleNotesHistory', [ModuleNotesController::class, 'getModuleNotesHistory'])->name('getModuleNotesHistory');
    Route::get('getModuleUpdateHistory', [ModuleNotesController::class, 'getModuleUpdateHistory'])->name('getModuleUpdateHistory');

    Route::group(['prefix' => 'freepbx-cdrs'], function () {
        Route::get('', [FreePBXController::class, 'index'])->name('freepbx-cdrs.list');
    });
    Route::get('getFreepbxAjaxRequest', [FreePBXController::class, 'getFreepbxAjaxRequest'])->name('getFreepbxAjaxRequest');


    Route::get('{any}', [RoutingController::class, 'root'])->name('any');
});
