<?php
/*
NB: This is not stand alone code and is intended to be used within "seriti/slim3-skeleton" framework
The code snippet below is for use within an existing src/setup_app.php file within this framework
add the below code snippet to the end of existing "src/setup_app.php" file.
This tells the framework about module: name, sub-memnu route list and title, database table prefix.
*/

$container['config']->set('module','service',['name'=>'Service manager',
                                             'route_root'=>'admin/service/',
                                             'route_list'=>['dashboard'=>'Dashboard','client'=>'Clients','contract_repeat'=>'Repeat contracts','contract_single'=>'Single contracts',
                                                            'diary'=>'Diary','service_visit'=>'Visits','invoice'=>'Invoices','setup_dashboard'=>'Setup'],
                                             'table_prefix'=>'srv_'
                                            ]);

