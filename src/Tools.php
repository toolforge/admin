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

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

class Tools {
	/**
	 * @var Cache $cache
	 */
	private $cache;

	/**
	 * @var LoggerInterface $logger
	 */
	protected $logger;

	/**
	 * @param Cache $cache Redis cache
	 * @param LoggerInterface|null $logger Log channel
	 */
	public function __construct( $cache, $logger = null ) {
		$this->cache = $cache;
		$this->logger = $logger ?: new \Psr\Log\NullLogger();
	}

	/**
	 * Get information about a tool
	 *
	 * @param string $tool
	 * @return array name, list of maintainers, homedir. Name will be false if
	 *     tool is not valid.
	 */
	public function getToolInfo( $tool ) {
		$ret = [
			'name' => false,
			'maintainers' => [],
			'home' => null,
		];
		$shellName = "tools.{$tool}";
		$g = posix_getgrnam( $shellName );
		$u = posix_getpwnam( $shellName );
		if ( $g && $u ) {
			$ret['name'] = $tool;
			$ret['maintainers'] = $this->getMemberInfo( $g['members'] );
			usort( $ret['maintainers'], function ( $a, $b ) {
				$aSort = isset( $a['gecos'] ) ? $a['gecos'] : $a['name'];
				$bSort = isset( $b['gecos'] ) ? $b['gecos'] : $b['name'];
				return ( $aSort == $bSort ) ? 0 :
					( ( $aSort < $bSort ) ? -1 : 1 );
			} );
			$ret['home'] = $u['dir'];
		}
		return $ret;
	}

	/**
	 * Get /etc/passwd info on a list of users.
	 * @param array $members
	 * @return array List of posix_getpwnam() data or ['name'=>...] for
	 *     unknown accounts
	 */
	protected function getMemberInfo( array $members ) {
		$ret = [];
		foreach ( $members as $member ) {
			$pwnam = posix_getpwnam( $member );
			if ( $pwnam === false ) {
				$ret[] = [ 'name' => $member ];
			} else {
				$ret[] = $pwnam;
			}
		}
		return $ret;
	}
}
