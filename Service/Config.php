<?php 
namespace App\Service;

use Psr\Container\ContainerInterface;
use Seriti\Tools\BASE_URL;
use Seriti\Tools\SITE_NAME;

class Config
{
    
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

    }

    /**
     * Example middleware invokable class
     *
     * @param  \Psr\Http\Message\ServerRequestInterface $request  PSR7 request
     * @param  \Psr\Http\Message\ResponseInterface      $response PSR7 response
     * @param  callable                                 $next     Next middleware
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke($request, $response, $next)
    {
        
        $module = $this->container->config->get('module','service');
        $menu = $this->container->menu;
        
        define('TABLE_PREFIX',$module['table_prefix']);
        define('MODULE_ID','SERVICE');
        define('MODULE_LOGO','<span class="glyphicon glyphicon-calendar"></span> ');
        define('MODULE_PAGE',URL_CLEAN_LAST);     

        define('TAX_RATE',0.15);
                
        $setup_pages = ['division','client_category','location_category','agent','visit_category','errand_category','service_feedback',
                        'service_day','service_round','item_units','service_price','pay_method'];

        $setup_link = '';
        if(in_array(MODULE_PAGE,$setup_pages)) {
            $page = 'setup_dashboard';
            $setup_link = '<a href="setup_dashboard"> -- back to setup options --</a><br/><br/>';
        } elseif(stripos(MODULE_PAGE,'_wizard') !== false) {
            $page = str_replace('_wizard','',MODULE_PAGE);
        } else {    
            $page = MODULE_PAGE;
        }

        define('ACCESS_RANK',['GOD'=>1,'ADMIN'=>2,'USER'=>5,'VIEW'=>10]);

        $submenu_html = $menu->buildNav($module['route_list'],$page).$setup_link;
        $this->container->view->addAttribute('sub_menu',$submenu_html);
       
        $response = $next($request, $response);
        
        return $response;
    }
}