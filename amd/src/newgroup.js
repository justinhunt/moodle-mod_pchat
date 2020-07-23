/**
 * Add a create new group modal to the page.
 *
 * @module     core_group/newgroup
 * @class      NewGroup
 * @package    core_group
 * @copyright  2017 Damyon Wiese <damyon@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/str', 'core/modal_factory', 'core/modal_events', 'core/fragment', 'core/ajax', 'core/yui'],
    function($, Str, ModalFactory, ModalEvents, Fragment, Ajax, Y) {

        /**
         * Constructor
         *
         * @param {String} selector used to find triggers for the new group modal.
         * @param {int} contextid
         *
         * Each call to init gets it's own instance of this class.
         */
        var NewGroup = function(selector, contextid) {
            this.contextid = contextid;
            this.init(selector);
        };

        /**
         * @var {Modal} modal
         * @private
         */
        NewGroup.prototype.modal = null;

        /**
         * @var {int} contextid
         * @private
         */
        NewGroup.prototype.contextid = -1;

        /**
         * Initialise the class.
         *
         * @param {String} selector used to find triggers for the new group modal.
         * @private
         * @return {Promise}
         */
        NewGroup.prototype.init = function(selector) {
            var triggers = selector;
            this.studentid = $(selector).attr('data-student-id');
            this.cmid = $(selector).attr('data-cm-id');

            // Fetch the title string.
            return Str.get_string('creategroup', 'core_group').then(function(title) {
                // Create the modal.
                return ModalFactory.create({
                    type: ModalFactory.types.SAVE_CANCEL,
                    title: title,
                    body: this.getBody()
                }, triggers);
            }.bind(this)).then(function(modal) {
                // Keep a reference to the modal.
                this.modal = modal;

                // Forms are big, we want a big modal.
                this.modal.setLarge();

                // We want to reset the form every time it is opened.
                this.modal.getRoot().on(ModalEvents.hidden, function() {
                    this.modal.setBody(this.getBody());
                }.bind(this));

                // We want to hide the submit buttons every time it is opened.
                this.modal.getRoot().on(ModalEvents.shown, function() {
                    this.modal.getRoot().append('<style>[data-fieldtype=submit] { display: none ! important; }</style>');
                }.bind(this));


                // We catch the modal save event, and use it to submit the form inside the modal.
                // Triggering a form submission will give JS validation scripts a chance to check for errors.
                this.modal.getRoot().on(ModalEvents.save, this.submitForm.bind(this));
                // We also catch the form submit event and use it to submit the form with ajax.
                this.modal.getRoot().on('submit', 'form', this.submitFormAjax.bind(this));

                return this.modal;
            }.bind(this));
        };

        /**
         * @method getBody
         * @private
         * @return {Promise}
         */
        NewGroup.prototype.getBody = function(formdata) {
            if (typeof formdata === "undefined") {
                formdata = {};
            }
            // Get the content of the modal.
            var params = {jsonformdata: JSON.stringify(formdata)};
            params.studentid = this.studentid;
            params.cmid = this.cmid;

            return Fragment.loadFragment(
                'mod_pchat',
                'new_group_form',
                this.contextid,
                params);
        };

        /**
         * @method handleFormSubmissionResponse
         * @private
         * @return {Promise}
         */
        NewGroup.prototype.handleFormSubmissionResponse = function() {
            this.modal.hide();

            Y.use('moodle-core-formchangechecker', function() {
                M.core_formchangechecker.reset_form_dirty_state();
            });

            $("[data-original-student]").trigger('change');
        };

        /**
         * @method handleFormSubmissionFailure
         * @private
         * @return {Promise}
         */
        NewGroup.prototype.handleFormSubmissionFailure = function(data) {
            // Oh noes! Epic fail :(
            // Ah wait - this is normal. We need to re-display the form with errors!
            this.modal.setBody(this.getBody(data));
            $("[data-original-student]").trigger('change');
        };

        /**
         * Private method
         *
         * @method submitFormAjax
         * @private
         * @param {Event} e Form submission event.
         */
        NewGroup.prototype.submitFormAjax = function(e) {
            // We don't want to do a real form submission.
            e.preventDefault();

            var changeEvent = document.createEvent('HTMLEvents');
            changeEvent.initEvent('change', true, true);

            // Prompt all inputs to run their validation functions.
            // Normally this would happen when the form is submitted, but
            // since we aren't submitting the form normally we need to run client side
            // validation.
            this.modal.getRoot().find(':input').each(function(index, element) {
                element.dispatchEvent(changeEvent);
            });

            // Now the change events have run, see if there are any "invalid" form fields.
            var invalid = $.merge(
                this.modal.getRoot().find('[aria-invalid="true"]'),
                this.modal.getRoot().find('.error')
            );

            // If we found invalid fields, focus on the first one and do not submit via ajax.
            if (invalid.length) {
                invalid.first().focus();
                return;
            }

            // Convert all the form elements values to a serialised string.
            var formData = this.modal.getRoot().find('form').serialize();

            // Now we can continue...
            Ajax.call([{
                methodname: 'mod_pchat_submit_create_group_form',
                args: {
                    contextid: this.contextid,
                    jsonformdata: JSON.stringify(formData),
                    studentid: parseInt(this.studentid) ?? 0,
                    cmid: parseInt(this.cmid) ?? 0
                },
                done: this.handleFormSubmissionResponse.bind(this, formData),
                fail: this.handleFormSubmissionFailure.bind(this, formData)
            }]);

            this.modal.hide();
        };

        /**
         * This triggers a form submission, so that any mform elements can do final tricks before the form submission is processed.
         *
         * @method submitForm
         * @param {Event} e Form submission event.
         * @private
         */
        NewGroup.prototype.submitForm = function(e) {
            e.preventDefault();
            this.modal.getRoot().find('form').submit();
        };

        return /** @alias module:core_group/newgroup */ {
            // Public variables and functions.
            /**
             * Attach event listeners to initialise this module.
             *
             * @method init
             * @param {string} selector The CSS selector used to find nodes that will trigger this module.
             * @param {int} contextid The contextid for the course.
             * @return {Promise}
             */
            init: function(selector, contextid) {
                return new NewGroup(selector, contextid);
            }
        };
    });