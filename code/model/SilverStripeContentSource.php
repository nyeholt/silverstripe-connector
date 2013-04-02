<?php


/**
 * An example external content connector that lists content from the filesystem
 * 
 * Please note: Set a base_path variable to prevent users entering any
 * arbitrary path!
 * 
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 * @license BSD License (http://silverstripe.org/BSD-license
 *
 */
class SilverStripeContentSource extends ExternalContentSource implements ExternalContentRepositoryProvider {

	public static $db = array(
		'ApiUrl' => 'Varchar(64)',
		'Username' => 'Varchar(64)',
		'Password' => 'Varchar(64)',
		'RootId' => 'Int',
	);
	public static $icon = array("silverstripe-connector/images/silverstripe", "folder");

	public function getCMSFields() {
		$fields = parent::getCMSFields();

		$fields->addFieldToTab('Root.Main', new TextField('ApiUrl', _t('ExternalContentSource.API_URL', 'Remote SilverStripe URL')));
		$fields->addFieldToTab('Root.Main', new TextField('Username', _t('ExternalContentSource.USER', 'Username')));
		$fields->addFieldToTab('Root.Main', new PasswordField('Password', _t('ExternalContentSource.PASS', 'Password')));
		$fields->addFieldToTab('Root.Main', new TextField('RootId', _t('ExternalContentSource.ROOT_ID', 'Root Page ID')));
		return $fields;
	}

	/**
	 * The silverstripe client to use
	 * @var SilverStripeClient
	 */
	protected $repo;

	/**
	 * Get the SilverStripeClient
	 * e(non-PHPdoc)
	 * @see external-content/code/model/ExternalContentRepositoryProvider#getRemoteRepository()
	 * @return SilverStripeClient
	 */
	public function getRemoteRepository() {
		if (!$this->repo) {
			$this->repo = new SilverStripeClient();
		}

		if (!$this->repo->isConnected()) {
			// connect away
			$this->repo->connect($this->ApiUrl, $this->Username, $this->Password);
		}
		return $this->repo;
	}

	/**
	 * Return a new matrix content importer 
	 * @see external-content/code/dataobjects/ExternalContentSource#getContentImporter()
	 */
	public function getContentImporter($target=null) {
		return new SilverStripeContentImporter();
	}

	/**
	 * Matrix content can only be imported into 
	 * the sitetree for now. 
	 * 
	 * @see external-content/code/dataobjects/ExternalContentSource#allowedImportTargets()
	 */
	public function allowedImportTargets() {
		return array('sitetree' => true);
	}

	/**
	 * Whenever we save the content source, we want to disconnect 
	 * the repository so that it reconnects with whatever new connection
	 * details are provided
	 * 
	 * @see sapphire/core/model/DataObject#onBeforeWrite()
	 */
	public function onBeforeWrite() {
		parent::onBeforeWrite();
		$repo = $this->getRemoteRepository();
		if ($repo->isConnected()) {
			$repo->disconnect();
		}
	}

	/**
	 * A cache for objects
	 * 
	 * @var array
	 */
	protected $objectCache = array();

	/**
	 * Get the object represented by ID
	 * 
	 * @param mixed $object
	 * 			Either an ID, or a prepulated wrapped object
	 * @param string $type
	 * 			The SilverStripe object type
	 * 
	 * @return DataObject
	 */
	public function getObject($object, $type='SiteTree') {
		$id = $object;
		if (is_object($object)) {
			$id = $object->SS_ID;
			$type = $object->ClassName ? $object->ClassName : $type;
		}

		if ($id && !strpos($id, '-')) {
			$id = "$id-$type";
		}

		if (!isset($this->objectCache[$id])) {
			// get the object from the repository
			try {
				if ($id) {
					$item = new SilverStripeContentItem($this, is_object($object) ? 0 : $id, is_object($object) ? $object : null);
				} else {
					// create a dummy dataobject representing the site root
					$object = new SiteTree();
					$object->SS_ID = 0;
					$item = new SilverStripeContentItem($this, 0, $object);
				}

				$this->objectCache[$id] = $item;
			} catch (Zend_Http_Client_Adapter_Exception $e) {
				$this->objectCache[$id] = null;
			}
		}

		return $this->objectCache[$id];
	}

	public function getRoot() {
		return $this->getObject($this->RootId);
	}

	public function stageChildren($showAll = false) {
		$root = $this->getRoot();
		if ($root) {
			$children = $root->stageChildren();

			$fakeId = '0-File';
			$fakeFiles = new SilverStripeContentItem($this, $fakeId);
			$fakeFiles->Title = 'Files';
			$fakeFiles->ID = $this->ID . '|' . $fakeId;
			$children->push($fakeFiles);
			return $children;
		}
		return new ArrayList();
	}

	/**
	 * Helper function to encode a remote ID that is safe to use within 
	 * silverstripe
	 * 
	 * @param $id
	 * 			The external content ID
	 * @return string
	 * 			A safely encoded ID
	 */
	public function encodeId($id) {
		return $id;
	}

	/**
	 * Decode an ID encoded by the above encodeId method
	 * 
	 * @param String $id
	 * 			The encoded ID
	 * @return String
	 * 			A decoded ID
	 */
	public function decodeId($id) {
		return $id;
	}

}

?>