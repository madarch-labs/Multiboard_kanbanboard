<?php

namespace Kanboard\Plugin\MultiBoard\Controller;

use Kanboard\Controller\BaseController;

class MultiBoardController extends BaseController
{
    public function show()
    {
        $user_id = $this->userSession->getId();
        $projects = $this->multiBoardModel->getAllowedProjects($user_id, $this->userSession->isAdmin());
        $filters = $this->multiBoardModel->parseFilters($projects);

        $columns = $this->multiBoardModel->getUnifiedColumns($filters['project_ids']);
        $tasks = $this->multiBoardModel->getTasks($filters, $user_id);
        $board = $this->multiBoardModel->buildBoard($tasks, $columns, $filters);

        $this->response->html($this->helper->layout->app('MultiBoard:board/show', array(
            'title' => t('All tasks board'),
            'projects' => $projects,
            'filters' => $filters,
            'all_columns' => $columns,
            'filter_columns' => $this->multiBoardModel->getUnifiedColumns(array_map('intval', array_keys($projects))),
            'columns' => $board['columns'],
            'groups' => $board['groups'],
            'nb_tasks' => array_sum(array_column($board['groups'], 'nb_tasks')),
            'assignees' => $this->multiBoardModel->getAssignees($filters['project_ids']),
            'url_params' => $this->multiBoardModel->getUrlParams($filters),
            'board_highlight_period' => $this->configModel->get('board_highlight_period'),
        )));
    }
}
