<?php

/**
 * BaseExternalAccount
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @property integer $id
 * @property string $type
 * @property string $accessId
 * @property integer $userId
 * @property User $User
 *
 */
abstract class BaseExternalAccount extends Aerial_Record
{
    public function setTableDefinition()
    {
        $this->setTableName('ExternalAccount');
        $this->hasColumn('id', 'integer', 4, array(
             'type' => 'integer',
             'primary' => true,
             'autoincrement' => true,
             'length' => '4',
             ));
        $this->hasColumn('type', 'string', 255, array(
             'type' => 'string',
             'notnull' => true,
             'length' => '255',
             ));
        $this->hasColumn('accessId', 'string', 255, array(
             'type' => 'string',
             'notnull' => true,
             'length' => '255',
             ));
        $this->hasColumn('userId', 'integer', 4, array(
             'type' => 'integer',
             'unsigned' => true,
             'notnull' => true,
             'length' => '4',
             ));


        $this->index('fk_ExternalAccount_User1', array(
             'fields' => 
             array(
              0 => 'userId',
             ),
             ));
        $this->option('collate', 'utf8_unicode_ci');
        $this->option('charset', 'utf8');
        $this->option('type', 'InnoDB');
    }

    public function setUp()
    {
        parent::setUp();
        $this->hasOne('User', array(
             'local' => 'userId',
             'foreign' => 'id',
             'onDelete' => 'cascade',
             'onUpdate' => 'cascade'));
    }

    public function construct()
    {
        $this->mapValue('_explicitType', 'za.co.rsajobs.vo.ExternalAccount');
    }
}