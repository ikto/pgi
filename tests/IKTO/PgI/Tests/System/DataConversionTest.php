<?php

namespace IKTO\PgI\Tests\System;

use IKTO\PgI\Database\Database;

class DataConversionTest extends \PHPUnit_Framework_TestCase
{
    /* @var Database */
    private $db;

    public static function setUpBeforeClass()
    {
        $db = new Database(
            $GLOBALS['test_db_dsn'],
            $GLOBALS['test_db_user'],
            $GLOBALS['test_db_pass']
        );
        $db->doQuery('TRUNCATE TABLE "data_types"');
        $db->doQuery('SELECT SETVAL($1::regclass, 1, false)', array(), array('data_types_id_seq'));
    }

    public function setUp()
    {
        $this->db = new Database(
            $GLOBALS['test_db_dsn'],
            $GLOBALS['test_db_user'],
            $GLOBALS['test_db_pass']
        );
    }

    public function tearDown()
    {
        $this->db = null;
    }

    /**
     * @dataProvider smallintProvider
     * @param integer $smallInt
     */
    public function testSmallint($smallInt)
    {
        $id = $this->getNewId();

        $this->db->doQuery('INSERT INTO "data_types" ("id", "f_smallint") VALUES ($1, $2)', array(), array($id, $smallInt));

        $row = $this->db->selectRowAssoc('SELECT * FROM "data_types" WHERE "id" = $1', array(), array($id));

        $this->assertEquals($smallInt, $row['f_smallint']);
    }

    public function smallintProvider()
    {
        return array(
            array(rand(-10,10)),
            array(rand(-10,10)),
            array(rand(-10,10)),
            array(rand(-10,10)),
            array(rand(-10,10)),
            array(rand(-10,10)),
            array(null),
        );
    }

    /**
     * @dataProvider realProvider
     * @param integer $real
     */
    public function testReal($real)
    {
        $id = $this->getNewId();

        $this->db->doQuery('INSERT INTO "data_types" ("id", "f_real") VALUES ($1, $2)', array(), array($id, $real));

        $row = $this->db->selectRowAssoc('SELECT * FROM "data_types" WHERE "id" = $1', array(), array($id));

        $this->assertEquals($real, $row['f_real']);
    }

    public function realProvider()
    {
        return array(
            array(rand(-100,100) / 10),
            array(rand(-100,100) / 10),
            array(rand(-100,100) / 10),
            array(rand(-100,100) / 10),
            array(rand(-100,100) / 10),
            array(rand(-100,100) / 10),
            array(null),
        );
    }

    /**
     * @dataProvider booleanProvider
     * @param bool $bool
     */
    public function testBoolean($bool)
    {
        $id = $this->getNewId();

        $this->db->doQuery('INSERT INTO "data_types" ("id", "f_boolean") VALUES ($1, $2)', array(), array($id, $bool));

        $row = $this->db->selectRowAssoc('SELECT * FROM "data_types" WHERE "id" = $1', array(), array($id));

        $this->assertEquals($bool, $row['f_boolean']);
    }

    public function booleanProvider()
    {
        return array(
            array(true),
            array(false),
            array(null),
        );
    }

    /**
     * @dataProvider varcharProvider
     * @param string $varChar
     */
    public function testVarchar($varChar)
    {
        $id = $this->getNewId();

        $this->db->doQuery('INSERT INTO "data_types" ("id", "f_varchar") VALUES ($1, $2)', array(), array($id, $varChar));

        $row = $this->db->selectRowAssoc('SELECT * FROM "data_types" WHERE "id" = $1', array(), array($id));

        $this->assertEquals($varChar, $row['f_varchar']);
    }

    public function varcharProvider()
    {
        return array(
            array($this->getRandomStringData(rand(5,30))),
            array($this->getRandomStringData(rand(5,30))),
            array($this->getRandomStringData(rand(5,30))),
            array($this->getRandomStringData(rand(5,30))),
            array($this->getRandomStringData(rand(5,30))),
            array($this->getRandomStringData(rand(5,30))),
            array(null),
        );
    }

    /**
     * @dataProvider byteaProvider
     * @param mixed $bytea
     */
    public function testBytea($bytea)
    {
        $id = $this->getNewId();

        $this->db->doQuery('INSERT INTO "data_types" ("id", "f_bytea") VALUES ($1, $2)', array(1=>'bytea'), array($id, $bytea));

        $row = $this->db->selectRowAssoc('SELECT * FROM "data_types" WHERE "id" = $1', array(), array($id));

        $this->assertEquals($bytea, $row['f_bytea']);
    }

    public function byteaProvider()
    {
        return array(
            array($this->getRandomByteaData(rand(5, 30))),
            array($this->getRandomByteaData(rand(5, 30))),
            array($this->getRandomByteaData(rand(5, 30))),
            array($this->getRandomByteaData(rand(5, 30))),
            array($this->getRandomByteaData(rand(5, 30))),
            array($this->getRandomByteaData(rand(5, 30))),
            array(null),
        );
    }

    /**
     * @dataProvider timestampProvider
     * @param \DateTime $timestampWithoutTimezone
     * @param \DateTime $timestampWithTimezone
     */
    public function testTimestamp($timestampWithoutTimezone, $timestampWithTimezone)
    {
        $id = $this->getNewId();

        $this->db->doQuery('INSERT INTO "data_types" ("id", "f_timestamp", "f_timestamptz") VALUES ($1, $2, $3)', array(1=>'timestamp'), array($id, $timestampWithoutTimezone, $timestampWithTimezone));

        $row = $this->db->selectRowAssoc('SELECT * FROM "data_types" WHERE "id" = $1', array(), array($id));

        $this->assertEquals($timestampWithoutTimezone, $row['f_timestamp']);
        $this->assertEquals($timestampWithTimezone, $row['f_timestamptz']);
    }

