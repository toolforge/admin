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
		// TODO: make this optional?
		$mycnf_file = Config::getStr( 'MY_CNF', '../replica.my.cnf' );
		$mycnf = parse_ini_file( $mycnf_file );

		$slim->config( [
			'qstat.uri' => Config::getStr( 'QSTAT_URI',
				'https://tools.wmflabs.org/gridengine-status'
			),
			'db.dsn' => Config::getStr( 'DB_DSN',
				'mysql:host=tools.labsdb;dbname=toollabs_p'
			),
			'db.user' => Config::getStr( 'DB_USER', $mycnf['user'] ),
			'db.pass' => Config::getStr( 'DB_PASS', $mycnf['password'] ),
		] );

		$slim->configureMode( 'production', function () use ( $slim ) {
			$slim->config( [
				'debug' => false,
				'log.level' => Config::getStr( 'LOG_LEVEL', 'INFO' ),
			] );

			// Install a custom error handler
			$slim->error( function ( \Exception $e ) use ( $slim ) {
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

		$slim->configureMode( 'development', function () use ( $slim ) {
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
		$container->singleton( 'i18nCache', function ( $c ) {
			return new JsonCache(
				$c->settings['i18n.path'], $c->log
			);
		} );

		$container->singleton( 'i18nContext', function ( $c ) {
			return new I18nContext(
				$c->i18nCache, $c->settings['i18n.default'], $c->log
			);
		} );

		$container->singleton( 'qstat', function ( $c ) {
			return new Qstat( $c->settings['qstat.uri'], $c->log );
		} );

		$container->singleton( 'tools', function ( $c ) {
			return new Tools( $c->log );
		} );

		$container->singleton( 'purifierConfig', function ( $c ) {
			$config = \HTMLPurifier_Config::createDefault();
			$config->set( 'HTML.Doctype', 'HTML 4.01 Transitional' );
			$config->set( 'URI.Base', 'https://tools.wmflabs.org' );
			$config->set( 'URI.MakeAbsolute', true );
			$config->set( 'URI.DisableExternalResources', true );
			$config->set( 'CSS.ForbiddenProperties', [
				'margin' => true,
				'margin-top' => true,
				'margin-right' => true,
				'margin-bottom' => true,
				'margin-left' => true,
				'padding' => true,
				'padding-top' => true,
				'padding-right' => true,
				'padding-bottom' => true,
				'padding-left' => true
			] );
		} );

		$container->singleton( 'purifier', function ( $c ) {
			return new \HTMLPurifier( $c->purifierConfig );
		} );

		$container->singleton( 'labsDao', function ( $c ) {
			return new Dao\LabsDao(
				$c->settings['db.dsn'],
				$c->settings['db.user'], $c->settings['db.pass'],
				$c->log
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
			new HumanFilters(),
			new HtmlPurifierExtension( $this->slim->container ),
			new \Twig_Extension_Debug(),
		];

		// Set default view data
		$view->replace( [
			'app' => $this->slim,
			'i18nCtx' => $this->slim->i18nContext,
		] );
	}

	/**
	 * @inherit
	 */
	protected function configureHeaderMiddleware() {
		$headers = parent::configureHeaderMiddleware();
		// The tablesort plugin needs eval (gross!)
		$headers['Content-Security-Policy'] .=
			"; script-src 'self' 'unsafe-eval'";
		return $headers;
	}

	/**
	 * Configure routes to be handled by application.
	 *
	 * @param \Slim\Slim $slim Application
	 */
	protected function configureRoutes( \Slim\Slim $slim ) {
		$slim->group( '/',
			function () use ( $slim ) {
				$slim->get( '', function () use ( $slim ) {
					$slim->render( 'splash.html' );
				} )->name( 'splash' );

				$slim->get( 'tools', function () use ( $slim ) {
					$page = new Pages\Tools( $slim );
					$page->setI18nContext( $slim->i18nContext );
					$page->setTools( $slim->tools );
					$page->setLabsDao( $slim->labsDao );
					$page();
				} )->name( 'tools' );
			}
		); // end group '/'

		$slim->group( '/oge/',
			function () use ( $slim ) {
				$slim->get( 'status', function () use ( $slim ) {
					$page = new Pages\OgeStatus( $slim );
					$page->setI18nContext( $slim->i18nContext );
					$page->setQstat( $slim->qstat );
					$page();
				} )->name( 'oge-status' );
			}
		); // end group '/oge'

		$slim->notFound( function () use ( $slim ) {
			$page = new Pages\NotFound( $slim );
			$page->setI18nContext( $slim->i18nContext );
			$page->setTools( $slim->tools );
			$page();
		} );
	}
}
