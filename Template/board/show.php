<?php
$baseParams = array('plugin' => 'MultiBoard');
$selectedKeys = $filters['all_columns'] ? array_keys($filter_columns) : $filters['columns'];
?>
<section id="main" class="multiboard">
    <div class="page-header">
        <ul>
            <li>
                <a href="#" class="js-multiboard-toggle-filters"><i class="fa fa-filter fa-fw" aria-hidden="true"></i><?= t('MultiBoard: filters') ?></a>
            </li>
            <li>
                <a href="#" class="js-multiboard-copy-link" data-copied="<?= t('Link copied') ?>"><i class="fa fa-link fa-fw" aria-hidden="true"></i><?= t('Copy link') ?></a>
            </li>
            <li>
                <?= $this->url->icon('refresh', t('Reset filters'), 'MultiBoardController', 'show', $baseParams) ?>
            </li>
            <li class="multiboard-summary">
                <?= t('Projects: %d of %d', count($filters['project_ids']), count($projects)) ?>,
                <?= t('columns: %d of %d', count($columns), count($all_columns)) ?>,
                <?= t('tasks: %d', $nb_tasks) ?>
            </li>
        </ul>
    </div>

    <?= $this->render('MultiBoard:board/filters', array(
        'projects' => $projects,
        'filters' => $filters,
        'filter_columns' => $filter_columns,
        'assignees' => $assignees,
    )) ?>

    <?php if (empty($projects)): ?>
        <p class="alert"><?= t('You do not have access to any active project.') ?></p>
    <?php elseif (empty($filters['project_ids'])): ?>
        <p class="alert"><?= t('No project selected.') ?></p>
    <?php elseif (empty($columns)): ?>
        <p class="alert"><?= t('No column selected.') ?></p>
    <?php elseif (! $filters['all_assignees'] && empty($filters['assignees'])): ?>
        <p class="alert"><?= t('No assignee selected.') ?></p>
    <?php else: ?>
        <div id="board-container" class="multiboard-container">
            <table id="board" class="multiboard-table">
                <tr class="board-swimlane-columns-multiboard">
                    <?php foreach ($columns as $key => $column): ?>
                        <th class="board-column-header">
                            <div class="board-column-expanded board-column-expanded-header">
                                <span class="board-column-title"><?= $this->text->e($column['title']) ?></span>
                                <span class="pull-right board-column-header-task-count">
                                    <span title="<?= t('Task count') ?>"><?= $column['nb_tasks'] ?></span>
                                    <?php if (count($columns) > 1): ?>
                                        <?php $hideParams = $url_params; $hideParams['columns'] = implode(',', array_diff($selectedKeys, array($key))); ?>
                                        <a href="<?= $this->url->href('MultiBoardController', 'show', $baseParams + $hideParams) ?>" class="multiboard-hide-column" title="<?= t('MultiBoard: hide column') ?>" aria-label="<?= t('MultiBoard: hide column') ?>"><i class="fa fa-eye-slash"></i></a>
                                    <?php endif ?>
                                </span>
                            </div>
                        </th>
                    <?php endforeach ?>
                </tr>

                <?php if (empty($groups)): ?>
                    <tr class="board-swimlane">
                        <?php foreach ($columns as $column): ?>
                            <td><div class="board-task-list board-column-expanded"></div></td>
                        <?php endforeach ?>
                    </tr>
                <?php endif ?>

                <?php foreach ($groups as $group): ?>
                    <?php if ($filters['group'] !== 'none'): ?>
                        <tr>
                            <th class="board-swimlane-header" colspan="<?= count($columns) ?>">
                                <?= $this->text->e($group['name']) ?>
                                <span class="board-column-header-task-count">(<?= $group['nb_tasks'] ?>)</span>
                            </th>
                        </tr>
                    <?php endif ?>
                    <tr class="board-swimlane">
                        <?php foreach ($columns as $key => $column): ?>
                            <td>
                                <div class="board-task-list board-column-expanded">
                                    <?php foreach ($group['columns'][$key] as $task): ?>
                                        <?= $this->render('MultiBoard:board/task', array(
                                            'task' => $task,
                                            'board_highlight_period' => $board_highlight_period,
                                            'show_project' => $filters['group'] !== 'project',
                                        )) ?>
                                    <?php endforeach ?>
                                </div>
                            </td>
                        <?php endforeach ?>
                    </tr>
                <?php endforeach ?>
            </table>
        </div>
    <?php endif ?>
</section>
