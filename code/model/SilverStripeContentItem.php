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
 

class SilverStripeContentItem extends ExternalContentItem
{
	protected $wrappedObject;
	
	public function __construct($source=null, $id=null, $content=null)
	{
		if ($content) {
			$this->wrappedObject = $content;
			parent::__construct($source, $content->SS_ID);
		} else {
			if (is_bool($id)) {
				$id = 0;
			}
			parent::__construct($source, $id);
		}
	}

	public function init($content = null)
	{
		$repo = $this->source->getRemoteRepository();
		
		if (!$this->wrappedObject && $this->externalId) {
			$this->wrappedObject = $repo->getNode(array('ClassName' => 'SiteTree', 'ID' => $this->externalId));
			/* @var $content DataObject */
		}
		
		if ($this->wrappedObject) {
			$allFields = $this->wrappedObject->getAllFields();
			
			foreach ($allFields as $field => $value) {
				if ($field == 'ID') {
					continue;
				}
				$this->$field = $value;
			}
		}
	}

	public function stageChildren()
	{
		$children = new DataObjectSet();
		$repo = $this->source->getRemoteRepository();

		try {
			if ($repo->isConnected()) {
				$kids = $repo->getChildren(array('ClassName'=>'SiteTree', 'ParentID' => $this->externalId));
				// Even though it returns actual dataobjects, we need to wrap them for sanity and safety's sake
				foreach ($kids as $childItem) {
					$item = $this->source->getObject($childItem);
					$children->push($item);
				}
			}
		} catch (FailedRequestException $fre) {
			error_log("Failed to retrieve children: ".$fre->getMessage());
			return $children;
		}

		return $children;
	}

	public function numChildren()
	{
		$children = $this->Children();
		return $children->Count();
	}

	public function getType()
	{
		return get_class($this->wrappedObject);
	}

	public function streamContent()
	{
	}
}

?>