<?php
 
class SilverStripeContentImporter extends ExternalContentImporter
{
	/**
	 * Override this to specify additional import handlers
	 *
	 * @var array
	 */
	public static $importer_classes = array();
	
	public function __construct() {
		$this->init();
	}

	public function init() {
		$this->contentTransforms['DataObject'] = new SilverStripeDataObjectImporter();
		$this->contentTransforms['UserDefinedForm'] = new SilverStripeFormImporter();
		$this->contentTransforms['EditableDropdown'] = new SilverStripeEditableDropdownImporter();
		$this->contentTransforms['EditableOption'] = new SilverStripeEditableOptionImporter();

		foreach (self::$importer_classes as $type => $cls) {
			$this->contentTransforms[$type] = new $cls;
		}
	}

	protected function getExternalType($item) {

		if ($item->ClassName) {
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
