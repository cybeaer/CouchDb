<?php

namespace phplib\DbAccess;

use InvalidArgumentException;
use UnexpectedValueException;

class CouchDb
{
    private $couch_server = '';
    private $couch_port = '';
    private $couch_user = '';
    private $couch_pass = '';
    private $couch_db = '';

    /* ------------------------- options ------------------------- */
    /**
     * @param string $server
     * @param int $port
     */
    public function setServer($server = 'http://127.0.0.1', $port = 5984)
    {
        if (!is_string($server)) {
            throw new InvalidArgumentException('server have to string.');
        }
        $this->couch_server = $server;

        if (!is_string($port) && !is_int($port)) {
            throw new InvalidArgumentException('port have to string or int.');
        }
        $this->couch_port = $port;
    }

    /**
     * @param $db
     */
    public function setDb($db)
    {
        if ($db == null || !is_string($db)) {
            throw new InvalidArgumentException('db have to be string.');
        }
        $this->couch_db = str_replace('/', '', $db);
    }

    /**
     * @param $user
     * @param $pass
     */
    public function setUser($user, $pass)
    {
        if ($user == null || $user == '' || !is_string($user)) {
            throw new InvalidArgumentException('user have to be string. if you want to use public access dont call the function setUser(...).');
        }
        $this->couch_user = $user;

        if ($pass == null || $pass == '' || !is_string($pass)) {
            throw new InvalidArgumentException('passwort have to be string. if you want to use public access dont call the function setUser(...).');
        }
        $this->couch_pass = $pass;

    }

    /* ------------------------- publics ------------------------- */
    /**
     * @param $design
     * @param $view
     * @param null $key
     * @param null $keyValue
     * @return mixed
     */
    public function getView($design, $view, $key = null, $keyValue = null)
    {
        if ($design == null || !is_string($design) || $design == '') {
            throw new InvalidArgumentException('design have to be string and cant be null or empty.');
        }
        if ($view == null || !is_string($view) || $view = '') {
            throw new InvalidArgumentException('view have to be string and cant be null or empty.');
        }
        if ($key != null && (!is_string($key) || $key == '')) {
            throw new InvalidArgumentException('when key is given it have to be string and cant be empty.');
        }
        if ($keyValue != null && (!is_string($keyValue) || $keyValue = '')) {
            throw new InvalidArgumentException('when key value is given it have to be string and cant be empty.');
        }
        $ch = $this->curlPrepare('GET', $this->couch_user, $this->couch_pass);
        if ($key != null && $keyValue != null) {
            $ch = $this->curlSetUrl($ch, '_design/' . $design . '/_view/' . $view . '?' . $key . '=\'' . $keyValue . '\'');
        } else {
            $ch = $this->curlSetUrl($ch, '_design/' . $design . '/_view/' . $view);
        }
        $res = $this->curlExec($ch);
        $this->curlClose($ch);
        return $res;
    }

    /**
     * @param $ch
     * @return mixed
     */
    private function curlExec($ch)
    {
        if ($ch == null) {
            throw new InvalidArgumentException('curl object cant be null.');
        }
        $response = curl_exec($ch);
        return $response;
    }

    /* ------------------------- privates ------------------------- */
    /**
     * @param $ch
     */
    private function curlClose($ch)
    {
        if ($ch == null) {
            throw new InvalidArgumentException('curl object cant be null.');
        }
        curl_close($ch);
    }

    /**
     * @param $ch
     * @param $url
     * @return mixed
     */
    private function curlSetUrl($ch, $url)
    {
        if ($ch == null) {
            throw new InvalidArgumentException('curl object cant be null.');
        }
        if ($url == null || $url == '') {
            throw new InvalidArgumentException('url cant be null or empty.');
        }
        curl_setopt($ch, CURLOPT_URL, $this->couch_server . ':' . $this->couch_port . '/' . $this->couch_db . '/' . $url);
        return $ch;
    }

    /**
     * @param $mode
     * @param null $user
     * @param null $pass
     * @return resource
     */
    private function curlPrepare($mode, $user = null, $pass = null)
    {
        if ($mode == null || ($mode != 'GET' && $mode != 'PUT' && $mode != 'DELETE')) {
            throw new InvalidArgumentException('mode have to be "GET|PUT|DELETE".');
        }
        if (($user != null && $user != '') && ($pass == null || $pass == '')) {
            throw new InvalidArgumentException('if user is given pass cant be null or empty.');
        }
        if (($pass != null && $pass != '') && ($user == null || $user == '')) {
            throw new InvalidArgumentException('if pass is given user cant be null or empty.');
        }
        if (($user != null && $pass != null) && ($user != '' && $pass != '') && (!is_string($user) || !is_string($pass))) {
            throw new InvalidArgumentException('user and pass have to be string and cant be empty.');
        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $mode);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-type: application/json',
            'Accept: */*'
        ));

        if ($user != null && $pass != null) {
            curl_setopt($ch, CURLOPT_USERPWD, $this->couch_user . ':' . $this->couch_pass);
        }

        return $ch;
    }

    /* ------------------------- test call ------------------------- */
    public function test()
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->couch_server . ':' . $this->couch_port . '/' . $this->couch_db . '/_design/testdocs/_view/all');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');//PUT,DELETE
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