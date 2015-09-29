<?php

/**
 * @author Marcus Nyeholt <marcus@silverstripe.com.au>
 * @license BSD License
 */
class SilverStripeContentItem extends ExternalContentItem {

	protected $wrappedObject;

	public function __construct($source=null, $id=null, $content=null) {
		if ($content) {
			$this->wrappedObject = $content;
			parent::__construct($source, $content->SS_ID . '-' . $content->ClassName);
		} else {
			if (is_bool($id)) {
				$id = 0;
			}
			parent::__construct($source, $id);
		}
	}

	public function init($content = null) {
		$repo = $this->source->getRemoteRepository();
		if (!$this->wrappedObject && $this->externalId && $id = $this->getSS_ID()) {
			try {
				// TODO : Update this to work with non-sitetree stuff too... 
				$this->wrappedObject = $repo->getNode(array('ClassName' => $this->getType(), 'ID' => $id));
			} catch (FailedRequestException $fre) {
				SS_Log::log($fre, SS_Log::WARN);
			}
		}

		if ($this->wrappedObject) {
			$allFields = $this->wrappedObject->toMap();
			foreach ($allFields as $field => $value) {
				if ($field == 'ID') {
					continue;
				}
				$this->$field = $value;
			}
		}
	}

	/**
	 * Return the numeric part of the ID
	 */
	public function getSS_ID() {
		$bits = explode('-', $this->externalId);
		return $bits[0];
	}

	/**
	 * Get the SilverStripe type of an object
	 *
	 * @return string
	 */
	public function getType() {
		if ($this->wrappedObject) {
			return get_class($this->wrappedObject);
		}
		// get it from the external ID
		$bits = explode('-', $this->externalId);
		return $bits[1];
	}

	public function stageChildren($showAll = false) {
		$children = new ArrayList();
		$repo = $this->source->getRemoteRepository();

		try {
			if ($repo->isConnected()) {
				$ssId = $this->getSS_ID();
				if (!$ssId) {
					$ssId = '0';
				}
				$kids = $repo->getChildren(array('ClassName' => ClassInfo::baseDataClass($this->getType()), 'ParentID' => $this->getSS_ID()));
				if (!$kids) {
					throw new Exception("No kids and null object returned for children of " . $this->getSS_ID());
				}
				// Even though it returns actual dataobjects, we need to wrap them for sanity and safety's sake
				foreach ($kids as $childItem) {
					$item = $this->source->getObject($childItem);
					$children->push($item);
				}
			}
		} catch (Exception $fre) {
			SS_Log::log($fre, SS_Log::WARN);
			return $children;
		}

		return $children;
	}

	public function numChildren() {
		if ($this->wrappedObject instanceof File && !($this->wrappedObject instanceof Folder)) {
			return 0;
		}
		$children = $this->Children();
		return $children->Count();
	}
	
	public function editableFieldMapping() {
		return array(
			'Title'			=> 'TextField',
			'MenuTitle'		=> 'TextField',
			'Content'		=> 'TextareaField',
		);
	}
	
	/**
	 * Write back to the content source
	 */
	public function remoteWrite($member = null) {
		foreach ($this->remoteProperties as $prop => $val) {
			$this->wrappedObject->$prop = $val;
		}
		
		$this->wrappedObject->ID = $this->getSS_ID();
		
		$xmlFormatter = new XMLDataFormatter;
		$xmlFormatter->relationDepth = 0;
		$xml = $xmlFormatter->convertDataObjectWithoutHeader($this->wrappedObject);

		$params = array('raw_body' => $xml, 'ClassName' => $this->getType(), 'ID' => $this->getSS_ID());
		$this->getSource()->getRemoteRepository()->saveObject($params);
	}

	/**
	 * Stream to browser or file
	 *
	 * @param string $toFile 
	 */
	public function streamContent($toFile = null) {
		$contentType = HTTP::getMimeType($this->Filename);

		// now get the file
		$contentUrl = $this->getSource()->ApiUrl . '/' . $this->Filename;
		$session = curl_init($contentUrl);

		if (!strlen($toFile)) {
			// QUICK HACK
			$n = $this->name;
			$filename = rawurlencode($n);

			header("Content-Disposition: atachment; filename=$filename");
			header("Content-Type: $contentType");
			// header("Content-Length: ".filesize("$path/$filename"));
			header("Pragma: no-cache");
			header("Expires: 0");
			curl_exec($session);
		} else {
			// get the file and store it into a local item
			curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
			$response = curl_exec($session);
			$fp = fopen($toFile, 'w');
			if (!$fp) {
				throw new Exception("Could not write file to $toFile");
			}
			fwrite($fp, $response);
			fclose($fp);
		}

		curl_close($session);
	}

	public function allowedImportTargets() {
		$targets = array(
			'sitetree' => true,
		);

		if ($this->wrappedObject instanceof File) {
			$targets = array(
				'file' => true,
			);
		}
		
		return $targets;
	}

}
