<?php
/**
 * Object_Freezer
 *
 * Copyright (c) 2008-2009, Sebastian Bergmann <sb@sebastian-bergmann.de>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Sebastian Bergmann nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package    Object_Freezer
 * @subpackage Tests
 * @author     Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright  2008-2009 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @since      File available since Release 1.0.0
 */

require_once 'PHPUnit/Framework.php';
require_once 'Object/Freezer/Storage/CouchDB.php';

/**
 * Tests for the Object_Freezer_Storage_CouchDB class.
 *
 * @package    Object_Freezer
 * @subpackage Tests
 * @author     Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright  2008-2009 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: @package_version@
 * @link       http://github.com/sebastianbergmann/php-object-freezer/
 * @since      Class available since Release 1.0.0
 */
class Object_Freezer_Storage_CouchDBTest extends PHPUnit_Framework_TestCase
{
    protected $freezer;
    protected $storage;

    /**
     * @covers Object_Freezer_Storage_CouchDB::__construct
     */
    protected function setUp()
    {
        if (!@fsockopen(OBJECT_FREEZER_COUCHDB_HOST, OBJECT_FREEZER_COUCHDB_PORT, $errno, $errstr)) {
            $this->markTestSkipped(
              sprintf(
                'CouchDB not running on %s:%d.',
                OBJECT_FREEZER_COUCHDB_HOST,
                OBJECT_FREEZER_COUCHDB_PORT
              )
            );
        }

        $idGenerator = $this->getMock('Object_Freezer_IdGenerator');
        $idGenerator->expects($this->any())
                    ->method('getId')
                    ->will($this->onConsecutiveCalls('a', 'b', 'c'));

        $this->freezer = new Object_Freezer($idGenerator);

        $this->storage = new Object_Freezer_Storage_CouchDB(
          'test',
          $this->freezer,
          OBJECT_FREEZER_COUCHDB_HOST,
          (int)OBJECT_FREEZER_COUCHDB_PORT
        );

        $this->storage->send('PUT', '/test');
    }

    protected function tearDown()
    {
        $this->storage->send('DELETE', '/test/');
    }

    /**
     * @covers Object_Freezer_Storage::store
     * @covers Object_Freezer_Storage_CouchDB::doStore
     * @covers Object_Freezer_Storage_CouchDB::send
     */
    public function testStoringAnObjectWorks()
    {
        $this->storage->store(new A(1, 2, 3));

        $this->assertEquals(
          array(
            '_id'   => 'a',
            'class' => 'A',
            'state' => array(
              'a'                         => 1,
              'b'                         => 2,
              'c'                         => 3,
              '__php_object_freezer_hash' => '3c0bd64e7f7143b457b51423b7f172f7172ef424'
            )
          ),
          $this->getFrozenObjectFromStorage('a')
        );
    }

    /**
     * @covers  Object_Freezer_Storage::store
     * @covers  Object_Freezer_Storage_CouchDB::doStore
     * @covers  Object_Freezer_Storage_CouchDB::send
     * @depends testStoringAnObjectWorks
     */
    public function testStoringAnObjectThatAggregatesOtherObjectsWorks()
    {
        $this->storage->store(new C);

        $this->assertEquals(
          array(
            '_id'   => 'a',
            'class' => 'C',
            'state' => array(
              'b'                         => '__php_object_freezer_b',
              '__php_object_freezer_hash' => '9a7b11d8709331ee16304d3c2c7c72fc4730f7c4'
            )
          ),
          $this->getFrozenObjectFromStorage('a')
        );

        $this->assertEquals(
          array(
            '_id'   => 'b',
            'class' => 'B',
            'state' => array(
              'a'                         => '__php_object_freezer_c',
              '__php_object_freezer_hash' => '1404f057855305a1f5734b8c31f417d460285c42'
            )
          ),
          $this->getFrozenObjectFromStorage('b')
        );

        $this->assertEquals(
          array(
            '_id'   => 'c',
            'class' => 'A',
            'state' => array(
              'a'                         => 1,
              'b'                         => 2,
              'c'                         => 3,
              '__php_object_freezer_hash' => '6f4ea6504fb30823218623e66cb47fff64373926'
            )
          ),
          $this->getFrozenObjectFromStorage('c')
        );
    }

