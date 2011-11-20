<?php

/**
 * BaseFilterUserDataMatch
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @property integer $id
 * @property string $name
 * @property double $possibleScore
 * @property double $achievedScore
 * @property integer $filterDataId
 * @property integer $filterUserMatchId
 * @property FilterData $FilterData
 * @property FilterUserMatch $FilterUserMatch
 *
 */
abstract class BaseFilterUserDataMatch extends Aerial_Record
{
    public function setTableDefinition()
    {
        $this->setTableName('FilterUserDataMatch');
        $this->hasColumn('id', 'integer', 4, array(
             'type' => 'integer',
             'primary' => true,
             'autoincrement' => true,
             'length' => '4',
             ));
        $this->hasColumn('name', 'string', 255, array(
             'type' => 'string',
             'length' => '255',
             ));
        $this->hasColumn('possibleScore', 'double', null, array(
             'type' => 'double',
             ));
        $this->hasColumn('achievedScore', 'double', null, array(
             'type' => 'double',
             ));
        $this->hasColumn('filterDataId', 'integer', 4, array(
             'type' => 'integer',
             'notnull' => true,
             'length' => '4',
             ));
        $this->hasColumn('filterUserMatchId', 'integer', 4, array(
             'type' => 'integer',
             'notnull' => true,
             'length' => '4',
             ));


        $this->index('fk_FilterUserDataMatch_FilterData1', array(
             'fields' => 
             array(
              0 => 'filterDataId',
             ),
             ));
        $this->index('fk_FilterUserDataMatch_FilterUserMatch1', array(
             'fields' => 
             array(
              0 => 'filterUserMatchId',
             ),
             ));
        $this->option('collate', 'utf8_unicode_ci');
        $this->option('charset', 'utf8');
        $this->option('type', 'InnoDB');
    }

    public function setUp()
    {
        parent::setUp();
        $this->hasOne('FilterData', array(
             'local' => 'filterDataId',
             'foreign' => 'id',
             'onDelete' => 'cascade',
             'onUpdate' => 'cascade'));

        $this->hasOne('FilterUserMatch', array(
             'local' => 'filterUserMatchId',
             'foreign' => 'id',
             'onDelete' => 'cascade',
             'onUpdate' => 'cascade'));
    }

    public function construct()
    {
        $this->mapValue('_explicitType', 'za.co.rsajobs.vo.FilterUserDataMatch');
    }
}