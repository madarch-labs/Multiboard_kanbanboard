<?php

namespace Kanboard\Plugin\MultiBoard\Model;

use Kanboard\Core\Base;
use Kanboard\Model\ColumnModel;
use Kanboard\Model\ProjectModel;
use Kanboard\Model\SwimlaneModel;
use Kanboard\Model\TaskModel;

/**
 * Сборка сводной доски по нескольким проектам.
 *
 * Колонки разных проектов объединяются по названию (без учёта регистра и
 * лишних пробелов), порядок объединённых колонок — по среднему
 * относительному положению колонки в проектах.
 */
class MultiBoardModel extends Base
{
    const GROUP_NONE = 'none';
    const GROUP_PROJECT = 'project';
    const GROUP_ASSIGNEE = 'assignee';

    const STATUS_OPEN = 'open';
    const STATUS_CLOSED = 'closed';
    const STATUS_ALL = 'all';

    /**
     * Проекты, доступные пользователю на просмотр: id => name.
     * Администратор видит все активные проекты.
     */
    public function getAllowedProjects($user_id, $is_admin)
    {
        if ($is_admin) {
            $projects = $this->projectModel->getListByStatus(ProjectModel::ACTIVE);
        } else {
            $projects = $this->projectUserRoleModel->getActiveProjectsByUser($user_id);
        }

        asort($projects, SORT_NATURAL | SORT_FLAG_CASE);

        return $projects;
    }

    /**
     * Ключ колонки для объединения и для URL.
     */
    public function getColumnKey($title)
    {
        $key = preg_replace('/\s+/u', ' ', trim((string) $title));
        $key = str_replace(',', ' ', $key);

        return function_exists('mb_strtolower') ? mb_strtolower($key, 'UTF-8') : strtolower($key);
    }

    /**
     * Разбор фильтров из query string.
     */
    public function parseFilters(array $allowed_projects)
    {
        $filters = array(
            'project_ids' => array(),
            'all_projects' => true,
            'columns' => array(),
            'all_columns' => true,
            'assignees' => array(),
            'all_assignees' => true,
            'q' => trim($this->request->getStringParam('q')),
            'status' => $this->request->getStringParam('status', self::STATUS_OPEN),
            'group' => $this->request->getStringParam('group', self::GROUP_NONE),
        );

        $projects = $this->splitList($this->request->getStringParam('projects'));

        if (! empty($projects) && ! in_array('all', $projects, true)) {
            $ids = array();

            foreach ($projects as $id) {
                if (ctype_digit($id) && isset($allowed_projects[(int) $id])) {
                    $ids[] = (int) $id;
                }
            }

            $filters['project_ids'] = array_values(array_unique($ids));
            $filters['all_projects'] = false;
        }

        if ($filters['all_projects']) {
            $filters['project_ids'] = array_map('intval', array_keys($allowed_projects));
        }

        $columns = $this->splitList($this->request->getStringParam('columns'));

        if (! empty($columns)) {
            $filters['columns'] = array_values(array_unique(array_map(array($this, 'getColumnKey'), $columns)));
            $filters['all_columns'] = false;
        }

        if (! in_array($filters['status'], array(self::STATUS_OPEN, self::STATUS_CLOSED, self::STATUS_ALL), true)) {
            $filters['status'] = self::STATUS_OPEN;
        }

        if (! in_array($filters['group'], array(self::GROUP_NONE, self::GROUP_PROJECT, self::GROUP_ASSIGNEE), true)) {
            $filters['group'] = self::GROUP_NONE;
        }

        // Исполнители: список через запятую из me, none (не назначено), id пользователей; "-" — никто.
        $assignees = $this->splitList($this->request->getStringParam('assignee'));

        if (! empty($assignees)) {
            $values = array();

            foreach ($assignees as $value) {
                if ($value === 'me' || $value === 'none' || ctype_digit($value)) {
                    $values[] = $value;
                }
            }

            $filters['assignees'] = array_values(array_unique($values));
            $filters['all_assignees'] = false;
        }

        return $filters;
    }

