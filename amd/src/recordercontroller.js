
define(['jquery', 'core/log','mod_pchat/definitions','mod_pchat/cloudpoodllloader','mod_pchat/recorderhelper','mod_pchat/transcriber-lazy'],
    function($, log, def, cloudpoodllloader,recorderhelper, transcriber) {

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

            dd.setup_transcriber();
            
            dd.setup_recorder();




        },

        is_browser_ok: function(){
            return (navigator && navigator.mediaDevices
                && navigator.mediaDevices.getUserMedia);
        },

        setup_transcriber: function(){
            var dd = this;
            dd.streamingresults = false;

            //init streaming transcriber
            var opts={};
            opts['language']=dd.activitydata.language;
            opts['region']=dd.activitydata.region;
            opts['transcriber']=dd.activitydata.transcriber;
            opts['token'] = dd.activitydata.token;
            opts['parent'] = dd.activitydata.parent;
            opts['owner'] = dd.activitydata.owner;
            opts['appid'] = dd.activitydata.appid;
            opts['expiretime'] = dd.activitydata.expiretime;
            opts['transcriber']=dd.activitydata.transcriber;

            if(opts['transcriber'] == def.transcriber_amazonstreaming) {
                transcriber.init(opts);
                transcriber.onFinalResult = function (transcript, result) {
                    dd.streamingresults.push(result);
                    //if recording over deal with final result
                    //if(!transcriber.active){
                    log.debug(dd.streamingresults);
                    //}

                    // theCallback(message);
                };
                transcriber.onPartialResult = function (transcript, result) {
                    //do nothing
                };
            }
        },

        setup_recorder: function(){
            var dd = this;

            //Set up the callback functions for the audio recorder

            //originates from the recording:started event
            //contains no meaningful data
            //See https://api.poodll.com
            var on_recording_start= function(eventdata){
                //start streaming transcriber
                if(dd.activitydata.transcriber == def.transcriber_amazonstreaming) {
                    if (transcriber.active) {
                        return;
                    }
                    //init our streamingresults
                    dd.streamingresults = [];
                    // first we get the microphone input from the browser (as a promise)...
                    window.navigator.mediaDevices.getUserMedia({
                        video: false,
                        audio: true,
                    }).then(function (stream) {
                        transcriber.start(stream, transcriber)
                    }).catch(function (error) {
                            log.debug(error);
                            log.debug('There was an error streaming your audio to Amazon Transcribe. Please try again.');
                        }
                    );
                }//end of if amazonstreaming
            };

            //originates from the recording:ended event
            //contains no meaningful data
            //See https://api.poodll.com
            var on_recording_end= function(eventdata){
                //stop streaming transcriber
                if(dd.activitydata.transcriber == def.transcriber_amazonstreaming) {
                    if (!transcriber.active) {
                        return;
                    }
                    transcriber.closeSocket();
                }
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
