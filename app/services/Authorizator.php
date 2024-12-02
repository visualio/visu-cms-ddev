<?php

namespace App\Services;

use Nette\Security\Permission;


class Authorizator
{
    public static function create(): Permission
    {
        $acl = new Permission;

        // roles
        $acl->addRole('a'); // admin
        $acl->addRole('u'); // user

        // resources
        $acl->addResource('Dashboard');
        $acl->addResource('Page');
        $acl->addResource('Article');
        $acl->addResource('Navigation');
        $acl->addResource('User');
        $acl->addResource('File');
        $acl->addResource('Tag');
        $acl->addResource('Error4xx');

        // rules
        $acl->allow('a', Permission::ALL, ['create', 'read', 'update', 'delete']);
        $acl->allow('u', Permission::ALL, ['read']);

        return $acl;
    }
}