<?php
/**
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2019, ownCloud GmbH
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\DAV\Tests\Unit\TrashBin;

use OCA\DAV\Connector\Sabre\Exception\FileLocked;
use OCA\DAV\Connector\Sabre\Exception\Forbidden;
use OCA\DAV\TrashBin\TrashBinFile;
use OCA\DAV\TrashBin\TrashBinManager;
use OCP\Files\FileInfo;
use OCP\Files\ForbiddenException;
use OCP\Files\StorageNotAvailableException;
use OCP\Lock\LockedException;
use Sabre\DAV\Exception\ServiceUnavailable;
use Test\TestCase;

class TrashBinFileTest extends TestCase {
	/**
	 * @var TrashBinFile
	 */
	private $trashBinFile;
	/**
	 * @var TrashBinManager | \PHPUnit\Framework\MockObject\MockObject
	 */
	private $trashBinManager;

	public function providesExceptions() : array {
		return [
			[Forbidden::class, new ForbiddenException('', false)],
			[FileLocked::class, new LockedException('')],
			[ServiceUnavailable::class, new StorageNotAvailableException()],
		];
	}

	protected function setUp() {
		parent::setUp();
		$fileInfo = $this->createMock(FileInfo::class);
		$this->trashBinManager = $this->createMock(TrashBinManager::class);
		$this->trashBinFile = new TrashBinFile('alice', $fileInfo, $this->trashBinManager);
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\Forbidden
	 * @expectedExceptionMessage Permission denied to write this file
	 */
	public function testPut() {
		$this->trashBinFile->put('');
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\Forbidden
	 * @expectedExceptionMessage Permission denied to read this file
	 */
	public function testGet() {
		$this->trashBinFile->get();
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\Forbidden
	 * @expectedExceptionMessage Permission denied to rename this resource
	 */
	public function testSetName() {
		$this->trashBinFile->setName('');
	}

	/**
	 * @dataProvider providesExceptions
	 * @param $expectedDavException
	 * @param $coreException
	 */
	public function testDelete($expectedDavException, $coreException) {
		$this->expectException($expectedDavException);
		$this->trashBinManager->method('delete')->willThrowException($coreException);
		$this->trashBinFile->delete();
	}
	/**
	 * @dataProvider providesExceptions
	 * @param $expectedDavException
	 * @param $coreException
	 */
	public function testRestore($expectedDavException, $coreException) {
		$this->expectException($expectedDavException);
		$this->trashBinManager->method('restore')->willThrowException($coreException);
		$this->trashBinFile->restore('');
	}
}
