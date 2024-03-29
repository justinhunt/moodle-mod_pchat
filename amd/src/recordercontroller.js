
define(['jquery', 'core/log','mod_pchat/definitions','mod_pchat/cloudpoodllloader','mod_pchat/recorderhelper'],
    function($, log, def, cloudpoodllloader,recorderhelper) {

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
            dd.recorderid = dd.activitydata.recorderid;
            dd.updatecontrolid = props.widgetid + def.C_UPDATECONTROL;
            dd.streamingresultsid = props.widgetid + def.C_STREAMINGCONTROL;


            //if the browser doesn't support html5 recording.
            //then do not go any further
            if(!dd.is_browser_ok()){
                return;
            }
            
            dd.setup_recorder();

        },

        is_browser_ok: function(){
            return (navigator && navigator.mediaDevices
                && navigator.mediaDevices.getUserMedia);
        },


        setup_recorder: function(){
            var dd = this;
            var theform = $('.mod_pchat_step2').find('form');
            var uploadwarning = $('.mod_pchat_uploadwarning');
            var recordingcontainer = $('.mod_pchat_recordingcontainer');

            //Set up the callback functions for the audio recorder

            //originates from the recording:started event
            //contains no meaningful data
            //See https://api.poodll.com
            var on_recording_start= function(eventdata){
                //init streaming transcriber results
                if(dd.activitydata.transcriber == def.transcriber_amazonstreaming) {
                    dd.streamingresults = [];
                }//end of if amazonstreaming
            };

            var on_speech = function (eventdata) {
                var speech = eventdata.capturedspeech;
                var speechresults = eventdata.speechresults;
                if(dd.activitydata.transcriber == def.transcriber_amazonstreaming) {
                    dd.streamingresults.push(speechresults);
                    log.debug(dd.streamingresults);
                }
            };

            //originates from the recording:ended event
            //contains no meaningful data
            //See https://api.poodll.com
            var on_recording_end= function(eventdata){
                uploadwarning.show();
            };

            //data sent here originates from the awaiting_processing event
            //See https://api.poodll.com
            var on_media_processing= function(eventdata){
                var updatecontrol = $('#' + dd.updatecontrolid);
                updatecontrol.val(eventdata.mediaurl);

                if(dd.activitydata.transcriber == def.transcriber_amazonstreaming &&
                    dd.streamingresults &&
                    dd.streamingresults.length > 0){
                    var streamingresults = $('#' + dd.streamingresultsid);
                    streamingresults.val(JSON.stringify(dd.streamingresults));
                }
                recordingcontainer.hide();
            };

            //data sent here originates from the file_submitted event
            //See https://api.poodll.com
            var on_file_submitted= function(){
                uploadwarning.hide();
                /* disable cancel button because users can try to leave too soon */
                $(".mod_solo_step2 .btn").attr('disabled', 'disabled');
                $(".mod_pchat_step2 .btn.btn-primary").show();
                theform.submit();
            };

            //init the recorder
            recorderhelper.init(dd.activitydata,
                on_recording_start,
                on_recording_end,
                on_media_processing,
                on_speech,
                on_file_submitted);
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
