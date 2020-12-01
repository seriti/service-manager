<?php
namespace App\Service;

use Psr\Container\ContainerInterface;
use App\Service\LocationCategory;

class LocationCategoryController
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke($request, $response, $args)
    {
        $table_name = TABLE_PREFIX.'location_category';
        $table = new LocationCategory($this->container->mysql,$this->container,$table_name);

        $table->setup();
        $html = $table->processTable();

        $template['html'] = $html;
        $template['title'] = MODULE_LOGO.'All Location categorys';
        return $this->container->view->render($response,'admin.php',$template);
    }
}
