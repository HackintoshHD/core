<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Files\Mount;


use OC\Files\Storage\StorageFactory;
use OC\Files\Storage\Wrapper\Wrapper;

class Mount extends \Test\TestCase {
	private function getMockStorage() {
		return $this->getMockBuilder('\OC\Files\Storage\Temporary')
			->disableOriginalConstructor()
			->getMock();
	}

	public function testFromStorageObject() {
		$storage = $this->getMockStorage();
		$mount = new \OC\Files\Mount\MountPoint($storage, '/foo');
		$this->assertInstanceOf('\OC\Files\Storage\Temporary', $mount->getStorage());
	}

	public function testFromStorageClassname() {
		$mount = new \OC\Files\Mount\MountPoint('\OC\Files\Storage\Temporary', '/foo');
		$this->assertInstanceOf('\OC\Files\Storage\Temporary', $mount->getStorage());
	}

	public function testWrapper() {
		$test = $this;
		$wrapper = function ($mountPoint, $storage) use (&$test) {
			$test->assertEquals('/foo/', $mountPoint);
			$test->assertInstanceOf('\OC\Files\Storage\Storage', $storage);
			return new Wrapper(array('storage' => $storage));
		};

		$loader = new StorageFactory();
		$loader->addStorageWrapper('test_wrapper', $wrapper);

		$storage = $this->getMockStorage();
		$mount = new \OC\Files\Mount\MountPoint($storage, '/foo', array(), $loader);
		$this->assertInstanceOf('\OC\Files\Storage\Wrapper\Wrapper', $mount->getStorage());
	}

	/**
	 * storage available
	 */
	public function testAvailabilityTrue() {
		$storage = $this->getMockStorage();
		$storage->method('getAvailability')
			->will($this->returnValue(true));

		$storage->expects($this->once())
			->method('test')
			->will($this->returnValue(true));
		$storage->expects($this->never())
			->method('setAvailability');

		$mount = new \OC\Files\Mount\MountPoint($storage, '/foo');
		$this->assertTrue($mount->isAvailable());
	}

	/**
	 * storage available, test failed
	 */
	public function testAvailabilityFailed() {
		$storage = $this->getMockStorage();
		$storage->method('getAvailability')
			->will($this->returnValue(true));

		$storage->expects($this->once())
			->method('test')
			->will($this->returnValue(false));
		$storage->expects($this->once())
			->method('setAvailability')
			->with($this->equalTo(false));

		$mount = new \OC\Files\Mount\MountPoint($storage, '/foo');
		$this->assertFalse($mount->isAvailable());
	}

	/**
	 * storage unavailable, no recheck
	 */
	public function testAvailabilityFalse() {
		$storage = $this->getMockStorage();
		$storage->method('getAvailability')
			->will($this->returnValue(false));
		$storage->method('getLastChecked')
			->will($this->returnValue(time()));

		$storage->expects($this->never())
			->method('test');

		$mount = new \OC\Files\Mount\MountPoint($storage, '/foo');
		$this->assertFalse($mount->isAvailable());
	}

	/**
	 * storage unavailable, recheck
	 */
	public function testAvailabilityFalseRecheck() {
		$storage = $this->getMockStorage();
		$storage->method('getAvailability')
			->will($this->returnValue(false));
		$storage->method('getLastChecked')
			->will($this->returnValue(0));

		$storage->expects($this->once())
			->method('test')
			->will($this->returnValue(false));
		$storage->expects($this->once())
			->method('setAvailability')
			->with($this->equalTo(false));

		$mount = new \OC\Files\Mount\MountPoint($storage, '/foo');
		$this->assertFalse($mount->isAvailable());
	}
}