    /**
     * Параметры URL для текущих фильтров (пустые/по умолчанию — опускаются).
     */
    public function getUrlParams(array $filters)
    {
        $params = array();

        if (! $filters['all_projects']) {
            $params['projects'] = implode(',', $filters['project_ids']);
        }

        if (! $filters['all_columns']) {
            $params['columns'] = implode(',', $filters['columns']);
        }

        if (! $filters['all_assignees']) {
            $params['assignee'] = empty($filters['assignees']) ? '-' : implode(',', $filters['assignees']);
        }

        foreach (array('q' => '', 'status' => self::STATUS_OPEN, 'group' => self::GROUP_NONE) as $name => $default) {
            if ($filters[$name] !== $default) {
                $params[$name] = $filters[$name];
            }
        }

        return $params;
    }

    /**
     * Объединённые колонки выбранных проектов, в порядке отображения.
     *
     * @return array key => [key, title, project_ids]
     */
    public function getUnifiedColumns(array $project_ids)
    {
        if (empty($project_ids)) {
            return array();
        }

        $rows = $this->db->table(ColumnModel::TABLE)
            ->columns('id', 'title', 'position', 'project_id')
            ->in('project_id', $project_ids)
            ->asc('project_id')
            ->asc('position')
            ->findAll();

        $byProject = array();

        foreach ($rows as $row) {
            $byProject[$row['project_id']][] = $row;
        }

        $columns = array();

        foreach ($byProject as $project_id => $projectColumns) {
            $count = count($projectColumns);

            foreach (array_values($projectColumns) as $index => $column) {
                $key = $this->getColumnKey($column['title']);

                if (! isset($columns[$key])) {
                    $columns[$key] = array(
                        'key' => $key,
                        'titles' => array(),
                        'weights' => array(),
                        'project_ids' => array(),
                        'column_ids' => array(),
                    );
                }

                $title = trim($column['title']);
                $columns[$key]['titles'][$title] = isset($columns[$key]['titles'][$title]) ? $columns[$key]['titles'][$title] + 1 : 1;
                $columns[$key]['weights'][] = $count > 1 ? $index / ($count - 1) : 0;
                $columns[$key]['project_ids'][] = (int) $project_id;
                $columns[$key]['column_ids'][] = (int) $column['id'];
            }
        }

        foreach ($columns as &$column) {
            arsort($column['titles']);
            $column['title'] = key($column['titles']);
            $column['weight'] = array_sum($column['weights']) / count($column['weights']);
            $column['project_ids'] = array_values(array_unique($column['project_ids']));
            unset($column['titles'], $column['weights']);
        }

        unset($column);

        uasort($columns, function ($a, $b) {
            if (abs($a['weight'] - $b['weight']) > 0.0001) {
                return $a['weight'] < $b['weight'] ? -1 : 1;
            }

            return strcmp($a['key'], $b['key']);
        });

        return $columns;
    }

    /**
     * Задачи выбранных проектов с учётом фильтров (кроме фильтра колонок).
     */
    public function getTasks(array $filters, $user_id)
    {
        if (empty($filters['project_ids'])) {
            return array();
        }

        $query = $this->taskFinderModel->getExtendedQuery()
            ->in(TaskModel::TABLE.'.project_id', $filters['project_ids'])
            ->asc(ProjectModel::TABLE.'.name')
            ->asc(SwimlaneModel::TABLE.'.position')
            ->asc(TaskModel::TABLE.'.position')
            ->asc(TaskModel::TABLE.'.id');

        if ($filters['status'] === self::STATUS_OPEN) {
            $query->eq(TaskModel::TABLE.'.is_active', TaskModel::STATUS_OPEN);
        } elseif ($filters['status'] === self::STATUS_CLOSED) {
            $query->eq(TaskModel::TABLE.'.is_active', TaskModel::STATUS_CLOSED);
        }

        if (! $filters['all_assignees']) {
            $owner_ids = array();

            foreach ($filters['assignees'] as $value) {
                if ($value === 'me') {
                    $owner_ids[] = (int) $user_id;
                } elseif ($value === 'none') {
                    $owner_ids[] = 0;
                } else {
                    $owner_ids[] = (int) $value;
                }
            }

            if (empty($owner_ids)) {
                return array();
            }

            $query->in(TaskModel::TABLE.'.owner_id', array_values(array_unique($owner_ids)));
        }

        if ($filters['q'] !== '') {
            if (preg_match('/^#?(\d+)$/', $filters['q'], $matches)) {
                $query->eq(TaskModel::TABLE.'.id', (int) $matches[1]);
            } else {
                $query->ilike(TaskModel::TABLE.'.title', '%'.$filters['q'].'%');
            }
        }

        $tasks = $query->findAll();

        $tags = $this->taskTagModel->getTagsByTaskIds(array_column($tasks, 'id'));

        foreach ($tasks as &$task) {
            $task['tags'] = isset($tags[$task['id']]) ? $tags[$task['id']] : array();
            $task['is_draggable'] = false;
        }

        return $tasks;
    }