    /**
     * @covers  Object_Freezer_Storage::store
     * @covers  Object_Freezer_Storage_CouchDB::doStore
     * @covers  Object_Freezer_Storage_CouchDB::send
     * @depends testStoringAnObjectThatAggregatesOtherObjectsWorks
     */
    public function testStoringAnObjectThatAggregatesOtherObjectsInAnArrayWorks()
    {
        $this->storage->store(new D);

        $this->assertEquals(
          array(
            '_id'   => 'a',
            'class' => 'D',
            'state' => array(
              'array' => array(
                0     => '__php_object_freezer_b'
              ),
              '__php_object_freezer_hash' => '94d21ff37706a2c2095a95262f73d45c2f0a32f4'
            )
          ),
          $this->getFrozenObjectFromStorage('a')
        );

        $this->assertEquals(
          array(
            '_id'   => 'b',
            'class' => 'A',
            'state' => array(
              'a'                         => 1,
              'b'                         => 2,
              'c'                         => 3,
              '__php_object_freezer_hash' => '767101a9414bac28c076e39e1dc3eb5403cf0534'
            )
          ),
          $this->getFrozenObjectFromStorage('b')
        );
    }

    /**
     * @covers  Object_Freezer_Storage::store
     * @covers  Object_Freezer_Storage_CouchDB::doStore
     * @covers  Object_Freezer_Storage_CouchDB::send
     * @depends testStoringAnObjectThatAggregatesOtherObjectsInAnArrayWorks
     */
    public function testStoringAnObjectThatAggregatesOtherObjectsInANestedArrayWorks()
    {
        $this->storage->store(new E);

        $this->assertEquals(
          array(
            '_id'   => 'a',
            'class' => 'E',
            'state' => array(
              'array' => array(
                'array' => array(
                  0 => '__php_object_freezer_b'
                )
              ),
              '__php_object_freezer_hash' => 'fc93dde8215b082590100d32e7b26dc188ce0815'
            )
          ),
          $this->getFrozenObjectFromStorage('a')
        );

        $this->assertEquals(
          array(
            '_id'   => 'b',
            'class' => 'A',
            'state' => array(
              'a'                         => 1,
              'b'                         => 2,
              'c'                         => 3,
              '__php_object_freezer_hash' => '767101a9414bac28c076e39e1dc3eb5403cf0534'
            )
          ),
          $this->getFrozenObjectFromStorage('b')
        );
    }

    /**
     * @covers  Object_Freezer_Storage::store
     * @covers  Object_Freezer_Storage_CouchDB::doStore
     * @covers  Object_Freezer_Storage_CouchDB::send
     * @depends testStoringAnObjectThatAggregatesOtherObjectsWorks
     */
    public function testStoringAnObjectGraphThatContainsCyclesWorks()
    {
        $root                = new Node;
        $root->left          = new Node;
        $root->right         = new Node;
        $root->left->parent  = $root;
        $root->right->parent = $root;

        $this->storage->store($root);

        $this->assertEquals(
          array(
            '_id'   => 'a',
            'class' => 'Node',
            'state' => array(
              'parent'                    => NULL,
              'left'                      => '__php_object_freezer_b',
              'right'                     => '__php_object_freezer_c',
              '__php_object_freezer_hash' => '76c7552f5312b057858119ad83876c54d96bacfc'
            )
          ),
          $this->getFrozenObjectFromStorage('a')
        );

        $this->assertEquals(
          array(
            '_id'   => 'b',
            'class' => 'Node',
            'state' => array(
              'parent'                    => '__php_object_freezer_a',
              'left'                      => NULL,
              'right'                     => NULL,
              '__php_object_freezer_hash' => '0d09cba6ea7db1e19d8c34a9059ef1656cf8f806'
            )
          ),
          $this->getFrozenObjectFromStorage('b')
        );

        $this->assertEquals(
          array(
            '_id'   => 'c',
            'class' => 'Node',
            'state' => array(
              'parent'                    => '__php_object_freezer_a',
              'left'                      => NULL,
              'right'                     => NULL,
              '__php_object_freezer_hash' => 'e1701657ea8f6fd87290416c125eb75abe4840d9'
            )
          ),
          $this->getFrozenObjectFromStorage('c')
        );
    }

