/**
 * requester
 *
 * @param  options
 */
(function ($) {
    $.fn.requester = function (options) {

        var object = this;
        init();

        /**
         * Start the plugin
         */
      function init() {
          object.params = new Array();
          object.params['lang'] = '';
          object.params['root_doc'] = '';

          object.countSubmit = 0;

         if (options != undefined) {
             $.each(options, function (index, val) {
               if (val != undefined && val != null) {
                   object.params[index] = val;
               }
             });
         }
      }

        /**
         * requester_injectNameRequester
         */
        this.requester_injectNameRequester = function () {
            // On UPDATE/ADD side
            $(document).ready(function () {
                var tickets_id = object.urlParam(window.location.href, 'id');
                // only in ticket form
               if (location.pathname.indexOf('front/ticket.form.php') > 0 ) {
                   object.injectName(tickets_id, 'central');
               } else if (location.pathname.indexOf('helpdesk.public.php') > 0
                    || location.pathname.indexOf('tracking.injector.php') > 0) {
                   object.injectName(tickets_id, 'helpdesk');
               }
            });
        };

         this.injectName = function (tickets_id, type) {
            // Inject fields
            if ($("input[name='name']") != undefined) {
                var formName = 'form_ticket';
               if (type == 'helpdesk') {
                   formName = 'helpdeskform';
               }

               if (type == 'central') {
                   object.requester_loadField(tickets_id, 0, formName);
               } else {
                   object.requester_loadFieldHelpdesk(tickets_id, formName);
               }

            }
         };

         this.requester_loadField = function(tickets_id, loadCounter, formName) {
                $.ajax({
                     url: object.params.root_doc + '/plugins/requester/ajax/ticket.php',
                     data: {'tickets_id': tickets_id, 'action': 'showForm', 'type': 'add'},
                     type: "POST",
                     dataType: "html",
                     success: function (response, opts) {
                        if (response != '') {
                            var requester = response;

                           if ($("#requester_name").length == 0 ) {

                               var name = $("input[name='name']");

                              if (name != undefined && name.length != 0) {
                                  name.closest('tr').before(requester);
                              } else {
                                 //add table
                                 var code_html = "<table class='tab_cadre_fixe'>"+requester+"</tr></table>";
                                 $('table#mainformtable4').before(code_html);
                              }
                           }
                        }
                     }
                  });
         };

            this.requester_loadFieldHelpdesk = function(tickets_id) {
                $.ajax({
                     url: object.params.root_doc + '/plugins/requester/ajax/ticket.php',
                     data: {'tickets_id': tickets_id, 'action': 'showFormHelpdesk', 'type': 'update'},
                     type: "POST",
                     dataType: "html",
                     success: function (response, opts) {
                        var requester = response;

                        var name = $("input[name='name']");

                        if (name != undefined) {
                            name.closest('tr').before(requester);
                        }
                     }
                  });
            };

         /**
         * Get url parameter
         *
         * @param string url
         * @param string name
         */
         this.urlParam = function (url, name) {
            var results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(url);
            if (results == null || results == undefined) {
                return  0;
            }

            return results[1];
         };

         return this;
    }
}(jQuery));
