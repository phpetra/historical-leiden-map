# UserBundle
Pre-configured implementation of the FOSUserBundle in symfony3 projects. 
Uses customized Dutch translations and twitter bootstrap twig templates.

##Installation

Two options: if you want to use the bundle as is, install it in the vendor directory. If you want to change things in templates etc. install it locally in your /src folder.

###Install in src
	
	cd /src
	mkdir Pdx
	mkdir Pdx/UserBundle
	cd Pdx/UserBundle
	git clone https://github.com/phpetra/UserBundle.git .
	
In this situation you need to install the FOSUserBundle separately through composer:

	composer require friendsofsymfony/user-bundle
	
###Install in vendor

Add the following to the `require` section of your composer.json file

	"friendsofsymfony/user-bundle": "~2.0@dev",
    "phpetra/userbundle" : "dev-master"
    
Add the repository (as the phpetra package is not on packagist yet):

	 "repositories": [
        {
            "type" : "vcs",
            "url" : "https://github.com/phpetra/UserBundle.git"
        }
    ],
    
Run `composer install`

##Configuration
Add al these settings in the approriate files.

#### /app/AppKernel.php 

	new \FOS\UserBundle\FOSUserBundle(),
	new \Pdx\UserBundle\PdxUserBundle()

### Configuration
    
#### /app/config/config.yml 

Under `parameters` section:

	locale: nl
	
	
Under `framework` section:

	translator:      { fallbacks: ["%locale%"] }
	
Add a new section:

	fos_user:
	    db_driver: orm 
	    firewall_name: site_security
	    user_class: Pdx\UserBundle\Entity\User #Setting the used Entity here
	    registration:
	        confirmation:
	            enabled:    true # change to true for required email confirmation
	            template:   FOSUserBundle:Registration:email.txt.twig
	    resetting:
	        token_ttl: 86400
        
#### /app/config/security.yml

Under the `role_hierarchy ` section, add your user roles:

    ROLE_ADMIN:       ROLE_USER
    ROLE_SUPER_ADMIN: ROLE_ADMIN

Under the `providers` section:

	fos_userbundle:
    	id: fos_user.user_provider.username_email

Under the `firewalls` section:

	main:
        pattern: ^/
        access_denied_url: /denied
        form_login:
            provider: fos_userbundle
            csrf_token_generator: security.csrf.token_manager
        logout:       true
        anonymous:    true
        
You can now protect your routes, add the required ones under `access_control`

	 - { path: ^/login$, role: IS_AUTHENTICATED_ANONYMOUSLY }
    - { path: ^/register, role: IS_AUTHENTICATED_ANONYMOUSLY }
    - { path: ^/resetting, role: IS_AUTHENTICATED_ANONYMOUSLY }
    - { path: ^/admin, roles: [ ROLE_ADMIN ] }
    - { path: ^/secret-stuff, roles: [ ROLE_USER ] }


#### app/config/routing.yml

	fos_user:
	    resource: "@FOSUserBundle/Resources/config/routing/all.xml"


Make sure you have a working connection to your database before requesting a page.

### Other things
You can also always copy the User Entity somewhere locally, and change the name in the config.yml fos_user section.




