lib-pdns
========

PHP library for modifying an PowerDNS MySQL database

Usage
----
```php

//setup the database config
$db = array(
	 'host'		=>	'localhost'
	,'user'		=>	'pdns'
	,'password'	=>	''
	,'driver'	=>	'mysql'
	,'port'		=>	3306
);
Config::set('pdns.db',$db); unset($db);

//getters
$domain = PDNS::getDomain('test.com');
$domain = PDNS::getDomainByHost('www.test.com');
$record = PDNS::getRecord(array('id'=>1));

//update - updates or creates record and finds it by ident
//this is the main function unless advanced actions are needed
$rv = PDNS::update('www.test.com','1.2.4.5','A');
if($rv === false)
	throw new Exception('Failed to update record: www.test.com');

//delete a record the preferred way
$rv = PDNS::delete('www.test.com','1.2.3.4','A');

//raw create record
$id = PDNS::createRecord(array(
	 'domain_id'		=>	1
	,'name'				=>	'ww1.test.com'
	,'type'				=>	'A'
	,'content'			=>	'1.2.3.4'
	,'ttl'				=>	60
	,'prio'				=>	''
));


//update raw record
$rv = PDNS::updateRecord(1,array('type'=>'CNAME','content'=>'test.com'));

//delete raw record
$rv = PDNS::deleteRecord(array('id'=>1));
```

Reference
---

### (array) PDNS::getDomain($name)
Get a domain by its name and return the database row

### (array) PDNS::getDomainByHost($name)
Take a FQDN and return its given domain database row

### (mixed) PDNS::update($identifier,$data,$type='A')
  * $identifier		Ident that gets passed to PDNS::getDomainByHost()
  * $data			The record data eg: 1.2.3.4
  * $type			The record type defaults to A
Returns the record ID on creation, TRUE on update, FALSE on failure

### (mixed) PDNS::delete($identifier,$data,$type='A')
  * $identifier		The host identifier eg: www.test.com
  * $data			The record data eg: 1.2.3.4
  * $type			Record type which defaults to A
Returns FALSE on failure, TRUE on success

Internal Refernce
----
This is for raw database actions, generally more advanced usage.

### (mixed) PDNS::createRecord($args)
Takes any amount of arguments to create a database record.
The current record schema looks like this
  * id			Record ID number
  * domain_id	Domain ID number
  * name		The record name eg: www.test.com
  * type		Record type eg: A
  * content		Record content eg: 1.2.3.4
  * ttl			Record TTL eg: 60
  * prio		The record priority mainly used for MX records eg: 10
  * change_date	This gets set automatically

### (mixed) PDNS::getRecord($args)
Similar to create by takes an array of arguments related to the 
schema and returns the database row.
Returns FALSE on failure

### (bool) PDNS::updateRecord($id=null,$args=array())
  * $id		Record ID number
  * $args	Parameters to update, see the schema for PDNS::createRecord()
Returns TRUE on success, FALSE on failure

### (bool) PDNS::deleteRcord($args=array())
  * $args	Similar to PDNS::getRecord()
Returns TRUE on success, FALSE on failure