    public function timestampProvider()
    {
        $timestamps = array();
        $timezones = array('Europe/London', 'Atlantic/Azores', 'America/Jujuy', 'Europe/Kiev');

        for ($i = 0; $i < 6; $i++) {
            $time = date('Y-m-d H:i:s', rand(time() - 100000, time() + 100000));
            $zone = $timezones[rand(0, count($timezones) - 1)];

            $timestamps[] = array(
                new \DateTime($time),
                new \DateTime($time, new \DateTimeZone($zone)),
            );
        }

        $timestamps[] = array(null, null);

        return $timestamps;
    }

    /**
     * @dataProvider jsonArrayProvider
     * @param array $testJson
     */
    public function testJson($testJson)
    {
        $id = $this->getNewId();

        $this->db->doQuery('INSERT INTO "data_types" ("id", "f_json") VALUES ($1, $2)', array(1=>'json'), array($id, $testJson));

        $row = $this->db->selectRowAssoc('SELECT * FROM "data_types" WHERE "id" = $1', array(), array($id));

        $this->assertEquals($testJson, $row['f_json']);
    }

    public function jsonArrayProvider()
    {
        return array(
            array($this->getRandomJsonArray(1,2)),
            array($this->getRandomJsonArray(1,2)),
            array($this->getRandomJsonArray(1,2)),
            array($this->getRandomJsonArray(1,2)),
            array($this->getRandomJsonArray(1,2)),
            array(null),
        );
    }

    /**
     * @dataProvider arrayOfSmallintProvider
     * @param array $testArray
     */
    public function testArrayOfSmallint($testArray)
    {
        $id = $this->getNewId();

        $this->db->doQuery('INSERT INTO "data_types" ("id", "f_a_smallint") VALUES ($1, $2)', array(), array($id, $testArray));

        $row = $this->db->selectRowAssoc('SELECT * FROM "data_types" WHERE "id" = $1', array(), array($id));

        $this->assertEquals($testArray, $row['f_a_smallint']);
    }

    public function arrayOfSmallintProvider()
    {
        $callback = function () {
            return rand(-5, 5);
        };

        return array(
            array($this->getRandomArrayOfElements(0, $callback)),
            array($this->getRandomArrayOfElements(0, $callback)),
            array($this->getRandomArrayOfElements(0, $callback)),
            array($this->getRandomArrayOfElements(1, $callback)),
            array($this->getRandomArrayOfElements(2, $callback)),
            array($this->getRandomArrayOfElements(3, $callback)),
        );
    }

    /**
     * @dataProvider arrayOfVarcharProvider
     * @param array $testArray
     */
    public function testArrayOfVarchar($testArray)
    {
        $id = $this->getNewId();

        $this->db->doQuery('INSERT INTO "data_types" ("id", "f_a_varchar") VALUES ($1, $2)', array(), array($id, $testArray));

        $row = $this->db->selectRowAssoc('SELECT * FROM "data_types" WHERE "id" = $1', array(), array($id));

        $this->assertEquals($testArray, $row['f_a_varchar']);
    }

    public function arrayOfVarcharProvider()
    {
        $t = $this;
        $callback = function () use ($t) {
            return $t->callPrivate('getRandomStringData', array(rand(5,20)));
        };

        return array(
            array($this->getRandomArrayOfElements(0, $callback)),
            array($this->getRandomArrayOfElements(0, $callback)),
            array($this->getRandomArrayOfElements(0, $callback)),
            array($this->getRandomArrayOfElements(1, $callback)),
            array($this->getRandomArrayOfElements(1, $callback)),
        );
    }

    public function callPrivate($method, $args)
    {
        return call_user_func_array(array($this, $method), $args);
    }

    private function getNewId()
    {
        return $this->db->getSeqNextValue('data_types_id_seq');
    }

    private function getRandomStringData($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZабвгдеєжзиіїйклмнопрстуфхцчшщьюяАБВГДЕЄЖЗИІЇЙКЛМНОПРСТУФХЦЧШЩЬЮЯ "\'\\_;.,';
        $srcLength = mb_strlen($characters, 'UTF-8');
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= mb_substr($characters, rand(0, $srcLength), 1, 'UTF-8');
        }
        return $randomString;
    }

    private function getRandomStringKey($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }

    private function getRandomByteaData($length = 20)
    {
        $f = fopen('/dev/urandom', 'r');
        $buffer = fread($f, $length);
        fclose($f);

        return $buffer;
    }

    private function getRandomJsonArray($minDepth, $maxDepth)
    {
        $node = array();
        $nodeSize = rand(5, 10);
        for ($i = 0; $i < $nodeSize; $i++) {
            $key = $this->getRandomStringKey(rand(1, 10));
            if (rand($minDepth, $maxDepth) > 0) {
                $node[$key] = $this->getRandomJsonArray($minDepth - 1, $maxDepth - 1);
            } else {
                $node[$key] = $this->getRandomStringData(rand(5, 20));
            }
        }

        return $node;
    }

    private function getRandomArrayOfElements($depth, $callback, $count = null)
    {
        if (!$count) {
            $count = rand(5, 10);
        }

        $output = array();
        for ($i = 0; $i < $count; $i++) {
            if ($depth > 0) {
                $output[] = $this->getRandomArrayOfElements($depth - 1, $callback, $count);
            } else {
                $output[] = $callback();
            }
        }

        return $output;
    }
}
