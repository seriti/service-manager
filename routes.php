<?php  
/*
NB: This is not stand alone code and is intended to be used within "seriti/slim3-skeleton" framework
The code snippet below is for use within an existing src/routes.php file within this framework
copy the "/service" group into the existing "/admin" group within existing "src/routes.php" file 
*/

$app->group('/admin', function () {

    $this->group('/service', function () {
        $this->post('/ajax', \App\Service\Ajax::class);

        $this->any('/dashboard', \App\Service\DashboardController::class);
        $this->any('/setup_dashboard', \App\Service\SetupDashboardController::class);
        $this->get('/setup_data', \App\Service\SetupDataController::class);
        $this->any('/report', \App\Service\ReportController::class);

        $this->any('/agent', App\Service\AgentController::class);
        $this->any('/client', App\Service\ClientController::class);
        $this->any('/client_file', App\Service\ClientFileController::class);
        $this->any('/client_image', App\Service\ClientImageController::class);
        $this->any('/client_category', App\Service\ClientCategoryController::class);
        $this->any('/client_contact', App\Service\ClientContactController::class);
        $this->any('/client_location', App\Service\ClientLocationController::class);
        $this->any('/contract_single', App\Service\ContractSingleController::class);
        $this->any('/contract_repeat', App\Service\ContractRepeatController::class);
        $this->any('/contract_file', App\Service\ContractFileController::class);
        $this->any('/contract_item', App\Service\ContractItemController::class);
        $this->any('/contract_visit', App\Service\ContractVisitController::class);

        $this->any('/diary', \App\Service\DiaryController::class);
        $this->any('/diary_visit', \App\Service\DiaryVisitController::class);
        $this->any('/diary_wizard', App\Service\DiaryWizardController::class);

        $this->any('/division', App\Service\DivisionController::class);
        $this->any('/errand_category', App\Service\ErrandCategoryController::class);
        $this->any('/location_category', App\Service\LocationCategoryController::class);
        $this->any('/pay_method', App\Service\PayMethodController::class);
        $this->any('/service_day', App\Service\ServiceDayController::class);
        $this->any('/service_errand', App\Service\ServiceErrandController::class);
        $this->any('/service_feedback', App\Service\ServiceFeedbackController::class);
        $this->any('/service_item', App\Service\ServiceItemController::class);
        $this->any('/item_units', App\Service\ItemUnitsController::class);
        $this->any('/service_price', App\Service\ServicePriceController::class);
        $this->any('/service_round', App\Service\ServiceRoundController::class);
        
        //all completed contract visits, maybe change to "invoice_visit"
        $this->any('/service_visit', App\Service\ServiceVisitController::class);
        $this->any('/invoice', App\Service\InvoiceController::class);
        $this->any('/invoice_file', App\Service\InvoiceFileController::class);
        $this->any('/invoice_item', App\Service\InvoiceItemController::class);
        $this->any('/invoice_setup', App\Service\InvoiceSetupController::class);
        $this->any('/invoice_wizard', App\Service\InvoiceWizardController::class);
        $this->any('/invoice_wizard_item', App\Service\InvoiceWizardItemController::class);
        
        $this->any('/visit_file', App\Service\VisitFileController::class);
        $this->any('/visit_category', App\Service\VisitCategoryController::class);
        $this->any('/visit_user_assist', App\Service\VisitUserAssistController::class);
        $this->any('/visit_item', App\Service\VisitItemController::class);
        
    })->add(\App\Service\Config::class);
        
})->add(\App\User\ConfigAdmin::class);



