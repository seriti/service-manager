<?php
namespace App\Service;

use Seriti\Tools\SetupModuleData;

class SetupData extends SetupModuledata
{

    public function setupSql()
    {
        $this->tables = ['client','client_category','client_contact','client_location','location_category','division','agent','pay_method',
                         'contract','contract_category','contract_item','contract_visit','contract_invoice','invoice_item',
                         'service_round','service_item','item_units','service_price','service_day','service_errand','errand_category',
                         'service_feedback','visit_category','visit_user_assist','visit_item',
                         'file'];

        $this->addCreateSql('client',
                            'CREATE TABLE `TABLE_NAME` (
                                `client_id` int(11) NOT NULL AUTO_INCREMENT,
                                `category_id` int(11) NOT NULL,
                                `client_code` varchar(64) NOT NULL,
                                `account_code` varchar(64) NOT NULL,
                                `name` varchar(250) NOT NULL,
                                `company_title` varchar(64) NOT NULL,
                                `company_no` varchar(64) NOT NULL,
                                `tax_reference` varchar(64) NOT NULL,
                                `sales_code` varchar(64) NOT NULL,
                                `status` varchar(64) NOT NULL,
                                PRIMARY KEY (`client_id`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8'); 

        $this->addCreateSql('client_category',
                            'CREATE TABLE `TABLE_NAME` (
                              `category_id` INT NOT NULL AUTO_INCREMENT,
                              `name` VARCHAR(250) NOT NULL,
                              `access` varchar(64) NOT NULL,
                              `access_level` int(11) NOT NULL,
                              `sort` INT NOT NULL,
                              `status` VARCHAR(64) NOT NULL,
                              PRIMARY KEY (`category_id`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8');

        $this->addCreateSql('client_contact',
                            'CREATE TABLE `TABLE_NAME` (
                                `contact_id` int(11) NOT NULL AUTO_INCREMENT,
                                `client_id` int(11) NOT NULL,
                                `location_id` int(11) NOT NULL,
                                `type_id` varchar(64) NOT NULL,
                                `name` varchar(64) NOT NULL,
                                `position` varchar(64) NOT NULL,
                                `tel` varchar(64) NOT NULL,
                                `cell` varchar(64) NOT NULL,
                                `email` varchar(64) NOT NULL,
                                `tel_alt` varchar(64) NOT NULL,
                                `cell_alt` varchar(64) NOT NULL,
                                `email_alt` varchar(64) NOT NULL,
                                `sort` int(11) NOT NULL,
                                `status` varchar(64) NOT NULL,
                                PRIMARY KEY (`contact_id`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8'); 

        $this->addCreateSql('client_location',
                            'CREATE TABLE `TABLE_NAME` (
                                `location_id` int(11) NOT NULL AUTO_INCREMENT,
                                `category_id` int(11) NOT NULL,
                                `client_id` int(11) NOT NULL,
                                `type_id` varchar(64) NOT NULL,
                                `name` varchar(250) NOT NULL,
                                `size` int(11) NOT NULL,
                                `address` TEXT NOT NULL,
                                `tel` varchar(64) NOT NULL,
                                `email` varchar(64) NOT NULL,
                                `map_lat` decimal(10,6) NOT NULL,
                                `map_lng` decimal(10,6) NOT NULL,
                                `sort` int(11) NOT NULL,
                                `status` varchar(64) NOT NULL,
                                PRIMARY KEY (`location_id`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8'); 

        $this->addCreateSql('location_category',
                            'CREATE TABLE `TABLE_NAME` (
                              `category_id` INT NOT NULL AUTO_INCREMENT,
                              `name` VARCHAR(250) NOT NULL,
                              `sort` INT NOT NULL,
                              `status` VARCHAR(64) NOT NULL,
                              PRIMARY KEY (`category_id`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8');


        $this->addCreateSql('division',
                            'CREATE TABLE `TABLE_NAME` (
                              `division_id` INT NOT NULL AUTO_INCREMENT,
                              `name` VARCHAR(250) NOT NULL,
                              `invoice_prefix` varchar(8) NOT NULL,
                              `invoice_no` int(11) NOT NULL,
                              `invoice_address` TEXT NOT NULL,
                              `invoice_contact` TEXT NOT NULL,
                              `invoice_info` TEXT NOT NULL,
                              `sort` INT NOT NULL,
                              `status` VARCHAR(64) NOT NULL,
                              PRIMARY KEY (`division_id`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8');

        $this->addCreateSql('agent',
                            'CREATE TABLE `TABLE_NAME` (
                              `agent_id` INT NOT NULL AUTO_INCREMENT,
                              `name` VARCHAR(250) NOT NULL,
                              `sort` INT NOT NULL,
                              `status` VARCHAR(64) NOT NULL,
                              PRIMARY KEY (`agent_id`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8');

        $this->addCreateSql('pay_method',
                            'CREATE TABLE `TABLE_NAME` (
                              `method_id` INT NOT NULL AUTO_INCREMENT,
                              `name` VARCHAR(250) NOT NULL,
                              `sort` INT NOT NULL,
                              `status` VARCHAR(64) NOT NULL,
                              PRIMARY KEY (`method_id`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8');

        $this->addCreateSql('contract',
                            'CREATE TABLE `TABLE_NAME` (
                              `contract_id` INT NOT NULL AUTO_INCREMENT,
                              `division_id` int(11) NOT NULL,
                              `type_id` VARCHAR(64) NOT NULL,
                              `client_id` int(11) NOT NULL,
                              `client_code` VARCHAR(64) NOT NULL,
                              `agent_id` int(11) NOT NULL,
                              `location_id` int(11) NOT NULL,
                              `contact_id` int(11) NOT NULL,
                              `round_id` int(11) NOT NULL,
                              `notify_prior` tinyint(1) NOT NULL,
                              `user_id_responsible` int(11) NOT NULL,
                              `user_id_sold` int(11) NOT NULL,
                              `user_id_signed` int(11) NOT NULL,
                              `user_id_checked` int(11) NOT NULL,
                              `signed_by` VARCHAR(250) NOT NULL,
                              `date_signed` date NOT NULL,
                              `date_renew` date NOT NULL,
                              `date_start` datetime NOT NULL,
                              `no_months` int(11) NOT NULL,
                              `no_visits` int(11) NOT NULL,
                              `no_assistants` int(11) NOT NULL,
                              `warranty_months` int(11) NOT NULL,
                              `price` decimal(12,2) NOT NULL,
                              `discount` decimal(12,2) NOT NULL,
                              `price_visit` decimal(12,2) NOT NULL,
                              `price_annual_pct` decimal(12,2) NOT NULL,
                              `time_estimate` int(11) NOT NULL,
                              `visit_day_id` int(11) NOT NULL,
                              `visit_time_from` time NOT NULL,
                              `visit_time_to` time NOT NULL,
                              `pay_method_id` int(11) NOT NULL,
                              `notes_admin` TEXT NOT NULL,
                              `notes_client` TEXT NOT NULL,
                              `status` VARCHAR(64) NOT NULL,
                              PRIMARY KEY (`contract_id`),
                              UNIQUE KEY `idx_contract1` (`client_code`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8');
      
        $this->addCreateSql('contract_category',
                            'CREATE TABLE `TABLE_NAME` (
                              `category_id` INT NOT NULL AUTO_INCREMENT,
                              `type_id` VARCHAR(64) NOT NULL,
                              `name` VARCHAR(250) NOT NULL,
                              `sort` INT NOT NULL,
                              `status` VARCHAR(64) NOT NULL,
                              PRIMARY KEY (`category_id`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8');
       
        $this->addCreateSql('contract_item',
                            'CREATE TABLE `TABLE_NAME` (
                              `data_id` INT NOT NULL AUTO_INCREMENT,
                              `contract_id` int(11) NOT NULL,
                              `item_id` int(11) NOT NULL,
                              `price` decimal(12,2) NOT NULL,
                              `notes` text NOT NULL,
                              `status` VARCHAR(64) NOT NULL,
                              PRIMARY KEY (`data_id`),
                              UNIQUE KEY `idx_contract_item1` (`contract_id`,`item_id`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8');

        $this->addCreateSql('contract_visit',
                            'CREATE TABLE `TABLE_NAME` (
                              `visit_id` INT NOT NULL AUTO_INCREMENT,
                              `category_id` int(11) NOT NULL,
                              `contract_id` int(11) NOT NULL,
                              `round_id` int(11) NOT NULL,
                              `feedback_id` int(11) NOT NULL,
                              `no_assistants` int(11) NOT NULL,
                              `user_id_tech` int(11) NOT NULL,
                              `user_id_booked` int(11) NOT NULL,
                              `date_booked` datetime NOT NULL,
                              `date_visit` date NOT NULL,
                              `time_from` time NOT NULL,
                              `time_to` time NOT NULL,
                              `notes` text NOT NULL,
                              `service_no` VARCHAR(64) NOT NULL,
                              `invoice_no` VARCHAR(64) NOT NULL,
                              `status` VARCHAR(64) NOT NULL,
                              PRIMARY KEY (`visit_id`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8');

        $this->addCreateSql('contract_invoice',
                            'CREATE TABLE `TABLE_NAME` (
                              `invoice_id` INT NOT NULL AUTO_INCREMENT,
                              `invoice_no` VARCHAR(64) NOT NULL,
                              `contract_id` int(11) NOT NULL,
                              `contact_id` int(11) NOT NULL,
                              `subtotal` decimal(12,2) NOT NULL,
                              `discount` decimal(12,2) NOT NULL,
                              `tax` decimal(12,2) NOT NULL,
                              `total` decimal(12,2) NOT NULL,
                              `date` datetime NOT NULL,
                              `notes` text NOT NULL,
                              `status` VARCHAR(64) NOT NULL,
                              PRIMARY KEY (`invoice_id`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8');

        $this->addCreateSql('invoice_item',
                            'CREATE TABLE `TABLE_NAME` (
                              `item_id` INT NOT NULL AUTO_INCREMENT,
                              `invoice_id` int(11) NOT NULL,
                              `item_code` varchar(64) NOT NULL,
                              `item_desc` varchar(255) NOT NULL,
                              `quantity` decimal(12,2) NOT NULL,
                              `units` varchar(64) NOT NULL,
                              `unit_price` decimal(12,2) NOT NULL,
                              `discount` decimal(12,2) NOT NULL,
                              `tax` decimal(12,2) NOT NULL,
                              `total` decimal(12,2) NOT NULL,
                              PRIMARY KEY (`item_id`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8');

        $this->addCreateSql('service_round',
                            'CREATE TABLE `TABLE_NAME` (
                              `round_id` INT NOT NULL AUTO_INCREMENT,
                              `name` VARCHAR(250) NOT NULL,
                              `sort` INT NOT NULL,
                              `status` VARCHAR(64) NOT NULL,
                              PRIMARY KEY (`round_id`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8');

        $this->addCreateSql('service_item',
                            'CREATE TABLE `TABLE_NAME` (
                              `item_id` INT NOT NULL AUTO_INCREMENT,
                              `division_id` int(11) NOT NULL,
                              `name` VARCHAR(250) NOT NULL,
                              `code` VARCHAR(64) NOT NULL,
                              `units_id` INT NOT NULL,
                              `sort` INT NOT NULL,
                              `status` VARCHAR(64) NOT NULL,
                              PRIMARY KEY (`item_id`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8');

        $this->addCreateSql('item_units',
                            'CREATE TABLE `TABLE_NAME` (
                              `units_id` INT NOT NULL AUTO_INCREMENT,
                              `name` VARCHAR(250) NOT NULL,
                              `sort` INT NOT NULL,
                              `status` VARCHAR(64) NOT NULL,
                              PRIMARY KEY (`units_id`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8');

        $this->addCreateSql('service_price',
                            'CREATE TABLE `TABLE_NAME` (
                              `price_id` INT NOT NULL AUTO_INCREMENT,
                              `division_id` int(11) NOT NULL,
                              `item_id` int(11) NOT NULL,
                              `location_category_id` int(11) NOT NULL,
                              `item_quantity` int(11) NOT NULL,
                              `price` decimal(12,2) NOT NULL,
                              PRIMARY KEY (`price_id`),
                              UNIQUE KEY `idx_service_price1` (`division_id`,`item_id`,`location_category_id`,`item_quantity`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8');

        $this->addCreateSql('service_day',
                            'CREATE TABLE `TABLE_NAME` (
                              `day_id` INT NOT NULL,
                              `name` VARCHAR(64) NOT NULL,
                              `sort` INT NOT NULL,
                              `status` VARCHAR(64) NOT NULL,
                              PRIMARY KEY (`day_id`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8');

        $this->addCreateSql('service_errand',
                            'CREATE TABLE `TABLE_NAME` (
                              `errand_id` INT NOT NULL AUTO_INCREMENT,
                              `client_id` INT NOT NULL,
                              `location_id` INT NOT NULL,
                              `category_id` INT NOT NULL,
                              `round_id` INT NOT NULL,
                              `date` date NOT NULL,
                              `time_arrive` time NOT NULL,
                              `time_leave` time NOT NULL,
                              `notes` TEXT NOT NULL,
                              `status` VARCHAR(64) NOT NULL,
                              PRIMARY KEY (`errand_id`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8');

        $this->addCreateSql('errand_category',
                            'CREATE TABLE `TABLE_NAME` (
                              `category_id` INT NOT NULL AUTO_INCREMENT,
                              `name` VARCHAR(250) NOT NULL,
                              `sort` INT NOT NULL,
                              `status` VARCHAR(64) NOT NULL,
                              PRIMARY KEY (`category_id`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8');

        $this->addCreateSql('service_feedback',
                            'CREATE TABLE `TABLE_NAME` (
                              `feedback_id` INT NOT NULL AUTO_INCREMENT,
                              `type_id` VARCHAR(64) NOT NULL,
                              `name` VARCHAR(250) NOT NULL,
                              `sort` INT NOT NULL,
                              `status` VARCHAR(64) NOT NULL,
                              PRIMARY KEY (`feedback_id`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8');

        $this->addCreateSql('visit_user_assist',
                            'CREATE TABLE `TABLE_NAME` (
                              `assist_id` int NOT NULL AUTO_INCREMENT,
                              `visit_id` int(11) NOT NULL,
                              `user_id` int(11) NOT NULL,
                              `note` text NOT NULL,
                              PRIMARY KEY (`assist_id`),
                              UNIQUE KEY `idx_visit_user_assist1` (`visit_id`,`user_id`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8');

        $this->addCreateSql('visit_item',
                            'CREATE TABLE `TABLE_NAME` (
                              `data_id` INT NOT NULL AUTO_INCREMENT,
                              `visit_id` int(11) NOT NULL,
                              `item_id` int(11) NOT NULL,
                              `quantity` decimal(12,2) NOT NULL,
                              `price` decimal(12,2) NOT NULL,
                              `notes` text NOT NULL,
                              `status` VARCHAR(64) NOT NULL,
                              PRIMARY KEY (`data_id`),
                              UNIQUE KEY `idx_visit_item1` (`visit_id`,`item_id`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8');

        $this->addCreateSql('visit_category',
                            'CREATE TABLE `TABLE_NAME` (
                              `category_id` INT NOT NULL AUTO_INCREMENT,
                              `name` VARCHAR(250) NOT NULL,
                              `sort` INT NOT NULL,
                              `chargeable` TINYINT(1) NOT NULL,
                              `status` VARCHAR(64) NOT NULL,
                              PRIMARY KEY (`category_id`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8');

        $this->addCreateSql('file',
                            'CREATE TABLE `TABLE_NAME` (
                              `file_id` int(10) unsigned NOT NULL,
                              `title` varchar(255) NOT NULL,
                              `file_name` varchar(255) NOT NULL,
                              `file_name_orig` varchar(255) NOT NULL,
                              `file_text` longtext NOT NULL,
                              `file_date` date NOT NULL,
                              `location_id` varchar(64) NOT NULL,
                              `location_rank` int(11) NOT NULL,
                              `key_words` text NOT NULL,
                              `description` text NOT NULL,
                              `file_size` int(11) NOT NULL,
                              `encrypted` tinyint(1) NOT NULL,
                              `file_name_tn` varchar(255) NOT NULL,
                              `file_ext` varchar(16) NOT NULL,
                              `file_type` varchar(16) NOT NULL,
                              PRIMARY KEY (`file_id`),
                              KEY `service_file_idx1` (`location_id`),
                              FULLTEXT KEY `service_file_idx2` (`key_words`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8');

        //initialisation
        //$this->addInitialSql('INSERT INTO `TABLE_PREFIXprovider` (name,email,status,contact_name) '.
        //                     'VALUES("My first provider","bob@provider.com","OK","bob")');
        

        //updates use time stamp in ['YYYY-MM-DD HH:MM'] format, must be unique and sequential
        //$this->addUpdateSql('YYYY-MM-DD HH:MM','Update TABLE_PREFIX--- SET --- "X"');
    }
}