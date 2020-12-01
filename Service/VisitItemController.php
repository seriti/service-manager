<?php
namespace App\Service;

use Psr\Container\ContainerInterface;
use App\Service\VisitItem;

class VisitItemController
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke($request, $response, $args)
    {
        $table_name = TABLE_PREFIX.'visit_item';
        $table = new VisitItem($this->container->mysql,$this->container,$table_name);

        $table->setup();
        $html = $table->processTable();

        $template['html'] = $html;
        $template['title'] = 'Service items in ADDITION to contract items';
        return $this->container->view->render($response,'admin_popup.php',$template);
    }
}
