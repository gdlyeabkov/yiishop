<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

class OrderModel extends ActiveRecord
{
    private $id;
    private $ownername;
    private $price;

    public static function tableName() {
        return '{{orders}}';
    }

}
