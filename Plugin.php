<?php

namespace DamianLewis\SortableRelations;

use System\Classes\PluginBase;

/**
 * SortableRelations Plugin Information File
 *
 * @package DamianLewis\SortableRelations
 */
class Plugin extends PluginBase
{
    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'Sortable Relations',
            'description' => 'Manage the sort order of many-to-many relations in the view list of the relations controller.',
            'author'      => 'Damian Lewis',
            'icon'        => 'icon-sort'
        ];
    }
}
