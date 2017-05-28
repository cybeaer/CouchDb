<?php

namespace phplib;

use InvalidArgumentException;

//new user doc
/*
{
    "_id": "org.couchdb.user:dbreader",
    "name": "dbreader",
    "type": "user",
    "roles": [],
    "password": "plaintext_password"
}
*/

//new view doc
/*
{
    "map": "function(doc) {
        if (doc.type == 'testdoc' && data != '') { emit(doc._id, doc); }
                          }"
}
*/

//new design
/*
 {
   "_id": "_design/someDesign",
   "language": "javascript",
   "views": {
       "someView": {
           "map": "function(doc) { if (doc.type == 'testdoc' && data != '') { emit(doc._id, doc); } }"
       }
   }
}
*/

class CouchDb
{
    private $couch_server = '';
    private $couch_port = '';
    private $couch_user = '';
    private $couch_pass = '';
    private $couch_db = '';
    private $responseAsObject = false;

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

    /**
     * @param bool $option
     */
    public function setResponseAsObject($option = false)
    {
        if (!is_bool($option)) {
            throw new InvalidArgumentException('Response as Object can only take boolean option.');
        }
        $this->responseAsObject = $option;
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
        return ($this->responseAsObject) ? json_decode($res) : $res;
    }

    /**
     * @param $method
     * @param $id
     * @param null $data
     * @param bool $force
     * @return mixed
     */
    public function send($method, $id, $data = null)
    {
        $res = null;

        if ($method == null || !is_string($method) || $method == '' || ($method != 'GET' && $method != 'PUT' && $method != 'DELETE')) {
            throw new InvalidArgumentException('method can only be GET|PUT|DELETE.');
        }

        $this->checkId($id);

        if ($data != null && (!is_object($data) && !is_string($data))) {
            throw new InvalidArgumentException('data have to be object or json string.');
        } else {
            if (!is_object($data)) {
                $data = json_decode($data);
            }
        }

        if ($method == 'PUT' && $data == null) {
            throw new InvalidArgumentException('Data cant be null at PUT.');
        }

        if ($method == 'PUT' || $method == 'DELETE') {
            $ch = $this->curlPrepare('GET', $this->couch_user, $this->couch_pass);
            $ch = $this->curlSetUrl($ch, $id);
            $res = $this->curlExec($ch);
            $res = json_decode($res);
            $this->curlClose($ch);

        }

        $ch = $this->curlPrepare($method, $this->couch_user, $this->couch_pass);
        if ($method == 'PUT' || $method == 'DELETE') {
            $id = (!isset($res->error)) ? $id . '?rev=' . $res->_rev : $id;
            if ($data != null) {
                if (!isset($res->error)) {
                    $data->_rev = $res->_rev;
                }
                $ch = $this->curlSetData($ch, $data);
            }
        }

        $ch = $this->curlSetUrl($ch, $id);
        $res = $this->curlExec($ch);
        $this->curlClose($ch);
        return ($this->responseAsObject) ? json_decode($res) : $res;
    }

    /**
     * @param $id
     * @param $filename
     * @return mixed
     *
     */
    public function addAttachment($id, $filename)
    {
        $this->checkId($id);

        if ($filename == null || $filename == '') {
            throw new InvalidArgumentException('filename cant be null or empty.');
        }
        if (!file_exists($filename)) {
            throw new InvalidArgumentException('file ' . $filename . ' dont exist.');
        }

        $ch = $this->curlPrepare('GET', $this->couch_user, $this->couch_pass);
        $ch = $this->curlSetUrl($ch, $id);
        $res = $this->curlExec($ch);
        $this->curlClose($ch);
        $res = json_decode($res);
        $rev = $res->_rev;

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $contentType = finfo_file($finfo, $filename);

        $payload = file_get_contents($filename);

        $header = array(
            'Content-type: ' . $contentType,
            'Accept: */*'
        );

        $ch = $this->curlPrepare('PUT', $this->couch_user, $this->couch_pass, $header);
        $ch = $this->curlSetUrl($ch, $id . '/'.$filename.'?rev=' . $rev);
        //$ch = $this->curlSetHeader($ch, $header);
        $ch = $this->curlSetData($ch, $payload);
        $res = $this->curlExec($ch);
        $this->curlClose($ch);
        return ($this->responseAsObject) ? json_decode($res) : $res;
    }

    /**
     * @param $designName
     * @param $viewName
     * @param $script
     * @return mixed
     */
    public function createView($designName, $viewName, $script)
    {
        if ($designName == null || !is_string($designName) || $designName == '') {
            throw new InvalidArgumentException('Design name have to be string and cant be null or empty.');
        }
        if ($viewName == null || !is_string($viewName) || $viewName == '') {
            throw new InvalidArgumentException('View name have to be string and cant be null or empty.');
        }
        if ($script == null || !is_string($script) || $script == '') {
            throw new InvalidArgumentException('View script have to be string and cant be null or empty.');
        }
        $ch = $this->curlPrepare('GET', $this->couch_user, $this->couch_pass);
        $ch = $this->curlSetUrl($ch, '_design/' . $designName);
        $req = $this->curlExec($ch);
        $this->curlClose($ch);

        $req = json_decode($req);
        $script = json_decode($script);
        $newDoc = '';

        if (isset($req->error)) {
            $newDoc = json_decode('{"_id": "_design/' . $designName . '","language": "javascript","views": {}}');
            $newDoc->views->{$viewName} = $script;
        } else {
            if (isset($req->views->$viewName)) {
                $req->views->$viewName = $script;
            } else {
                $req->views->{$viewName} = $script;
            }
            $newDoc = $req;
        }

        $ch = $this->curlPrepare('PUT', $this->couch_user, $this->couch_pass);
        $ch = $this->curlSetUrl($ch, '_design/' . $designName);
        if ($newDoc != null) {
            $ch = $this->curlSetData($ch, $newDoc);
        }
        $res = $this->curlExec($ch);
        $this->curlClose($ch);

        return ($this->responseAsObject) ? json_decode($res) : $res;
    }

