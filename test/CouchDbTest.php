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
    private function initConnection($server, $port, $db, $user, $pass){
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

    /* ---------------------------------- unit tests ---------------------------------- */
    /**
     * @dataProvider ProviderConstruct
     */
    public function testConstruct($server, $port, $db, $user, $pass, $expection)
    {
        if ($expection != null) {
            $this->expectException($expection);
        }

        $this->initConnection($server, $port, $db, $user, $pass);
        $this->assertTrue(true);
    }

    public function testSendCommand($exception){
        if ($exception != null){
            $this->expectExceptionCode($exception);
        }



    }
}
