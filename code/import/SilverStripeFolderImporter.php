<?php

class SilverStripeFolderImporter implements ExternalContentTransformer
{

    public function transform($item, $parentObject, $duplicateStrategy)
    {
        $folderChildren = $item->stageChildren();
        $newFolder = new Folder();
        $parentId = $parentObject ? $parentObject->ID : 0;
        $existing = DataObject::get_one('File', '"ParentID" = \'' . Convert::raw2sql($parentId) . '\' and "Name" = \'' . Convert::raw2sql($item->Name) . '\'');
        if ($existing && $duplicateStrategy == ExternalContentTransformer::DS_SKIP) {
            // just return the existing children
            return new TransformResult($existing, $folderChildren);
        } elseif ($existing && $duplicateStrategy == ExternalContentTransformer::DS_OVERWRITE) {
            $newFolder = $existing;
        }
        $newFolder->Name = $item->Title;
        $newFolder->Title = $item->Title;
        $newFolder->MenuTitle = $item->Title;
        $newFolder->ParentID = $parentObject->ID;
        $newFolder->Sort = 0;
        $newFolder->write();
        if (!file_exists($newFolder->getFullPath())) {
            mkdir($newFolder->getFullPath(), Filesystem::$folder_create_mask);
        }
        return new TransformResult($newFolder, $folderChildren);
    }
}
