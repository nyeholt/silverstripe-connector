<?php
/**

Copyright (c) 2009, SilverStripe Australia PTY LTD - www.silverstripe.com.au
All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:

    * Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the 
      documentation and/or other materials provided with the distribution.
    * Neither the name of SilverStripe nor the names of its contributors may be used to endorse or promote products derived from this software 
      without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE 
IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE 
LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE 
GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, 
STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY 
OF SUCH DAMAGE.

*/

/**
 * An example external content connector that lists content from the filesystem
 * 
 * Please note: Set a base_path variable to prevent users entering any
 * arbitrary path!
 * 
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 *
 */
class SilverStripeContentSource extends ExternalContentSource implements ExternalContentRepositoryProvider
{
	public static $db = array(
		'ApiUrl' => 'Varchar(64)',
		'Username' => 'Varchar(64)',
		'Password' => 'Varchar(64)',
		'RootId' => 'Int',
	);
	
	public static $icon = array("silverstripe-connector/images/silverstripe", "folder");

	public function getCMSFields()
	{
		$fields = parent::getCMSFields();

		$fields->addFieldToTab('Root.Main', new TextField('ApiUrl', _t('ExternalContentSource.API_URL', 'API Url')));
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
	public function getRemoteRepository()
	{
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
	public function getContentImporter($target=null)
	{
		return new SilverStripeContentImporter();
	}
	
	/**
	 * Matrix content can only be imported into 
	 * the sitetree for now. 
	 * 
	 * @see external-content/code/dataobjects/ExternalContentSource#allowedImportTargets()
	 */
	public function allowedImportTargets()
	{
		return array('sitetree' => true);
	}
	

	/**
	 * Whenever we save the content source, we want to disconnect 
	 * the repository so that it reconnects with whatever new connection
	 * details are provided
	 * 
	 * @see sapphire/core/model/DataObject#onBeforeWrite()
	 */
	public function onBeforeWrite()
	{
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
	 *			The SilverStripe object type
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
			return $root->stageChildren();	
		}
		return new DataObjectSet();
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