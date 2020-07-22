// Put this file in path/to/plugin/amd/src
// You can call it anything you like

define(['jquery'], function($) {

    return {
        init: function() {

            // Put whatever you like here. $ is available
            // to you as normal.
            $(".someclass").change(function() {
                alert("It changed!!");
            });
        }
    };
});