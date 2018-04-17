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

/**
 * @return bool
 */
function plugin_requester_install() {
   global $DB;

   include_once (GLPI_ROOT . "/plugins/requester/inc/profile.class.php");

   if (!TableExists("glpi_plugin_requester_configs")) {

      // table sql creation
      $DB->runFile(GLPI_ROOT . "/plugins/requester/install/sql/empty.sql");

      foreach ($DB->request("SELECT `id` FROM `glpi_profiles`") as $prof) {
         $rights = ['plugin_requester' => READ + UPDATE + CREATE + PURGE];
         PluginRequesterProfile::addDefaultProfileInfos($prof['id'], $rights, true);
      }
   }

   PluginRequesterProfile::createFirstAccess($_SESSION['glpiactiveprofile']['id']);

   return true;
}

 /**
  * Uninstall process for plugin : need to return true if succeeded
  *
  * @return bool
  */
function plugin_requester_uninstall() {
   global $DB;

   include_once (GLPI_ROOT . "/plugins/requester/inc/profile.class.php");

   // Plugin tables deletion
   $tables = ["glpi_plugin_requester_tickets"];

   foreach ($tables as $table) {
      $DB->query("DROP TABLE IF EXISTS `$table`;");
   }

   //Delete rights associated with the plugin
   $profileRight = new ProfileRight();
   foreach (PluginRequesterProfile::getAllRights() as $right) {
      $profileRight->deleteByCriteria(['name' => $right['field']]);
   }
   return true;
}

////// SEARCH FUNCTIONS ///////(){

/**
 * Define search option for types of the plugins
 * @param $itemtype
 * @return array
 */
function plugin_requester_getAddSearchOptions($itemtype) {

   $sopt = [];
   if (Session::haveRight("plugin_requester", READ)) {
      if ($itemtype == 'Ticket') {
         $sopt[1200]['table']          = 'glpi_plugin_requester_tickets';
         $sopt[1200]['field']          = 'requester_name';
         $sopt[1200]['linkfield']      = 'id';
         $sopt[1200]['massiveaction']  = false;
         $sopt[1200]['name']           = __('Name of requester', 'requester');
         $sopt[1200]['joinparams']     = ['beforejoin' => ['table' => getTableForItemType('Ticket')]];

      }
   }
   return $sopt;
}

/**
 * @param $type
 * @param $ID
 * @param $data
 * @param $num
 * @return string
 */
function plugin_requester_giveItem($type, $ID, $data, $num) {

   $searchopt = &Search::getOptions($type);
   $table = $searchopt[$ID]["table"];
   $field = $searchopt[$ID]["field"];

   switch ($table.'.'.$field) {
      case "glpi_plugin_requester_tickets.requester_name" :

         if ($type == 'Ticket') {
            $ticket = new PluginRequesterTicket();
            if ($ticket->getFromDBByQuery("WHERE `tickets_id` = ".$data['raw']['id'])) {
               return $ticket->fields['requester_name'];
            }
         }
         break;

   }
   return "";
}

/**
 * Define database relations
 *
 * @return array
 */
function plugin_requester_getDatabaseRelations() {

   $plugin = new Plugin();

   if ($plugin->isActivated("requester")) {
      return ["glpi_tickets" => ["glpi_plugin_requester_tickets" => "tickets_id"]];
   }
   return  [];
}
