<?php

/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 requester plugin for GLPI
 Copyright (C) 2013-2016 by the requester Development Team.

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

include ('../../../inc/includes.php');

Html::header_nocache();
Session::checkLoginUser();
header("Content-Type: text/html; charset=UTF-8");

if (isset($_POST['action'])) {

   switch ($_POST['action']) {
      case "load" :

         $params = ['root_doc' => $CFG_GLPI['root_doc']];

         if (Session::haveRight("plugin_requester", CREATE)) {
            echo "<script type='text/javascript'>";
            echo "var requester = $(document).requester(" . json_encode($params) . ");";
            echo "requester.requester_injectNameRequester();";
            echo "</script>";
         }

         break;
   }
}
