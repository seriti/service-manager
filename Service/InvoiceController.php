<?php
namespace App\Service;

use Psr\Container\ContainerInterface;
use App\Service\Invoice;

class InvoiceController
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke($request, $response, $args)
    {
        $table_name = TABLE_PREFIX.'contract_invoice';
        $table = new Invoice($this->container->mysql,$this->container,$table_name);

        $table->setup();
        $html = $table->processTable();

        $template['html'] = $html;
        $template['title'] = MODULE_LOGO.'All contract invoices';
                
        return $this->container->view->render($response,'admin.php',$template);
    }
}
