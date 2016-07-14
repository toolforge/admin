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

class Qstat {
	/**
	 * @var LoggerInterface $logger
	 */
	protected $logger;

	/**
	 * @var string $uri
	 */
	protected $uri;

	/**
	 * @param string $uri OGE status endpoint
	 * @param LoggerInterface $logger Log channel
	 */
	public function __construct( $uri, $logger = null ) {
		$this->logger = $logger ?: new \Psr\Log\NullLogger();
		$this->uri = $uri;
	}

	public function getStatus() {
		$data = [];
		$client = new Client();
		$response = $client->get( $this->uri );
		$body = $response->getBody();
		$json = json_decode( $body, true );
		if ( $json && array_key_exists( 'data', $json ) ) {
			$data = $json['data'];
		} else {
			$this->logger->error( 'Error fetching OGE status data', [
				'method' => __METHOD__,
				'status' => $response->getStatusCode(),
				'body' => $body,
			] );
		}
		return $data;
	}
}
