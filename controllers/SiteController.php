<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;

use yii\web\Response;
use yii\web\Request;
use yii\helpers\Url;
use app\models\UserModel;
use app\models\ProductModel;
use app\models\OrderModel;
use \yii\helpers\Json;

use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;

class SiteController extends Controller {

    public $layout = 'yiishop';

    public function actions() {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ]
        ];
    }

    public function actionIndex() {
        return $this->render('index');
    }

    public function actionAdminorders() {
        $response = Yii::$app->getResponse();
        $response->format = Response::FORMAT_JSON;
        $allOrders = OrderModel::find()->all();
        $jsonOrders = [];
        foreach($allOrders as $order){
            array_push($jsonOrders, [
                "id" => $order->__get("id"),
                "ownername" => $order->__get("ownername"),
                "price" => ((int)$order->__get("price")) 
            ]);
        }
        $response->data = $jsonOrders;
        return $response;
    }

    public function actionAdminproductsadd($productname, $productprice) {
        
        // echo("productname: $productname|productprice: $productprice"); 
        // die;

        // $newProduct = new ProductModel;
        // $newProduct->name = \Yii::$app->getRequest()->$queryParams["productname"];
        // $newProduct->price = \Yii::$app->getRequest()->$queryParams["productprice"];;
        // $newProduct.save();

        // $newProduct = new ProductModel;
        // $newProduct->id = 3;
        // $newProduct->name = $productname;
        // $newProduct->price = $productprice;
        // $newProduct->save();
        
        $newProduct = new ProductModel;
        $newProduct->name = \Yii::$app->request->get('productname');
        $newProduct->price = \Yii::$app->request->get('productprice');
        // print_r($newProduct); 
        // die;
        $newProduct->save();

        $response = Yii::$app->getResponse();
        $response->format = Response::FORMAT_JSON;
        $response->data = [ "status" => "OK" ];
        return $response;
    }

    public function actionAdminproductsdelete() {
        $deletedProductName = \Yii::$app->request->get('productname');
        Customer::deleteAll("name = $deletedProductName");
        
        $response = Yii::$app->getResponse();
        $response->format = Response::FORMAT_JSON;
        $response->data = [ "status" => "OK" ];
        return $response;
    }

    public function actionUsersbucketdelete(string $useremail, string $productname, string $productid) {
        
        // $jsonBucket = Json::decode("[
        //     {
        //         \"id\":\"125\",
        //         \"name\":\"Phone\",
        //         \"price\":854
        //     },
        //     {
        //         \"id\":\"1\",
        //         \"name\":\"2\",
        //         \"price\":3
        //     },
        //     {
        //         \"id\":\"4\",
        //         \"name\":\"5\",
        //         \"price\":6
        //     }
        // ]");
        // $jsonBucket = array_filter($jsonBucket, function($product) {
        //     return $product["id"] !== "4";
        // });
        // echo(Json::encode($jsonBucket));
        // die;
        
        $currentUser = UserModel::findOne([ "email" => $useremail]);
        $response = Yii::$app->getResponse();
        $response->format = Response::FORMAT_JSON;
        if($currentUser !== null){
            $jsonBucket = Json::decode($currentUser->productsInBucket);
            $jsonBucket = array_filter($jsonBucket, function($product) use($productid) {
                return $product["id"] !== $productid;
            });
            $currentUser->productsInBucket = Json::encode($jsonBucket);
            $currentUser->save();
        } else {
            $response->data = [ "status" => "Error", "message" => "Error" ];
            return $response;
        }
        $response->data = [ "status" => "OK", "message" => "success" ];
        return $response;
    }

    public function actionUsersbucketbuy($useremail) {
        // $commonPrice = 0;
        // $jsonBucket = Json::decode("[
        //     {
        //         \"id\":\"125\",
        //         \"name\":\"Phone\",
        //         \"price\":854
        //     },
        //     {
        //         \"id\":\"15\",
        //         \"name\":\"Ipad\",
        //         \"price\":54
        //     }
        // ]");
        // $moneys = 1000;
        // foreach($jsonBucket as $product){
        //     $commonPrice += $product["price"];
        // }
        // if($moneys >= $commonPrice){
        //     $jsonBucket = Json::encode("[]");
        //     $moneys -= $commonPrice;
        //     echo "куплено moneys: $moneys, commonPrice: $commonPrice";
        //     echo($jsonBucket);
        // } else {
        //     echo "некуплено moneys: $moneys, commonPrice: $commonPrice";
        //     print_r($jsonBucket);
        // }
        // die;

        $response = Yii::$app->getResponse();
        $response->format = Response::FORMAT_JSON;
        
        $currentUser = UserModel::findOne([ "email" => $useremail]);
        if($currentUser !== null){
            $jsonBucket = Json::decode($currentUser->productsInBucket);
            $commonPrice = 0;
            foreach($jsonBucket as $product){
                $commonPrice += $product["price"];
            }
            if($currentUser->moneys >= $commonPrice){
                $currentUser->moneys -= $commonPrice;
                $currentUser->productsInBucket = "[]";
                $currentUser->update();
                $newOrder = new OrderModel;
                $newOrder->ownername = $useremail;
                $newOrder->price = $commonPrice;
                $newOrder->save();
                $response->data = [ "success" => "OK", "message" => "success" ];
            } else {
                $response->data = [ "success" => "Error", "message" => "Error" ];
            }   
        } else {
            $response->data = [ "success" => "Error", "message" => "Error" ];
        }
        return $response;
    }

    public function actionUsersamount(string $useremail, int $amount) {

        $response = Yii::$app->getResponse();
        $response->format = Response::FORMAT_JSON;
        
        $currentUser = UserModel::findOne(["email" => $useremail]);
        if($currentUser !== null){
            $currentUser->moneys += $amount;
            $currentUser->update();
            $response->data = [ "status" => "OK", "moneys" => $currentUser->__get("moneys"), "message" => "success" ];
        } else {
            $response->data = [ "status" => "Error", "message" => "Error" ];
        }

        return $response;
        
    }

    public function actionUserscheck($useremail, $userpassword) {
        
        $response = Yii::$app->getResponse();
        $response->format = Response::FORMAT_JSON;
        $response->data = [ "status" => "Error", "message" => "Error" ];

        $currentUser = UserModel::findOne([ "email" => $useremail]);
        if($currentUser !== null){
            $passwordCheck = \Yii::$app->getSecurity()->validatePassword($userpassword, $currentUser->password) && $userpassword !== '';
            if($passwordCheck && $useremail === $currentUser->email) {
                $response->data = [ "status" => "OK", "user" => $currentUser ];
            } else {
                $response->data = [ "status" => "Error", "message" => "Error" ];
            }
        } else {
            $response->data = [ "status" => "Error", "message" => "Error" ];
        }
        return $response;
    }

    public function actionUsersusercreatesuccess(string $useremail, string $userpassword, string $username, int $userage) {
        
        // echo("useremail: $useremail");
        // die;

        $allUsers = UserModel::findAll(true);
        $userExists = false;
        $response = Yii::$app->getResponse();
        $response->format = Response::FORMAT_JSON;
        foreach($allUsers as $user){
            if($useremail === $user->__get("email")){
                $userExists = true;
            }
        }
        if($userExists){
            $response->data = [ "status" => "rollback" ];
            return $response;
        } else {
            $encodedPassword = "#";
            
            // $encodedPassword = \Yii::$app->getSecurity()->generatePasswordHash(\Yii::$app->request->get("userpassword"));
            // $encodedPassword = \Yii::$app->getSecurity()->generatePasswordHash($userpassword);
            $encodedPassword = \Yii::$app->getSecurity()->generatePasswordHash(\Yii::$app->getRequest()->queryParams["userpassword"]);

            $newUser = new UserModel;
            
            // $newUser->email = \Yii::$app->request->get("useremail");
            // $newUser->password = $encodedPassword;
            // $newUser->name = \Yii::$app->request->get("username");
            // $newUser->age = \Yii::$app->request->get("userage");
            
            // $newUser->email = $useremail;
            // $newUser->password = $encodedPassword;
            // $newUser->name = $username;
            // $newUser->age = $userage;

            $newUser->email = \Yii::$app->getRequest()->queryParams["useremail"];
            $newUser->password = $encodedPassword;
            $newUser->name = \Yii::$app->getRequest()->queryParams["username"];
            $newUser->age = \Yii::$app->getRequest()->queryParams["userage"];

            $newUser->save();

            $response->data = [ "status" => "OK" ];
            return $response;
        }
    }

    public function actionUsersbucketadd(string $useremail, string $productname, int $productprice) {
        
        // $jsonBucket = Json::decode("[
        //     {
        //         \"id\":\"125\",
        //         \"name\":\"Phone\",
        //         \"price\":854
        //     }
        // ]");
        // array_push($jsonBucket, [
        //     "id" => "1",
        //     "name" => "2",
        //     "price" => "3"
        // ]);
        // // print_r($jsonBucket);
        // echo Json::encode($jsonBucket);
        // die;

        $currentUser = UserModel::findOne([ "email" => $useremail]);
        
        $response = Yii::$app->getResponse();
        $response->format = Response::FORMAT_JSON;

        if($currentUser !== null){
            $jsonBucket = Json::decode($currentUser->productsInBucket);
            array_push($jsonBucket, [
                "id" => ((string)rand(1, 500)),
                "name" => "$productname",
                "price" => $productprice
            ]);
            $currentUser->productsInBucket = Json::encode($jsonBucket);
            $currentUser->save();
            $response->data = [ "status" => "OK", "message" => "success" ];
        } else {
            $response->data = [ "status" => "Error", "message" => "Error" ];
        }
        
        return $response;
    }

    public function actionUsersbucket(string $useremail) {
        $currentUser = UserModel::findOne([ "email" => $useremail]);
        $response = Yii::$app->getResponse();
        $response->format = Response::FORMAT_JSON;
        if($currentUser !== null){
            $jsonProductsInBucket = Json::decode($currentUser->productsInBucket);
            // echo $jsonProductsInBucket;
            // $bucketParts = explode("\"", $jsonProductsInBucket);
            // echo implode("", $bucketParts);
            // die;
            // $bucket = implode("", $bucketParts);
            $bucket = $jsonProductsInBucket;
            
            $response->data = [ "productsInBucket" => $bucket, "message" => "success" ];
        } else {
            $response->data = [ "productsInBucket" => "[]", "message" => "success" ];
        }
        return $response;
    }

    public function actionProduct() {
        $response = Yii::$app->getResponse();
        $response->format = Response::FORMAT_JSON;
        
        $urlParts = explode("/", Yii::$app->request->url);
        $currentProduct = ProductModel::findOne(["id" => $urlParts[3]]);
        if($currentProduct !== null){
            $returnedProduct = [
                "id" => $currentProduct->__get("id"),
                "name" => $currentProduct->__get("name"),
                "price" => (int)$currentProduct->__get("price"),
            ];
            $response->data = [ "product" => $returnedProduct ];
        } else {
            $response->data = [ "product" => [] ];
        }
        
        return $response;
    }

    public function actionHome() {
        
        $allProducts = ProductModel::find()->all();
        $jsonProducts = [];
        foreach($allProducts as $product){
            array_push($jsonProducts, [
                "id" => $product->__get("id"),
                "name" => $product->__get("name"),
                "price" => ((int)$product->__get("price")) 
            ]);
        }
        $response = Yii::$app->getResponse();
        $response->format = Response::FORMAT_JSON;
        $response->data = [
            "allProducts" => $jsonProducts,
            "message" => "success" 
        ];
        return $response;
    }

    public function actionOthers() {
        
        $exception = Yii::$app->errorHandler->exception;
        echo "redirect";
        die;
        if ($exception instanceof \yii\web\NotFoundHttpException) {
            return $this->render('index', ['redirectroute' => Url::current()]);
        } else {
            return $this->render('index', ['redirectroute' => Url::current()]);
            echo(Yii::$app->request->url);
        }

    }

}
