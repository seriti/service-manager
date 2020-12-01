<?php
namespace App\Service;

use Psr\Container\ContainerInterface;
use App\Service\ItemUnits;

class ItemUnitsController
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke($request, $response, $args)
    {
        $table_name = TABLE_PREFIX.'item_units';
        $table = new ItemUnits($this->container->mysql,$this->container,$table_name);

        $table->setup();
        $html = $table->processTable();

        $template['html'] = $html;
        $template['title'] = MODULE_LOGO.'All Service item units';
        return $this->container->view->render($response,'admin.php',$template);
    }
}
