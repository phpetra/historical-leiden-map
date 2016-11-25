<?php

namespace AppBundle\Menu;


use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class Builder implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function publicMenu(FactoryInterface $factory, array $options)
    {
        $menu = $factory->createItem('root');
        $menu->setChildrenAttribute('class', 'nav navbar-nav');

        $menu->addChild('Kaart', [
            'label' => 'Beschikbare datasets',
            'route' => 'maps',
        ])
            //->setAttribute('dropdown', true)
            ->setAttribute('icon', 'fa fa-map');

        $menu->addChild('Panden', [
            'label'           => 'Overzichtskaart datasets',
            'route'           => 'panden',
        ])
            ->setAttribute('icon', 'fa fa-map');

        return $menu;
    }

    public function userMenu(FactoryInterface $factory, array $options)
    {
        $menu = $factory->createItem('root');
        $menu->setChildrenAttribute('class', 'nav navbar-nav navbar-right nav-menu');

        $user = $this->container->get('security.token_storage')->getToken()->getUser();
        $userName = $user->getUsername();

        $menu->addChild('Uitleg',
            ['route' => 'manage-dataset-info'])
            ->setAttribute('icon', 'fa fa-help');


        $menu->addChild('Project', array('label' => 'Datasets'))
            ->setAttribute('dropdown', true)
            ->setAttribute('icon', 'fa fa-chart');

        $menu['Project']
            ->addChild('Mijn datasets', array('route' => 'manage-dataset-index'))
            ->setAttribute('icon', 'fa fa-list');

        $menu['Project']
            ->addChild('Nieuwe dataset', array('route' => 'manage-dataset-new'))
            ->setAttribute('icon', 'fa fa-wrench');


        $menu->addChild('User', array('label' => 'Hallo ' . $userName))
            ->setAttribute('dropdown', true)
            ->setAttribute('icon', 'fa fa-user');


        $tr = $this->container->get('translator');

        $menu['User']->addChild($tr->trans('account.settings', array(), 'FOSUserBundle'), array(
            'route' => 'fos_user_profile_edit'
        ))
            ->setAttribute('icon', 'fa fa-wrench');

        $menu['User']->addChild($tr->trans('account.change_password', array(), 'FOSUserBundle'), array(
            'route' => 'fos_user_change_password'
        ))
            ->setAttribute('icon', 'fa fa-lock');

        $menu['User']->addChild('')->setAttribute('class', 'divider');
        $menu['User']->addChild($tr->trans('layout.logout', array(), 'FOSUserBundle'), array(
            'route' => 'fos_user_security_logout'
        ))
            ->setAttribute('icon', 'fa fa-sign-out');

        return $menu;
    }


    public function adminMenu(FactoryInterface $factory, array $options)
    {
        $menu = $factory->createItem('root');
        $menu->setChildrenAttribute('class', 'nav navbar-nav navbar-right nav-menu');


        $menu->addChild('SubDash', array('label' => 'Voortgang'))
            ->setAttribute('dropdown', true)
            ->setAttribute('icon', 'fa fa-bar-chart');
        $menu['SubDash']->addChild('Dashboard', array('route' => 'admin-dashboard'))
            ->setAttribute('icon', 'fa fa-gear');
        $menu['SubDash']->addChild('Rapportage invoer', array('route' => 'admin-progress-board'))
            ->setAttribute('icon', 'fa fa-pie-chart');


        $menu->addChild('Subs', array('label' => 'Werkspul'))
            ->setAttribute('dropdown', true)
            ->setAttribute('icon', 'fa fa-file-o');
        $menu['Subs']->addChild('Opdrachten', array('route' => 'admin_assignments_index'))
            ->setAttribute('icon', 'fa fa-tasks');

        $menu['Subs']->addChild('RecordTypes', array(
            'label' => 'Soorten aktes',
            'route' => 'pdx_admin_recordtype_index'
        ))
            ->setAttribute('icon', 'fa fa-file-text-o');

        $menu->addChild('Alle fixers', array('route' => 'admin_member_index'))
            ->setAttribute('icon', 'fa fa-user');

        return $menu;
    }
}