    /**
     * Исполнители, встречающиеся в задачах выбранных проектов: id => name.
     */
    public function getAssignees(array $project_ids)
    {
        if (empty($project_ids)) {
            return array();
        }

        $rows = $this->db->table(TaskModel::TABLE)
            ->columns('users.id', 'users.username', 'users.name')
            ->join('users', 'id', 'owner_id')
            ->in(TaskModel::TABLE.'.project_id', $project_ids)
            ->neq(TaskModel::TABLE.'.owner_id', 0)
            ->groupBy('users.id', 'users.username', 'users.name')
            ->findAll();

        $users = array();

        foreach ($rows as $row) {
            $users[(int) $row['id']] = $row['name'] ?: $row['username'];
        }

        asort($users, SORT_NATURAL | SORT_FLAG_CASE);

        return $users;
    }

    /**
     * Строки доски (группы) с задачами, разложенными по видимым колонкам.
     */
    public function buildBoard(array $tasks, array $columns, array $filters)
    {
        $visible = array();

        foreach ($columns as $key => $column) {
            if ($filters['all_columns'] || in_array($key, $filters['columns'], true)) {
                $visible[$key] = $column;
            }
        }

        $groups = array();

        foreach ($tasks as $task) {
            $columnKey = $this->getColumnKey($task['column_name']);

            if (! isset($visible[$columnKey])) {
                continue;
            }

            list($groupId, $groupName) = $this->getGroup($task, $filters['group']);

            if (! isset($groups[$groupId])) {
                $groups[$groupId] = array(
                    'id' => $groupId,
                    'name' => $groupName,
                    'nb_tasks' => 0,
                    'columns' => array_fill_keys(array_keys($visible), array()),
                );
            }

            $groups[$groupId]['columns'][$columnKey][] = $task;
            $groups[$groupId]['nb_tasks']++;
        }

        if ($filters['group'] !== self::GROUP_NONE) {
            uasort($groups, function ($a, $b) {
                return strnatcasecmp($a['name'], $b['name']);
            });
        }

        foreach ($visible as $key => &$column) {
            $column['nb_tasks'] = 0;

            foreach ($groups as $group) {
                $column['nb_tasks'] += count($group['columns'][$key]);
            }
        }

        unset($column);

        return array('columns' => $visible, 'groups' => array_values($groups));
    }

    private function getGroup(array $task, $group)
    {
        if ($group === self::GROUP_PROJECT) {
            return array('p'.$task['project_id'], $task['project_name']);
        }

        if ($group === self::GROUP_ASSIGNEE) {
            if (empty($task['owner_id'])) {
                return array('u0', t('MultiBoard: without assignee'));
            }

            return array('u'.$task['owner_id'], $task['assignee_name'] ?: $task['assignee_username']);
        }

        return array('all', '');
    }

    private function splitList($value)
    {
        $items = array();

        foreach (explode(',', (string) $value) as $item) {
            $item = trim($item);

            if ($item !== '') {
                $items[] = $item;
            }
        }

        return $items;
    }
}
