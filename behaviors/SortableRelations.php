<?php

namespace DamianLewis\SortableRelations\Behaviors;

use October\Rain\Exception\ApplicationException;
use Backend\Classes\ControllerBehavior;

class SortableRelations extends ControllerBehavior
{
    /**
     * @inheritDoc
     */
    protected $requiredProperties = ['sortableRelationConfig'];

    /**
     * @var array Configuration values that must exist when applying the primary config file.
     * - modelClass: Class name for the parent models
     * - relationName: Name of the sortable relation
     */
    protected $requiredConfig = ['modelClass', 'relationName'];

    /**
     * The parent model class name.
     *
     * @var string
     */
    public $modelClass;

    /**
     * The relation name.
     *
     * @var string
     */
    public $relationName;

    /**
     * SortableRelations constructor.
     *
     * @param $controller
     *
     * @throws \ApplicationException
     * @throws \SystemException
     */
    public function __construct($controller)
    {
        parent::__construct($controller);

        $this->assetPath = '/plugins/damianlewis/sortablerelations/assets';

        /*
         * Build configuration
         */
        $this->config = $this->makeConfig($controller->sortableRelationConfig, $this->requiredConfig);

        $this->initRelationList();
    }

    /**
     * Disable sorting and order the view.list by the pivot sort order.
     *
     * @param object $config
     *
     * @return void
     */
    public function relationExtendConfig($config)
    {
        $config->view['showSorting'] = false;
        $config->view['defaultSort'] = [
            'column'    => 'pivot[relation_sort_order]',
            'direction' => 'asc'
        ];
    }

    /**
     * Ajax event handler to update the relation sort order with a new position.
     *
     * @return void
     * @throws \October\Rain\Exception\ApplicationException
     */
    public function update_onRelationReorder()
    {
        $model = $this->getParentModelClass();
        $relationName = $this->getRelationName();
        $parentId = $this->getParentId();
        $relatedId = $this->getRelatedId();

        call_user_func_array("{$model}::find", [$parentId])
            ->{$relationName}()
            ->updateExistingPivot($relatedId, ['relation_sort_order' => post('position')]);
    }

    /**
     * Add the page assets need for sorting.
     *
     * @return void
     */
    protected function initRelationList()
    {
        $this->addJs('js/Sortable.js'); // Sortable.min.js has a bug: https://stackoverflow.com/questions/48804134/rubaxa-sortable-failed-to-execute-matches-on-element-is-not-a-valid-se
        $this->addJs('js/list-widget-sortable.js');
        $this->addCss('css/list-widget-sortable.css');
    }

    /**
     * Get the name of the parent model class from the config file.
     *
     * @return string
     * @throws \October\Rain\Exception\ApplicationException
     */
    protected function getParentModelClass()
    {
        if ($this->modelClass !== null) {
            return $this->modelClass;
        }

        $modelClass = $this->getConfig('modelClass');

        if (!$modelClass) {
            throw new ApplicationException('Please specify the modelClass property for the parent model');
        }

        return $this->modelClass = $modelClass;
    }

    /**
     * Get the name of the relation from the config file.
     *
     * @return string
     * @throws \October\Rain\Exception\ApplicationException
     */
    protected function getRelationName()
    {
        if ($this->relationName !== null) {
            return $this->relationName;
        }

        $relationName = $this->getConfig('relationName');

        if (!$relationName) {
            throw new ApplicationException('Please specify the relationName property');
        }

        return $this->relationName = $relationName;
    }

    /**
     * Get the posted id for the related model.
     *
     * @return int
     * @throws \October\Rain\Exception\ApplicationException
     */
    protected function getRelatedId()
    {
        if ($relatedId = post('relatedId')) {
            return (int)$relatedId;
        }

        throw new ApplicationException('Please specify the ID for the related model.');
    }

    /**
     * Get the posted id for the parent model.
     *
     * @return int
     * @throws \October\Rain\Exception\ApplicationException
     */
    protected function getParentId()
    {
        if ($parentId = post('parentId')) {
            return (int)$parentId;
        }

        throw new ApplicationException('Please specify the ID for the parent model.');
    }
}