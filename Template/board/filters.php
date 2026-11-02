<form method="get" action="<?= $this->url->dir() ?>" class="multiboard-filters panel" id="multiboard-filters">
    <input type="hidden" name="controller" value="MultiBoardController">
    <input type="hidden" name="action" value="show">
    <input type="hidden" name="plugin" value="MultiBoard">
    <input type="hidden" name="projects" value="" class="js-multiboard-value" data-group="project" data-none="none">
    <input type="hidden" name="columns" value="" class="js-multiboard-value" data-group="column" data-none="-">
    <input type="hidden" name="assignee" value="" class="js-multiboard-value" data-group="assignee" data-none="-">

    <div class="multiboard-filters-grid">
        <fieldset class="multiboard-filter-group">
            <legend><?= t('Projects') ?></legend>
            <div class="multiboard-filter-actions">
                <a href="#" class="js-multiboard-check" data-group="project" data-checked="1"><i class="fa fa-check-square-o fa-fw"></i><?= t('MultiBoard: select all') ?></a>
                <a href="#" class="js-multiboard-check" data-group="project" data-checked="0"><i class="fa fa-square-o fa-fw"></i><?= t('MultiBoard: clear') ?></a>
            </div>
            <div class="multiboard-checkbox-list">
                <?php foreach ($projects as $project_id => $project_name): ?>
                    <label>
                        <input type="checkbox" class="js-multiboard-item" data-group="project" value="<?= (int) $project_id ?>"
                            <?= in_array((int) $project_id, $filters['project_ids'], true) ? 'checked' : '' ?>>
                        <?= $this->text->e($project_name) ?>
                    </label>
                <?php endforeach ?>
            </div>
        </fieldset>

        <fieldset class="multiboard-filter-group">
            <legend><?= t('Columns') ?></legend>
            <div class="multiboard-filter-actions">
                <a href="#" class="js-multiboard-check" data-group="column" data-checked="1"><i class="fa fa-check-square-o fa-fw"></i><?= t('MultiBoard: select all') ?></a>
                <a href="#" class="js-multiboard-check" data-group="column" data-checked="0"><i class="fa fa-square-o fa-fw"></i><?= t('MultiBoard: clear') ?></a>
            </div>
            <div class="multiboard-checkbox-list">
                <?php foreach ($filter_columns as $key => $column): ?>
                    <label title="<?= t('MultiBoard: used in %d projects', count($column['project_ids'])) ?>">
                        <input type="checkbox" class="js-multiboard-item" data-group="column" value="<?= $this->text->e($key) ?>"
                            <?= $filters['all_columns'] || in_array($key, $filters['columns'], true) ? 'checked' : '' ?>>
                        <?= $this->text->e($column['title']) ?>
                        <span class="multiboard-muted">(<?= count($column['project_ids']) ?>)</span>
                    </label>
                <?php endforeach ?>
            </div>
        </fieldset>

        <fieldset class="multiboard-filter-group">
            <legend><?= t('MultiBoard: assignees') ?></legend>
            <div class="multiboard-filter-actions">
                <a href="#" class="js-multiboard-check" data-group="assignee" data-checked="1"><i class="fa fa-check-square-o fa-fw"></i><?= t('MultiBoard: select all') ?></a>
                <a href="#" class="js-multiboard-check" data-group="assignee" data-checked="0"><i class="fa fa-square-o fa-fw"></i><?= t('MultiBoard: clear') ?></a>
            </div>
            <div class="multiboard-checkbox-list">
                <?php $assigneeItems = array('me' => t('MultiBoard: me'), 'none' => t('MultiBoard: not assigned')) + $assignees; ?>
                <?php foreach ($assigneeItems as $value => $label): ?>
                    <label<?= in_array((string) $value, array('me', 'none'), true) ? ' class="multiboard-special"' : '' ?>>
                        <input type="checkbox" class="js-multiboard-item" data-group="assignee" value="<?= $this->text->e($value) ?>"
                            <?= $filters['all_assignees'] || in_array((string) $value, $filters['assignees'], true) ? 'checked' : '' ?>>
                        <?= $this->text->e($label) ?>
                    </label>
                <?php endforeach ?>
            </div>
        </fieldset>

        <fieldset class="multiboard-filter-group">
            <legend><?= t('Tasks') ?></legend>

            <label for="multiboard-q"><?= t('Search') ?></label>
            <input type="text" id="multiboard-q" name="q" value="<?= $this->text->e($filters['q']) ?>" placeholder="<?= t('Title or #id') ?>" class="js-multiboard-default" data-default="">

            <label for="multiboard-status"><?= t('Status') ?></label>
            <select id="multiboard-status" name="status" class="js-multiboard-default" data-default="open">
                <option value="open" <?= $filters['status'] === 'open' ? 'selected' : '' ?>><?= t('MultiBoard: open tasks') ?></option>
                <option value="closed" <?= $filters['status'] === 'closed' ? 'selected' : '' ?>><?= t('MultiBoard: closed tasks') ?></option>
                <option value="all" <?= $filters['status'] === 'all' ? 'selected' : '' ?>><?= t('MultiBoard: all tasks') ?></option>
            </select>

            <label for="multiboard-group"><?= t('MultiBoard: group by') ?></label>
            <select id="multiboard-group" name="group" class="js-multiboard-default" data-default="none">
                <option value="none" <?= $filters['group'] === 'none' ? 'selected' : '' ?>><?= t('MultiBoard: no grouping') ?></option>
                <option value="project" <?= $filters['group'] === 'project' ? 'selected' : '' ?>><?= t('MultiBoard: by project') ?></option>
                <option value="assignee" <?= $filters['group'] === 'assignee' ? 'selected' : '' ?>><?= t('MultiBoard: by assignee') ?></option>
            </select>
        </fieldset>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-blue"><?= t('MultiBoard: apply') ?></button>
    </div>
</form>
