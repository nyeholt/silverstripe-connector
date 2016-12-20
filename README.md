# SilverStripe to SilverStripe Connector Module

## Maintainer Contacts
*  Marcus Nyeholt (marcus@silverstripe.com.au>)

## Requirements
*  SilverStripe 3.2+
*  The External Content module.

## Getting started

* Install the module locally
* Install restful server (silverstripe/restfulserver)
  on the remote SilverStripe system. This is required so that the connector
  can get in to the remote system - note that there are some config settings that need
  to be added to the remote system config also. You _may_ need to add more for other
  items that you want to get from the API (eg calendar date times)

```yml
SiteTree:
  api_access: 1
  searchable_fields:
    ParentID: 
      filter: ExactMatchFilter
      title: Parent ID
File:
  api_access: 1
  searchable_fields:
    ParentID: 
      filter: ExactMatchFilter
      title: Parent ID
```

* Create the SilverStripe content source
* Enter the URL of the remote SilverStripe system, a username and a password.
  Be aware that these ARE stored in plain text (so that they can be used to
  connect for later requests), so please only use a user with read 
  permissions in your site and can't access the CMS backend. 

## Project Links
*  [GitHub Project Page](https://github.com/nyeholt/silverstripe-connector)
*  [Issue Tracker](https://github.com/nyeholt/silverstripe-connector/issues)
