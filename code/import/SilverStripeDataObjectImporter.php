<?php

class SilverStripeDataObjectImporter implements ExternalContentTransformer
{
    protected function importDataObject($item, $parentObject, $duplicateStrategy)
    {
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
            $filter = '"Title" = \'' . Convert::raw2sql($item->Title) . '\' AND "ParentID" = ' . ((int) $parentObject->ID);
            
            $existing = DataObject::get_one($cls, $filter);
            if ($existing && $existing->exists()) {
                if ($duplicateStrategy == ExternalContentTransformer::DS_OVERWRITE) {
                    $obj = $existing;
                    $obj->ClassName = $cls;
                } elseif ($duplicateStrategy == ExternalContentTransformer::DS_SKIP) {
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
        
        if ($obj->hasExtension('Versioned') && $parentObject->isPublished()) {
            $obj->publish('Stage', 'Live');
        }

        return $obj;
    }

    public function transform($item, $parentObject, $duplicateStrategy)
    {
        $new = $this->importDataObject($item, $parentObject, $duplicateStrategy);
        return new TransformResult($new, $item->stageChildren());
    }
}