    /**
     * @param $designName
     * @param $viewName
     */
    public function deleteView($designName, $viewName = null)
    {
        if ($designName == null || !is_string($designName) || $designName == '') {
            throw new InvalidArgumentException('Design name have to be string and cant be null or empty.');
        }
        if ($viewName != null && (!is_string($viewName) || $viewName == '')) {
            throw new InvalidArgumentException('View name have to be string and cant be null or empty.');
        }

        $ch = $this->curlPrepare('GET', $this->couch_user, $this->couch_pass);
        $ch = $this->curlSetUrl($ch, '_design/' . $designName);
        $req = $this->curlExec($ch);
        $this->curlClose($ch);

        $req = json_decode($req);
        $res = null;

        if ($viewName == null) {
            $ch = $this->curlPrepare('DELETE', $this->couch_user, $this->couch_pass);
            $ch = $this->curlSetUrl($ch, '_design/' . $designName . '?rev=' . $req->_rev);

        } else {
            if (isset($req->views->$viewName)) {
                unset($req->views->$viewName);
                $ch = $this->curlPrepare('PUT', $this->couch_user, $this->couch_pass);
                $ch = $this->curlSetUrl($ch, '_design/' . $designName . '?rev=' . $req->_rev);
                $ch = $this->curlSetData($ch, json_encode($req));
            }

        }
        $res = $this->curlExec($ch);
        $this->curlClose($ch);

        return ($this->responseAsObject) ? json_decode($res) : $res;
    }

    /* ------------------------- privates ------------------------- */
    /**
     * @param $ch
     * @return mixed
     */
    private function curlExec($ch)
    {
        $this->checkCH($ch);
        $response = curl_exec($ch);
        return $response;
    }

    /**
     * @param $ch
     */
    private function curlClose($ch)
    {
        $this->checkCH($ch);
        curl_close($ch);
    }

    /**
     * @param $ch
     * @param $data
     * @return mixed
     */
    private function curlSetData($ch, $data)
    {
        $this->checkCH($ch);
        $this->checkData($data);
        if (is_object($data)) {
            $data = json_encode($data);
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
        $this->checkCH($ch);
        $this->checkUrl($url);
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
        $this->checkCH($ch);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        return $ch;
    }

    /**
     * @param $mode
     * @param null $user
     * @param null $pass
     * @return resource
     */
    private function curlPrepare($mode, $user = null, $pass = null, $header = null)
    {
        if ($mode == null || ($mode != 'GET' && $mode != 'PUT' && $mode != 'DELETE')) {
            throw new InvalidArgumentException('mode have to be "GET|PUT|DELETE".');
        }
        if (($user != null && trim($user) !== '') && $pass === null) {
            throw new InvalidArgumentException('if user is given pass cant be empty.');
        }
        if ($pass != null && ($user == null || $user === '')) {
            throw new InvalidArgumentException('if pass is given user cant be null or empty.');
        }
        if (($user != null && $pass != null) && (!is_string($user) || !is_string($pass))) {
            throw new InvalidArgumentException('user and pass have to be string and cant be empty.');
        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $mode);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($header == null) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-type: application/json',
                'Accept: */*'
            ));
        } else {
            $ch = $this->curlSetHeader($ch, $header);
        }

        if ($user != null && $pass != null) {
            curl_setopt($ch, CURLOPT_USERPWD, $this->couch_user . ':' . $this->couch_pass);
        }

        return $ch;
    }

    /* ------------------------- parameter checks ------------------------- */
    /**
     * @param $ch
     */
    private function checkCH($ch)
    {
        if ($ch == null || !is_resource($ch)) {
            throw new InvalidArgumentException('curl object cant be null.');
        }
    }

    /**
     * @param $url
     */
    private function checkUrl($url)
    {
        if ($url == null || $url == '' || !is_string($url)) {
            throw new InvalidArgumentException('url cant be null or empty.');
        }
    }

    /**
     * @param $data
     */
    private function checkData($data)
    {
        if ($data != null && (!is_object($data) && !is_string($data))) {
            throw new InvalidArgumentException('data have to be object or json string.');
        }
        if (is_string($data) && trim($data) == '') {
            throw new InvalidArgumentException('data cant be empty.');
        }
    }

    /**
     * @param $id
     */
    private function checkId($id)
    {
        if ($id == null) {
            throw new InvalidArgumentException('id cant be null.');
        }
        if (!is_string($id) && !is_numeric($id)) {
            throw new InvalidArgumentException('id have to be string or numeric.');
        }
    }
}