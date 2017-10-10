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

	public function __construct( $dsn, $user, $pass, $logger = null ) {
		parent::__construct( $dsn, $user, $pass, $logger );
		// FIXME: Horrible hack for T164971
		$this->dbh->exec( 'set names latin1' );
	}

	public function getAllUsers() {
		$users = [];
		$records = $this->fetchAll( 'SELECT * FROM users' );
		foreach ( $records as $idx => $row ) {
			$users[$row['name']] = $row;
		}
		return $users;
	}

	public function getAllTools() {
		$tools = [];
		$records = $this->fetchAll( 'SELECT * FROM tools ORDER BY name ASC' );
		foreach ( $records as $idx => $row ) {
			$tools[$row['name']] = $this->toolsRowToArray( $row );
		}
		return $tools;
	}

	public function getTool( $name ) {
		$row = $this->fetch(
			'SELECT * FROM tools WHERE name = ?',
			[ $name ]
		);
		if ( $row ) {
			return $this->toolsRowToArray( $row );
		} else {
			return false;
		}
	}

	protected function toolsRowToArray( $row ) {
		$info = null;
		if ( $row['toolinfo'] != '' ) {
			$info = json_decode( $row['toolinfo'], true );
		}
		if ( $info === null ) {
			$info = [ [ 'description' => $row['description'] ] ];
		}
		if ( !array_key_exists( 0, $info ) ) {
			$info = [ $info ];
		}
		// Filter out things that are not hosted on Toolforge
		$row['toolinfo'] = array_filter( $info, function ( $tool ) {
			return array_key_exists( 'url', $tool ) ?
				( strpos( $tool['url'], 'tools.wmflabs.org' ) !== false ) :
				true;
		} );
		$row['maintainers'] = explode( ' ', $row['maintainers'] );
		sort( $row['maintainers'] );
		return $row;
	}
}
