<?php

/**
 * BaseAccountVerificationCode
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @property integer $id
 * @property string $code
 * @property timestamp $createdAt
 * @property timestamp $updatedAt
 * @property Doctrine_Collection $users
 *
 */
abstract class BaseAccountVerificationCode extends Aerial_Record
{
    public function setTableDefinition()
    {
        $this->setTableName('AccountVerificationCode');
        $this->hasColumn('id', 'integer', 4, array(
             'type' => 'integer',
             'primary' => true,
             'autoincrement' => true,
             'length' => '4',
             ));
        $this->hasColumn('code', 'string', 40, array(
             'type' => 'string',
             'notnull' => true,
             'length' => '40',
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
        $this->hasMany('User as users', array(
             'local' => 'id',
             'foreign' => 'accountVerificationCodeId'));

        $timestampable0 = new Doctrine_Template_Timestampable();
        $this->actAs($timestampable0);
    }

    public function construct()
    {
        $this->mapValue('_explicitType', 'za.co.rsajobs.vo.AccountVerificationCode');
    }
}