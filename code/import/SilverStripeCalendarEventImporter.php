<?php
/**
 
*/
 
class SilverStripeCalendarEventImporter extends SilverStripeDataObjectImporter {
	public function transform($item, $parentObject, $duplicateStrategy)	{
		$new = $this->importDataObject($item, $parentObject, $duplicateStrategy);

		// now lets load in all the actual EditableUserForm items
		$client = $item->getSource()->getRemoteRepository();
        $kids = $client->getRelatedItems(array('ClassName' => 'CalendarEvent', 'ID' => $item->getSS_ID(), 'Relation' => 'DateTimes'));
		$children = new ArrayList();

		foreach ($kids as $option) {
			$optionItem = $item->getSource()->getObject($option);
			$children->push($optionItem);
		}

		return new TransformResult($new, $children);
	}
}
