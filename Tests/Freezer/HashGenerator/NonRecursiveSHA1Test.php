<?php
/**
 * Object_Freezer
 *
 * Copyright (c) 2008-2011, Sebastian Bergmann <sb@sebastian-bergmann.de>.
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
 * @copyright  2008-2011 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @since      File available since Release 1.0.0
 */

require_once 'Object/Freezer/HashGenerator/NonRecursiveSHA1.php';

require_once join(DIRECTORY_SEPARATOR, array(dirname(dirname(__DIR__)), '_files', 'A.php'));
require_once join(DIRECTORY_SEPARATOR, array(dirname(dirname(__DIR__)), '_files', 'B.php'));
require_once join(DIRECTORY_SEPARATOR, array(dirname(dirname(__DIR__)), '_files', 'C.php'));
require_once join(DIRECTORY_SEPARATOR, array(dirname(dirname(__DIR__)), '_files', 'D.php'));
require_once join(DIRECTORY_SEPARATOR, array(dirname(dirname(__DIR__)), '_files', 'E.php'));
require_once join(DIRECTORY_SEPARATOR, array(dirname(dirname(__DIR__)), '_files', 'F.php'));
require_once join(DIRECTORY_SEPARATOR, array(dirname(dirname(__DIR__)), '_files', 'Node.php'));
require_once join(DIRECTORY_SEPARATOR, array(dirname(dirname(__DIR__)), '_files', 'Node2.php'));

/**
 * Tests for the Object_Freezer_HashGenerator_NonRecursiveSHA1 class.
 *
 * @package    Object_Freezer
 * @subpackage Tests
 * @author     Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright  2008-2011 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: @package_version@
 * @link       http://github.com/sebastianbergmann/php-object-freezer/
 * @since      Class available since Release 1.0.0
 */
class Object_Freezer_HashGenerator_NonRecursiveSHA1Test extends PHPUnit_Framework_TestCase
{
    protected $hashGenerator;
    protected $idGenerator;

    /**
     * @covers Object_Freezer_HashGenerator_NonRecursiveSHA1::__construct
     */
    protected function setUp()
    {
        $this->idGenerator = $this->getMock('Object_Freezer_IdGenerator');
        $this->idGenerator->expects($this->any())
                          ->method('getId')
                          ->will($this->onConsecutiveCalls('a', 'b', 'c'));

        $this->hashGenerator = new Object_Freezer_HashGenerator_NonRecursiveSHA1(
          $this->idGenerator
        );
    }

    protected function tearDown()
    {
        $this->hashGenerator = NULL;
        $this->idGenerator   = NULL;
    }

    /**
     * @covers Object_Freezer_HashGenerator_NonRecursiveSHA1::getHash
     */
    public function testObjectCanBeHashed()
    {
        $this->assertEquals(
          '1a66b04455fbcb456fca201730e8b9fb1336d2e7',
          $this->hashGenerator->getHash(new A(1, 2, 3))
        );
    }

    /**
     * @covers Object_Freezer_HashGenerator_NonRecursiveSHA1::getHash
     */
    public function testHashedObjectCanBeHashed()
    {
        $object = new A(1, 2, 3);
        $object->__php_object_freezer_hash = '1a66b04455fbcb456fca201730e8b9fb1336d2e7';

        $this->assertEquals(
          '1a66b04455fbcb456fca201730e8b9fb1336d2e7',
          $this->hashGenerator->getHash($object)
        );
    }

    /**
     * @covers  Object_Freezer_HashGenerator_NonRecursiveSHA1::getHash
     * @depends testObjectCanBeHashed
     */
    public function testObjectWithAggregatedResourceCanBeHashed()
    {
        $this->assertEquals(
          'e69f20e9f683920d3fb4329abd951e878b1f9372',
          $this->hashGenerator->getHash(new F)
        );
    }

    /**
     * @covers  Object_Freezer_HashGenerator_NonRecursiveSHA1::getHash
     * @depends testObjectCanBeHashed
     */
    public function testObjectWithAggregatedObjectCanBeHashed()
    {
        $this->assertEquals(
          '2fd22ce656b849cb086889e5eacd1da49228eb0a',
          $this->hashGenerator->getHash(new B)
        );
    }

    /**
     * @covers  Object_Freezer_HashGenerator_NonRecursiveSHA1::getHash
     * @depends testObjectWithAggregatedObjectCanBeHashed
     */
    public function testObjectThatAggregatesOtherObjectsInAnArrayCanBeHashed()
    {
        $this->assertEquals(
          '08fbe76f6e026529706b3f839bb89ef553f2244f',
          $this->hashGenerator->getHash(new D)
        );
    }

    /**
     * @covers  Object_Freezer_HashGenerator_NonRecursiveSHA1::getHash
     * @depends testObjectThatAggregatesOtherObjectsInAnArrayCanBeHashed
     */
    public function testObjectThatAggregatesOtherObjectsInANestedArrayCanBeHashed()
    {
        $this->assertEquals(
          '4a90e5557becb306532cc9d68dea147d3ef1a3ae',
          $this->hashGenerator->getHash(new E)
        );
    }

    /**
     * @covers  Object_Freezer_HashGenerator_NonRecursiveSHA1::getHash
     * @depends testObjectWithAggregatedObjectCanBeHashed
     */
    public function testObjectGraphThatContainsCyclesCanBeHashed()
    {
        $root                = new Node;
        $root->left          = new Node;
        $root->right         = new Node;
        $root->left->parent  = $root;
        $root->right->parent = $root;

        $this->assertEquals(
          '830f3470aa75a83deab25ef5a7617a5967f07ed4',
          $this->hashGenerator->getHash($root)
        );
    }

    /**
     * @covers  Object_Freezer_HashGenerator_NonRecursiveSHA1::getHash
     * @depends testObjectGraphThatContainsCyclesCanBeHashed
     */
    public function testObjectGraphThatContainsCyclesCanBeHashed2()
    {
        $a = new Node2('a');
        $b = new Node2('b', $a);
        $c = new Node2('c', $a);

        $this->assertEquals(
          '8e3d1f054f570f708241f0ee5519c0913b802465',
          $this->hashGenerator->getHash($a)
        );
    }

    /**
     * @covers            Object_Freezer_HashGenerator_NonRecursiveSHA1::getHash
     * @covers            Object_Freezer_Util::getInvalidArgumentException
     * @expectedException InvalidArgumentException
     */
    public function testExceptionIsThrownIfNotAnObjectIsPassedToGetHash()
    {
        $this->hashGenerator->getHash(NULL);
    }
}
