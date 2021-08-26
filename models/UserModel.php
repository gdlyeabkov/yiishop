<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

class UserModel extends ActiveRecord {
    private string $id;
    private $email;
    private $password;
    private $name;
    private $age;
    private $moneys;
    private $productsInBucket;

    public static function tableName() {
        return '{{users}}';
    }

}
