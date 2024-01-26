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
 * Display list of tools
 */
class Tools extends Controller {
	/**
	 * @var \Tools\Admin\LabsDao
	 */
	protected $labsDao;

	/**
	 * @param \Tools\Admin\LabsDao $dao
	 */
	public function setLabsDao( $dao ) {
		$this->labsDao = $dao;
	}

	protected function handleGet() {
		$users = $this->labsDao->getAllUsers();
		$tools = $this->labsDao->getAllTools();

		$this->view->set( 'users', $users );
		$this->view->set( 'tools', $tools );
		$this->render( 'tools.html' );
	}
}
