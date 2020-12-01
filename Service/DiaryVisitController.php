<?php
namespace App\Service;

use Psr\Container\ContainerInterface;
use App\Service\DiaryVisit;

class DiaryVisitController
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke($request, $response, $args)
    {
        $table_name = TABLE_PREFIX.'contract_visit';
        $diary = new DiaryVisit($this->container->mysql,$this->container,$table_name);

        $diary->setup();
        $html = $diary->processRecord();

        $template['html'] = $html;
        //$template['title'] = MODULE_LOGO.'All Client contacts';
        return $this->container->view->render($response,'admin_popup.php',$template);
    }
}
