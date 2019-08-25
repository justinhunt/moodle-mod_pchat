
define(['jquery', 'core/log','mod_pchat/definitions','mod_pchat/cloudpoodllloader','mod_pchat/recorderhelper'], function($, log, def, cloudpoodllloader,recorderhelper) {

    "use strict"; // jshint ;_;

    log.debug('Recorder controller: initialising');

    return {

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
                log.debug('PChat Recorder Controller: No config found on page. Giving up.');
                return;
            }

            dd.cmid = props.cmid;
          //  dd.holderid = props.widgetid + '_holder';
            dd.recorderid = dd.activitydata.recorderid;
            dd.updatecontrolid = props.widgetid + def.C_UPDATECONTROL;
          //  dd.playerid = props.widgetid + '_player';
          //  dd.sorryboxid = props.widgetid + '_sorrybox';

            //if the browser doesn't support html5 recording.
            //then warn and do not go any further
            if(!dd.is_browser_ok()){
              //  $('#' + dd.sorryboxid).show();
                return;
            }

            dd.setup_recorder();
            //dd.process_html(dd.activitydata);
            //dd.register_events();
        },

        is_browser_ok: function(){
            return (navigator && navigator.mediaDevices
                && navigator.mediaDevices.getUserMedia);
        },



        setup_recorder: function(){
            var dd = this;

            //Set up the callback functions for the audio recorder

            //originates from the recording:started event
            //contains no meaningful data
            //See https://api.poodll.com
            var on_recording_start= function(eventdata){
                //do something
            };

            //originates from the recording:ended event
            //contains no meaningful data
            //See https://api.poodll.com
            var on_recording_end= function(eventdata){
                //do something
            };

            //data sent here originates from the awaiting_processing event
            //See https://api.poodll.com
            var on_media_processing= function(eventdata){
                var updatecontrol = $('#' + dd.updatecontrolid);
                updatecontrol.val(eventdata.mediaurl);
            };

            //init the recorder
            recorderhelper.init(dd.activitydata,
                on_recording_start,
                on_recording_end,
                on_media_processing);
        },


        process_html: function(){
            var opts = this.activitydata;
            //these css classes/ids are all passed in from php in
            //renderer.php::fetch_activity_amd
            var controls ={};
            this.controls = controls;

            //init drop downs

        },

        register_events: function() {
            var dd = this;


        }
    };//end of returned object
});//total end