    /**
     * @covers  Object_Freezer_Storage::store
     * @covers  Object_Freezer_Storage_CouchDB::doStore
     * @covers  Object_Freezer_Storage_CouchDB::send
     * @depends testStoringAnObjectGraphThatContainsCyclesWorks
     */
    public function testStoringAnObjectGraphThatContainsCyclesWorks2()
    {
        $a = new Node2;
        $b = new Node2($a);
        $c = new Node2($a);

        $this->storage->store($a);

        $this->assertEquals(
          array(
            '_id'   => 'a',
            'class' => 'Node2',
            'state' => array(
              'parent'   => NULL,
              'children' => array(
                0 => '__php_object_freezer_b',
                1 => '__php_object_freezer_c'
              ),
              '__php_object_freezer_hash' => '6119e9af8bbb9a5c576dc52813603b24792b79e9'
            )
          ),
          $this->getFrozenObjectFromStorage('a')
        );

        $this->assertEquals(
          array(
            '_id'   => 'b',
            'class' => 'Node2',
            'state' => array(
              'parent'                    => '__php_object_freezer_a',
              'children'                  => array(),
              '__php_object_freezer_hash' => 'c5455b75bdba8b359388fac7a95d5da98ad25195'
            )
          ),
          $this->getFrozenObjectFromStorage('b')
        );

        $this->assertEquals(
          array(
            '_id'   => 'c',
            'class' => 'Node2',
            'state' => array (
              'parent'                    => '__php_object_freezer_a',
              'children'                  => array(),
              '__php_object_freezer_hash' => '4e15cba2069c84bbd53583a771e2cd5ea3fe6d29'
            )
          ),
          $this->getFrozenObjectFromStorage('c')
        );
    }

    /**
     * @covers  Object_Freezer_Storage::store
     * @covers  Object_Freezer_Storage::fetch
     * @covers  Object_Freezer_Storage_CouchDB::doStore
     * @covers  Object_Freezer_Storage_CouchDB::doFetch
     * @covers  Object_Freezer_Storage_CouchDB::send
     * @depends testStoringAnObjectWorks
     */
    public function testStoringAndFetchingAnObjectWorks()
    {
        $object = new A(1, 2, 3);
        $this->storage->store($object);

        $this->assertEquals($object, $this->storage->fetch('a'));
    }

    /**
     * @covers  Object_Freezer_Storage::store
     * @covers  Object_Freezer_Storage::fetch
     * @covers  Object_Freezer_Storage_CouchDB::doStore
     * @covers  Object_Freezer_Storage_CouchDB::doFetch
     * @covers  Object_Freezer_Storage_CouchDB::send
     * @depends testStoringAnObjectWorks
     */
    public function testStoringAndFetchingAnObjectWorks2()
    {
        $a = new A(1, 2, 3);
        $this->storage->store($a);
        $id = $a->__php_object_freezer_uuid;

        $this->assertSame(
          $this->storage->fetch($id), $this->storage->fetch($id)
        );
    }

    /**
     * @covers  Object_Freezer_Storage::store
     * @covers  Object_Freezer_Storage::fetch
     * @covers  Object_Freezer_Storage_CouchDB::doStore
     * @covers  Object_Freezer_Storage_CouchDB::doFetch
     * @depends testStoringAnObjectThatAggregatesOtherObjectsWorks
     */
    public function testStoringAndFetchingAnObjectThatAggregatesOtherObjectsWorks()
    {
        $object = new C;
        $this->storage->store($object);

        $this->assertEquals($object, $this->storage->fetch('a'));
    }

    /**
     * @covers  Object_Freezer_Storage::store
     * @covers  Object_Freezer_Storage::fetch
     * @covers  Object_Freezer_Storage::fetchArray
     * @covers  Object_Freezer_Storage_CouchDB::doStore
     * @covers  Object_Freezer_Storage_CouchDB::doFetch
     * @depends testStoringAnObjectThatAggregatesOtherObjectsInAnArrayWorks
     */
    public function testStoringAndFetchingAnObjectThatAggregatesOtherObjectsInAnArrayWorks()
    {
        $object = new D;
        $this->storage->store($object);

        $this->assertEquals($object, $this->storage->fetch('a'));
    }

