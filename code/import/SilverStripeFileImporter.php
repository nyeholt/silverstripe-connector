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
 
class SilverStripeFileImporter implements ExternalContentTransformer
{
	public function transform($item, $parentObject, $duplicateStrategy)
	{
		$newFile = $this->getTypeForFile($item->Name);
		$folderPath = $parentObject->getRelativePath();
		$parentId = $parentObject ? $parentObject->ID : 0;
		$filter = 'ParentID = \''.Convert::raw2sql($parentId).'\' and Title = \''.Convert::raw2sql($item->Name).'\'';
		$existing = DataObject::get_one('File', $filter);
		if ($existing && $duplicateStrategy == ExternalContentTransformer::DS_SKIP) {
			// just return the existing children
			return new TransformResult($existing, null);
		} else if ($existing && $duplicateStrategy == ExternalContentTransformer::DS_OVERWRITE) {
			$newFile = $existing;
		}
		//
		$newFile->Name = $item->Name;
		$newFile->Title = $item->Name;
		$newFile->MenuTitle = $item->Name;
		//
		$size = filesize($item->FilePath);
		$details = array(
			'size' => $size,
			'name' => $item->Name,
			'tmp_name' => $item->FilePath
		);
		$upload = new FileLoader();
		$folderPath = trim(substr($folderPath, strpos($folderPath, '/')), '/');
		$upload->loadIntoFile($details, $newFile, $folderPath);

		return new TransformResult($newFile, null);
	}

	protected function getTypeForFile($filename)
	{
		static $images = array('jpeg', 'jpg', 'bmp', 'png', 'gif');
		$ext = strtolower(substr(strrchr($filename, "."), 1));
		return in_array($ext, $images) ? new Image() : new File();
	}
}

?>