<?php
/**
 * This file is part of Tool Labs Admin
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
	 * @var LoggerInterface $logger
	 */
	protected $logger;

	/**
	 * @param LoggerInterface $logger Log channel
	 */
	public function __construct( $logger = null ) {
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
		$shallName = "tools.{$tool}";
		$g = posix_getgrnam( $shallName );
		$u = posix_getpwnam( $shallName );
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

	/**
	 * Get list of currently active webservices.
	 * @return array
	 */
	public function getActiveWebservices() {
		// TODO: add caching for 5-10 minutes in redis
		$services = [];
		// FIXME: $active_proxy = file_get_contents( '/etc/active-proxy' );
		$active_proxy = 'tools-proxy-01';
		$proxy_uri = "http://{$active_proxy}:8081/list";

		$client = new Client();
		$response = $client->get( $proxy_uri );
		$body = $response->getBody();
		$json = json_decode( $body, true );
		if ( $json ) {
			$proxies = json_decode( $body, true );
			foreach ( $proxies as $key => $value ) {
				if (
					array_key_exists( 'status', $value ) &&
					$value['status'] == 'active'
				) {
					$services[$key] = 1;
				}
			}
		} else {
			$this->logger->error( 'Error fetching webproxy status data', [
				'method' => __METHOD__,
				'status' => $response->getStatusCode(),
				'body' => $body,
			] );
		}
		return $services;
	}
}
