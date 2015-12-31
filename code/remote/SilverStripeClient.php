<?php

/**
 * 
 * @license BSD License http://silverstripe.org/bsd-license
 */
class SilverStripeClient
{

    public function __construct()
    {
        $this->api = new WebApiClient(null, self::$methods);
        $this->api->setUseCookies(true);
        $this->api->setMaintainSession(true);

        $this->api->addReturnHandler('arraylist', new DataObjectSetReturnHandler());
        $this->api->addReturnHandler('dataobject', new DataObjectReturnHandler());
    }

    public function connect($url, $username, $password)
    {
        $this->api->setBaseUrl($url);
        $this->api->setAuthInfo($username, $password);
    }

    public function isConnected()
    {
        return $this->api->getBaseUrl() != null;
    }

    public function disconnect()
    {
        $this->api->setBaseUrl(null);
    }

    public function __call($method, $args)
    {
        return $this->call($method, isset($args[0]) ? $args[0] : array());
    }

    private $callCount = 0;

    public function call($method, $args)
    {
        $this->callCount++;
        try {
            return $this->api->callMethod($method, $args);
        } catch (Zend_Http_Client_Exception $zce) {
        }
    }

    public static $methods = array(
        'getNode'            => array(
            'url'                => '/api/v1/{ClassName}/{ID}',
            'return'            => 'dataobject',
            'cache'                => 120
        ),
        'getChildren'        => array(
            'url'                => '/api/v1/{ClassName}',
            'params'            => array('ParentID'),
            'return'            => 'arraylist',
            'cache'                => 120
        ),
        'getRelatedItems'    => array(
            'url'                => '/api/v1/{ClassName}/{ID}/{Relation}',
            'return'            => 'arraylist',
            'cache'                => 120
        ),
        'saveObject'        => array(
            'url'                => '/api/v1/{ClassName}/{ID}',
            'method'            => 'PUT',
            'raw'                => true
        )
    );
}

class RemoteDataObjectHandler
{

    private $baseClass = 'SiteTree';
    private $remap = array(
        'ID' => 'SS_ID',
    );

    protected function getRemoteObject($node)
    {
        $clazz = $node->nodeName;
        $object = null;
        // do we have this data object type?
        if (ClassInfo::exists($clazz)) {
            // we'll create one
            $object = new $clazz;
        } else {
            $object = new Page();
        }

        // track the name property and set it LAST again to handle special cases like file
        // that overwrite other properties when $name is set
        $name = null;
        foreach ($node->childNodes as $property) {
            if ($property instanceof DOMText) {
                continue;
            }
            $pname = $property->nodeName;
            if (isset($this->remap[$pname])) {
                $pname = $this->remap[$pname];
            }
            if ($pname == 'Filename') {
                $name = $property->nodeValue;
            }
            $object->$pname = $property->nodeValue;
        }
        
        $object->SourceClassName = $clazz;
        
        if (!is_null($name)) {
            $object->Filename = $name;
        }

        return $object;
    }
}

class DataObjectSetReturnHandler extends RemoteDataObjectHandler implements ReturnHandler
{

    public function handleReturn($raw)
    {
        $xml = new DomDocument();
        // cleaning up some junk
        $raw = str_replace(array('<?xml version="1.0" encoding="UTF-8"?>', '', '', ''), '', $raw);
        $xml->loadXML($raw);
        $objects = new ArrayList();
        // lets get all the items beneath the root item
        foreach ($xml->childNodes as $node) {
            if ($node->nodeName == 'ArrayList') {
                foreach ($node->childNodes as $childNode) {
                    if ($childNode instanceof DOMText) {
                        continue;
                    }

                    $objects->push($this->getRemoteObject($childNode));
                }
            }
        }
        return $objects;
    }
}

class DataObjectReturnHandler extends RemoteDataObjectHandler implements ReturnHandler
{

    public function handleReturn($raw)
    {
        $xml = new DomDocument;
        $raw = str_replace(array('', '', ''), '', $raw);
        $xml->loadXML($raw);
        $obj = $this->getRemoteObject($xml->childNodes->item(0));
        return $obj;
    }
}
