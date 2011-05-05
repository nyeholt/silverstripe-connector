# SilverStripe to SilverStripe Connector Module

## Maintainer Contacts
*  Marcus Nyeholt (marcus@silverstripe.com.au>)

## Requirements
*  SilverStripe 2.4+
*  The External Content module.

## Getting started

* Install the module locally
* Install the module on the remote SilverStripe system. This is required so that the connector
  can get in to the remote system - note that there are two config settings that need
  to be added to the remote system config also
  * SiteTree::$api_access = true;
  * DataObject::add_extension('DataObject', 'ParentSearchable');
* Create the SilverStripe content source
* Enter the URL of the remote SilverStripe system, a username and a password.
  Be aware that these ARE stored in plain text (so that they can be used to
  connect for later requests), so please only use a user with read 
  permissions in your site and can't access the CMS backend. 

## Project Links
*  [GitHub Project Page](https://github.com/nyeholt/silverstripe-connector)
*  [Issue Tracker](https://github.com/nyeholt/silverstripe-connector/issues)
