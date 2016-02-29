<?php
 
class SilverStripeDataObjectImporter implements ExternalContentTransformer
{
	protected function importDataObject($item, $parentObject, $duplicateStrategy) {
		Versioned::reading_stage('Stage');
		
		$ignore = array(
			'ClassName' => true,
			'Status' => true,
			'ID' => true,
		);

		
		$cls = 'Page';
		if (strlen($item->ClassName) && ClassInfo::exists($item->ClassName)) {
			$cls = $item->ClassName;
		}
		
		if ($cls == 'SiteTree') {
			$cls = 'Page';
		}

		$obj = new $cls;

		$obj->Version = 1;
		
		if ($parentObject && $parentObject->hasExtension('Hierarchy')) {
			if($cls == "SilverStripeContentItem" && $item->Title == "Files") {
				$fakeId = '0-File';
				$fakeFiles = new SilverStripeContentItem($item->source, $fakeId);
				$fakeFiles->Title = 'Files';
				$fakeFiles->ID = $item->ID . ExternalContent::ID_SEPARATOR . $fakeId;
				return $fakeFiles;
			}

			$filter = '"Title" = \'' . Convert::raw2sql($item->Title) . '\' AND "ParentID" = ' . ((int) $parentObject->ID);
			
			$existing = DataObject::get_one($cls, $filter);
			if ($existing && $existing->exists()) {
				if ($duplicateStrategy == ExternalContentTransformer::DS_OVERWRITE) {
					$obj = $existing;
					$obj->ClassName = $cls;
				} else if ($duplicateStrategy == ExternalContentTransformer::DS_SKIP) {
					return $existing;
				}
			}
		}
		

		foreach ($item->getRemoteProperties() as $field => $value) {
			if (!isset($ignore[$field]) && $obj->hasField($field)) {
				$obj->$field = $value;
			}
		}
		
		$obj->RemoteNodeId = $item->getSS_ID();
		$obj->RemoteSystemId = $item->getSource()->ID;

		$obj->ParentID = $parentObject->ID;
		if ($parentObject->SubsiteID) {
			$obj->SubsiteID = $parentObject->SubsiteID;
		}
		
		$obj->write();

		// Adds proper support for 2.4 so everything doesn't get published since
		// the built in API will show unpublished pages. 3.x doesn't have this issue
		$itemPublished = false;
		if(isset($item->Status) && $item->Status == "Published") {
			$itemPublished = true;
		}
		
		// Changed here for $itemPublished OR $parentObject->isPublished to account for the 2.4 issue
		if ($parentObject->ClassName != "Folder" && $obj->hasExtension('Versioned') && ($itemPublished || $parentObject->isPublished())) {
			$obj->publish('Stage', 'Live');
		}

		return $obj;
	}

	public function transform($item, $parentObject, $duplicateStrategy) {
		$new = $this->importDataObject($item, $parentObject, $duplicateStrategy);
		return new TransformResult($new, $item->stageChildren());
	}
}
