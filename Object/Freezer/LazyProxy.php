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
 * @author     Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright  2008-2009 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @since      File available since Release 1.0.0
 */

/**
 * Proxy for a frozen object that is replaced with a thawed object when needed.
 *
 * @package    Object_Freezer
 * @author     Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright  2008-2009 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: @package_version@
 * @link       http://github.com/sebastianbergmann/php-object-freezer/
 * @since      Class available since Release 1.0.0
 */
class Object_Freezer_LazyProxy
{
    /**
     * @var Object_Freezer_Storage
     */
    protected $storage;

    /**
     * @var string
     */
    protected $uuid;

    /**
     * Constructor.
     *
     * @param Object_Freezer_Storage $storage
     * @param string                 $uuid
     */
    public function __construct(Object_Freezer_Storage $storage, $uuid)
    {
        $this->storage = $storage;
        $this->uuid    = $uuid;
    }

    /**
     * Replaces the lazy proxy object with the real object and
     * delegates the attribute read access to it.
     *
     * @param  string $name
     * @return mixed
     */
    public function __get($name)
    {
        $object    = $this->replaceProxy();
        $attribute = new ReflectionProperty($object, $name);
        $attribute->setAccessible(TRUE);

        return $attribute->getValue($object);
    }

    /**
     * Replaces the lazy proxy object with the real object and
     * delegates the attribute write access to it.
     *
     * @param string $name
     * @param mixed  $value
     */
    public function __set($name, $value)
    {
        $object    = $this->replaceProxy();
        $attribute = new ReflectionProperty($object, $name);
        $attribute->setAccessible(TRUE);

        $attribute->setValue($object, $value);
    }

    /**
     * Replaces the lazy proxy object with the real object and
     * delegates the message to it.
     *
     * @param  string $name
     * @param  array  $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        $object    = $this->replaceProxy();
        $reflector = new ReflectionMethod($object, $name);

        return $reflector->invokeArgs($object, $arguments);
    }

    /**
     * Replaces the lazy proxy object with the real object.
     *
     * @return object
     */
    protected function replaceProxy()
    {
        $trace     = debug_backtrace();
        $reflector = new ReflectionObject($trace[3]['object']);
        $object    = $this->storage->fetch($this->uuid);

        foreach ($reflector->getProperties() as $attribute) {
            $attribute->setAccessible(TRUE);

            if ($attribute->getValue($trace[3]['object']) === $this) {
                $attribute->setValue($trace[3]['object'], $object);
                break;
            }
        }

        return $object;
    }
}
