<?php

class SilverStripeFileImporter implements ExternalContentTransformer {
	
	/**
	 *
	 * @param SilverStripeContentItem $item
	 * @param type $parentObject
	 * @param type $duplicateStrategy
	 * @return TransformResult 
	 */
	public function transform($item, $parentObject, $duplicateStrategy) {
		$newFile = $this->getTypeForFile($item->Name);
		$newFile = new $newFile;
		$folderPath = $parentObject->getRelativePath();
		$parentId = $parentObject ? $parentObject->ID : 0;
		$filter = '"ParentID" = \'' . Convert::raw2sql($parentId) . '\' and "Title" = \'' . Convert::raw2sql($item->Name) . '\'';
		$existing = DataObject::get_one('File', $filter);
		
		if ($existing && $duplicateStrategy == ExternalContentTransformer::DS_SKIP) {
			// just return the existing children
			return new TransformResult($existing, null);
		} else if ($existing && $duplicateStrategy == ExternalContentTransformer::DS_OVERWRITE) {
			$newFile = $existing;
		}
		$newFile->Name = $item->Name;
		$newFile->RemoteNodeId = $item->getSS_ID();
		$newFile->RemoteSystemId = $item->getSource()->ID;
		$newFile->Title = $item->Title;
		$newFile->ParentID = $parentId;
		$newFile->write();
		
		$filepath = Director::baseFolder().'/'.$newFile->Filename;
		Filesystem::makeFolder(dirname($filepath));
		$item->streamContent($filepath);
		
		return new TransformResult($newFile, null);
	}

	protected function getTypeForFile($filename) {
		static $images = array('jpeg', 'jpg', 'bmp', 'png', 'gif');
		$ext = strtolower(substr(strrchr($filename, "."), 1));
		return in_array($ext, $images) ? new Image() : new File();
	}

}
