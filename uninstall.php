<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit();
}

delete_option('pime_admin_notice_msg');
delete_option('pime_admin_notice_style');
delete_option('pime_admin_notice_enable');
