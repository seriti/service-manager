<?php
namespace App\Service;

use Psr\Container\ContainerInterface;

use Seriti\Tools\Secure;

use App\Service\InvoiceWizard;
use App\Service\Helpers;

class InvoiceWizardItemController
{
    protected $container;
        

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }


    public function __invoke($request, $response, $args)
    {
        $db = $this->container->mysql;

        $contract_id = Secure::clean('integer',$_GET['id']);
        $invoice_type = Secure::clean('basic',$_GET['type']);
        
        $html = Helpers::getInvoiceItems($db,TABLE_PREFIX,$contract_id,'HTML',$invoice_type);

        $template['html'] = $html;
        $template['title'] = MODULE_LOGO.'Invoice wizard items';
        //$template['javascript'] = $wizard->getJavascript();

        return $this->container->view->render($response,'admin_popup.php',$template);
    }
}