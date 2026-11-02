<li <?= $this->app->checkMenuSelection('MultiBoardController', 'show', 'MultiBoard') ?>>
    <?= $this->url->link(t('All tasks board'), 'MultiBoardController', 'show', array('plugin' => 'MultiBoard')) ?>
</li>
