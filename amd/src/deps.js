define(['jquery', 'core/log', 'theme_boost/tether'], function ($, log, Tether) {
    "use strict"; // jshint ;_;

    /*
    This file is a dependency of loader that is called by popover to ensure correct things are loaded in the right sequence
     */

    log.debug('PChat deps: initialising');

    window.Tether = Tether;
    //really wish we did not have to do this, but theme_boost/popover can not be relied on to have a jquery
    if(!window.jQuery) {
        window.jQuery = $;
    }
    return {};//end of return value
});