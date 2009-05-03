Object_Freezer
==============

This class provides the low-level functionality required to store ("freeze") PHP
objects to and retrieve ("thaw") PHP objects from an object store.

Object_Freezer::freeze()
------------------------

Freezes an object.

In the example below, we freeze an object of class `A`. As this object
aggregates an object of class `B`, the object freezer has to freeze two objects
in total.

    <?php
    require_once 'Object/Freezer.php';

    class A
    {
        protected $b;

        public function __construct()
        {
            $this->b = new B;
        }
    }

    class B
    {
        protected $foo = 'bar';
    }

    $freezer = new Object_Freezer;
    var_dump($freezer->freeze(new A));
    ?>

Below is the output of the code example above.

    array(2) {
      ["root"]=>
      string(36) "32246c35-f47b-4fbc-a2ad-ed14e520865e"
      ["objects"]=>
      array(2) {
        ["32246c35-f47b-4fbc-a2ad-ed14e520865e"]=>
        array(3) {
          ["className"]=>
          string(1) "A"
          ["isDirty"]=>
          bool(true)
          ["state"]=>
          array(2) {
            ["b"]=>
            string(57) "__php_object_freezer_3cd682bf-8eba-4fec-90e2-ebe98aa07ab7"
            ["__php_object_freezer_hash"]=>
            string(40) "8b80da9c38c0c41c829cbbefbca9b18aa67ff607"
          }
        }
        ["3cd682bf-8eba-4fec-90e2-ebe98aa07ab7"]=>
        array(3) {
          ["className"]=>
          string(1) "B"
          ["isDirty"]=>
          bool(true)
          ["state"]=>
          array(2) {
            ["foo"]=>
            string(3) "bar"
            ["__php_object_freezer_hash"]=>
            string(40) "e04e935f09f2d526258d8a16613c5bce31e84e87"
          }
        }
      }
    }

If the object has not been frozen before, the attribute
`__php_object_freezer_uuid` will be added to it.

The reference to the object of class `B` that the object of class `A` had before
it was frozen has been replaced with the UUID of the frozen object of class `B`
(`__php_object_freezer_3cd682bf-8eba-4fec-90e2-ebe98aa07ab7`).

The result array's `root` element contains the UUID for the now frozen object of
class `A` (`32246c35-f47b-4fbc-a2ad-ed14e520865e`).

Object_Freezer::thaw()
----------------------

Thaws an object.

    <?php
    require_once 'Object/Freezer.php';

    require_once 'A.php';
    require_once 'B.php';

    $freezer = new Object_Freezer;

    var_dump(
      $freezer->thaw(
        array(
          'root'    => '32246c35-f47b-4fbc-a2ad-ed14e520865e',
          'objects' => array(
            '32246c35-f47b-4fbc-a2ad-ed14e520865e' => array(
              'className' => 'A',
              'isDirty'   => FALSE,
              'state'     => array(
                'b' => '__php_object_freezer_3cd682bf-8eba-4fec-90e2-ebe98aa07ab7',
              ),
            ),
            '3cd682bf-8eba-4fec-90e2-ebe98aa07ab7' => array(
              'className' => 'B',
              'isDirty'   => FALSE,
              'state'     => array(
                'foo' => 'bar',
              )
            )
          )
        )
      )
    );
    ?>

Below is the output of the code example above.

    object(A)#3 (2) {
      ["b":protected]=>
      object(B)#5 (2) {
        ["foo":protected]=>
        string(3) "bar"
        ["__php_object_freezer_uuid"]=>
        string(36) "3cd682bf-8eba-4fec-90e2-ebe98aa07ab7"
      }
      ["__php_object_freezer_uuid"]=>
      string(36) "32246c35-f47b-4fbc-a2ad-ed14e520865e"
    }

Object_Freezer_Storage_CouchDB
==============================

This class provides the means to use CouchDB as an object storage.

    <?php
    require_once 'Object/Freezer/Storage/CouchDB.php';

    class A
    {
        protected $b;

        public function __construct()
        {
            $this->b = new B;
        }
    }

    class B
    {
        protected $foo = 'bar';
    }

    $storage = new Object_Freezer_Storage_CouchDB(
      'database', new Object_Freezer, TRUE, 'localhost', 5984
    );

    $id = $storage->store(new A);

    var_dump($storage->fetch($id));
    ?>

Below is the output of the code example above.

    object(A)#3 (2) {
      ["b":protected]=>
      object(B)#5 (2) {
        ["foo":protected]=>
        string(3) "bar"
        ["__php_object_freezer_uuid"]=>
        string(36) "3cd682bf-8eba-4fec-90e2-ebe98aa07ab7"
      }
      ["__php_object_freezer_uuid"]=>
      string(36) "32246c35-f47b-4fbc-a2ad-ed14e520865e"
    }

