<?php

namespace phplib\DbAccess;

use InvalidArgumentException;

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
            throw new InvalidArgumentException('server have to be string.');
        }
        $this->couch_server = $server;

        if (!is_string($port) && !is_int($port)) {
            throw new InvalidArgumentException('port have to be string or int.');
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
     * @param $method
     * @param $id
     * @param null $data
     * @param bool $force
     * @return mixed
     */
    public function send($method, $id, $data = null, $force = false)
    {
        if ($method == null || !is_string($method) || $method == '' || ($method != 'GET' && $method != 'PUT' && $method != 'DELETE')) {
            throw new InvalidArgumentException('method can only be GET|PUT|DELETE.');
        }

        if ($id != null) {
            throw new InvalidArgumentException('id cant be null.');
        }
        if (!is_string($id) && !is_numeric($id)) {
            throw new InvalidArgumentException('id have to be string or numeric.');
        }

        if ($data != null && (!is_object($data) && !is_string($data))) {
            throw new InvalidArgumentException('data have to be object or json string.');
        } else {
            if (!is_object($data)) {
                $data = json_decode($data);
            }
        }

        if ($force != null && !is_bool($force)) {
            throw new InvalidArgumentException('force can only be true or false.');
        }

        if (($force!= null && $force) || $method == 'DELETE') {
            $ch = $this->curlPrepare('GET', $this->couch_user, $this->couch_pass);
            $ch = $this->curlSetUrl($ch, $id);
            $res = $this->curlExec($ch);
            $this->curlClose($ch);
            $data->_rev = $res->_rev;
        }

        $ch = $this->curlPrepare($method, $this->couch_user, $this->couch_pass);
        $ch = $this->curlSetUrl($ch, $id);
        if ($data != null) {
            $ch = $this->curlSetData($ch, $data);
        }
        $res = $this->curlExec($ch);
        $this->curlClose($ch);
        return $res;
    }

    /**
     * @param $id
     * @param $filename
     * @return mixed
     *
     */
    public function addAttachment($id, $filename)
    {
        $ch = $this->curlPrepare('GET', $this->couch_user, $this->couch_pass);
        $ch = $this->curlSetUrl($ch, $id);
        $res = $this->curlExec($ch);
        $this->curlClose($ch);
        $rev = $res->_rev;

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $contentType = finfo_file($finfo, $filename);

        $payload = file_get_contents($filename);

        $header = array(
            'Content-type: ' . $contentType,
            'Accept: */*'
        );

        $ch = $this->curlPrepare('PUT', $this->couch_user, $this->couch_pass);
        $ch = $this->curlSetUrl($ch, $id . '?rev=' . $rev);
        $ch = $this->curlSetHeader($ch, $header);
        $ch = $this->curlSetData($ch, $payload);
        $res = $this->curlExec($ch);
        $this->curlClose($ch);
        return $res;
    }

    /* ------------------------- privates ------------------------- */
    /**
     * @param $ch
     * @return mixed
     */
    private function curlExec($ch)
    {
        if ($ch == null || !is_object($ch)) {
            throw new InvalidArgumentException('curl object cant be null.');
        }
        $response = curl_exec($ch);
        return $response;
    }

    /**
     * @param $ch
     */
    private function curlClose($ch)
    {
        if ($ch == null || !is_object($ch)) {
            throw new InvalidArgumentException('curl object cant be null.');
        }
        curl_close($ch);
    }

    /**
     * @param $ch
     * @param $data
     * @return mixed
     */
    private function curlSetData($ch, $data)
    {
        if ($data != null && (!is_object($data) && !is_string($data))) {
            throw new InvalidArgumentException('data have to be object or json string.');
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        return $ch;
    }

    /**
     * @param $ch
     * @param $url
     * @return mixed
     */
    private function curlSetUrl($ch, $url)
    {
        if ($ch == null || !is_object($ch)) {
            throw new InvalidArgumentException('curl object cant be null.');
        }
        if ($url == null || $url == '') {
            throw new InvalidArgumentException('url cant be null or empty.');
        }
        curl_setopt($ch, CURLOPT_URL, $this->couch_server . ':' . $this->couch_port . '/' . $this->couch_db . '/' . $url);
        return $ch;
    }

    /**
     * @param $ch
     * @param array $header
     * @return mixed
     */
    private function curlSetHeader($ch, Array $header)
    {
        if ($ch == null || !is_object($ch)) {
            throw new InvalidArgumentException('curl object cant be null.');
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
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
}