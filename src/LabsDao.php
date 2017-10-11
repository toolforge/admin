<?php
/**
 * This file is part of Toolforge Admin
 * Copyright (C) 2016  Wikimedia Foundation and contributors
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation, either version 3 of the License, or (at your option)
 * any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tools\Admin;

use Wikimedia\Slimapp\Dao\AbstractDao;

/**
 * Data access object for Labs database
 */
class LabsDao extends AbstractDao {

	private $cache;
	private $toolinfo;

	public function __construct(
		$dsn, $user, $pass, $cache, $toolinfo, $logger = null
	) {
		parent::__construct( $dsn, $user, $pass, $logger );
		// FIXME: Horrible hack for T164971
		$this->dbh->exec( 'set names latin1' );
		$this->cache = $cache;
		$this->toolinfo = $toolinfo;
	}

	public function getAllUsers() {
		$key = 'labdb:users';
		$users = $this->cache->load( $key );
		if ( !$users ) {
			$records = $this->fetchAll( 'SELECT * FROM users' );
			foreach ( $records as $idx => $row ) {
				$users[$row['name']] = $row;
			}
			$this->cache->save( $key, $users, 900 );
		}
		return $users;
	}

	public function getAllTools() {
		$key = 'labsdb:alltools';
		$tools = $this->cache->load( $key );
		if ( !$tools ) {
			$records = $this->fetchAll(
				'SELECT * FROM tools ORDER BY name ASC' );
			foreach ( $records as $idx => $row ) {
				$tools[$row['name']] = $this->toolsRowToArray( $row );
			}
			$this->cache->save( $key, $tools, 900 );
		}
		return $tools;
	}

	public function getTool( $name ) {
		$key = "labsdb:tool:{$name}";
		$tool = $this->cache->load( $key );
		if ( !$tool ) {
			$row = $this->fetch(
				'SELECT * FROM tools WHERE name = ?',
				[ $name ]
			);
			if ( $row ) {
				$tool = $this->toolsRowToArray( $row );
				$this->cache->save( $key, $tool, 900 );
			}
		}
		return $tool;
	}

	protected function toolsRowToArray( $row ) {
		$info = $this->toolinfo->getInfo( $row['name'] );
		if ( !$info ) {
			$info = [ [
				'name' => $row['name'],
				'title' => $row['name'],
				'description' => $row['description'],
			] ];
		}
		usort( $info, function ( $a, $b ) {
			$an = $a['title'] ?: $a['name'];
			$bn = $b['title'] ?: $b['name'];
			return strcmp( $an, $bn );
		} );
		$row['toolinfo'] = $info;
		$row['maintainers'] = explode( ' ', $row['maintainers'] );
		sort( $row['maintainers'] );
		return $row;
	}
}
