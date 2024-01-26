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

namespace Tools\Admin\Pages;

use Wikimedia\Slimapp\Controller;

/**
 * Redirect to a page on wiki.
 */
class Redirect extends Controller {
	/**
	 * @var string
	 */
	protected $baseUrl;

	public function setBaseUrl( $base ) {
		$this->baseUrl = $base;
	}

	protected function handleGet( $title ) {
		// TODO: validate the $title starts with an uppercase alpha
		$this->redirect( $this->baseUrl . urlencode( $title ) );
	}
}
