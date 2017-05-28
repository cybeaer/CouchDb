<?php
require_once 'src/CouchDb.php';
require_once 'vendor/autoload.php';

use phplib\CouchDb;
use PHPUnit\Framework\TestCase;

class CouchDbTest extends TestCase
{
    /* ---------------------------------- multiuse functions ---------------------------------- */
    /**
     * @param $server
     * @param $port
     * @param $db
     * @param $user
     * @param $pass
     * @return CouchDb
     */
    private function initConnection($server, $port, $db, $user, $pass)
    {
        $cdb = new CouchDb();
        if ($server != null && $port != null) {
            $cdb->setServer($server, $port);
        }
        $cdb->setDb($db);
        if ($user !== null && $pass != null) {
            $cdb->setUser($user, $pass);
        }
        return $cdb;
    }

    /**
     * Call protected/private method of a class.
     *
     * @param object &$object Instantiated object that we will run method on.
     * @param string $methodName Method name to call
     * @param array $parameters Array of parameters to pass into method.
     *
     * @return mixed Method return.
     */
    public function invokeMethod(&$object, $methodName, array $parameters = array())
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    /* ---------------------------------- dataprovider ---------------------------------- */
    public function ProviderConstruct()
    {
        return array(
            array('http://127.0.0.1', 5984, 'unittests', 'phpunit', 'unittests', null),
            array('http://127.0.0.1', 5984, 'unittests', null, null, null),
            array(null, null, 'unittests', 'phpunit', 'unittests', null),
            array('http://127.0.0.1', new stdClass(), 'unittests', 'phpunit', 'unittests', InvalidArgumentException::class),
            array(new stdClass(), 5984, 'unittests', 'phpunit', 'unittests', InvalidArgumentException::class),
            array('http://127.0.0.1', 5984, new stdClass(), 'phpunit', 'unittests', InvalidArgumentException::class),
            array('http://127.0.0.1', 5984, 'unittests', new stdClass(), 'unittests', InvalidArgumentException::class),
            array('http://127.0.0.1', 5984, 'unittests', 'phpunit', new stdClass(), InvalidArgumentException::class),
        );
    }

    public function ProviderSendCommand()
    {
        //$method, $id, $data, $force, $result, $exception
        $testData = new stdClass();
        $testData->id = 'testid';
        $testData->someAttribute = 'testValue';
        return array(
            array('PUT', 'testid', $testData, null),
            array('PUT', 'testid', json_encode($testData), null),
            array('PUT', 'testid', 123, InvalidArgumentException::class),
            array('wrong method', 'testid', $testData, InvalidArgumentException::class),
            array('PUT', 'testid', null, InvalidArgumentException::class),
            array('PUT', new stdClass(), $testData, InvalidArgumentException::class),
            array('GET', 'testid', null, null),
            array('GET', new stdClass(), null, InvalidArgumentException::class),
            array('DELETE', 'testid', null, null),
            array('DELETE', new stdClass(), null, InvalidArgumentException::class),
        );
    }

    public function ProviderCheckId()
    {
        return array(
            array('someid', null),
            array(123, null),
            array(new stdClass(), InvalidArgumentException::class),
            array(null, InvalidArgumentException::class),
        );
    }

    public function ProviderCheckCh()
    {
        $ch = curl_init();
        return array(
            array($ch, null),
            array('not a resource', InvalidArgumentException::class),
            array(null, InvalidArgumentException::class),

        );
    }

    public function ProviderCheckUrl()
    {
        return array(
            array('someurl', null),
            array('', InvalidArgumentException::class),
            array(123, InvalidArgumentException::class),
            array(null, InvalidArgumentException::class),
        );
    }

    public function ProviderCheckData()
    {
        $stringData = '{someAttribute: someValue}';
        $objectData = new stdClass();
        $objectData->someAttribute = 'someValue';
        return array(
            array($stringData, null),
            array($objectData, null),
            array('', InvalidArgumentException::class),
            array(null, null),
            array(123, InvalidArgumentException::class),
        );
    }

    public function ProviderSetHeader()
    {
        $ch = curl_init();
        $header = array('Content-type: text/plain', 'Content-length: 100');
        return array(
            array($ch, $header, null),
            array($ch, 'not a array', TypeError::class),
            array($ch, null, TypeError::class),
            array('not a ressource', $header, InvalidArgumentException::class),
            array(null, $header, InvalidArgumentException::class),
        );
    }

    public function ProviderCurlPrepare()
    {
        return array(
            array('GET', 'user', 'pass', null),
            array('GET', null, null, null),
            array('PUT', 'user', 'pass', null),
            array('DELETE', 'user', 'pass', null),
            array('GETS', 'user', 'pass', InvalidArgumentException::class),
            array('GET', null, 'pass', InvalidArgumentException::class),
            array('GET', 'user', null, InvalidArgumentException::class),
            array('GET', 'user', 123, InvalidArgumentException::class),
            array('GET', 123, 'pass', InvalidArgumentException::class),
            array('GET', null, 'pass', InvalidArgumentException::class),
            array('GET', '', 'pass', InvalidArgumentException::class),
            array('GET', 'user', '', null),
            array('GET', '', '', null),
        );
    }

