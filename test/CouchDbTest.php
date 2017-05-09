<?php
/**
 * Created by PhpStorm.
 * User: mike
 * Date: 09.05.2017
 * Time: 21:39
 */

use phplib\DbAccess\CouchDb;

class CouchDbTest extends PHPUnit_Framework_TestCase
{
    private $server = 'http://127.0.0.1';
    private $port = '5984';
    private $user = 'blobuser';
    private $pass = 'b5%7&%E$vT.N';
    private $db = 'testdb';

    /*
    $cdb = new CouchDb();
    var_dump($cdb->test());
    */
}
