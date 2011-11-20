<?php

/**
 * BaseMetrics
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @property integer $id
 * @property integer $userCount
 * @property timestamp $createdAt
 * @property timestamp $updatedAt
 *
 */
abstract class BaseMetrics extends Aerial_Record
{
    public function setTableDefinition()
    {
        $this->setTableName('Metrics');
        $this->hasColumn('id', 'integer', 4, array(
             'type' => 'integer',
             'primary' => true,
             'unsigned' => true,
             'autoincrement' => true,
             'length' => '4',
             ));
        $this->hasColumn('userCount', 'integer', 4, array(
             'type' => 'integer',
             'length' => '4',
             ));
        $this->hasColumn('createdAt', 'timestamp', null, array(
             'type' => 'timestamp',
             ));
        $this->hasColumn('updatedAt', 'timestamp', null, array(
             'type' => 'timestamp',
             ));

        $this->option('collate', 'utf8_unicode_ci');
        $this->option('charset', 'utf8');
        $this->option('type', 'InnoDB');
    }

    public function setUp()
    {
        parent::setUp();
        $timestampable0 = new Doctrine_Template_Timestampable();
        $this->actAs($timestampable0);
    }

    public function construct()
    {
        $this->mapValue('_explicitType', 'za.co.rsajobs.vo.Metrics');
    }
}