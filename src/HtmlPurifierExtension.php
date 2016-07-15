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

use Slim\Helper\Set;
use Twig_Extension;
use Twig_SimpleFilter;

/**
 * Use HtmlPurifier from Twig.
 */
class HtmlPurifierExtension extends Twig_Extension {

	/**
	 * @var Set $container
	 */
	protected $container;

	/**
	 * @var string $member
	 */
	protected $member;

	/**
	 * @param Set $c Expected to hold a configured HtmlPurifier instance
	 * @param string $member Name of the HtmlPurifier instance
	 */
	public function __construct( Set $c, $member = 'purifier' ) {
		$this->container = $c;
		$this->member = $member;
	}

	public function getFilters() {
		return [
			new Twig_SimpleFilter(
				'purify', [ $this, 'purifyFilterCallback' ],
				[ 'is_safe' => [ 'html' ] ]
			),
		];
	}

	/**
	 * Sanitize a string using our HtmlPurifier.
	 *
	 * @param string $str
	 * @return string Sanitized html
	 */
	public function purifyFilterCallback( $str ) {
		$purifier = $this->container->get( $this->member );
		return $purifier->purify( $str );
	}

	public function getName() {
		return 'HtmlPurifier';
	}
}
