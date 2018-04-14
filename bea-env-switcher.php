<?php
/*
Plugin Name: BEA ENV Switcher
Plugin URI: https://github.com/BeAPI/bea-env-switcher
Description: Be API environnement switcher
Author: https://beapi.fr
Version: 1.0.2
Author URI: https://beapi.fr
 ----
 Copyright 2017 Be API Technical team (human@beapi.fr)
 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.
 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.
 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

use Purl\Url;

/**
 * Add env switcher to admin bar
 * Inspired by http://37signals.com/svn/posts/3535-beyond-the-default-rails-stages
 *
 * STAGES constant must be a serialized array of 'stage' => 'url' elements:
 *
 *   $stages = [
 *    'development' => 'http://example.dev',
 *    'staging'     => 'http://example-staging.com',
 *    'production'  => 'http://example.com'
 *   ];
 *
 *   define('ENVIRONMENTS', serialize($stages));
 *
 * WP_STAGE must be defined as the current stage
 */
class BEA_ENV_Switcher {
	public function __construct() {
		add_action( 'admin_bar_menu', array( $this, 'admin_bar_stage_switcher' ) );
		add_action( 'wp_before_admin_bar_render', array( $this, 'admin_css' ) );

		add_action( 'wp_footer', array( $this, 'add_environment_notification' ) );
		add_action( 'admin_footer', array( $this, 'add_environment_notification' ) );
	}

	public function admin_bar_stage_switcher( $admin_bar ) {
		if ( ! is_super_admin() ) {
			return;
		}

		if ( ! defined( 'ENVIRONMENTS' ) || ! defined( 'WP_ENV' ) ) {
			return;
		}

		$stages        = unserialize( ENVIRONMENTS );
		$current_stage = WP_ENV;
		foreach ( $stages as $stage => $url ) {
			if ( $stage === $current_stage ) {
				continue;
			}
			if ( is_multisite() && defined( 'SUBDOMAIN_INSTALL' ) && SUBDOMAIN_INSTALL && ! is_main_site() ) {
				$url = $this->multisite_url( $url ) . $_SERVER['REQUEST_URI'];
			} else {
				$url .= $_SERVER['REQUEST_URI'];
			}
			$admin_bar->add_menu( [
				'id'     => 'environment',
				'parent' => 'top-secondary',
				'title'  => ucwords( $current_stage ),
				'href'   => '#',
				'meta'   => [
					'class' => 'environment-' . sanitize_html_class( strtolower( $current_stage ) ),
				],
			] );
			$admin_bar->add_menu( [
				'id'     => "stage_$stage",
				'parent' => 'environment',
				'title'  => ucwords( $stage ),
				'href'   => $url,
			] );
		}
	}

	public function admin_css() { ?>
        <style>
            #wp-admin-bar-environment > a {
                font-weight: bold !important;
            }

            #wp-admin-bar-environment > a:before {
                content: "\f177";
                top: 2px;
            }
        </style>
		<?php
	}

	private function multisite_url( $url ) {
		$stage_url          = new Url( $url );
		$current_site       = new Url( get_home_url( get_current_blog_id() ) );
		$current_site->host = str_replace( $current_site->registerableDomain, $stage_url->registerableDomain, $current_site->host );

		return rtrim( $current_site->getUrl(), '/' ) . $_SERVER['REQUEST_URI'];
	}

	public static function add_environment_notification() {
		if ( ! is_admin() && defined( 'WP_ENV' ) && 'prod' === WP_ENV && ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$colors = [
			'default'        => 'orange',
			'dev'            => 'green',
			'qualif'         => 'orange',
			'contrib'        => 'orange',
			'pre-production' => 'orange',
			'prod'           => 'red',
		];
		$color  = defined( 'WP_ENV' ) && isset( $colors[ WP_ENV ] ) ? $colors[ WP_ENV ] : $colors['default'];
		self::print_inline_style( $color );
	}

	private static function print_inline_style( $color ) {
		?>
        <style>
            #wp-admin-bar-environment > a {
                background-color: <?= $color ?> !important;
                color: white !important;
            }
        </style>
		<?php
	}
}

new BEA_ENV_Switcher;