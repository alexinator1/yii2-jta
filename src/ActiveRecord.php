<?php

namespace alexinator1\jta;
use Yii;
use yii\base\Exception;


class ActiveRecord extends \yii\db\ActiveRecord
{
    private $_pivotAttributes = [];

    /**
     * Overwrite find method to make it return instance of alexinator1/jta/ActiveQuery class
     * implements working with junction table attributes
     *
     * @return object
     * @throws \yii\base\InvalidConfigException
     */
    public static function find()
    {
        return Yii::createObject(ActiveQuery::className(), [get_called_class()]);
    }

    /**
     * @param $name
     * @param $value
     */
    public function setPivotAttribute($name, $value)
    {
        $this->_pivotAttributes[$name] = $value;
    }

    public function __get($name)
    {
        if(isset($this->_pivotAttributes[$name])) {
            return $this->_pivotAttributes[$name];
        }

        return parent::__get($name);
    }


    public function __set($name, $value)
    {
        if(isset($this->_pivotAttributes[$name])){
            throw new Exception("Attribute '$name' is attached from junction table and read-only");
        }

        parent::__set($name, $value);
    }
}