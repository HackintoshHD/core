<?php
/**
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2018, ownCloud GmbH
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

namespace OCA\Files_External\Tests;

use OCA\Files_External\Lib\Storage\AmazonS3;

/**
 * Class AmazonS3Migration
 *
 * @group DB
 *
 * @package OCA\Files_External\Tests
 */
class AmazonS3MigrationTest extends \Test\TestCase {

	/**
	 * @var \OC\Files\Storage\Storage instance
	 */
	protected $instance;

	/** @var array */
	protected $params;

	/** @var string */
	protected $oldId;

	/** @var string */
	protected $newId;

	protected function setUp() {
		parent::setUp();

		$uuid = $this->getUniqueID();

		$this->params['key'] = 'key'.$uuid;
		$this->params['secret'] = 'secret'.$uuid;
		$this->params['bucket'] = 'bucket'.$uuid;

		$this->oldId = 'amazon::' . $this->params['key'] . \md5($this->params['secret']);
		$this->newId = 'amazon::' . $this->params['bucket'];
	}

	protected function tearDown() {
		$this->deleteStorage($this->oldId);
		$this->deleteStorage($this->newId);

		parent::tearDown();
	}

	public function testUpdateLegacyOnlyId() {
		// add storage ids
		$oldCache = new \OC\Files\Cache\Cache($this->oldId);

		// add file to old cache
		$fileId = $oldCache->put('foobar', ['size' => 0, 'mtime' => \time(), 'mimetype' => 'httpd/directory']);

		try {
			$this->instance = new AmazonS3($this->params);
		} catch (\Exception $e) {
			//ignore
		}
		$storages = $this->getStorages();

		$this->assertArrayHasKey($this->newId, $storages);
		$this->assertArrayNotHasKey($this->oldId, $storages);
		$this->assertSame((int)$oldCache->getNumericStorageId(), (int)$storages[$this->newId]);

		list($storageId, $path) = \OC\Files\Cache\Cache::getById($fileId);

		$this->assertSame($this->newId, $storageId);
		$this->assertSame('foobar', $path);
	}

	public function testUpdateLegacyAndNewId() {
		// add storage ids

		$oldCache = new \OC\Files\Cache\Cache($this->oldId);
		new \OC\Files\Cache\Cache($this->newId);

		// add file to old cache
		$fileId = $oldCache->put('/', ['size' => 0, 'mtime' => \time(), 'mimetype' => 'httpd/directory']);

		try {
			$this->instance = new AmazonS3($this->params);
		} catch (\Exception $e) {
			//ignore
		}
		$storages = $this->getStorages();

		$this->assertArrayHasKey($this->newId, $storages);
		$this->assertArrayNotHasKey($this->oldId, $storages);

		$this->assertNull(\OC\Files\Cache\Cache::getById($fileId), 'old filecache has not been cleared');
	}

	/**
	 * @param $storages
	 * @return array
	 */
	public function getStorages() {
		$storages = [];
		$stmt = \OC::$server->getDatabaseConnection()->prepare(
			'SELECT `numeric_id`, `id` FROM `*PREFIX*storages` WHERE `id` IN (?, ?)'
		);
		$stmt->execute([$this->oldId, $this->newId]);
		while ($row = $stmt->fetch()) {
			$storages[$row['id']] = $row['numeric_id'];
		}
		return $storages;
	}

	/**
	 * @param string $id
	 */
	public function deleteStorage($id) {
		$stmt = \OC::$server->getDatabaseConnection()->prepare(
			'DELETE FROM `*PREFIX*storages` WHERE `id` = ?'
		);
		$stmt->execute([$id]);
	}
}
