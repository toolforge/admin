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
 * Get javascript metadata for searching tools.
 */
class ToolsJavascript extends Controller {
	/**
	 * @var \Tools\Admin\LabsDao $labsDao
	 */
	protected $labsDao;

	/**
	 * @param \Tools\Admin\LabsDao $dao
	 */
	public function setLabsDao( $dao ) {
		$this->labsDao = $dao;
	}

	protected function handleGet() {
		$tools = $this->labsDao->getAllTools();

		$this->view->set( 'tools', $tools );
		$this->contentType( 'application/javascript; charset=utf-8' );
		$this->lastModified( time() );
		$this->expires( '+15 minutes' );
		$this->render( 'tools.js' );
	}
}
