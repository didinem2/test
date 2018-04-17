<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 requester plugin for GLPI
 Copyright (C) 2013-2016 by the requester Development Team.

 https://github.com/InfotelGLPI/requester
 -------------------------------------------------------------------------

 LICENSE

 This file is part of requester.

 requester is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 requester is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with requester. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}


/**
 * Class PluginRequesterTicket
 */
class PluginRequesterTicket extends CommonITILObject {

   static $rightname = "plugin_requester";

   /**
    * @param Ticket $ticket
    */
   static function emptyTicket(Ticket $ticket) {
      if (!empty($_POST)) {
         self::setSessions($_POST);
      } else if (!empty($_REQUEST)) {
         self::setSessions($_REQUEST);
      }
   }


   /**
    * Print the name of requester ticket form
    *
    * @return Nothing (display)
    * */
   function showFormHelpdesk() {

      // validation des droits
      if (!$this->canView()) {
         return false;
      }

      // Create item
      $this->getEmpty();

      // If values are saved in session we retrieve it
      if (isset($_SESSION['glpi_plugin_requester_ticket'])) {

         foreach ($_SESSION['glpi_plugin_requester_ticket'] as $key => $value) {
            $this->fields[$key] = stripslashes($value);

         }
      }

      echo "<tr class='tab_bg_1'><td>";
      echo __('Name of requester', 'requester');
      echo "<span class='red'>*</span>&nbsp;";
      echo "</td><td>";
      Html::autocompletionTextField($this, "requester_name");
      echo "</td></tr>";

   }

   /**
    * Print the ticket form
    *
    * @param $ID integer ID of the item
    * @param $options array
    *     - target filename : where to go when done.
    *     - withtemplate boolean : template or basic item
    *
    * @return Nothing (display)
    * */
   function showForm($ID, $options = []) {

      // validation des droits
      if (!$this->canView()) {
         return false;
      }

      if ($ID > 0) {
         if (!$this->fields['requester_name'] = self::getTicketFromDB($ID)) {
            $this->getEmpty();
         }
      } else {
         // Create item
         $this->getEmpty();
      }

      // If values are saved in session we retrieve it
      if (isset($_SESSION['glpi_plugin_requester_ticket'])) {
         foreach ($_SESSION['glpi_plugin_requester_ticket'] as $key => $value) {
            $this->fields[$key] = stripslashes($value);

         }
      }

      unset($_SESSION['glpi_plugin_requester_ticket']);

      echo "<tr class='tab_bg_1'><th width='13%'>";
      echo __('Name of requester', 'requester');
      echo "<span class='red'>*</span>&nbsp;";
      echo "</th><td id='requester_name'>";
      $options = ['option' => 'disabled="disabled"'];
      if (Session::haveRight("plugin_requester", CREATE)) {
         $options = [];
      } else if (Session::haveRight("plugin_requester", UPDATE)) {
         $options = [];
      }
      Html::autocompletionTextField($this, "requester_name", $options);
      echo "</td></tr>";

   }

   /**
    * Returns the value for this ticket
    *
    * @param $tickets_id
    *
    * @return bool
    */
   function getTicketFromDB($tickets_id) {
      $requester = new PluginRequesterTicket();
      if ($requester->getFromDBByQuery("WHERE `tickets_id` = " .$tickets_id)) {
         return $requester->fields['requester_name'];
      }
      return false;
   }


   /**
    * Set requester_name mandatory
    *
    * @param Ticket $ticket
    * @return boolean
    */
   static function checkMandatoryFields(Ticket $ticket) {

      if ($ticket->canUpdate()) {
         if (!isset($ticket->input['requester_name'])
            || empty($ticket->input['requester_name'])) {
            Session::addMessageAfterRedirect(sprintf(__("Mandatory fields are not filled. Please correct: %s"), __('Name of requester', 'requester')), false, ERROR);
            $ticket->input = false;
         }
      }

      return true;
   }

   /**
    * @param \Ticket $ticket
    *
    * @return \Ticket
    */
   static function preUpdateTicket(Ticket $ticket) {
      if (Session::haveRight("plugin_requester", UPDATE) && isset($ticket->input['update'])
          && (isset($ticket->input['type']) || isset($ticket->input['requesttypes_id']))) {
         self::checkMandatoryFields($ticket);

         if (isset($ticket->input['requester_name'])) {

            $requester = new PluginRequesterTicket();
            if (!($requester_name = $requester->getTicketFromDB($ticket->getID()))) {
               $requester_name = "";
            }

            if ($requester_name != $ticket->input['requester_name']) {
               $ticket->updates[]                   = "requester_name";
               $ticket->updates[]                   = "date_mod";
               $ticket->oldvalues['requester_name'] = $requester_name;
               $ticket->input['date_mod'] = date('Y-m-d H:i:s');
            }
         }
      }
      return $ticket;
   }

