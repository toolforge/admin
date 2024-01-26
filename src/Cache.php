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

use Redis;

class Cache {
	private Redis $redis;
	private string $prefix;

	/**
	 * @param string $host
	 * @param int $port
	 */
	public function __construct( string $host, int $port = 6379 ) {
		$this->redis = new Redis();
		$this->redis->connect( $host, $port, 2 );
		$this->redis->setOption(
			Redis::OPT_READ_TIMEOUT, 2 );
		$this->redis->setOption(
			Redis::OPT_SERIALIZER, Redis::SERIALIZER_NONE );
		$u = posix_getpwuid( getmyuid() );
		$this->prefix = sha1( "{$u['name']}.{$u['dir']}" );
	}

	private function key( string $val ): string {
		return "{$this->prefix}:{$val}";
	}

	public function load( $key ) {
		$val = $this->redis->get( $this->key( $key ) );
		if ( $val !== false ) {
			$val = json_decode( $val, true );
		}
		return $val;
	}

	public function save( $key, $val, $ttl = 300 ) {
		return $this->redis->setEx(
			$this->key( $key ),
			$ttl,
			json_encode( $val )
		);
	}
}
