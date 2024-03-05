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

use Wikimedia\SimpleI18n\I18nContext;
use Wikimedia\SimpleI18n\JsonCache;
use Wikimedia\Slimapp\AbstractApp;
use Wikimedia\Slimapp\Config;

class App extends AbstractApp {
	/**
	 * Apply settings to the Slim application.
	 *
	 * @param \Slim\Slim $slim Application
	 */
	protected function configureSlim( \Slim\Slim $slim ) {
		$slim->configureMode( 'production', static function () use ( $slim ) {
			$slim->config( [
				'debug' => false,
				'log.level' => Config::getStr( 'LOG_LEVEL', 'INFO' ),
			] );

			// Install a custom error handler
			$slim->error( static function ( \Exception $e ) use ( $slim ) {
				$errorId = substr( session_id(), 0, 8 ) . '-' .
					substr( uniqid(), -8 );
				$slim->log->critical( $e->getMessage(), [
					'exception' => $e,
					'errorId' => $errorId,
				] );
				$slim->view->set( 'errorId', $errorId );
				$slim->render( 'error.html' );
			} );
		} );

		$slim->configureMode( 'development', static function () use ( $slim ) {
			$slim->config( [
				'debug' => true,
				'log.level' => Config::getStr( 'LOG_LEVEL', 'DEBUG' ),
				'view.cache' => false,
			] );
		} );
	}

	/**
	 * Configure inversion of control/dependency injection container.
	 *
	 * @param \Slim\Helper\Set $container IOC container
	 */
	protected function configureIoc( \Slim\Helper\Set $container ) {
		$container->singleton( 'i18nCache', static function ( $c ) {
			return new JsonCache(
				$c->settings['i18n.path'], $c->log
			);
		} );

		$container->singleton( 'i18nContext', static function ( $c ) {
			return new I18nContext(
				$c->i18nCache, $c->settings['i18n.default'], $c->log
			);
		} );
	}

	/**
	 * Configure view behavior.
	 *
	 * @param \Slim\View $view Default view
	 */
	protected function configureView( \Slim\View $view ) {
		$view->parserOptions = [
			'charset' => 'utf-8',
			'cache' => $this->slim->config( 'view.cache' ),
			'debug' => $this->slim->config( 'debug' ),
			'auto_reload' => true,
			'strict_variables' => false,
			'autoescape' => true,
		];

		// Install twig parser extensions
		$view->parserExtensions = [
			new \Slim\Views\TwigExtension(),
			new \Wikimedia\SimpleI18n\TwigExtension( $this->slim->i18nContext ),
			new \Twig_Extension_Debug(),
		];

		// Set default view data
		$view->replace( [
			'app' => $this->slim,
			'i18nCtx' => $this->slim->i18nContext,
		] );
	}

	/**
	 * @inheritDoc
	 */
	protected function configureHeaderMiddleware() {
		$headers = parent::configureHeaderMiddleware();
		$headers['Content-Security-Policy'] =
			"default-src 'self' https://tools-static.wmflabs.org; " .
			"child-src 'none'; " .
			"object-src 'none'; " .
			"img-src 'self' data: https://tools-static.wmflabs.org";
		return $headers;
	}

	/**
	 * Configure routes to be handled by application.
	 *
	 * @param \Slim\Slim $slim Application
	 */
	protected function configureRoutes( \Slim\Slim $slim ) {
		$slim->hook( 'slim.before.router', static function () use ( $slim ) {
			$env = $slim->environment;
			$path = $env['PATH_INFO'];
			$qs = $env['QUERY_STRING'];

			if ( substr( $path, 0, 2 ) === '/.' ) {
				// Treat /. as /? by moving path data into query string. This
				// may be legacy legacy behavior. Documented previously as
				// being used for error_document handling where query strings
				// were not allowed in the webserver/proxy config.
				$qs = substr( $path, 2 );
				$path = '/';
			}

			if ( $path === '/' && $qs !== '' ) {
				// Map query string routes into path based routes.
				$parts = explode( '=', $qs, 2 );
				if ( $parts[0] === 'list' ) {
					$path .= 'tools';

				} elseif ( $parts[0] === 'status' ) {
					$path .= 'oge/status';

				} elseif ( in_array( $parts[0], [ '403', '404', '500', '503' ] ) ) {
					$path .= "error/{$parts[0]}";

				} elseif ( $parts[0] === 'tool' && count( $parts ) === 2 ) {
					$path .= "tool/{$parts[1]}";

				} elseif ( preg_match( '/^[A-Z]/', $parts[0] ) ) {
					$path .= "wiki/{$parts[0]}";

				} else {
					$slim->log->info( 'Unhandled query string: {qs}', [
						'qs' => $qs,
					] );
				}
				$qs = '';
			}

			$env['PATH_INFO'] = $path;
			$env['QUERY_STRING'] = $qs;
			// Clear Slim\Http\Request's internal query string cache
			unset( $env['slim.request.query_hash'] );
		}, 0 );

		$slim->group( '/',
			static function () use ( $slim ) {
				$slim->get( '', static function () use ( $slim ) {
					$slim->render( 'splash.html' );
				} )->name( 'splash' );

				$slim->get( 'error/:errorCode', static function ( $errorCode ) use ( $slim ) {
					$page = new Pages\Error( $slim );
					$page->setI18nContext( $slim->i18nContext );
					$page( $errorCode );
				} )->name( 'error' );

				$slim->get( 'favicon.ico', static function () use ( $slim ) {
					$slim->redirect(
						'https://tools-static.wmflabs.org/toolforge/favicons/favicon.ico',
						301
					);
				} )->name( 'favicon' );

				$slim->get( 'tools', static function () use ( $slim ) {
					$page = new Pages\Redirect( $slim );
					$page->setBaseUrl( 'https://toolhub.wikimedia.org' );
					$page( '/' );
				} )->name( 'tools' );

				$slim->get( 'tool/:name', static function ( $name ) use ( $slim ) {
					$page = new Pages\Redirect( $slim );
					$page->setBaseUrl( 'https://toolsadmin.wikimedia.org/tools/id/' );
					$page( $name );
				} )->name( 'tool' );

				$slim->get( 'oge/status', static function () use ( $slim ) {
					$page = new Pages\Redirect( $slim );
					$page->setBaseUrl( 'https://sge-jobs.toolforge.org' );
					$page( '/' );
				} )->name( 'oge-status' );

				$slim->get( 'healthz', static function () use ( $slim ) {
					$page = new Pages\Healthz();
					$page();
				} )->name( 'healthz' );
			}
		); // end group '/'

		$slim->notFound( static function () use ( $slim ) {
			$page = new Pages\Error( $slim );
			$page->setI18nContext( $slim->i18nContext );
			$page( '404', true );
		} );
	}
}
