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
require_once 'Object/Freezer.php';

require_once join(DIRECTORY_SEPARATOR, array(__DIR__, '_files', 'A.php'));
require_once join(DIRECTORY_SEPARATOR, array(__DIR__, '_files', 'B.php'));
require_once join(DIRECTORY_SEPARATOR, array(__DIR__, '_files', 'Base.php'));
require_once join(DIRECTORY_SEPARATOR, array(__DIR__, '_files', 'C.php'));
require_once join(DIRECTORY_SEPARATOR, array(__DIR__, '_files', 'D.php'));
require_once join(DIRECTORY_SEPARATOR, array(__DIR__, '_files', 'E.php'));
require_once join(DIRECTORY_SEPARATOR, array(__DIR__, '_files', 'Extended.php'));
require_once join(DIRECTORY_SEPARATOR, array(__DIR__, '_files', 'F.php'));
require_once join(DIRECTORY_SEPARATOR, array(__DIR__, '_files', 'ConstructorCounter.php'));
require_once join(DIRECTORY_SEPARATOR, array(__DIR__, '_files', 'Node.php'));
require_once join(DIRECTORY_SEPARATOR, array(__DIR__, '_files', 'Node2.php'));

/**
 * Tests for the Object_Freezer class.
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
class Object_FreezerTest extends PHPUnit_Framework_TestCase
{
    protected $freezer;
    protected $idGenerator;

    /**
     * @covers Object_Freezer::__construct
     */
    protected function setUp()
    {
        $this->idGenerator = $this->getMock('Object_Freezer_IdGenerator');
        $this->idGenerator->expects($this->any())
                          ->method('getId')
                          ->will($this->onConsecutiveCalls('a', 'b', 'c'));

        $this->freezer = new Object_Freezer($this->idGenerator);
    }

    /**
     * @covers Object_Freezer::freeze
     */
    public function testFreezingAnObjectWorks()
    {
        $this->assertEquals(
          array(
            'root'    => 'a',
            'objects' => array(
              'a' => array(
                'className' => 'A',
                'isDirty'   => TRUE,
                'state'     => array(
                  'a'                         => 1,
                  'b'                         => 2,
                  'c'                         => 3,
                  '__php_object_freezer_hash' => '3c0bd64e7f7143b457b51423b7f172f7172ef424'
                )
              )
            )
          ),
          $this->freezer->freeze(new A(1, 2, 3))
        );
    }

    /**
     * @covers Object_Freezer::freeze
     */
    public function testFreezingAnObjectOfAnExtendedClassWorks()
    {
        $this->assertEquals(
          array(
            'root'    => 'a',
            'objects' => array(
              'a' => array(
                'className' => 'Extended',
                'isDirty'   => TRUE,
                'state' => array(
                  'd' => 'd',
                  'e' => 'e',
                  'f' => 'f',
                  'a' => 'a',
                  'b' => 'b',
                  '__php_object_freezer_hash' => '78e22e75eb22e8ca26127a89c156365b9a1e9a6e',
                )
              )
            )
          ),
          $this->freezer->freeze(new Extended)
        );
    }

    /**
     * @covers Object_Freezer::freeze
     */
    public function testFreezingAnObjectThatAggregatesAResourceWorks()
    {
        $this->assertEquals(
          array(
            'root'    => 'a',
            'objects' => array(
              'a' => array(
                'className' => 'F',
                'isDirty'   => TRUE,
                'state'     => array(
                  'file'                      => NULL,
                  '__php_object_freezer_hash' => 'e57d9e232b4f1691aceeea16e1099728b7c03830'
                )
              )
            )
          ),
          $this->freezer->freeze(new F)
        );
    }

    /**
     * @covers  Object_Freezer::freeze
     * @depends testFreezingAnObjectWorks
     */
    public function testFreezingAnObjectThatAggregatesOtherObjectsWorks()
    {
        $this->assertEquals(
          array(
            'root'    => 'a',
            'objects' => array(
              'a' => array(
                'className' => 'C',
                'isDirty'   => TRUE,
                'state' => array(
                  'b'                         => '__php_object_freezer_b',
                  '__php_object_freezer_hash' => '9a7b11d8709331ee16304d3c2c7c72fc4730f7c4'
                )
              ),
              'b' => array(
                'className' => 'B',
                'isDirty'   => TRUE,
                'state' => array(
                  'a'                         => '__php_object_freezer_c',
                  '__php_object_freezer_hash' => '1404f057855305a1f5734b8c31f417d460285c42'
                )
              ),
              'c' => array(
                'className' => 'A',
                'isDirty'   => TRUE,
                'state' => array(
                  'a'                         => 1,
                  'b'                         => 2,
                  'c'                         => 3,
                  '__php_object_freezer_hash' => '6f4ea6504fb30823218623e66cb47fff64373926'
                )
              )
            )
          ),
          $this->freezer->freeze(new C)
        );
    }

    /**
     * @covers  Object_Freezer::freeze
     * @covers  Object_Freezer::freezeArray
     * @depends testFreezingAnObjectThatAggregatesOtherObjectsWorks
     */
    public function testFreezingAnObjectThatAggregatesOtherObjectsInAnArrayWorks()
    {
        $this->assertEquals(
          array(
            'root'    => 'a',
            'objects' => array(
              'a' => array(
                'className' => 'D',
                'isDirty'   => TRUE,
                'state'     => array(
                  'array' => array(
                    0 => '__php_object_freezer_b'
                  ),
                  '__php_object_freezer_hash' => '94d21ff37706a2c2095a95262f73d45c2f0a32f4'
                )
              ),
              'b' => array(
                'className' => 'A',
                'isDirty'   => TRUE,
                'state'     => array(
                  'a'                         => 1,
                  'b'                         => 2,
                  'c'                         => 3,
                  '__php_object_freezer_hash' => '767101a9414bac28c076e39e1dc3eb5403cf0534'
                )
              )
            )
          ),
          $this->freezer->freeze(new D)
        );
    }

    /**
     * @covers  Object_Freezer::freeze
     * @covers  Object_Freezer::freezeArray
     * @depends testFreezingAnObjectThatAggregatesOtherObjectsInAnArrayWorks
     */
    public function testFreezingAnObjectThatAggregatesOtherObjectsInANestedArrayWorks()
    {
        $this->assertEquals(
          array(
            'root'    => 'a',
            'objects' => array(
              'a' => array(
                'className' => 'E',
                'isDirty'   => TRUE,
                'state'     => array(
                  'array' => array(
                    'array' => array(
                      0 => '__php_object_freezer_b'
                    )
                  ),
                  '__php_object_freezer_hash' => 'fc93dde8215b082590100d32e7b26dc188ce0815'
                ),
              ),
              'b' => array(
                'className' => 'A',
                'isDirty'   => TRUE,
                'state'     => array(
                  'a' => 1,
                  'b' => 2,
                  'c' => 3,
                  '__php_object_freezer_hash' => '767101a9414bac28c076e39e1dc3eb5403cf0534'
                )
              )
            )
          ),
          $this->freezer->freeze(new E)
        );
    }

    /**
     * @covers  Object_Freezer::freeze
     * @depends testFreezingAnObjectThatAggregatesOtherObjectsWorks
     */
    public function testFreezingAnObjectGraphThatContainsCyclesWorks()
    {
        $root                = new Node;
        $root->left          = new Node;
        $root->right         = new Node;
        $root->left->parent  = $root;
        $root->right->parent = $root;

        $this->assertEquals(
          array(
            'root'    => 'a',
            'objects' => array(
              'a' => array(
                'className' => 'Node',
                'isDirty'   => TRUE,
                'state'     => array(
                  'parent'                    => NULL,
                  'left'                      => '__php_object_freezer_b',
                  'right'                     => '__php_object_freezer_c',
                  'payload'                   => NULL,
                  '__php_object_freezer_hash' => '0b78e0ce8a31baa6174474e2e84256eb06acafca'
                )
              ),
              'b' => 
              array(
                'className' => 'Node',
                'isDirty'   => TRUE,
                'state'     => array(
                  'parent'                    => '__php_object_freezer_a',
                  'left'                      => NULL,
                  'right'                     => NULL,
                  'payload'                   => NULL,
                  '__php_object_freezer_hash' => '4c138823f68eaeada0d122ed08354cb776022703'
                )
              ),
              'c' => array(
                'className' => 'Node',
                'isDirty'   => TRUE,
                'state'     => array(
                  'parent'                    => '__php_object_freezer_a',
                  'left'                      => NULL,
                  'right'                     => NULL,
                  'payload'                   => NULL,
                  '__php_object_freezer_hash' => 'e168d40c488fd27ecadfb3a5efa34ca2a10c6400'
                )
              )
            )
          ),
          $this->freezer->freeze($root)
        );
    }

    /**
     * @covers  Object_Freezer::freeze
     * @covers  Object_Freezer::freezeArray
     * @depends testFreezingAnObjectGraphThatContainsCyclesWorks
     */
    public function testFreezingAnObjectGraphThatContainsCyclesWorks2()
    {
        $a = new Node2('a');
        $b = new Node2('b', $a);
        $c = new Node2('c', $a);

        $this->assertEquals(
          array(
            'root'    => 'a',
            'objects' => array(
              'a' => array(
                'className' => 'Node2',
                'isDirty'   => TRUE,
                'state'     => array(
                  'parent'   => NULL,
                  'children' => array(
                    0 => '__php_object_freezer_b',
                    1 => '__php_object_freezer_c'
                  ),
                  'payload'                   => 'a',
                  '__php_object_freezer_hash' => 'e72fff28068b932cc1cbf7cd3ee19438145a2db2'
                )
              ),
              'b' => array(
                'className' => 'Node2',
                'isDirty'   => TRUE,
                'state'     => array(
                  'parent'   => '__php_object_freezer_a',
                  'children' => array(),
                  'payload'                   => 'b',
                  '__php_object_freezer_hash' => '7d784d361c301e8f9ea58e75d2288d2c8563ce24'
                )
              ),
              'c' => array(
                'className' => 'Node2',
                'isDirty'   => TRUE,
                'state'     => array(
                  'parent'   => '__php_object_freezer_a',
                  'children' => array(),
                  'payload'                   => 'c',
                  '__php_object_freezer_hash' => '6763b776a62bebae3da18961bb42b22dba7ce441'
                )
              )
            )
          ),
          $this->freezer->freeze($a)
        );
    }

    /**
     * @covers  Object_Freezer::freeze
     * @covers  Object_Freezer::thaw
     * @depends testFreezingAnObjectWorks
     */
    public function testFreezingAndThawingAnObjectWorks()
    {
        $object = new A(1, 2, 3);

        $this->assertEquals(
          $object, $this->freezer->thaw($this->freezer->freeze($object))
        );
    }

    /**
     * @covers  Object_Freezer::freeze
     * @covers  Object_Freezer::thaw
     * @depends testFreezingAndThawingAnObjectWorks
     */
    public function testRepeatedlyFreezingAndThawingAnObjectWorks()
    {
        $object = new A(1, 2, 3);

        $this->assertEquals(
          $object, $this->freezer->thaw(
            $this->freezer->freeze(
              $this->freezer->thaw($this->freezer->freeze($object))
            )
          )
        );
    }

    /**
     * @covers  Object_Freezer::freeze
     * @covers  Object_Freezer::thaw
     * @depends testFreezingAnObjectWorks
     */
    public function testFreezingAndThawingAnObjectOfAnExtendedClassWorks()
    {
        $object = new Extended;

        $this->assertEquals(
          $object, $this->freezer->thaw($this->freezer->freeze($object))
        );
    }

    /**
     * @covers  Object_Freezer::freeze
     * @covers  Object_Freezer::thaw
     * @depends testFreezingAnObjectThatAggregatesOtherObjectsWorks
     */
    public function testFreezingAndThawingAnObjectThatAggregatesOtherObjectsWorks()
    {
        $object = new C;

        $this->assertEquals(
          $object, $this->freezer->thaw($this->freezer->freeze($object))
        );
    }

    /**
     * @covers  Object_Freezer::freeze
     * @covers  Object_Freezer::thaw
     * @depends testFreezingAndThawingAnObjectThatAggregatesOtherObjectsWorks
     */
    public function testRepeatedlyFreezingAndThawingAnObjectThatAggregatesOtherObjectsWorks()
    {
        $object = new C;

        $this->assertEquals(
          $object, $this->freezer->thaw(
            $this->freezer->freeze(
              $this->freezer->thaw(
                $this->freezer->freeze($object)
              )
            )
          )
        );
    }

    /**
     * @covers  Object_Freezer::freeze
     * @covers  Object_Freezer::freezeArray
     * @covers  Object_Freezer::thaw
     * @covers  Object_Freezer::thawArray
     * @depends testFreezingAnObjectThatAggregatesOtherObjectsInAnArrayWorks
     */
    public function testFreezingAndThawingAnObjectThatAggregatesOtherObjectsInAnArrayWorks()
    {
        $object = new D;

        $this->assertEquals(
          $object, $this->freezer->thaw($this->freezer->freeze($object))
        );
    }

    /**
     * @covers  Object_Freezer::freeze
     * @covers  Object_Freezer::freezeArray
     * @covers  Object_Freezer::thaw
     * @covers  Object_Freezer::thawArray
     * @depends testFreezingAnObjectThatAggregatesOtherObjectsInANestedArrayWorks
     */
    public function testFreezingAndThawingAnObjectThatAggregatesOtherObjectsInANestedArrayWorks()
    {
        $object = new E;

        $this->assertEquals(
          $object, $this->freezer->thaw($this->freezer->freeze($object))
        );
    }

    /**
     * @covers  Object_Freezer::freeze
     * @covers  Object_Freezer::freezeArray
     * @covers  Object_Freezer::thaw
     * @covers  Object_Freezer::thawArray
     * @depends testFreezingAnObjectGraphThatContainsCyclesWorks
     */
    public function testFreezingAndThawingAnObjectGraphThatContainsCyclesWorks()
    {
        $root                = new Node;
        $root->left          = new Node;
        $root->right         = new Node;
        $root->left->parent  = $root;
        $root->right->parent = $root;

        $this->assertEquals(
          $root, $this->freezer->thaw($this->freezer->freeze($root))
        );
    }

    /**
     * @covers  Object_Freezer::freeze
     * @covers  Object_Freezer::freezeArray
     * @covers  Object_Freezer::thaw
     * @covers  Object_Freezer::thawArray
     * @depends testFreezingAndThawingAnObjectGraphThatContainsCyclesWorks
     */
    public function testFreezingAndThawingAnObjectGraphThatContainsCyclesWorks2()
    {
        $a = new Node2('a');
        $b = new Node2('b', $a);
        $c = new Node2('c', $a);

        $this->assertEquals(
          $a, $this->freezer->thaw($this->freezer->freeze($a))
        );
    }

    /**
     * @covers  Object_Freezer::thaw
     * @depends testFreezingAndThawingAnObjectWorks
     */
    public function testConstructorIsNotCalledWhenAnObjectIsThawed()
    {
        $this->freezer->thaw($this->freezer->freeze(new ConstructorCounter));
        $this->assertEquals(1, ConstructorCounter::$numTimesConstructorCalled);
    }

    /**
     * @covers            Object_Freezer::freeze
     * @covers            Object_Freezer_Util::getInvalidArgumentException
     * @expectedException InvalidArgumentException
     */
    public function testExceptionIsThrownIfNotAnObjectIsPassedAsFirstArgumentToFreeze()
    {
        $this->freezer->freeze(NULL);
    }

    /**
     * @covers            Object_Freezer::thaw
     * @covers            Object_Freezer_Util::getInvalidArgumentException
     * @expectedException RuntimeException
     */
    public function testExceptionIsThrownWhenClassCouldNotBeFound()
    {
        $this->freezer->thaw(
          array(
            'root'    => '173a01cc-3ca0-41a8-9a7e-d6db5657d20f',
            'objects' => array(
              '173a01cc-3ca0-41a8-9a7e-d6db5657d20f' => array(
                'className' => 'NotExistingClass',
                'state'     => array(
                  '__php_object_freezer_uuid' => '173a01cc-3ca0-41a8-9a7e-d6db5657d20f',
                )
              )
            )
          )
        );
    }

    /**
     * @covers Object_Freezer::__construct
     * @covers Object_Freezer::setIdGenerator
     * @covers Object_Freezer::getIdGenerator
     * @covers Object_Freezer::setHashGenerator
     * @covers Object_Freezer::getHashGenerator
     * @covers Object_Freezer::setBlacklist
     * @covers Object_Freezer::getBlacklist
     * @covers Object_Freezer::setUseAutoload
     * @covers Object_Freezer::getUseAutoload
     */
    public function testConstructorWithDefaultArguments()
    {
        $freezer = new Object_Freezer;

        $this->assertType(
          'Object_Freezer_IdGenerator_UUID', $freezer->getIdGenerator()
        );

        $this->assertType(
          'Object_Freezer_HashGenerator_NonRecursiveSHA1', $freezer->getHashGenerator()
        );

        $this->assertEquals(array(), $freezer->getBlacklist());
        $this->assertTrue($freezer->getUseAutoload());
    }

    /**
     * @covers            Object_Freezer::__construct
     * @covers            Object_Freezer::setUseAutoload
     * @covers            Object_Freezer_Util::getInvalidArgumentException
     * @expectedException InvalidArgumentException
     */
    public function testExceptionIsRaisedForInvalidConstructorArguments()
    {
        $freezer = new Object_Freezer(NULL, NULL, array(), NULL);
    }

    /**
     * @covers Object_Freezer::isDirty
     */
    public function testNonDirtyObjectIsRecognizedAsNotBeingDirty()
    {
        $object = new A(1, 2, 3);
        $object->__php_object_freezer_hash = '1a66b04455fbcb456fca201730e8b9fb1336d2e7';

        $this->assertFalse($this->freezer->isDirty($object));
    }

    /**
     * @covers Object_Freezer::isDirty
     */
    public function testDirtyObjectIsRecognizedAsBeingDirty()
    {
        $object = new A(3, 2, 1);
        $object->__php_object_freezer_hash = 'a6efdb77cb879e26cf30635156cf045a7e7f9564';

        $this->assertTrue($this->freezer->isDirty($object));
    }

    /**
     * @covers Object_Freezer::isDirty
     */
    public function testDirtyObjectIsRecognizedAsBeingDirty2()
    {
        $object = new A(1, 2, 3);

        $this->assertTrue($this->freezer->isDirty($object));
    }

    /**
     * @covers Object_Freezer::isDirty
     */
    public function testDirtyObjectIsRecognizedAsBeingDirty3()
    {
        $object = new A(3, 2, 1);
        $object->__php_object_freezer_hash = 'a6efdb77cb879e26cf30635156cf045a7e7f9564';

        $this->assertTrue($this->freezer->isDirty($object, TRUE));
        $this->assertFalse($this->freezer->isDirty($object));
    }

    /**
     * @covers            Object_Freezer::isDirty
     * @covers            Object_Freezer_Util::getInvalidArgumentException
     * @expectedException InvalidArgumentException
     */
    public function testExceptionIsThrownIfNotAnObjectIsPassedAsFirstArgumentToIsDirty()
    {
        $this->freezer->isDirty(NULL);
    }

    /**
     * @covers            Object_Freezer::isDirty
     * @covers            Object_Freezer_Util::getInvalidArgumentException
     * @expectedException InvalidArgumentException
     */
    public function testExceptionIsThrownIfNotABooleanIsPassedAsSecondArgumentToIsDirty()
    {
        $this->freezer->isDirty(new StdClass, NULL);
    }
}
