<?php

namespace phplib\DbAccess;

use InvalidArgumentException;
use UnexpectedValueException;

class CouchDb
{
    private $couch_server = 'http://127.0.0.1';
    private $couch_port = '5984';
    private $couch_user = 'blobuser';
    private $couch_pass = 'b5%7&%E$vT.N';
    private $couch_db = 'testdb';//'blobfarmer';

    public function setServer($server = 'http://127.0.0.1', $port = 5984)
    {
        if(!is_string($server)){
            throw new InvalidArgumentException('server have to string.');
        }
        $this->couch_server = $server;

        if(!is_string($port) && !is_int($port)){
            throw new InvalidArgumentException('port have to string or int.');
        }
        $this->couch_port = $port;
    }

    public function setDb($db)
    {
        if($db == null || !is_string($db)){
            throw new InvalidArgumentException('db have to be string.');
        }
        $this->couch_db = str_replace('/','',$db);
    }

    public function setUser($user,$pass)
    {
        if($user == null || $user == '' || !is_string($user)){
            throw new InvalidArgumentException('user have to be string. if you want to use public access dont call the function setUser(...).');
        }
        $this->couch_user = $user;

        if($pass == null || $pass == '' || !is_string($pass)){
            throw new InvalidArgumentException('passwort have to be string. if you want to use public access dont call the function setUser(...).');
        }
        $this->couch_pass = $pass;

    }

    public function get(){}

    public function put(){}


    public function test()
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->couch_server . ':' . $this->couch_port . '/' . $this->couch_db . '/_design/testdocs/_view/all');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-type: application/json',
            'Accept: */*'
        ));

        curl_setopt($ch, CURLOPT_USERPWD, $this->couch_user . ':' . $this->couch_pass);
        $response = curl_exec($ch);

        curl_close($ch);

        return $response;
    }

}

$cdb = new CouchDb();
var_dump($cdb->test());