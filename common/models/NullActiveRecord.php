<?php

namespace common\models;

use Yii;

/**
 * This is the base null model class for any ActiveRecord
 *
 * $URL: https://tarax.toyouke.com/svn/MALL/common/models/NullActiveRecord.php $
 * $Id: NullActiveRecord.php 1223 2015-08-02 01:35:03Z mori $
 *
 */

abstract class NullActiveRecord extends \yii\base\Model implements \yii\db\ActiveRecordInterface
{
    abstract public static function primaryKey();

    public function getAttribute($name)
    {
        return $this->$name;
    }

    public function setAttribute($name, $value)
    {
        return;
    }

    public function hasAttribute($name)
    {
        return parent::hasAttribute($name);
    }

    abstract public function getPrimaryKey($asArray = false);

    abstract public function getOldPrimaryKey($asArray = false);

    abstract public static function isPrimaryKey($keys);

    public static function find()
    {
        return new \yii\db\ActiveQueryInterface();
    }

    public static function findOne($condition)
    {
        return new static();
    }

    public static function findAll($condition)
    {
        return [ static::findOne($condition) ];
    }

    /* @return integer the number of rows updated */
    public static function updateAll($attributes, $condition = null)
    {
        return 0;
    }

    /* @return integer the number of rows deleted */
    public static function deleteAll($condition = null)
    {
        return 0;
    }

    /* @return boolean whether the saving succeeded */
    public function save($runValidation = true, $attributeNames = null)
    {
        return false;
    }

    /* @return boolean whether the attributes are valid and the record is inserted successfully. */
    public function insert($runValidation = true, $attributes = null)
    {
        return false;
    }


    /* @return integer|boolean the number of rows affected, or false if validation fails
     * or updating process is stopped for other reasons.
     * Note that it is possible that the number of rows affected is 0, even though the
     * update execution is successful.
     */
    public function update($runValidation = true, $attributeNames = null)
    {
        return 0;
    }

     /* @return integer|boolean the number of rows deleted, or false if the deletion is unsuccessful for some reason. */
    public function delete()
    {
        return 0;
    }

    public function getIsNewRecord()
    {
        return false;
    }

    /**
     * Returns a value indicating whether the given active record is the same as the current one.
     * Two [[getIsNewRecord()|new]] records are considered to be not equal.
     * @param static $record record to compare to
     * @return boolean whether the two active records refer to the same row in the same database table.
     */
    public function equals($record)
    {
        return $record instanceof static;
    }

    /**
     * Returns the relation object with the specified name.
     * A relation is defined by a getter method which returns an object implementing the [[ActiveQueryInterface]]
     * (normally this would be a relational [[ActiveQuery]] object).
     * It can be declared in either the ActiveRecord class itself or one of its behaviors.
     * @param string $name the relation name
     * @param boolean $throwException whether to throw exception if the relation does not exist.
     * @return ActiveQueryInterface the relational query object
     */
    public function getRelation($name, $throwException = true)
    {
        return new \yii\db\ActiveQueryInterface();
    }

    /*
     * @param string $name the case sensitive name of the relationship.
     * @param static $model the record to be linked with the current one.
     * @param array $extraColumns additional column values to be saved into the junction table.
     * This parameter is only meaningful for a relationship involving a junction table
     * (i.e., a relation set with `[[ActiveQueryInterface::via()]]`.)
     */
    public function link($name, $model, $extraColumns = [])
    {
        return;
    }

    public function unlink($name, $model, $delete = false)
    {
        return;
    }

    public static function getDb()
    {
        return Yii::$app->db;
    }

}
