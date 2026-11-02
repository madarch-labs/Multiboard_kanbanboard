<div class="
        task-board multiboard-task
        <?= $task['is_active'] == 1 ? 'task-board-status-open '.($task['date_modification'] > (time() - $board_highlight_period) ? 'task-board-recent' : '') : 'task-board-status-closed' ?>
        color-<?= $task['color_id'] ?>"
     data-task-id="<?= $task['id'] ?>"
     data-task-url="<?= $this->url->href('TaskViewController', 'show', array('task_id' => $task['id'])) ?>">

    <div class="task-board-expanded">
        <div class="task-board-header">
            <?php if ($this->user->hasProjectAccess('TaskModificationController', 'edit', $task['project_id'])): ?>
                <?= $this->render('task/dropdown', array('task' => $task, 'redirect' => 'board')) ?>
                <?php if ($this->projectRole->canUpdateTask($task)): ?>
                    <?= $this->modal->large('edit', '', 'TaskModificationController', 'edit', array('task_id' => $task['id'])) ?>
                <?php endif ?>
            <?php else: ?>
                <strong><?= '#'.$task['id'] ?></strong>
            <?php endif ?>

            <?php if (! empty($task['owner_id'])): ?>
                <span class="task-board-assignee">
                    <?= $this->text->e($task['assignee_name'] ?: $task['assignee_username']) ?>
                </span>
            <?php endif ?>

            <?= $this->render('board/task_avatar', array('task' => $task)) ?>
        </div>

        <?php if ($show_project): ?>
            <div class="multiboard-task-project">
                <?= $this->url->link(
                    '<i class="fa fa-folder-o fa-fw" aria-hidden="true"></i>'.$this->text->e($task['project_name']),
                    'BoardViewController',
                    'show',
                    array('project_id' => $task['project_id']),
                    false,
                    '',
                    t('Open project board')
                ) ?>
            </div>
        <?php endif ?>

        <?= $this->hook->render('template:board:private:task:before-title', array('task' => $task)) ?>
        <div class="task-board-title">
            <?= $this->url->link($this->text->e($task['title']), 'TaskViewController', 'show', array('task_id' => $task['id'])) ?>
        </div>
        <?= $this->hook->render('template:board:private:task:after-title', array('task' => $task)) ?>

        <?= $this->render('board/task_footer', array(
            'task' => $task,
            'not_editable' => false,
            'project' => array('id' => $task['project_id'], 'name' => $task['project_name']),
        )) ?>
    </div>
</div>
