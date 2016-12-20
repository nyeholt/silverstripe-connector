<?php
/**
 
*/
 
class SilverStripeCalendarDateTimeImporter extends SilverStripeDataObjectImporter {
	public function transform($item, $parentObject, $duplicateStrategy)	{
		$new = $this->importDataObject($item, $parentObject, $duplicateStrategy);
        $new->EventID = $parentObject->ID;
        $new->write();
		$children = new ArrayList();
		return new TransformResult($new, $children);
	}
}
