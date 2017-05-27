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
     * @param $force
     * @param $result
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
}
