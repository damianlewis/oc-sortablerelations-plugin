<?php

namespace DamianLewis\SortableRelations\Traits;

use DamianLewis\October\Pivot\Traits\PivotEventTrait;
use Model;
use October\Rain\Database\Collection;
use October\Rain\Exception\ApplicationException;

trait SortableRelations
{
    use PivotEventTrait;

    /**
     * Registers the pivot events to listen for.
     *
     * @return void
     */
    public static function boot()
    {
        parent::boot();

        // After attaching a new the relation reload the relations to update the cache.
        static::pivotAttached(function ($model, $relationName) {
            foreach ($model->sortableRelations as $sortableRelationName) {
                if ($relationName == $sortableRelationName) {
                    $model->reloadRelations($relationName);
                }
            }
        });

        // Before updating the relation sort order, reorder the relations that are affected by the update.
        static::pivotUpdating(function ($model, $relationName, $pivotIds, $pivotIdsAttributes) {
            foreach ($model->sortableRelations as $sortableRelationName) {
                if ($relationName == $sortableRelationName) {
                    foreach ($pivotIdsAttributes as $id => $attributes) {
                        $relation = $model->{$relationName}()
                            ->where('id', $id)
                            ->first();
                        $target = $model->{$relationName}()
                            ->wherePivot('relation_sort_order', $attributes['relation_sort_order'])
                            ->first();

                        if ($relation->pivot->relation_sort_order > $target->pivot->relation_sort_order) {
                            $model->shiftRelations($relationName, $target, false, false, $relation->pivot->relation_sort_order);
                        } else {
                            $model->shiftRelations($relationName, $target, true, true, $relation->pivot->relation_sort_order);
                        }

                        $model->reloadRelations($relationName);
                    }
                }
            }
        });

        // Before removing the relation, reorder the relations that are affected by the removal.
        static::pivotDetaching(function ($model, $relationName, $pivotIds) {
            foreach ($model->sortableRelations as $sortableRelationName) {
                if ($relationName == $sortableRelationName) {
                    foreach ($pivotIds as $id) {
                        if (!is_null($target = $model->getAdjacentRelation($relationName, $id))) {
                            $model->shiftRelations($relationName, $target);
                            $model->reloadRelations($relationName);
                        }
                    }
                }
            }
        });
    }

    /**
     * Add the default sort order to the pivot attributes.
     *
     * @param string $relationName
     * @param mixed  $ids
     * @param array  $attributes
     *
     * @return array
     * @throws \October\Rain\Exception\ApplicationException
     */
    public function changeAttributes($relationName, $ids, $attributes = [])
    {
        foreach ($this->sortableRelations as $sortableRelationName) {
            if ($relationName == $sortableRelationName) {
                if ($ids instanceof Collection) {
                    throw new ApplicationException('Expected an array but got a collection when attaching the relations.');
                }

                if (is_int($ids) || $ids instanceof Model) {
                    $attributes['relation_sort_order'] = $this->{$relationName}->count() + 1;
                }

                if (is_array($ids)) {
                    $newIds = [];
                    $count = 1;

                    foreach ($ids as $index => $id) {
                        if (is_int($id)) {
                            $newIds[$id] = ['relation_sort_order' => $this->{$relationName}->count() + $count++];
                        }

                        if (is_array($id)) {
                            $newIds[$index]['relation_sort_order'] = $this->{$relationName}->count() + $count++;
                        }
                    }

                    $ids = $newIds;
                }

                return [$ids, $attributes];
            }
        }
    }

    /**
     * Shift the relations left or right (up or down the sorted relations list).
     *
     * @param string                       $relationName
     * @param \October\Rain\Database\Model $relation
     * @param bool                         $isLeftShift
     * @param bool                         $inReverse Traverse the relations list in reverse
     * @param int                          $untilSortOrder
     *
     * @return void
     */
    protected function shiftRelations(
        $relationName,
        $relation,
        $isLeftShift = true,
        $inReverse = false,
        $untilSortOrder = null
    ) {
        if (!is_null($untilSortOrder) && $relation->pivot->relation_sort_order == $untilSortOrder) {
            return;
        }

        if (!is_null($target = $this->getAdjacentRelation($relationName, $relation, $inReverse))) {
            $this->shiftRelations($relationName, $target, $isLeftShift, $inReverse, $untilSortOrder);
        }

        $isLeftShift ? $relation->pivot->relation_sort_order -= 1 : $relation->pivot->relation_sort_order += 1;
        $relation->pivot->save();
    }

    /**
     * Get the next or previous relation in the sorted relations list.
     *
     * @param string                           $relationName
     * @param \October\Rain\Database\Model|int $relation
     * @param bool                             $isPrevious
     *
     * @return \October\Rain\Database\Model|null
     */
    protected function getAdjacentRelation($relationName, $relation, $isPrevious = false)
    {
        $relation = $this->parseRelation($relationName, $relation);

        return $this->{$relationName}()
            ->wherePivot('relation_sort_order', $relation->pivot->relation_sort_order + (1 * ($isPrevious ? -1 : 1)))
            ->first();
    }

    /**
     * Return the model for the relation.
     *
     * @param string                           $relationName
     * @param \October\Rain\Database\Model|int $relation
     *
     * @return \October\Rain\Database\Model
     */
    protected function parseRelation($relationName, $relation)
    {
        if (is_int($relation)) {
            $relation = $this->{$relationName}->where('id', $relation)->first();
        }

        return $relation;
    }
}