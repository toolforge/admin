<?php
/**
 * This file is part of Toolforge Admin
 * Copyright (C) 2017  Wikimedia Foundation and contributors
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

class Toolinfo {
	/**
	 * @var LoggerInterface $logger
	 */
	protected $logger;

	/**
	 * @var string $uri
	 */
	protected $uri;

	/**
	 * @var Cache $cache
	 */
	private $cache;

	private $info;

	/**
	 * @param string $uri Toolinfo endpoint
	 * @param Cache $cache
	 * @param LoggerInterface|null $logger Log channel
	 */
	public function __construct( $uri, $cache, $logger = null ) {
		$this->logger = $logger ?: new \Psr\Log\NullLogger();
		$this->uri = $uri;
		$this->cache = $cache;
	}

	/**
	 * Get toolinfo records for a tool.
	 * @param string $tool
	 * @return array
	 */
	public function getInfo( $tool ) {
		if ( $this->info === null ) {
			$this->info = $this->fetchInfo();
		}
		return static::aGet( $tool, $this->info, [] );
	}

	private function fetchInfo() {
		$key = 'toolinfo:info';
		$info = $this->cache->load( $key );
		if ( !$info ) {
			$client = new Client();
			$response = $client->get( $this->uri );
			$body = $response->getBody();
			$json = json_decode( $body, true );
			if ( $json ) {
				$info = $this->filterToolinfo( $json );
				$this->cache->save( $key, $info, 3600 );
			} else {
				$this->logger->error( 'Error fetching toolinfo data', [
					'method' => __METHOD__,
					'status' => $response->getStatusCode(),
					'body' => $body,
				] );
				$info = [];
			}
		}
		return $info;
	}

	private function filterToolinfo( $raw ) {
		$toolinfo = [];
		foreach ( $raw as $info ) {
			if ( false !== strpos( $info['url'], 'tools.wmflabs.org' ) ) {
				preg_match(
					'#^(https?:)?//tools.wmflabs.org/([^/]+).*#',
					$info['url'],
					$m
				);
				$tool = $m[2];
				if ( !array_key_exists( $tool, $toolinfo ) ) {
					$toolinfo[$tool] = [];
				}
				$toolinfo[$tool][] = [
					'name' => $info['name'],
					'title' => $info['title'],
					'description' => $info['description'],
					'url' => $info['url'],
					'keywords' => static::aGet( 'keywords', $info ),
					'author' => static::aGet( 'author', $info ),
					'repository' => static::aGet( 'repository', $info ),
				];
			}
		}
		return $toolinfo;
	}

	private static function aGet( $key, $arr, $default = null ) {
		return array_key_exists( $key, $arr ) ? $arr[$key] : $default;
	}
}
