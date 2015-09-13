<?php

namespace alexinator1\jta;

class ActiveQuery extends \yii\db\ActiveQuery
{
    use JuncActiveRelationTrait;

    public $pivotAttributes = [];

    public function viaTable($tableName, $link, callable $callable = null, $pivotAttributes = [])
    {
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

}