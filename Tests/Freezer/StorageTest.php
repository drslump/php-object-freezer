<?php
/**
 * Object_Freezer
 *
 * Copyright (c) 2008-2010, Sebastian Bergmann <sb@sebastian-bergmann.de>.
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
 * @copyright  2008-2010 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @since      File available since Release 1.0.0
 */

require_once 'PHPUnit/Framework.php';
require_once 'Object/Freezer/Storage.php';

/**
 * Tests for the Object_Freezer_Storage class.
 *
 * @package    Object_Freezer
 * @subpackage Tests
 * @author     Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright  2008-2010 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: @package_version@
 * @link       http://github.com/sebastianbergmann/php-object-freezer/
 * @since      Class available since Release 1.0.0
 */
class Object_Freezer_StorageTest extends PHPUnit_Framework_TestCase
{
    protected $stub;

    protected function setUp()
    {
        $this->stub = $this->getMockForAbstractClass('Object_Freezer_Storage');
    }

    protected function tearDown()
    {
        $this->stub = NULL;
    }

    /**
     * @covers Object_Freezer_Storage::__construct
     */
    public function testConstructorWithDefaultArguments()
    {
        $this->assertType(
          'Object_Freezer', $this->readAttribute($this->stub, 'freezer')
        );
    }

    /**
     * @covers            Object_Freezer_Storage::store
     * @covers            Object_Freezer_Util::getInvalidArgumentException
     * @expectedException InvalidArgumentException
     */
    public function testExceptionIsThrownIfNotAnObjectIsPassedAsArgumentToStore()
    {
        $this->stub->store(NULL);
    }

    /**
     * @covers            Object_Freezer_Storage::fetch
     * @covers            Object_Freezer_Util::getInvalidArgumentException
     * @expectedException InvalidArgumentException
     */
    public function testExceptionIsThrownIfNotAStringIsPassedAsArgumentToFetch()
    {
        $this->stub->fetch(NULL);
    }
}
