<?php
/*
 -------------------------------------------------------------------------
 Requester plugin for GLPI
 Copyright (C) 2003-2016 by the Requester Development Team.

 -------------------------------------------------------------------------

 LICENSE

 This file is part of Requester.

 Requester is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 Requester is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Requester. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

// Init the hooks of the plugins -Needed
function plugin_init_requester() {
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['requester'] = true;
    $PLUGIN_HOOKS['change_profile']['requester'] = ['PluginRequesterProfile','changeProfile'];

   if (Session::getLoginUserID()) {
       Plugin::registerClass('PluginRequesterProfile', ['addtabon' => 'Profile']);

      $PLUGIN_HOOKS['add_javascript']['requester'] = ["scripts/requester.js"];
      $PLUGIN_HOOKS['javascript']['requester'] = ["js/requester.js"];

      if (strpos($_SERVER['REQUEST_URI'], "ticket.form.php") !== false
         || strpos($_SERVER['REQUEST_URI'], "helpdesk.public.php") !== false
           || strpos($_SERVER['REQUEST_URI'], "tracking.injector.php") !== false) {

         if ('lefttab' == $_SESSION['glpilayout']) {
            $PLUGIN_HOOKS['add_javascript']['requester'][] = 'scripts/requester_load_scripts_lefttab.js';
         } else {
            $PLUGIN_HOOKS['add_javascript']['requester'][] = 'scripts/requester_load_scripts.js';

         }
      }

       // Purge
       $PLUGIN_HOOKS['pre_item_purge']['requester'] = [
           'Ticket' => ['PluginRequesterTicket', 'purgeItem'],
           'Profile' => ['PluginRequesterProfile', 'purgeProfiles']
       ];

       $PLUGIN_HOOKS['pre_item_add']['requester']    = ['Ticket' => ['PluginRequesterTicket', 'preAddTicket']];
       $PLUGIN_HOOKS['pre_item_update']['requester'] = ['Ticket' => ['PluginRequesterTicket', 'preUpdateTicket']];

       $PLUGIN_HOOKS['item_add']['requester']        = ['Ticket' => ['PluginRequesterTicket', 'afterAdd']];
       $PLUGIN_HOOKS['item_update']['requester']     = ['Ticket' => ['PluginRequesterTicket', 'afterUpdate']];

   }
   // Notifications
   $PLUGIN_HOOKS['item_get_datas']['requester'] = ['NotificationTargetTicket' => ['PluginRequesterTicket', 'addDatas']];
}

/**
 * Get the name and the version of the plugin - Needed
 *
 * @return array
 */
function plugin_version_requester() {

    return  [
        'name' => _n('Requester', 'requester', 1, 'requester'),
        'version' => '1.1.0',
        'license' => 'GPLv2+',
        'author'  => "Infotel",
        'homepage'=>'',
        'minGlpiVersion' => '9.3',// For compatibility / no install in version < 0.90
    ];

}

/**
 * Optional : check prerequisites before install : may print errors or add to message after redirect
 *
 * @return bool
 */
function plugin_requester_check_prerequisites() {
   if (version_compare(GLPI_VERSION, '9.3', 'lt') || version_compare(GLPI_VERSION, '9.4', 'ge')) {
      echo __('This plugin requires GLPI >= 9.3', 'requester');
      return false;
   }
   return true;
}


/**
 * Uninstall process for plugin : need to return true if succeeded : may display messages or add to message after redirect
 *
 * @return bool
 */
function plugin_requester_check_config() {
    return true;
}
