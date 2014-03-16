<?php
/*

	Plugin Name: FlexiCache
	Plugin URI: http://twitter.com/flexicache
	Description: A fast, full-featured and flexible caching system which will improve the performance and availability of any WordPress site.
	Author: Simon Holliday
	Version: 1.2.4.4
	Author URI: http://simonholliday.com/

	--

	Copyright (C) 2010-2013 Simon Holliday (http://simonholliday.com/)

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.

*/

require_once 'FlexiCache.php';

register_deactivation_hook(__FILE__, array('FlexiCache_Wp','deactivatePlugin'));

FlexiCache_Wp::init();
