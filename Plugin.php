<?php

namespace Kanboard\Plugin\MultiBoard;

use Kanboard\Core\Plugin\Base;
use Kanboard\Core\Security\Role;
use Kanboard\Core\Translator;

class Plugin extends Base
{
    public function initialize()
    {
        $this->applicationAccessMap->add('MultiBoardController', array('show'), Role::APP_USER);

        $this->route->addRoute('/multiboard', 'MultiBoardController', 'show', 'MultiBoard');

        $this->hook->on('template:layout:css', array('template' => 'plugins/MultiBoard/Assets/css/multiboard.css'));
        $this->hook->on('template:layout:js', array('template' => 'plugins/MultiBoard/Assets/js/multiboard.js'));

        $this->template->hook->attach('template:dashboard:sidebar', 'MultiBoard:dashboard/sidebar');
        $this->template->hook->attach('template:header:dropdown', 'MultiBoard:header/dropdown');
    }

    public function onStartup()
    {
        Translator::load('en_US', __DIR__.'/Locale');

        $language = $this->languageModel->getCurrentLanguage();

        if ($language !== 'en_US') {
            Translator::load($language, __DIR__.'/Locale');
        }
    }

    public function getClasses()
    {
        return array(
            'Plugin\MultiBoard\Model' => array('MultiBoardModel'),
        );
    }

    public function getPluginName()
    {
        return 'MultiBoard';
    }

    public function getPluginDescription()
    {
        return t('One board with tasks from all projects, with project and column filters stored in the URL');
    }

    public function getPluginAuthor()
    {
        return 'Fedrs';
    }

    public function getPluginVersion()
    {
        return '1.0.0';
    }

    public function getPluginHomepage()
    {
        return '';
    }

    public function getCompatibleVersion()
    {
        return '>=1.2.20';
    }
}
