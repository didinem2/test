<?php

/*
 -------------------------------------------------------------------------
 Requester plugin for GLPI
 Copyright (C) 2003-2016 by the Requester Development Team.

 https://forge.indepnet.net/projects/requester
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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Class PluginRequesterProfile
 *
 * This class manages the profile rights of the plugin
 *
 * @package    Requester
 */
class PluginRequesterProfile extends Profile
{

   /**
    * @param int $nb
    *
    * @return \translated
    */
   static function getTypeName($nb = 0) {
      return __('Requester', 'requester');
   }

   /**
    * Get tab name for item
    *
    * @param CommonGLPI $item
    * @param int|type $withtemplate
    * @return string
    */
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if ($item->getType() == 'Profile') {
         return self::getTypeName(2);
      }
      return '';
   }

   /**
    * display tab content for item
    *
    * @param CommonGLPI $item
    * @param int|type $tabnum
    * @param int|type $withtemplate
    * @return bool
    * @global type $CFG_GLPI
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      if ($item->getType() == 'Profile') {
         $ID   = $item->getID();
         $prof = new self();

         self::addDefaultProfileInfos($ID,
                                      ['plugin_requester' => ALLSTANDARDRIGHT]);
         $prof->showForm($ID);
      }

      return true;
   }

   /**
    * show profile form
    *
    * @param int $profiles_id
    * @param bool $openform
    * @param bool $closeform
    * @return bool
    * @internal param type $ID
    * @internal param type $options
    */
   function showForm($profiles_id = 0, $openform = true, $closeform = true) {

      echo "<div class='firstbloc'>";
      if (($canedit = Session::haveRightsOr(self::$rightname, [READ + UPDATE + CREATE + PURGE]))
         && $openform
      ) {
         $profile = new Profile();
         echo "<form method='post' action='" . $profile->getFormURL() . "'>";
      }

      $profile = new Profile();
      $profile->getFromDB($profiles_id);

      $rights = $this->getAllRights();
      $profile->displayRightsChoiceMatrix($rights, ['canedit'       => $canedit,
         'default_class' => 'tab_bg_2',
         'title'         => __('General')]);

      if ($canedit
         && $closeform
      ) {
         echo "<div class='center'>";
         echo Html::hidden('id', ['value' => $profiles_id]);
         echo Html::submit(_sx('button', 'Save'), ['name' => 'update']);
         echo "</div>\n";
         Html::closeForm();
      }
      echo "</div>";

      $this->showLegend();
   }

   /**
    * Get all rights
    *
    * @param type $all
    * @return array
    */
   static function getAllRights($all = false) {

      $rights = [
         ['itemtype' => 'PluginRequesterTicket',
            'label'    => __('Requester', 'requester'),
            'field'    => 'plugin_requester'
         ]
      ];

      return $rights;
   }

   /**
    * Init profiles
    *
    **/

   static function translateARight($old_right) {
      switch ($old_right) {
         case '':
            return 0;
         case 'r' :
            return READ;
         case 'w':
            return ALLSTANDARDRIGHT;
         case '0':
         case '1':
            return $old_right;

         default :
            return 0;
      }
   }


   /**
    * @since 0.85
    * Migration rights from old system to the new one for one profile
    * @param $profiles_id the profile ID
    * @return bool
    */
   static function migrateOneProfile($profiles_id) {
      global $DB;
      //Cannot launch migration if there's nothing to migrate...
      if (!TableExists('glpi_plugin_requester_profiles')) {
         return true;
      }

      foreach ($DB->request('glpi_plugin_requester_profiles',
         "`profiles_id`='$profiles_id'") as $profile_data) {

         $matching = ['requester' => 'plugin_requester'];
         $current_rights = ProfileRight::getProfileRights($profiles_id, array_values($matching));
         foreach ($matching as $old => $new) {
            if (!isset($current_rights[$old])) {
               $query = "UPDATE `glpi_profilerights` 
                         SET `rights`='" . self::translateARight($profile_data[$old]) . "' 
                         WHERE `name`='$new' AND `profiles_id`='$profiles_id'";
               $DB->query($query);
            }
         }
      }
   }

   /**
    * Initialize profiles, and migrate it necessary
    */
   static function initProfile() {
      global $DB;
      $profile = new self();

      //Add new rights in glpi_profilerights table
      foreach ($profile->getAllRights(true) as $data) {
         if (countElementsInTable("glpi_profilerights",
               "`name` = '" . $data['field'] . "'") == 0
         ) {
            ProfileRight::addProfileRights([$data['field']]);
         }
      }

      //Migration old rights in new ones
      foreach ($DB->request("SELECT `id` FROM `glpi_profiles`") as $prof) {
         self::migrateOneProfile($prof['id']);
      }
      foreach ($DB->request("SELECT *
                           FROM `glpi_profilerights` 
                           WHERE `profiles_id`='" . $_SESSION['glpiactiveprofile']['id'] . "' 
                              AND `name` LIKE '%plugin_requester%'") as $prof) {
         $_SESSION['glpiactiveprofile'][$prof['name']] = $prof['rights'];
      }
   }

   /**
    * Initialize profiles, and migrate it necessary
    */
   static function changeProfile() {
      global $DB;

      foreach ($DB->request("SELECT *
                           FROM `glpi_profilerights` 
                           WHERE `profiles_id`='" . $_SESSION['glpiactiveprofile']['id'] . "' 
                              AND `name` LIKE '%plugin_requester%'") as $prof) {
         $_SESSION['glpiactiveprofile'][$prof['name']] = $prof['rights'];
      }

      if ($_SESSION["glpiactiveprofile"]["interface"] == "helpdesk") {
         $_SESSION["glpi_plugin_requester_loaded"] = 0;
      }

   }

   /**
    * @param $profiles_id
    */
   static function createFirstAccess($profiles_id) {

      $rights = ['plugin_requester' => READ + UPDATE + CREATE + PURGE];

      self::addDefaultProfileInfos($profiles_id,
         $rights, true);

   }

   /**
    * @param $profile
    **/
   static function addDefaultProfileInfos($profiles_id, $rights, $drop_existing = false) {

      $profileRight = new ProfileRight();
      foreach ($rights as $right => $value) {
         if (countElementsInTable('glpi_profilerights',
                                  "`profiles_id`='$profiles_id' AND `name`='$right'") && $drop_existing
         ) {
            $profileRight->deleteByCriteria(['profiles_id' => $profiles_id, 'name' => $right]);
         }
         if (!countElementsInTable('glpi_profilerights',
                                   "`profiles_id`='$profiles_id' AND `name`='$right'")
         ) {
            $myright['profiles_id'] = $profiles_id;
            $myright['name']        = $right;
            $myright['rights']      = $value;
            $profileRight->add($myright);

            //Add right to the current session
            $_SESSION['glpiactiveprofile'][$right] = $value;
         }
      }
   }

}
