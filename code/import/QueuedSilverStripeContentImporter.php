<?php
 
class QueuedSilverStripeContentImporter extends QueuedExternalContentImporter
{
	/**
	 * Override this to specify additional import handlers
	 *
	 * @var array
	 */
	public static $importer_classes = array();
	
	public function init() {
		$this->contentTransforms['DataObject'] = new SilverStripeDataObjectImporter();
		$this->contentTransforms['UserDefinedForm'] = new SilverStripeFormImporter();
		$this->contentTransforms['EditableDropdown'] = new SilverStripeEditableDropdownImporter();
		$this->contentTransforms['EditableOption'] = new SilverStripeEditableOptionImporter();
        
        $this->contentTransforms['CalendarEvent'] = new SilverStripeCalendarEventImporter();
        $this->contentTransforms['CalendarDateTime'] = new SilverStripeCalendarDateTimeImporter();
        
        
		$this->contentTransforms['File'] = new SilverStripeFileImporter();
		$this->contentTransforms['Folder'] = new SilverStripeFolderImporter();
		
		foreach (self::$importer_classes as $type => $cls) {
			$this->contentTransforms[$type] = new $cls;
		}
	}

	protected function getExternalType($item) {
		if ($item->SourceClassName) {
			if (isset($this->contentTransforms[$item->SourceClassName])) {
				// we're expecting the type
				return $item->SourceClassName;
			}

			$name = null;
			$hierarchy = ClassInfo::ancestry($item->ClassName);
			foreach ($hierarchy as $ancestor => $val) {
				if (isset($this->contentTransforms[$ancestor])) {
					$name = $ancestor;
				}
			}
			if ($name) {
				return $name;
			}
		}
		
		return 'DataObject';
	}
}
