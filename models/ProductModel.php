<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

class ProductModel extends ActiveRecord {
    private $id;
    private $name;
    private $price;

    public static function tableName() {
        return '{{products}}';
    }

    public function rules() {
        return [
            [['id', 'name'], 'string', 'max' => 255],
            [['price'], 'integer', 'max' => 255]
        ];
    }

}
