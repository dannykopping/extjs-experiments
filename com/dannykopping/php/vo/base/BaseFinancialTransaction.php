<?php

/**
 * BaseFinancialTransaction
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @property integer $id
 * @property double $amount
 * @property integer $numCredits
 * @property string $bank
 * @property string $reference
 * @property integer $userId
 * @property timestamp $createdAt
 * @property timestamp $updatedAt
 * @property User $User
 * @property Doctrine_Collection $billingHistories
 * @property Doctrine_Collection $credits
 *
 */
abstract class BaseFinancialTransaction extends Aerial_Record
{
    public function setTableDefinition()
    {
        $this->setTableName('FinancialTransaction');
        $this->hasColumn('id', 'integer', 4, array(
             'type' => 'integer',
             'primary' => true,
             'autoincrement' => true,
             'length' => '4',
             ));
        $this->hasColumn('amount', 'double', null, array(
             'type' => 'double',
             ));
        $this->hasColumn('numCredits', 'integer', 4, array(
             'type' => 'integer',
             'length' => '4',
             ));
        $this->hasColumn('bank', 'string', 100, array(
             'type' => 'string',
             'notnull' => true,
             'length' => '100',
             ));
        $this->hasColumn('reference', 'string', 16, array(
             'type' => 'string',
             'unique' => true,
             'notnull' => true,
             'length' => '16',
             ));
        $this->hasColumn('userId', 'integer', 4, array(
             'type' => 'integer',
             'unsigned' => true,
             'notnull' => true,
             'length' => '4',
             ));
        $this->hasColumn('createdAt', 'timestamp', null, array(
             'type' => 'timestamp',
             ));
        $this->hasColumn('updatedAt', 'timestamp', null, array(
             'type' => 'timestamp',
             ));


        $this->index('fk_FinancialTransaction_User1', array(
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

        $this->hasMany('BillingHistory as billingHistories', array(
             'local' => 'id',
             'foreign' => 'financialTransactionId'));

        $this->hasMany('Credit as credits', array(
             'local' => 'id',
             'foreign' => 'financialTransactionId'));

        $timestampable0 = new Doctrine_Template_Timestampable();
        $this->actAs($timestampable0);
    }

    public function construct()
    {
        $this->mapValue('_explicitType', 'za.co.rsajobs.vo.FinancialTransaction');
    }
}