<?php

use Phalcon\Mvc\Controller;
use MongoDB\BSON\ObjectID;

/**
 * IndexController class
 *
 * @package Spotify Main
 *
 */
class MongoController extends Controller
{
    public function indexAction()
    {
        $user = new Users($this->mongo, 'user', 'user');

        // $insertOneResult = $user->insertOne([
        //     'username' => '$username',
        // ]);

        // printf("Inserted %d document(s)\n", $insertOneResult->getInsertedCount());
        $deleteResult =  $user->deleteOne(['_id' => new ObjectID((string) '6257b5aa9cf87b33680ef543')]);
        // db.test_users.deleteOne( );
        printf("Deleted %d document(s)\n", $deleteResult->getDeletedCount());
        echo "<pre>";
        print_r($user);
    }

    public function loginAction()
    {
    }
    public function registerAction()
    {
    }

    public function logoutAction()
    {
    }
}