   /**
    * @param \Ticket $ticket
    */
   static function preAddTicket(Ticket $ticket) {

      if (Session::haveRight("plugin_requester", CREATE)) {
         self::checkMandatoryFields($ticket);
      }
      if (isset($ticket->input['requester_name'])) {
         $_SESSION["glpi_plugin_requester_ticket"]['requester_name'] = $ticket->input['requester_name'];
      }
   }

   /**
    * @param Ticket $ticket
    */
   static function afterUpdate(Ticket $ticket) {
      if (Session::haveRight("plugin_requester", UPDATE) && isset($ticket->input['requester_name'])) {

         $requester = new PluginRequesterTicket();
         if ($requester->getFromDBByQuery("WHERE `tickets_id` = " . $ticket->getID())) {

            $requester_name    = $requester->fields['requester_name'];
            $requester->fields = ['id'             => $requester->getID(),
                                       'requester_name' => $ticket->input['requester_name']];

            $requester->updateInDB(['requester_name'], ['requester_name' => $requester_name]);

            $changes = ['old_value' => $requester_name,
                             'new_value' => $ticket->input['requester_name']];

            self::addLog($ticket->getID(), $changes);

         } else {
            $requester->fields = ['tickets_id'     => $ticket->getID(),
                                       'requester_name' => $ticket->input['requester_name']];
            $requester->addToDB();

         }

         if (isset($_SESSION['glpi_plugin_requester_ticket'])) {
            unset($_SESSION['glpi_plugin_requester_ticket']);
         }
      }

   }

   /**
    * @param Ticket $ticket
    *
    * @return bool
    */
   static function afterAdd(Ticket $ticket) {

      if (!is_array($ticket->input) || !count($ticket->input)) {
         // Already cancel by another plugin
         return false;
      }
      if (Session::haveRight("plugin_requester", CREATE)) {
         $requester = new PluginRequesterTicket();
         $requester->fields = ['tickets_id'     => $ticket->getID(),
                                    'requester_name' => $ticket->input['requester_name']];
         $requester->addToDB();

         $changes = ['old_value' => "",
                          'new_value' => $ticket->input['requester_name']];

         self::addLog($ticket->getID(), $changes);

         if (isset($_SESSION['glpi_plugin_requester_ticket'])) {
            unset($_SESSION['glpi_plugin_requester_ticket']);
         }

      }
      return true;
   }

   /**
    * @param $tickets_id
    * @param $input
    * @param $action
    */
   static function addLog($tickets_id, $input) {

      $changes[0] = 1200;
      $changes[1] = $input['old_value'];
      $changes[2] = $input['new_value'];

      Log::history($tickets_id, 'Ticket', $changes, 'PluginRequesterTicket');
   }

   /**
    * @param $input
    */
   static function setSessions($input) {

      foreach ($input as $key => $values) {
         switch ($key) {
            case 'requester_name':
               $_SESSION['glpi_plugin_requester_ticket'][$key] = $values;
               break;
               case 'requester_user':
               $_SESSION['glpi_plugin_requester_ticket'][$key] = $values;
               break;
         }

      }
   }

   /**
    * Purge item
    *
    * @param type $item
    */
   static function purgeItem($item) {
      switch ($item->getType()) {
         case 'Ticket':
            $temp = new self();
            $temp->deleteByCriteria(['tickets_id' => $item->getField("id")], 1);
      }
   }

   /**
    * @param \NotificationTargetTicket $target
    */
   static function addDatas(NotificationTargetTicket $target) {

      $plugin = new plugin();

      $target->datas['##lang.ticket.requester##'] = __('Name of requester', 'requester');
      $target->datas['##ticket.requester##'] = "";
      
      $target->datas['##ticket.title##'] = "";

      if (isset($target->obj->fields['id']) && isset($target->obj->input['requester_name'])) {
         if ($plugin->isActivated('requester')) {
               $target->datas['##ticket.requester##'] = $target->obj->input['requester_name'];
         }
      } else if (isset($target->obj->fields['id'])) {
         if ($plugin->isActivated('requester')) {
            $requester_ticket = new PluginRequesterTicket();
            if ($requester_ticket->getFromDBByQuery("WHERE `tickets_id` =" . $target->obj->fields['id'])) {
               $target->datas['##ticket.requester##'] = $requester_ticket->fields['requester_name'];
            }
         }
      }
   }
}
