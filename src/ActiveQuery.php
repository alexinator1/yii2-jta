<?php

namespace alexinator1\jta;

use yii\db\Query;

class ActiveQuery extends \yii\db\ActiveQuery
{
    use JuncActiveRelationTrait;

    public $pivotAttributes = [];


    private $_viaModels;

    public function viaTable($tableName, $link, callable $callable = null, $pivotAttributes = [])
    {

        if(!is_array($pivotAttributes)){
            $pivotAttributes = [$pivotAttributes];
        }

        $relation = new ActiveQuery(get_class($this->primaryModel), [
            'from' => [$tableName],
            'link' => $link,
            'multiple' => true,
            'asArray' => true,
            'pivotAttributes' => $pivotAttributes
        ]);

        $this->via = $relation;

        if ($callable !== null) {
            call_user_func($callable, $relation);
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function prepare($builder)
    {
        // NOTE: because the same ActiveQuery may be used to build different SQL statements
        // (e.g. by ActiveDataProvider, one for count query, the other for row data query,
        // it is important to make sure the same ActiveQuery can be used to build SQL statements
        // multiple times.
        if (!empty($this->joinWith)) {
            $this->buildJoinWith();
            $this->joinWith = null;    // clean it up to avoid issue https://github.com/yiisoft/yii2/issues/2687
        }

        if (empty($this->from)) {
            /* @var $modelClass ActiveRecord */
            $modelClass = $this->modelClass;
            $tableName = $modelClass::tableName();
            $this->from = [$tableName];
        }

        if (empty($this->select) && !empty($this->join)) {
            list(, $alias) = $this->getQueryTableName($this);
            $this->select = ["$alias.*"];
        }

        if ($this->primaryModel === null) {
            // eager loading
            $query = Query::create($this);
        } else {
            // lazy loading of a relation
            $where = $this->where;

            if ($this->via instanceof self) {
                // via junction table
                $viaModels = $this->via->findJunctionRows([$this->primaryModel]);
                $this->filterByModels($viaModels);
            } elseif (is_array($this->via)) {
                // via relation
                /* @var $viaQuery ActiveQuery */
                list($viaName, $viaQuery) = $this->via;
                if ($viaQuery->multiple) {
                    $viaModels = $viaQuery->all();
                    $this->primaryModel->populateRelation($viaName, $viaModels);
                } else {
                    $model = $viaQuery->one();
                    $this->primaryModel->populateRelation($viaName, $model);
                    $viaModels = $model === null ? [] : [$model];
                }
                $this->filterByModels($viaModels);
            } else {
                $this->filterByModels([$this->primaryModel]);
            }


            $this->_viaModels = !empty($viaModels) ? $viaModels : [];
            $query = Query::create($this);
            $this->where = $where;
        }

        if (!empty($this->on)) {
            $query->andWhere($this->on);
        }

        return $query;
    }

    public function populate($rows)
    {
        $models = parent::populate($rows);
        if(!empty($this->link) && !empty($this->via)){
            $this->populateJunctionAttributes($models);
        }
        return $models;
    }


    public function populateJunctionAttributes(&$models)
    {
        $modelKey = array_keys($this->link)[0];
        $viaModelKey = $this->link[$modelKey];

        $pivotAttributes = $this->via->pivotAttributes;

        foreach($models as $model){
            foreach($this->_viaModels as $viaModel){

                if($this->isViaModelRelated($model, $viaModel, $modelKey, $viaModelKey)){
                    $this->populatePivotAttributes($model, $viaModel, $pivotAttributes);
                }
            }
        }
    }


    private function isViaModelRelated($model, $viaModel, $modelKey, $viaModelKey)
    {
        $modelPkValue = is_array($model) ? $model[$modelKey] : $model->{$modelKey};
        return $modelPkValue == $viaModel[$viaModelKey];
    }

}