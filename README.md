Grouphub
=========

De business laag van de GroupHub applicatie: https://wiki.surfnet.nl/display/P3GFeI2015/2.+Business+Laag

# Host machine requirements

 - Virtualbox
 - Vagrant
 - Ansible

## Vagrant plugins
Make sure you have the following vagrant plugins installed.

    vagrant-hostsupdater >=(0.0.11)
    vagrant-share >=(1.1.4, system)
    vagrant-vbguest >=(0.10.1)

# Installation
- Run `vagrant up` in order to get the vagrant machine running

```sh
<projectdir>$ vagrant ssh
<vagrantbox>$ cd /vagrant
<vagrantbox>$ composer install
<vagrantbox>$ bin/console assetic:dump
```

# Add
`145.100.180.203 ldap.surfuni.org idp.surfuni.org`

# Getting started
After starting and provisioning your vagrant box you can go to:
<http://dev.grouphub.org/app_dev.php>

# Running synchronization scripts
Note: you might have to add `app_dev.php/` to the `grouphub_api_url` parameter for dev
```sh
<projectdir>$ vagrant ssh
<vagrantbox>$ cd /vagrant
<vagrantbox>$ php bin/console grouphub:sync -vvv
```

# Accessing VOOT url's
These URL's are secured with the username `voot` and the password specified in the parameters.yml config file.

- <http://dev.grouphub.org/voot/user/[userId]/groups>
- <http://dev.grouphub.org/voot/user/[userId]/groups/[groupId]>

# Deployment

## Requirements

 - sshd (with a configured 'deployment' user)
 - apache2 (vhost see below)
   * mod_expires
   * mod_headers
 - nodejs
   * uglify-js
   * uglifycss
 - git
 - acl
 - php
   * php5-intl
   * php5-curl
   * php5-apcu
   * php5-ldap

Consider setting `opcache.validate_timestamps` to `0` in php.ini for a lot of free performance!

Also make sure there is a directory `/project/dir/` available which is writable by the `deployment` user.

If you want to deploy the app you will need capistrano-symfony:

```
gem install airbrussh
gem install capistrano
gem install capistrano-composer
gem install capistrano-harrow
gem install capistrano-symfony
gem install i18n
gem install net-scp
gem install net-ssh
gem install sshkit
```
 
## Vhost

Minimum requirements:

```sh
<VirtualHost *:80>
    ServerName grouphub.org
    
    DocumentRoot /project/dir/current/web
    
    Alias /simplesaml /project/dir/current/vendor/simplesamlphp/simplesamlphp/www
    
    <Directory /project/dir/current/web>
        Options FollowSymLinks
        AllowOverride All
        Order Allow,Deny
        Allow from All
    </Directory>
</VirtualHost>
```

Usage of HTTPS is highly recommended.

## Process

To do an actual deployment, make sure a stage is available in app/config/deployment/stages/. Then run 

```sh
cap [stage-name] deploy
```

This script will ask the branch/tag of the software to deploy. The default will probably be sufficient in most cases.

The first time the script will most likely fail because the configuration is invalid, fix this manually as described below, 
then run the script again.

## Configuration

Configuration can be found in app/config/parameters.yml:

```sh
parameters:
    # A secret/random key that's used to generate certain security-related tokens
    secret: ThisTokenIsNotSoSecretChangeIt

    # Details of where and how to connect to the api
    grouphub_api_url: http://api.grouphub.surfuni.org
    grouphub_api_username: 'grouphub'
    grouphub_api_password: ~

    # LDAP read connection details
    ldap_read_host: ~
    ldap_read_port: 389
    ldap_read_dn:   ~
    ldap_read_pass: ~
        
    # LDAP write connection details (can be the same as the read LDAP)
    ldap_write_host: ~
    ldap_write_port: 389
    ldap_write_dn:   ~
    ldap_write_pass: ~
    
    # LDAP fallback clients connection details (will be used when no results are found)
    ldap_fallback:
        alias1: # This alias will be matched based on the combined DC's of the empty DN e.g.: 'domain1.surfuni.org'
            host: ~
            port: 389
            dn:   ~
            pass: ~
        alias2: # This alias will be matched based on the combined DC's of the empty DN e.g.: 'domain2.surfuni.org'
            host: ~
            port: 389
            dn:   ~
            pass: ~

    # DN of where the users are located in LDAP, can be multiple DN's seperated by a comma
    users_dn: ['ou=Users,ou=SURFUni,dc=surfuni,dc=org']
    user_query: 'cn=*'
    # DN of where groups are located in LDAP, can be multiple DN's seperated by a comma
    groups_dn: ['ou=Formalgroups,dc=surfuni,dc=org']
    group_query: 'cn=*'
    # Root group of where Grouphub groups will be stored
    grouphub_dn: 'ou=Grouphub,dc=surfuni,dc=org'
    # Subgroups located beneath the 'grouphub' DN where formal and adhoc groups will be stored
    formal_dn: 'ou=SemiFormal,ou=Grouphub,dc=surfuni,dc=org'
    adhoc_dn: 'ou=AdHoc,ou=Grouphub,dc=surfuni,dc=org'
    
    # Mapping of GroupHub properties to LDAP properties
    ldap.mapping:
        user:
            firstName: givenname
            lastName:  sn
            loginName: uid
            email:     mail
        group:
            name_prefix: ~              # prefix to add to the name of the group
            description: description    # description property mapping
            accountName: ~              # if set, the mapped property will also be filled with the 'cn' of the group
            owner: ~                    # if set, the mapped property will be filled with the 'owner' reference of the group
            name: ~                     # if set, the mapped property will be filled with the name of the group
            extra_attributes:           # extra -static- attributes, which will be added if not empty
                objectClass: groupOfNames

    # Whether or not to sync admins to dedicated groups and, if so, to which DN
    # Note this DN should not be located beneath one of the groups mentioned earlier
    admin_groups_sync: false
    admin_groups_dn: ~

    # The password to secure the VOOT actions
    password_voot: ~

    # Details for connecting to SAML provider
    simplesamlphp.path: simplesaml/
    simplesamlphp.admin_password: ~
    simplesamlphp.technical_contact_name: ~
    simplesamlphp.technical_contact_email: ~
    simplesamlphp.idp: http://idp.surfuni.org/simplesaml/saml2/idp/metadata.php

    # Application title and URL
    application_title: 'SURFuni'
    url: http://grouphub.surfuni.org

    # Naming of various columns
    my_groups_column_title_en: My groups
    my_groups_column_title_nl: Mijn groepen
    org_groups_column_title_en: Organisation groups
    org_groups_column_title_nl: Organisatie groepen
    all_groups_column_title_en: All groups
    all_groups_column_title_nl: Alle groepen

    # Documentation URL
    documentation_url: https://wiki.surfnet.nl/display/Grouphub/Grouphub+Home
```
## Crontab setup

Add the following to the crontab:

```sh
# Sync everything once a day at 02:34
 34 2 * * * /project/dir/current/bin/console grouphub:sync -e=prod
# Sync modified groups back to LDAP every 5 minutes
*/5 * * * * /project/dir/current/current/bin/console grouphub:sync -e=prod --type=queue
```

also see: [Docs](docs/)
