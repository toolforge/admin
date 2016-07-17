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

namespace Tools\Admin\Pages;

use Wikimedia\Slimapp\Controller;

/**
 * Error page
 */
class Error extends Controller {
	/**
	 * @var \Tools\Admin\Tools $tools
	 */
	protected $tools;

	/**
	 * @param \Tools\Admin\Tools $tools
	 */
	public function setTools( $tools ) {
		$this->tools = $tools;
	}

	protected function handleGet( $errorCode ) {
		$env = \Slim\Environment::getInstance();
		$uri = $env['HTTP_X_ORIGINAL_URI'] ?: $env['PATH_INFO'];
		if ( preg_match( '@^/([^/]+)/@', $uri, $match ) ) {
			$info = $this->tools->getToolInfo( $match[1] );
		} else {
			$info = [
				'name' => false,
				'maintainers' => []
			];
		}

		$this->view->set( 'uri', $uri );
		$this->view->set( 'tool', $info['name'] );
		$this->view->set( 'maintainers', $info['maintainers'] );
		$this->render( "errors/{$errorCode}.html" );
	}
}
