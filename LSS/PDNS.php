<?php
/**
 *  OpenLSS - Lighter Smarter Simpler
 *
 *	This file is part of OpenLSS.
 *
 *	OpenLSS is free software: you can redistribute it and/or modify
 *	it under the terms of the GNU Lesser General Public License as
 *	published by the Free Software Foundation, either version 3 of
 *	the License, or (at your option) any later version.
 *
 *	OpenLSS is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU Lesser General Public License for more details.
 *
 *	You should have received a copy of the 
 *	GNU Lesser General Public License along with OpenLSS.
 *	If not, see <http://www.gnu.org/licenses/>.
 */
namespace LSS;

class PDNS {

	static $db = false;

	public static function _get(){
		if(self::$db == false){
			self::$db = new Db();
			self::connect();
		}
		return self::$db;
	}

	public function connect(){
		return self::_get()->setConfig(Config::getMerged('pdns','db'))->connect();
	}

	public static function fetchDomain($name){
		return self::_get()->fetch(
			 'SELECT * FROM `domains` WHERE name=?'
			,array($name)
		);
	}

	public static function fetchDomainByHost($fqdn){
		$fqdncrumbs = explode('.',$fqdn);
		while(true){
			$zone = implode($fqdncrumbs,'.');
			$domain = self::fetchDomain($zone);
			if(is_array($domain)) break;
			array_shift($fqdncrumbs);
			if(count($fqdncrumbs) == 0) break;
		}
		return (is_array($domain)) ? $domain : false;
	}

	public static function fetchRecord($args){
		$where = Db::prepWhere($args);
		return self::_get()->fetch('SELECT * FROM `records`'.array_shift($where),$where);
	}

	public static function createRecord($args=array()){
		$args['change_date'] = time();
		return self::_get()->insert(
			'records'
			,$args
		);
	}

	public static function updateRecord($id=null,$args=array()){
		if(!is_numeric($id)) return false;
		$args['change_date'] = time();
		return self::_get()->update(
			 'records'
			,'id'
			,$id
			,$args
		);
	}

	public static function deleteRecord($args=array()){
		if(!is_numeric(mda_get($args,'id'))) return false;
		$where = Db::prepWhere($args);
		return self::_get()->run('DELETE FROM `records`'.array_shift($where),$where);
	}

	public static function update($identifier,$data,$type='A'){
		$identifier = self::_cleanHostname($identifier);
		$data = self::_cleanHostname($data);
		$type = strtoupper($type);
		//get domain
		$domain = self::fetchDomainByHost($identifier);
		if(!is_array($domain)){
			dolog('DNS Zone for '.$identifier.' not found',LOG_ERROR);
			return false;
		}
		dolog('Using DNS Zone: '.mda_get($domain,'name'));
		//check for existing
		$record = self::fetchRecord(array(
			 'domain_id'	=> mda_get($domain,'id')
			,'type'			=> $type
			,'name'			=> $identifier
			,'content'		=> $data
			)
		);
		if(is_array($record) && is_numeric(mda_get($record,'id'))){
			dolog('DNS Zone already contained the record, updating change_date only');
			return self::updateRecord(mda_get($record,'id'));
		}
		//insert new record
		dolog('Inserting record '.$identifier.' IN '.$type.' '.$data);
		return self::createRecord(array(
				 'domain_id'	=> mda_get($domain,'id')
				,'name'			=> $identifier
				,'type'			=> $type 
				,'content'		=> $data
				,'ttl'			=> 60
				,'prio'			=> 0
			)
		);
	}

	public static function delete($identifier,$data,$type='A'){
		$identifier = self::_cleanHostname($identifier);
		$data = self::_cleanHostname($data);
		$type = strtoupper($type);
		$record = self::fetchRecord(array(
			 'type'			=> $type
			,'name'			=> $identifier
			,'content'		=> $data
			)
		);
		$info = '"'.$identifier.' '.$type.' '.$data.'"';
		if(is_array($record) && is_numeric(mda_get($record,'id'))){
			dolog('Record '.$info.' found, deleting');
			return self::deleteRecord($record);
		}
		dolog('Record '.$info.' NOT found');
		return false;
	}

	private static function _cleanHostname($hostname=''){
		$orig_hn = $hostname;
		$c = 1;
		while($c>0)
			str_replace('..','.',$hostname,$c);
		$hostname = trim($hostname,'. ');
		if($hostname != $orig_hn)
			dolog('Hostname '.$orig_hn.' auto-repaired to '.$hostname);
		return $hostname;
	}

}
