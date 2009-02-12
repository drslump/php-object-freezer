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

require_once 'Object/Freezer/Storage.php';

/**
 * CouchDB object storage.
 *
 * @package    Object_Freezer
 * @author     Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright  2008-2009 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: @package_version@
 * @link       http://github.com/sebastianbergmann/php-object-freezer/
 * @since      Class available since Release 1.0.0
 */
class Object_Freezer_Storage_CouchDB extends Object_Freezer_Storage
{
    /**
     * @var string
     */
    protected $database;

    /**
     * @var string
     */
    protected $host;

    /**
     * @var int
     */
    protected $port;

    /**
     * Constructor.
     *
     * @param  string         $database The name of the database to use.
     * @param  Object_Freezer $freezer  The Object_Freezer to use.
     * @param  string         $host     The hostname of the CouchDB instance to use.
     * @param  int            $port     The port number of the CouchDB instance to use.
     * @throws InvalidArgumentException
     */
    public function __construct($database, Object_Freezer $freezer = NULL, $host = 'localhost', $port = 5984)
    {
        parent::__construct($freezer);

        // Bail out if a non-string was passed.
        if (!is_string($database)) {
            throw Object_Freezer_Util::getInvalidArgumentException(1, 'string');
        }

        // Bail out if a non-string was passed.
        if (!is_string($host)) {
            throw Object_Freezer_Util::getInvalidArgumentException(3, 'object');
        }

        // Bail out if a non-integer was passed.
        if (!is_int($port)) {
            throw Object_Freezer_Util::getInvalidArgumentException(4, 'integer');
        }

        $this->database = $database;
        $this->host     = $host;
        $this->port     = $port;
    }

    /**
     * Freezes an object and stores it in the object storage.
     *
     * @param  object $object
     * @return string
     * @throws InvalidArgumentException
     */
    protected function doStore($object)
    {
        $frozenObject = $this->freezer->freeze($object);
        $payload      = array('docs' => array());

        foreach ($frozenObject['objects'] as $_id => $_object) {
            if ($_object['isDirty'] !== FALSE) {
                $payload['docs'][] = array(
                  '_id'   => $_id,
                  'class' => $_object['className'],
                  'state' => $_object['state']
                );
            }
        }

        if (!empty($payload['docs'])) {
            $this->send(
              'POST',
              '/' . $this->database . '/_bulk_docs',
              json_encode($payload)
            );
        }

        return $object->__php_object_freezer_uuid;
    }

    /**
     * Fetches a frozen object from the object storage and thaws it.
     *
     * @param  string $id      The ID of the object that is to be fetched.
     * @param  array  $objects Only used internally.
     * @return object
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function doFetch($id, array &$objects = array())
    {
        if (empty($objects)) {
            $isRoot = TRUE;
        } else {
            $isRoot = FALSE;
        }

        $response = $this->send('GET', '/' . $this->database . '/' . $id);

        if (strpos($response['headers'], 'HTTP/1.0 200 OK') === 0) {
            $object = json_decode($response['body'], TRUE);
        } else {
            throw new RuntimeException(
              sprintf('Object with id "%s" could not be fetched.', $id)
            );
        }

        if (!isset($objects[$id])) {
            $objects[$id] = array(
              'className' => $object['class'],
              'isDirty'   => FALSE,
              'state'     => $object['state']
            );

            $this->fetchArray($object['state'], $objects);
        }

        if ($isRoot) {
            return $this->freezer->thaw(
              array(
                'root'    => $id,
                'objects' => $objects
              )
            );
        }
    }

    /**
     * 
     *
     * @param  array  $array
     * @param  array  $objects
     */
    protected function fetchArray(array $array, array &$objects = array())
    {
        foreach ($array as $value) {
            if (is_array($value)) {
                $this->fetchArray($value, $objects);
            }

            else if (is_string($value) &&
                     strpos($value, '__php_object_freezer_') === 0) {
                $this->doFetch(
                  str_replace('__php_object_freezer_', '', $value), $objects
                );
            }
        }
    }

    /**
     * Sends an HTTP request to the CouchDB server.
     *
     * @param  string $method
     * @param  string $url
     * @param  string $payload
     * @return array
     * @throws RuntimeException
     */
    public function send($method, $url, $payload = NULL)
    {
        $socket = fsockopen($this->host, $this->port, $errno, $errstr);

        if (!$socket) {
            throw new RuntimeException($errno . ': ' . $errstr);
        }

        $request = $method . ' ' . $url . " HTTP/1.0\r\nHost: localhost\r\n";

        if ($payload !== NULL) {
            $request .= 'Content-Length: ' . strlen($payload) . "\r\n\r\n";
            $request .= $payload;
        }

        $request .= "\r\n";
        fwrite($socket, $request);

        $buffer = '';

        while (!feof($socket)) {
            $buffer .= fgets($socket);
        }

        list($headers, $body) = explode("\r\n\r\n", $buffer);

        return array('headers' => $headers, 'body' => $body);
    }
}
