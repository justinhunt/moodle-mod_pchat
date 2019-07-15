/* jshint ignore:start */
define(['jquery','jqueryui', 'core/log','mod_pchat/definitions'], function($, jqui, log, def) {

    "use strict"; // jshint ;_;

    log.debug('Activity controller: initialising');

    return {

        cmid: null,
        activitydata: null,
        recorderid: null,
        controls: null,

        //for making multiple instances
        clone: function(){
            return $.extend(true,{},this);
        },

        //pass in config, the jquery video/audio object, and a function to be called when conversion has finshed
        init: function(props){
            var dd = this.clone();

            //pick up opts from html
            var theid='#amdopts_' + props.widgetid;
            var configcontrol = $(theid).get(0);
            if(configcontrol){
                dd.activitydata = JSON.parse(configcontrol.value);
                $(theid).remove();
            }else{
                //if there is no config we might as well give up
                log.debug('Read Seed Test Controller: No config found on page. Giving up.');
                return;
            }

            dd.cmid = props.cmid;
            dd.process_html();
            dd.register_events();
        },

        register_events: function() {
            var dd = this;

            //events for other controls on the page
            //ie not recorders
            //dd.controls.passagecontainer.click(function(){log.debug('clicked');})
        },

        process_html: function(){
            var opts = this.activitydata;
            //these css classes/ids are all passed in from php in
            //renderer.php::fetch_activity_amd
            var controls ={
                hider: $('.' + opts['hider']),
            };
            this.controls = controls;
        },

        send_submission: function(filename,rectime){

            //set up our ajax request
            var xhr = new XMLHttpRequest();
            var that = this;
            
            //set up our handler for the response
            xhr.onreadystatechange = function(e){
                if(this.readyState===4){
                    if(xhr.status==200){
                        log.debug('ok we got an attempt submission response');
                        //get a yes or forgetit or tryagain
                        var payload = xhr.responseText;
                        var payloadobject = JSON.parse(payload);
                        if(payloadobject){
                            switch(payloadobject.success) {
                                case false:
                                    log.debug('attempted item evaluation failure');
                                    if (payloadobject.message) {
                                        log.debug('message: ' + payloadobject.message);
                                    }

                                case true:
                                default:
                                    log.debug('attempted submission accepted');
                                    that.attemptid=payloadobject.data;
                                    that.doquizlayout();
                                    break;

                            }
                        }
                     }else{
                        log.debug('Not 200 response:' + xhr.status);
                    }


                }
            };

            var params = "cmid=" + that.cmid + "&filename=" + filename + "&rectime=" + rectime;
            xhr.open("POST",M.cfg.wwwroot + '/mod/pchat/ajaxhelper.php', true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.setRequestHeader("Cache-Control", "no-cache");
            xhr.send(params);
        }

    };//end of returned object
});//total end
