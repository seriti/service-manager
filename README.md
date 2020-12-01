# Service manager module. 

## Designed for small business applications.

Manage a physical service business such as security, cleaning, maintenance. 
Setup multiple business divisions, each supplying different service items and consumables with pricing. 
Manage multiple service rounds where each round has its own teams and calendar/diary.
Create unlimited clients with multiple locations and contacts per client.
Create single shot or repeat contracts for your service to the clients and manage all documentation associated with contracts. 
Add daily diary entries based on contracts within 15minute time slots, View and manage these entries and collect feedback from staff visits to clients.  
Generate invoices based on client contracts independant of visits, or including consumables used on visit. 

## Requires Seriti Slim 3 MySQL Framework skeleton

This module integrates seamlessly into [Seriti skeleton framework](https://github.com/seriti/slim3-skeleton).
You need to first install the skeleton framework and then download the source files for the module and follow these instructions.

It is possible to use this module independantly from the seriti skeleton but you will still need the [Seriti tools library](https://github.com/seriti/tools).
It is strongly recommended that you first install the seriti skeleton to see a working example of code use before using it within another application framework.
That said, if you are an experienced PHP programmer you will have no problem doing this and the required code footprint is very small.  

## Install the module

1.) Install Seriti Skeleton framework(see the framework readme for detailed instructions):   
    **composer create-project seriti/slim3-skeleton [directory-for-app]**
    Make sure that you have thsi working before you proceed.

2.) Download a copy of Service manager module source code directly from github and unzip,  
or by using **git clone https://github.com/seriti/service-manager** from command line.
Once you have a local copy of module code check that it has following structure:

/Service/(all module implementation classes are in this folder)  
/setup_app.php  
/routes.php  
/templates/(all templates required in this folder)  

3.) Copy the **Service** folder and all its contents into "[directory-for-app]/app" folder.

4.) Open the routes.php file and insert the **$this->group('/service', function (){}** route definition block
within the existing  **$app->group('/admin', function () {}** code block contained in existing skeleton **[directory-for-app]/src/routes.php** file.
5.) Open the setup_app.php file and  add the module config code snippet into bottom of skeleton **[directory-for-app]/src/setup_app.php** file.
Please check the "table_prefix" value to ensure that there will not be a clash with any existing tables in your database.

6.) Copy the contents of **templates** folder to **[directory-for-app]/templates/** folder

7.) Now in your browser goto URL:  

**http://localhost:8000/admin/service/dashboard** if you are using php built in server  
OR  
**http://www.yourdomain.com/admin/service/dashboard** if you have configured a domain on your server  
OR
Click **Dashboard** menu option and you will see list of available modules, click **Service manager**  

Now click link at bottom of page **Setup Database**: This will create all necessary database tables with table_prefix as defined above.  
Thats it, you are good to go. Select **Setup** Tab and: Setup business divisions and service items. Create client categories and physical location/address categories.
Specify service units, categories, standard feedback, service days and payments methods and agents who bring you business. 
Then you are ready to click **Clients** tab and start adding your clients with their location and contact details. 
Finally specify client contract details, and then use dashboard wizards to schedule visits in diary, and invoice based on contracts.
