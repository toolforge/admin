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

	/**
	 * @param string $errorCode HTTP error code
	 * @param bool $notFoundHandler Are we being called as the notFound
	 *   handler for the app?
	 */
	protected function handleGet( $errorCode, $notFoundHandler = false ) {
		$env = \Slim\Environment::getInstance();
		$uri = $env['HTTP_X_ORIGINAL_URI'] ?: $env['PATH_INFO'];
		if ( preg_match( '@^/([^/]+)/@', $uri, $match ) ) {
			$info = $this->tools->getToolInfo( $match[1] );

			if ( $notFoundHandler && $info['name'] !== false ) {
				// Route was for a known tool
				if ( $uri === "/{$info['name']}" ) {
					// Redirect bare /<toolname> to /<toolname>
					$this->redirect( "/{$info['name']}/", 301 );
				} else {
					// The tool's service must be down. Send a 503 response.
					$errorCode = '503';
				}
			}
		} else {
			$info = [
				'name' => false,
				'maintainers' => []
			];
		}

		$httpStatus = $notFoundHandler ? (int)$errorCode : 200;
		$this->view->set( 'uri', $uri );
		$this->view->set( 'tool', $info['name'] );
		$this->view->set( 'maintainers', $info['maintainers'] );
		$this->render( "errors/{$errorCode}.html", [], $httpStatus );
	}
}