    public function ProviderAddAttachment()
    {
        return array(
            array('testdata','testcoverage.png', null),
            array('testdata','testcoverage_not_exist.png', InvalidArgumentException::class),
            array('testdata',null, InvalidArgumentException::class),
            array('testdata','', InvalidArgumentException::class),
            array(null,'testcoverage.png', InvalidArgumentException::class),
            array('','testcoverage.png', InvalidArgumentException::class),
            array(123,'testcoverage.png', InvalidArgumentException::class),
        );
    }

    public function ProviderResponseAsObject()
    {
        return array(
            array(true, null),
            array(false, null),
            array('some other value', InvalidArgumentException::class),
            array(null, InvalidArgumentException::class),
        );
    }

    /* ---------------------------------- unit tests ---------------------------------- */
    /**
     * @dataProvider ProviderConstruct
     * @param $server
     * @param $port
     * @param $db
     * @param $user
     * @param $pass
     * @param $expection
     */
    public function testConstruct($server, $port, $db, $user, $pass, $expection)
    {
        if ($expection != null) {
            $this->expectException($expection);
        }

        $this->initConnection($server, $port, $db, $user, $pass);
        $this->assertTrue(true);
    }

    /**
     * @dataProvider ProviderSendCommand
     * @param $method
     * @param $id
     * @param $data
     * @param $exception
     */
    public function testSendCommand($method, $id, $data, $exception)
    {
        if ($exception != null) {
            $this->expectException($exception);
        }
        $cdb = $this->initConnection('http://127.0.0.1', 5984, 'unittests', 'phpunit', 'unittests');
        $return = $cdb->send($method, $id, $data);
        $return = json_decode($return);
        switch ($method) {
            case 'GET':
                $this->assertTrue(isset($return->_id) && $return->_id == $id);
                break;
            case 'PUT':
                $this->assertTrue(isset($return->ok) && $return->ok == true);
                break;
            case 'DELETE':
                $this->assertTrue(isset($return->ok) && $return->ok == true);
                break;
            default:
                break;
        }
    }

    /**
     * @dataProvider ProviderResponseAsObject
     * @param $option
     * @param $expection
     */
    public function testSetResponseAsObject($option, $expection)
    {
        if ($expection != null) {
            $this->expectException($expection);
        }
        $cdb = new CouchDb();
        $cdb->setResponseAsObject($option);
        $this->assertTrue(true);
    }

    /**
     * @dataProvider ProviderAddAttachment
     * @param $id
     * @param $filename
     * @param $exception
     */
    public function testAddAttachment($id, $filename, $exception)
    {
        if ($exception != null) {
            $this->expectException($exception);
        }
        $cdb = $this->initConnection('http://127.0.0.1', 5984, 'unittests', 'phpunit', 'unittests');
        $cdb->setResponseAsObject(true);
        $testData = new stdClass();
        $testData->id = 'testdata';
        $putResponse = $cdb->send('PUT', $id, $testData);
        $return = $cdb->addAttachment($id,$filename);
        $this->assertTrue(isset($return->ok) && $return->ok == true);
    }

    //public function testGetView(){}

    //public function testCreateView(){}

    //public function testDeleteView(){}

    /* ---------------------------------- unit tests - privates ---------------------------------- */
    /**
     * @dataProvider ProviderCheckId
     * @param $id
     * @param $expection
     */
    public function testCheckId($id, $expection)
    {
        if ($expection != null) {
            $this->expectException($expection);
        }
        $cdb = new CouchDb();
        $this->invokeMethod($cdb, 'checkId', array($id));
        self::assertTrue(true);
    }

    /**
     * @dataProvider ProviderCheckCh
     * @param $ch
     * @param $expection
     */
    public function testCheckCH($ch, $expection)
    {
        if ($expection != null) {
            $this->expectException($expection);
        }
        $cdb = new CouchDb();
        $this->invokeMethod($cdb, 'checkCH', array($ch));
        self::assertTrue(true);
    }

    /**
     * @dataProvider ProviderCheckUrl
     * @param $url
     * @param $expection
     */
    public function testCheckUrl($url, $expection)
    {
        if ($expection != null) {
            $this->expectException($expection);
        }
        $cdb = new CouchDb();
        $this->invokeMethod($cdb, 'checkUrl', array($url));
        self::assertTrue(true);
    }

    /**
     * @dataProvider ProviderCheckData
     * @param $data
     * @param $expection
     */
    public function testCheckData($data, $expection)
    {
        if ($expection != null) {
            $this->expectException($expection);
        }
        $cdb = new CouchDb();
        $this->invokeMethod($cdb, 'checkData', array($data));
        self::assertTrue(true);
    }

    /**
     * @dataProvider ProviderSetHeader
     * @param $ch
     * @param $header
     * @param $expection
     */
    public function testCurlSetHeader($ch, $header, $expection)
    {
        if ($expection != null) {
            $this->expectException($expection);
        }
        $cdb = new CouchDb();
        $ret = $this->invokeMethod($cdb, 'curlSetHeader', array($ch, $header));
        $this->assertNotNull($ret);
        $this->assertTrue(is_resource($ret));
    }

    /**
     * @dataProvider ProviderCurlPrepare
     * @param $mode
     * @param $user
     * @param $pass
     * @param $expection
     */
    public function testCurlPrepare($mode, $user, $pass, $expection)
    {
        if ($expection != null) {
            $this->expectException($expection);
        }
        $cdb = new CouchDb();
        $ret = $this->invokeMethod($cdb, 'curlPrepare', array($mode, $user, $pass));
        $this->assertNotNull($ret);
        $this->assertTrue(is_resource($ret));
    }
}
