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

namespace Tools\Admin;

if ( !defined( 'APP_ROOT' ) ) {
	define( 'APP_ROOT', dirname( __DIR__ ) );
}
require_once APP_ROOT . '/vendor/autoload.php';

// Ensure that a default timezone is set
set_error_handler( function ( $errno, $errstr ) {
	throw new Exception( $errstr );
} );
try {
	date_default_timezone_get();
} catch ( Exception $e ) {
	// Use UTC if not specified anywhere in .ini
	date_default_timezone_set( 'UTC' );
}
restore_error_handler();

// TODO: Handle legacy madness of /?(...) URLs
// TODO: Handle legacy madness of /.(...) URLs
// TODO: Handle legacy bare tool name -> tool/ 301 redir
// TODO: Handle acting as global 503 handler
// TODO: Handle acting as global 404 handler
// TODO: Handle query string 302 to wikitech content

// Load environment settings from .env if present
if ( is_readable( APP_ROOT . '/.env' ) ) {
	\Wikimedia\Slimapp\Config::load( APP_ROOT . '/.env' );
}

$app = new App( APP_ROOT );
$app->run();