    /**
     * @covers  Object_Freezer_Storage::store
     * @covers  Object_Freezer_Storage::fetch
     * @covers  Object_Freezer_Storage::fetchArray
     * @covers  Object_Freezer_Storage_CouchDB::doStore
     * @covers  Object_Freezer_Storage_CouchDB::doFetch
     * @depends testStoringAnObjectThatAggregatesOtherObjectsInANestedArrayWorks
     */
    public function testStoringAndFetchingAnObjectThatAggregatesOtherObjectsInANestedArrayWorks()
    {
        $object = new E;
        $this->storage->store($object);

        $this->assertEquals($object, $this->storage->fetch('a'));
    }

    /**
     * @covers  Object_Freezer_Storage::store
     * @covers  Object_Freezer_Storage::fetch
     * @covers  Object_Freezer_Storage_CouchDB::doStore
     * @covers  Object_Freezer_Storage_CouchDB::doFetch
     * @depends testStoringAnObjectGraphThatContainsCyclesWorks
     */
    public function testStoringAndFetchingAnObjectGraphThatContainsCyclesWorks()
    {
        $root                = new Node;
        $root->left          = new Node;
        $root->right         = new Node;
        $root->left->parent  = $root;
        $root->right->parent = $root;

        $this->storage->store($root);

        $this->assertEquals($root, $this->storage->fetch('a'));
    }

    /**
     * @covers  Object_Freezer_Storage::store
     * @covers  Object_Freezer_Storage::fetch
     * @covers  Object_Freezer_Storage::fetchArray
     * @covers  Object_Freezer_Storage_CouchDB::doStore
     * @covers  Object_Freezer_Storage_CouchDB::doFetch
     * @depends testStoringAndFetchingAnObjectGraphThatContainsCyclesWorks
     */
    public function testStoringAndFetchingAnObjectGraphThatContainsCyclesWorks2()
    {
        $root   = new Node2;
        $left   = new Node2($root);
        $parent = new Node2($root);

        $this->storage->store($root);

        $this->assertEquals($root, $this->storage->fetch('a'));
    }

    /**
     * @covers            Object_Freezer_Storage_CouchDB::__construct
     * @covers            Object_Freezer_Util::getInvalidArgumentException
     * @expectedException InvalidArgumentException
     */
    public function testExceptionIsThrownIfFirstConstructorArgumentIsNotAString()
    {
        $storage = new Object_Freezer_Storage_CouchDB(NULL);
    }

    /**
     * @covers            Object_Freezer_Storage_CouchDB::__construct
     * @covers            Object_Freezer_Util::getInvalidArgumentException
     * @expectedException InvalidArgumentException
     */
    public function testExceptionIsThrownIfThirdConstructorArgumentIsNotAString()
    {
        $storage = new Object_Freezer_Storage_CouchDB('test', NULL, NULL);
    }

    /**
     * @covers            Object_Freezer_Storage_CouchDB::__construct
     * @covers            Object_Freezer_Util::getInvalidArgumentException
     * @expectedException InvalidArgumentException
     */
    public function testExceptionIsThrownIfFourthConstructorArgumentIsNotAnInteger()
    {
        $storage = new Object_Freezer_Storage_CouchDB('test', NULL, 'localhost', NULL);
    }

    /**
     * @covers            Object_Freezer_Storage::fetch
     * @covers            Object_Freezer_Storage_CouchDB::doFetch
     * @expectedException RuntimeException
     */
    public function testExceptionIsThrownIfObjectCouldNotBeFetched()
    {
        $this->storage->fetch('a');
    }

    protected function getFrozenObjectFromStorage($id)
    {
        $buffer = $this->storage->send('GET', '/test/' . $id);
        $buffer = $buffer['body'];

        $frozenObject = json_decode($buffer, TRUE);
        unset($frozenObject['_rev']);

        return $frozenObject;
    }
}
