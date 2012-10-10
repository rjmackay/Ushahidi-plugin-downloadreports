<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Download Reports Hook.
 * This hook will take care of adding a link in the nav_main_top section.
 *
 * PHP version 5
 * LICENSE: This source file is subject to LGPL license
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/lesser.html
 * @license    http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL)
 */

// Hook into the nav main_top

class report {

	public function __construct()
	{
		// Hook into routing
		Event::add('system.pre_controller', array($this, 'add'));

		Event::add('ushahidi_action.nav_main_top', array($this, '_add_nav'));
	}
	
	public function add()
	{
		switch (Router::$current_uri) {
			case "reports/download":
				plugin::add_stylesheet('downloadreports/views/css/download_reports');
				Event::add('ushahidi_action.header_scripts', array($this, '_add_scripts'));
			break;
		}
	}

	public function _add_nav()
	{
		$page = Event::$data;
		// Add plugin link to nav_main_top
		echo "<li><a href='" . url::site() . "reports/download' class='".($page == 'download' ? 'active' : '')."'>" . utf8::strtoupper(Kohana::lang('ui_main.download_reports')) . "</a></li>";

	}
	
	public function _add_scripts()
	{
		echo html::script(url::file_loc('js') . 'media/js/jquery.validate.min', true);
		echo html::script(url::file_loc('js') . 'media/js/jquery.ui.min', true);
	}

}

new report();
