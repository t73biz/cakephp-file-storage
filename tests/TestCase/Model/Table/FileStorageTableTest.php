<?php
namespace Burzum\FileStorage\Test\TestCase\Model\Behavior;

use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\ORM\TableRegistry;
use Burzum\FileStorage\TestSuite\FileStorageTestCase;

/**
 * File Storage Test
 *
 * @author Florian Krämer
 * @copyright 2012 - 2015 Florian Krämer
 * @license MIT
 */
class FileStorageTableTest extends FileStorageTestCase {

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array(
		'plugin.Burzum\FileStorage.FileStorage'
	);

/**
 * startTest
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->FileStorage = TableRegistry::get('Burzum/FileStorage.FileStorage');
	}

/**
 * endTest
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		unset($this->FileStorage);
		unset($this->FileStorageBehavior);
		TableRegistry::clear();
	}

/**
 * testBeforeDelete
 *
 * @return void
 */
	public function testBeforeDelete() {
		$entity = $this->FileStorage->get('file-storage-1');
		$event = new Event('Model.beforeDelete', $this->FileStorage);
		$this->FileStorage->beforeDelete($event, $entity);
		$this->assertEquals($this->FileStorage->record, $entity);
	}

/**
 * testAfterDelete
 *
 * @return void
 */
	public function testAfterDelete() {
		$entity = $this->FileStorage->get('file-storage-1');
		$entity->adapter = 'Local';
		$event = new Event('FileStorage.afterDelete',  $this->FileStorage, [
			'record' => $entity,
			'adapter' => 'Local'
		]);
		$result = $this->FileStorage->afterDelete($event, $entity, []);
		$this->assertTrue($result);
	}

/**
 * testGetFileInfoFromUpload
 *
 * @return void
 */
	public function testGetFileInfoFromUpload() {
		$filename = \Cake\Core\Plugin::path('Burzum/FileStorage') . DS . 'tests' . DS . 'Fixture' . DS . 'File' . DS . 'titus.jpg';

		$data = new \ArrayObject([
			'file' => [
				'name' => 'titus.jpg',
				'tmp_name' => $filename
			]
		]);

		$this->FileStorage->getFileInfoFromUpload($data);

		$this->assertEquals(332643, $data['filesize']);
		$this->assertEquals('Local', $data['adapter']);
		$this->assertEquals('image/jpeg', $data['mime_type']);
		$this->assertEquals('jpg', $data['extension']);
		$this->assertEquals('file_storage', $data['model']);
	}

/**
 * Testing a complete save call
 *
 * @link https://github.com/burzum/cakephp-file-storage/issues/85
 * @return void
 */
	public function testFileSaving() {
		$listenersToTest = [
			'LocalListener',
		];
		$results = [];
		foreach ($listenersToTest as $listener) {
			$this->_removeListeners();
			EventManager::instance()->on($this->listeners[$listener]);
			$entity = $this->FileStorage->newEntity([
				'model' => 'Document',
				'adapter' => 'Local',
				'file' => [
					'error' => UPLOAD_ERR_OK,
					'size' => filesize($this->fileFixtures . 'titus.jpg'),
					'type' => 'image/jpeg',
					'name' => 'tituts.jpg',
					'tmp_name' => $this->fileFixtures . 'titus.jpg'
				]
			]);
			$this->FileStorage->configureUploadValidation([
				'allowedExtensions' => ['jpg'],
				'validateUploadArray' => true,
				'localFile' => true,
				'validateUploadErrors' => true
			]);
			$this->FileStorage->save($entity);
			$this->assertEquals($entity->errors(), []);
			$results[] = $entity;
		}
	}
}
