(function ($) {
    'use strict';

    /**
     * Initialize Alerts
     */
    function initAlerts() {
        // Auto-dismiss after 5 seconds
        setTimeout(function () {
            $('.avdp-alert-success, .avdp-alert-info').fadeOut(300, function () {
                $(this).remove();
            });
        }, 5000);

        // Manual dismiss
        $(document).on('click', '.avdp-alert-dismiss', function () {
            $(this).closest('.avdp-alert').fadeOut(200, function () {
                $(this).remove();
            });
        });
    }

    /**
     * Initialize Weekly Hours
     */
    function initWeeklyHours() {
        $('.avdp-day-toggle').on('change', function () {
            var $row = $(this).closest('tr');
            var $inputs = $row.find('.avdp-slots-container input');

            if ($(this).is(':checked')) {
                $inputs.prop('disabled', false);
                $inputs.css('opacity', '1');
            } else {
                $inputs.prop('disabled', true);
                $inputs.css('opacity', '0.5');
            }
        });

        // Initial state check
        $('.avdp-day-toggle').each(function () {
            var $row = $(this).closest('tr');
            var $inputs = $row.find('.avdp-slots-container input');
            if (!$(this).is(':checked')) {
                $inputs.prop('disabled', true).css('opacity', '0.5');
            }
        });
    }

    /**
     * Initialize Date Overrides
     */
    function initDateOverrides() {
        // Add Blocked Date
        $('.add-blocked-date').on('click', function () {
            var targetId = $(this).data('target');
            var html = '<div class="date-override-row">' +
                '<input type="date" name="date_overrides[blocked_dates][]" required>' +
                '<button type="button" class="button remove-date-override">' + avdpAdmin.i18n.remove + '</button>' +
                '</div>';
            $('#' + targetId).append(html);
        });

        // Add Allowed Date
        $('.add-allowed-date').on('click', function () {
            var targetId = $(this).data('target');
            var index = Date.now();
            var method = $('#method').val() || 'fixed';
            var html;

            if (method === 'daily') {
                // Day Based: Date only, no time inputs
                html = '<div class="date-override-row">' +
                    '<input type="date" name="date_overrides[allowed_dates][' + index + '][date]" required>' +
                    '<button type="button" class="button remove-date-override">' + avdpAdmin.i18n.remove + '</button>' +
                    '</div>';
            } else {
                // Fixed/Flexible: Include time inputs
                html = '<div class="date-override-row">' +
                    '<input type="date" name="date_overrides[allowed_dates][' + index + '][date]" required>' +
                    '<input type="time" name="date_overrides[allowed_dates][' + index + '][start]" value="09:00" class="allowed-date-time-inputs" required>' +
                    '<span class="allowed-date-time-inputs">' + avdpAdmin.i18n.to + '</span>' +
                    '<input type="time" name="date_overrides[allowed_dates][' + index + '][end]" value="17:00" class="allowed-date-time-inputs" required>' +
                    '<button type="button" class="button remove-date-override">' + avdpAdmin.i18n.remove + '</button>' +
                    '</div>';
            }

            $('#' + targetId).append(html);
        });

        // Remove Date Override
        $(document).on('click', '.remove-date-override', function () {
            var $form = $(this).closest('form');
            $(this).closest('.date-override-row').remove();

            // Trigger form change detection to enable save button
            if ($form.length) {
                $form.trigger('avdp:check-changes');
            }
        });

        // Show inline range picker
        $(document).on('click', '.add-date-range', function () {
            var targetId = $(this).data('target');
            var type     = $(this).data('type');
            var $list    = $('#' + targetId);

            // Only one range picker open at a time per list
            if ($list.find('.date-range-picker-row').length) {
                $list.find('.range-start').focus();
                return;
            }

            var html =
                '<div class="date-override-row date-range-picker-row">' +
                    '<input type="date" class="range-start">' +
                    '<span>' + (avdpAdmin.i18n.to || 'to') + '</span>' +
                    '<input type="date" class="range-end">' +
                    '<button type="button" class="button button-primary confirm-date-range"' +
                        ' data-type="' + type + '" data-target="' + targetId + '">' +
                        (avdpAdmin.i18n.add || 'Add') +
                    '</button>' +
                    '<button type="button" class="button cancel-date-range">' +
                        (avdpAdmin.i18n.cancel || 'Cancel') +
                    '</button>' +
                '</div>';

            $list.append(html);
            $list.find('.range-start').focus();
        });

        // Cancel range picker
        $(document).on('click', '.cancel-date-range', function () {
            $(this).closest('.date-range-picker-row').remove();
        });

        // Confirm range — expand into individual date rows
        $(document).on('click', '.confirm-date-range', function () {
            var $btn       = $(this);
            var type       = $btn.data('type');
            var targetId   = $btn.data('target');
            var $pickerRow = $btn.closest('.date-range-picker-row');
            var $list      = $('#' + targetId);
            var $form      = $list.closest('form');

            var startVal = $pickerRow.find('.range-start').val();
            var endVal   = $pickerRow.find('.range-end').val();

            $pickerRow.find('.range-start, .range-end').removeClass('avdp-field-error');

            if (!startVal || !endVal) {
                if (!startVal) { $pickerRow.find('.range-start').addClass('avdp-field-error'); }
                if (!endVal)   { $pickerRow.find('.range-end').addClass('avdp-field-error'); }
                return;
            }

            var start = new Date(startVal + 'T00:00:00');
            var end   = new Date(endVal   + 'T00:00:00');

            if (end < start) { var tmp = start; start = end; end = tmp; }

            var rowsHtml  = '';
            var current   = new Date(start);
            var baseIndex = Date.now();
            var i         = 0;

            while (current <= end) {
                var dateStr = current.toISOString().split('T')[0];

                if (type === 'blocked') {
                    rowsHtml +=
                        '<div class="date-override-row">' +
                            '<input type="date" name="date_overrides[blocked_dates][]" value="' + dateStr + '" required>' +
                            '<button type="button" class="button remove-date-override">' + (avdpAdmin.i18n.remove || 'Remove') + '</button>' +
                        '</div>';
                } else {
                    var idx    = baseIndex + i;
                    var method = $('#method').val() || 'fixed';

                    if (method === 'daily') {
                        rowsHtml +=
                            '<div class="date-override-row">' +
                                '<input type="date" name="date_overrides[allowed_dates][' + idx + '][date]" value="' + dateStr + '" required>' +
                                '<button type="button" class="button remove-date-override">' + (avdpAdmin.i18n.remove || 'Remove') + '</button>' +
                            '</div>';
                    } else {
                        rowsHtml +=
                            '<div class="date-override-row">' +
                                '<input type="date" name="date_overrides[allowed_dates][' + idx + '][date]" value="' + dateStr + '" required>' +
                                '<input type="time" name="date_overrides[allowed_dates][' + idx + '][start]" value="09:00" class="allowed-date-time-inputs" required>' +
                                '<span class="allowed-date-time-inputs">' + (avdpAdmin.i18n.to || 'to') + '</span>' +
                                '<input type="time" name="date_overrides[allowed_dates][' + idx + '][end]" value="17:00" class="allowed-date-time-inputs" required>' +
                                '<button type="button" class="button remove-date-override">' + (avdpAdmin.i18n.remove || 'Remove') + '</button>' +
                            '</div>';
                    }
                }

                current.setDate(current.getDate() + 1);
                i++;
            }

            $pickerRow.before(rowsHtml);
            $pickerRow.remove();

            if ($form.length) {
                $form.trigger('avdp:check-changes');
            }
        });
    }

    /**
     * Initialize Booking Window
     */
    function initBookingWindow() {
        // Handle switching between Dynamic and Predefined
        $(document).on('change', '.range-type-select', function () {
            var $container = $(this).closest('.avdp-range-input-group');
            var type = $(this).val();

            if (type === 'dynamic') {
                $container.find('.range-dynamic').show().removeAttr('disabled');
                $container.find('.range-date').hide().attr('disabled', 'disabled');
            } else {
                $container.find('.range-dynamic').hide().attr('disabled', 'disabled');
                $container.find('.range-date').css('display', 'inline-block').removeAttr('disabled'); // Ensure display block/inline-block
            }
        });

        // Trigger change on load to set initial state
        $('.range-type-select').trigger('change');
    }

    /**
     * Initialize Form Change Tracking
     * Monitors all form inputs and enables/disables save button based on changes
     * Also warns users before navigating away with unsaved changes
     */
    function initFormChangeTracking() {
        var $forms = $('.avdp-wrap form');
        var hasUnsavedChanges = false;

        if ($forms.length === 0) {
            return;
        }

        // Add beforeunload handler to warn about unsaved changes
        $(window).on('beforeunload', function (e) {
            if (hasUnsavedChanges) {
                var message = avdpAdmin.i18n.unsavedChanges || 'You have unsaved changes. Are you sure you want to leave?';
                e.returnValue = message;
                return message;
            }
        });

        $forms.each(function () {
            var $form = $(this);
            var $saveButton = $form.find('button[type="submit"], input[type="submit"]');

            if ($saveButton.length === 0) {
                return;
            }

            // Store initial form state
            var initialState = captureFormState($form);

            // Disable save button initially
            disableSaveButton($saveButton);

            // Track changes on all form inputs
            $form.on('change input', 'input, select, textarea', function () {
                var formHasChanges = checkFormChanges($form, $saveButton, initialState);
                hasUnsavedChanges = formHasChanges;
            });

            // Special handling for checkboxes and radios
            $form.on('change', 'input[type="checkbox"], input[type="radio"]', function () {
                var formHasChanges = checkFormChanges($form, $saveButton, initialState);
                hasUnsavedChanges = formHasChanges;
            });

            // Handle dynamically added elements (date overrides, etc.)
            $form.on('DOMNodeInserted', function () {
                // Small delay to ensure DOM is updated
                setTimeout(function () {
                    var formHasChanges = checkFormChanges($form, $saveButton, initialState);
                    hasUnsavedChanges = formHasChanges;
                }, 100);
            });

            // Handle removed elements and manual checks
            $form.on('DOMNodeRemoved avdp:check-changes', function () {
                setTimeout(function () {
                    var formHasChanges = checkFormChanges($form, $saveButton, initialState);
                    hasUnsavedChanges = formHasChanges;
                }, 100);
            });

            // Reset tracking after form submission
            $form.on('submit', function () {
                hasUnsavedChanges = false;
                enableSaveButton($saveButton);
            });
        });
    }

    /**
     * Capture current form state
     */
    function captureFormState($form) {
        var state = {};

        // Serialize all form data
        var formData = $form.serializeArray();

        // Store in object for easy comparison
        $.each(formData, function (i, field) {
            if (state[field.name]) {
                // Handle multiple values (like checkboxes)
                if (!Array.isArray(state[field.name])) {
                    state[field.name] = [state[field.name]];
                }
                state[field.name].push(field.value);
            } else {
                state[field.name] = field.value;
            }
        });

        // Also track unchecked checkboxes
        $form.find('input[type="checkbox"]').each(function () {
            var name = $(this).attr('name');
            if (name && !$(this).is(':checked')) {
                if (!state[name]) {
                    state[name] = '__unchecked__';
                }
            }
        });

        return state;
    }

    /**
     * Check if form has changes
     */
    function checkFormChanges($form, $saveButton, initialState) {
        var currentState = captureFormState($form);
        var hasChanges = !compareStates(initialState, currentState);

        if (hasChanges) {
            enableSaveButton($saveButton);
        } else {
            disableSaveButton($saveButton);
        }

        return hasChanges;
    }

    /**
     * Compare two form states
     */
    function compareStates(state1, state2) {
        // Get all keys from both states
        var keys1 = Object.keys(state1);
        var keys2 = Object.keys(state2);

        // Check if number of keys is different
        if (keys1.length !== keys2.length) {
            return false;
        }

        // Compare each key
        for (var i = 0; i < keys1.length; i++) {
            var key = keys1[i];

            // Check if key exists in both states
            if (!(key in state2)) {
                return false;
            }

            // Compare values
            var val1 = state1[key];
            var val2 = state2[key];

            // Handle arrays
            if (Array.isArray(val1) && Array.isArray(val2)) {
                if (val1.length !== val2.length) {
                    return false;
                }
                for (var j = 0; j < val1.length; j++) {
                    if (val1[j] !== val2[j]) {
                        return false;
                    }
                }
            } else if (val1 !== val2) {
                return false;
            }
        }

        return true;
    }

    /**
     * Disable save button
     */
    function disableSaveButton($button) {
        $button.prop('disabled', true);
        $button.css({
            'opacity': '0.5',
            'cursor': 'not-allowed'
        });
        $button.attr('title', 'No changes to save');
    }

    /**
     * Enable save button
     */
    function enableSaveButton($button) {
        $button.prop('disabled', false);
        $button.css({
            'opacity': '1',
            'cursor': 'pointer'
        });
        $button.attr('title', 'Save changes');
    }

    /**
     * Initialize Copy Buttons
     * For CSS selector page copy functionality with subtle feedback
     */
    function initCopyButtons() {
        $(document).on('click', '.avdp-copy-btn', function () {
            var $btn = $(this);
            var targetId = $btn.data('target');
            var input = $('#' + targetId);
            var val = input.val();

            if (val && val.startsWith('.') && $btn.hasClass('avdp-copy-selector')) {
                val = val.substring(1);
            }

            navigator.clipboard.writeText(val).then(function () {
                var originalText = $btn.text();
                $btn.text(avdpAdmin.i18n.copied || 'Copied!');
                setTimeout(function () {
                    $btn.text(originalText);
                }, 1500);
            });
        });
    }

    /**
     * Initialize Tab Switching
     * For CSS selector page tabs
     */
    function initTabSwitching() {
        $(document).on('click', '.avdp-tabs-wrapper .avdp-tab', function () {
            var tabName = $(this).data('tab');
            var $wrapper = $(this).closest('.avdp-tabs-wrapper');

            $wrapper.find('.avdp-tab').removeClass('active');
            $(this).addClass('active');

            $wrapper.find('.avdp-tab-content').removeClass('active');
            $wrapper.find('.avdp-tab-content[data-tab="' + tabName + '"]').addClass('active');
        });
    }

    /**
     * Initialize Availability Method UI
     * Shows/hides relevant fields based on selected booking type
     */
    function initAvailabilityMethodUI() {
        var $methodSelect = $('#method');

        if ($methodSelect.length === 0) {
            return;
        }

        function updateUI() {
            var method = $methodSelect.val();

            // Defaults
            var showSlots = true;
            var showBuffers = true;
            var showTimeInputs = true;
            var showDaysLimits = false;
            var showDurationLimits = false;
            var showNotice = true; // Minute-based minimum notice

            if (method === 'daily') {
                showSlots = false;
                showBuffers = false;
                showTimeInputs = false;
                showDaysLimits = true;
                showDurationLimits = false;
                showNotice = false; // Day Based doesn't need minute-based notice
            } else if (method === 'flexible') {
                showSlots = true;
                showBuffers = true;
                showTimeInputs = true;
                showDaysLimits = false;
                showDurationLimits = true;
                showNotice = true;
            } else { // fixed
                showSlots = true;
                showBuffers = true;
                showTimeInputs = true;
                showDaysLimits = false;
                showDurationLimits = false;
                showNotice = true;
            }

            // Toggle Visibility
            $('#row-slot-interval').toggle(showSlots);
            $('#row-buffer-before, #row-buffer-after').toggle(showBuffers);
            $('.avdp-slot-row').toggle(showTimeInputs);
            $('#row-minimum-notice').toggle(showNotice);

            $('#row-min-days, #row-max-days').toggle(showDaysLimits);
            $('#row-min-duration, #row-max-duration').toggle(showDurationLimits);

            // Toggle time inputs in Available Dates section
            $('.allowed-date-time-inputs').toggle(showTimeInputs);

            // Update data attribute for dynamic "Add new" functionality
            $('#allowed-dates-list').attr('data-show-times', showTimeInputs ? '1' : '0');
        }

        $methodSelect.on('change', updateUI);
        updateUI(); // Run on load
    }

    /**
     * Initialize Settings Theme Switcher
     * Updates available themes based on selected datepicker library
     */
    function initSettingsThemeSwitcher() {
        var $libSelect = $('#datepicker_library');
        var $themeSelect = $('#datepicker_theme');

        if ($libSelect.length === 0 || $themeSelect.length === 0) {
            return;
        }

        // Theme Definitions
        var themes = {
            'xdsoft': {
                'light': avdpAdmin.i18n.themeLight || 'Light (Default)',
                'dark': avdpAdmin.i18n.themeDark || 'Dark'
            }
        };

        var currentTheme = $themeSelect.val();

        // Function to update themes
        function updateThemes() {
            var lib = $libSelect.val();
            var availableThemes = themes[lib] || themes['xdsoft']; // Fallback

            $themeSelect.empty();

            $.each(availableThemes, function (key, label) {
                var $option = $('<option></option>').attr('value', key).text(label);
                if (key === currentTheme) {
                    $option.prop('selected', true);
                }
                $themeSelect.append($option);
            });

            // If current theme is not in the new list, select the first one (default)
            if (!$themeSelect.find('option[value="' + currentTheme + '"]').length) {
                $themeSelect.find('option:first').prop('selected', true);
            }

            // Update currentTheme
            currentTheme = $themeSelect.val();
        }

        // Bind change event
        $libSelect.on('change', function () {
            updateThemes();
        });

        // Track theme change
        $themeSelect.on('change', function () {
            currentTheme = $(this).val();
        });

        // Initial populate
        updateThemes();
    }

    /**
     * Initialize Restore Defaults
     * Fills form with default values (client-side only). User must click Save to persist.
     */
    function initRestoreDefaults() {
        $(document).on('click', '.avdp-restore-defaults', function () {
            var $btn = $(this);
            var $form = $btn.closest('form');
            var page = $form.data('page') || (typeof avdpAdmin !== 'undefined' ? avdpAdmin.restoreDefaultsPage : '');
            var defaults = typeof avdpAdmin !== 'undefined' ? avdpAdmin.restoreDefaults : {};

            if (!defaults || Object.keys(defaults).length === 0) {
                return;
            }

            if (!window.confirm(avdpAdmin.i18n.restoreDefaultsConfirm || "Reset to default\n\nThis will reset all settings on this page to their factory defaults. Your current configuration will be replaced — but only after you click Save Changes.\n\nClick OK to load the default values, or Cancel to keep your current settings.")) {
                return;
            }

            if (page === 'availability') {
                applyAvailabilityDefaults($form, defaults);
            } else if (page === 'integration') {
                applyIntegrationDefaults($form, defaults);
            } else if (page === 'settings') {
                applySettingsDefaults($form, defaults);
            }

            $form.trigger('avdp:check-changes');
        });
    }

    function applyAvailabilityDefaults($form, d) {
        $form.find('#method').val(d.method || 'fixed');

        var bw = d.booking_window || {};
        $form.find('select[name="booking_window[from_type]"]').val(bw.from_type || 'dynamic');
        $form.find('select[name="booking_window[to_type]"]').val(bw.to_type || 'dynamic');
        $form.find('input[name="booking_window[from_value_dynamic]"]').val(bw.from_value || '0');
        $form.find('input[name="booking_window[to_value_dynamic]"]').val(bw.to_value || '30');
        $form.find('input[name="booking_window[from_value_date]"]').val('');
        $form.find('input[name="booking_window[to_value_date]"]').val('');
        $form.find('.range-type-select').trigger('change');

        var ts = d.time_settings || {};
        $form.find('#slot_interval').val(ts.slot_interval || 30);
        $form.find('#minimum_notice').val(ts.minimum_notice !== undefined ? ts.minimum_notice : 0);
        $form.find('#buffer_before').val(ts.buffer_before || 0);
        $form.find('#buffer_after').val(ts.buffer_after || 0);
        $form.find('#min_days').val(ts.min_days || 1);
        $form.find('#max_days').val(ts.max_days || 14);
        $form.find('#min_duration').val(ts.min_duration || 1);
        $form.find('#max_duration').val(ts.max_duration || 24);

        var wh = d.weekly_hours || {};
        var days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        days.forEach(function (day) {
            var dayData = wh[day] || {};
            var $row = $form.find('input[name="weekly_hours[' + day + '][enabled]"]').closest('tr');
            var enabled = dayData.enabled !== false;
            $row.find('.avdp-day-toggle').prop('checked', enabled);
            var slots = dayData.slots || [];
            if (slots.length === 0) {
                slots = [{ start: '09:00', end: '17:00' }];
            }
            var $container = $row.find('.avdp-slots-container');
            $container.find('.avdp-slot-row').remove();
            slots.forEach(function (slot) {
                var html = '<div class="avdp-slot-row">' +
                    '<input type="time" name="weekly_hours[' + day + '][slots][0][start]" value="' + (slot.start || '09:00') + '" ' + (enabled ? '' : 'disabled') + '>' +
                    '<span>' + (avdpAdmin.i18n.to || 'to') + '</span>' +
                    '<input type="time" name="weekly_hours[' + day + '][slots][0][end]" value="' + (slot.end || '17:00') + '" ' + (enabled ? '' : 'disabled') + '>' +
                    '</div>';
                $container.append(html);
            });
            $row.find('.avdp-day-toggle').trigger('change');
        });

        $('#blocked-dates-list .date-override-row').remove();
        $('#allowed-dates-list .date-override-row').remove();

        $('#method').trigger('change');
    }

    function applyIntegrationDefaults($form, d) {
        var map = {
            'selector_single': 'single_field',
            'selector_start_date': 'start_date',
            'selector_start_time': 'start_time',
            'selector_end_date': 'end_date',
            'selector_end_time': 'end_time',
            'selector_start_datetime': 'start_datetime',
            'selector_end_datetime': 'end_datetime'
        };
        $.each(map, function (inputId, key) {
            var $input = $form.find('#' + inputId);
            if ($input.length && d[key] !== undefined) {
                $input.val(d[key]);
            }
        });
    }

    function applySettingsDefaults($form, d) {
        $form.find('#date_format').val(d.date_format || 'Y-m-d');
        $form.find('#time_format').val(d.time_format || 'H:i');
        $form.find('#timezone').val(d.timezone || 'UTC');
        $form.find('#datepicker_library').val(d.datepicker_library || 'xdsoft').trigger('change');
        var defaultTheme = d.datepicker_theme || 'light';
        $form.find('#datepicker_theme').val(defaultTheme).trigger('change');
        $form.find('#datepicker_language').val(d.datepicker_language || 'en');
        $form.find('#datepicker_display_method').val(d.datepicker_display_method || 'dropdown');
    }

    /**
     * Initialize Preset Selector
     * Renders a collapsible inline panel with preset cards.
     * Clicking a card confirms and applies the preset directly.
     */
    function initPresetSelector() {
        var $panel = $('#avdp-preset-panel');
        if ($panel.length === 0) { return; }

        var presets = (typeof avdpAdmin !== 'undefined' && avdpAdmin.presets) ? avdpAdmin.presets : [];
        var i18n    = (typeof avdpAdmin !== 'undefined' && avdpAdmin.i18n)    ? avdpAdmin.i18n    : {};

        // --- Build preset cards ---
        var $grid = $('#avdp-preset-grid');
        $grid.empty();

        $.each(presets, function (_, preset) {
            var methodLabel     = { fixed: 'Fixed Time Slots', daily: 'Day Based', flexible: 'Flexible Range' };
            var methodBadgeClass = { fixed: 'avdp-badge-fixed', daily: 'avdp-badge-daily', flexible: 'avdp-badge-flexible' };
            var method = (preset.data && preset.data.method) ? preset.data.method : 'fixed';

            var $card = $(
                '<div class="avdp-preset-card" data-preset-id="' + escHtml(preset.id) + '" tabindex="0" role="button" aria-label="' + escHtml(preset.name) + '">' +
                    '<div class="avdp-preset-card-header">' +
                        '<span class="avdp-preset-name">' + escHtml(preset.name) + '</span>' +
                        '<span class="avdp-preset-badge ' + escHtml(methodBadgeClass[method] || '') + '">' + escHtml(methodLabel[method] || method) + '</span>' +
                    '</div>' +
                    '<p class="avdp-preset-desc">' + escHtml(preset.description) + '</p>' +
                    '<p class="avdp-preset-rec"><strong>' + escHtml(i18n.recommendedFields || 'Recommended fields:') + '</strong> ' + escHtml(preset.recommended_fields) + '</p>' +
                '</div>'
            );

            $grid.append($card);
        });

        // --- Toggle collapse/expand ---
        $panel.on('click keypress', '.avdp-preset-panel-toggle', function (e) {
            if (e.type === 'keypress' && e.which !== 13 && e.which !== 32) { return; }
            e.preventDefault();

            var $body    = $('#avdp-preset-body');
            var $toggle  = $(this);
            var isOpen   = $toggle.attr('aria-expanded') === 'true';

            if (isOpen) {
                $body.slideUp(200, function () { $body.attr('hidden', ''); });
                $toggle.attr('aria-expanded', 'false');
                $panel.removeClass('is-open');
            } else {
                $body.removeAttr('hidden').hide().slideDown(200);
                $toggle.attr('aria-expanded', 'true');
                $panel.addClass('is-open');
            }
        });

        // --- Card click: confirm then apply ---
        $panel.on('click keypress', '.avdp-preset-card', function (e) {
            if (e.type === 'keypress' && e.which !== 13 && e.which !== 32) { return; }

            var presetId = $(this).data('preset-id');
            var preset   = null;
            $.each(presets, function (_, p) {
                if (p.id === presetId) { preset = p; return false; }
            });
            if (!preset) { return; }

            if (!window.confirm(i18n.loadPresetConfirm || "Load preset\n\nThis will replace your current availability settings with the selected preset — but only after you click Save Changes.\n\nClick OK to load the preset, or Cancel to keep your current settings.")) {
                return;
            }

            // Mark selected visually
            $panel.find('.avdp-preset-card').removeClass('is-selected');
            $(this).addClass('is-selected');

            var $form = $('form.avdp-settings-form[data-page="availability"]');
            applyPreset($form, preset.data);
            $form.trigger('avdp:check-changes');
        });
    }

    /**
     * Apply a preset data object to the availability form.
     * Mirrors applyAvailabilityDefaults() but accepts any preset.
     */
    function applyPreset($form, d) {
        $form.find('#method').val(d.method || 'fixed');

        var bw = d.booking_window || {};
        $form.find('select[name="booking_window[from_type]"]').val(bw.from_type || 'dynamic');
        $form.find('select[name="booking_window[to_type]"]').val(bw.to_type   || 'dynamic');
        $form.find('input[name="booking_window[from_value_dynamic]"]').val(bw.from_value !== undefined ? bw.from_value : '0');
        $form.find('input[name="booking_window[to_value_dynamic]"]').val(bw.to_value   !== undefined ? bw.to_value   : '30');
        $form.find('input[name="booking_window[from_value_date]"]').val('');
        $form.find('input[name="booking_window[to_value_date]"]').val('');
        $form.find('.range-type-select').trigger('change');

        var ts = d.time_settings || {};
        $form.find('#slot_interval').val(ts.slot_interval    !== undefined ? ts.slot_interval    : 30);
        $form.find('#minimum_notice').val(ts.minimum_notice  !== undefined ? ts.minimum_notice   : 0);
        $form.find('#buffer_before').val(ts.buffer_before    !== undefined ? ts.buffer_before    : 0);
        $form.find('#buffer_after').val(ts.buffer_after      !== undefined ? ts.buffer_after     : 0);
        $form.find('#min_days').val(ts.min_days              !== undefined ? ts.min_days         : 1);
        $form.find('#max_days').val(ts.max_days              !== undefined ? ts.max_days         : 14);
        $form.find('#min_duration').val(ts.min_duration      !== undefined ? ts.min_duration     : 1);
        $form.find('#max_duration').val(ts.max_duration      !== undefined ? ts.max_duration     : 24);

        var wh   = d.weekly_hours || {};
        var days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        days.forEach(function (day) {
            var dayData = wh[day];
            var $row    = $form.find('input[name="weekly_hours[' + day + '][enabled]"]').closest('tr');
            var enabled = (dayData && dayData.enabled === true);
            $row.find('.avdp-day-toggle').prop('checked', enabled);

            var rawSlots = (dayData && dayData.slots && dayData.slots.length) ? dayData.slots : [];
            var slots = rawSlots.length ? rawSlots : [{ start: '09:00', end: '17:00' }];
            var $container = $row.find('.avdp-slots-container');
            $container.find('.avdp-slot-row').remove();
            slots.forEach(function (slot) {
                var html = '<div class="avdp-slot-row">' +
                    '<input type="time" name="weekly_hours[' + day + '][slots][0][start]" value="' + (slot.start || '09:00') + '" ' + (enabled ? '' : 'disabled') + '>' +
                    '<span>' + ((typeof avdpAdmin !== 'undefined' && avdpAdmin.i18n && avdpAdmin.i18n.to) || 'to') + '</span>' +
                    '<input type="time" name="weekly_hours[' + day + '][slots][0][end]" value="' + (slot.end || '17:00') + '" ' + (enabled ? '' : 'disabled') + '>' +
                    '</div>';
                $container.append(html);
            });
            $row.find('.avdp-day-toggle').trigger('change');
        });

        $('#blocked-dates-list .date-override-row').remove();
        $('#allowed-dates-list .date-override-row').remove();

        $('#method').trigger('change');
    }

    /**
     * Initialize Availability Calendar Preview Widget
     * Real calendar view with week navigation, timezone-aware availability accuracy.
     */
    function initAvailabilityPreview() {
        var $card = $('#avdp-availability-preview');
        if ($card.length === 0) { return; }

        // Site timezone passed from PHP via wp_localize_script
        var _tz = (typeof avdpAdmin !== 'undefined' && avdpAdmin.timezone) ? avdpAdmin.timezone : '';

        var DAY_ABBR    = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        var MONTH_ABBR  = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                           'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        var WEEKDAY_KEYS = ['sunday', 'monday', 'tuesday', 'wednesday',
                            'thursday', 'friday', 'saturday'];

        // ---- Date helpers ----

        // ---- Date / timezone helpers ----

        function pad2(n) { return n < 10 ? '0' + n : '' + n; }

        function toDateKey(d) {
            return d.getFullYear() + '-' + pad2(d.getMonth() + 1) + '-' + pad2(d.getDate());
        }

        // Today's date (midnight) in the site timezone
        function getTodayInTz() {
            var now = new Date();
            if (!_tz) { return new Date(now.getFullYear(), now.getMonth(), now.getDate()); }
            try {
                var pts = new Intl.DateTimeFormat('en-US', {
                    timeZone: _tz, year: 'numeric', month: 'numeric', day: 'numeric'
                }).formatToParts(now);
                var y = 0, mo = 0, d = 0;
                for (var i = 0; i < pts.length; i++) {
                    if (pts[i].type === 'year')  { y  = parseInt(pts[i].value, 10); }
                    if (pts[i].type === 'month') { mo = parseInt(pts[i].value, 10); }
                    if (pts[i].type === 'day')   { d  = parseInt(pts[i].value, 10); }
                }
                return new Date(y, mo - 1, d);
            } catch (e) {
                return new Date(now.getFullYear(), now.getMonth(), now.getDate());
            }
        }

        function getMonday(date) {
            var d   = new Date(date.getFullYear(), date.getMonth(), date.getDate());
            var day = d.getDay();
            d.setDate(d.getDate() - (day === 0 ? 6 : day - 1));
            return d;
        }

        function getWeekDates(monday) {
            var dates = [];
            for (var i = 0; i < 7; i++) {
                dates.push(new Date(monday.getFullYear(), monday.getMonth(), monday.getDate() + i));
            }
            return dates;
        }

        function formatWeekLabel(monday) {
            var sunday = new Date(monday.getFullYear(), monday.getMonth(), monday.getDate() + 6);
            var start  = MONTH_ABBR[monday.getMonth()] + ' ' + monday.getDate();
            var end    = MONTH_ABBR[sunday.getMonth()] + ' ' + sunday.getDate() + ', ' + sunday.getFullYear();
            return start + ' \u2013 ' + end;
        }

        // Convert "H:i" slot string from PHP → "09:00 AM" display format
        function slotToDisplay(slot) {
            var p  = slot.split(':');
            var h  = parseInt(p[0], 10);
            var m  = parseInt(p[1], 10);
            var period = h >= 12 ? 'PM' : 'AM';
            var dh = h % 12 || 12;
            return pad2(dh) + ':' + pad2(m) + '\u202f' + period;
        }

        // ---- Render functions ----

        // ALL unavailable days — same compact style across every mode
        function renderUnavailableDay(date, dayIdx, isToday) {
            var cls = 'avdp-cal-day avdp-cal-day--slots avdp-cal-day--unavailable';
            if (isToday) { cls += ' avdp-cal-day--today'; }
            var html  = '<div class="' + cls + '">';
            html += '<div class="avdp-cal-slot-header">';
            html += '<span class="avdp-cal-slot-hdr-day">'  + DAY_ABBR[dayIdx] + '</span>';
            html += '<span class="avdp-cal-slot-hdr-date">' + date.getDate() + ' ' + MONTH_ABBR[date.getMonth()] + '</span>';
            html += '</div>';
            html += '<div class="avdp-cal-unavail-body"><span>Unavailable</span></div>';
            html += '</div>';
            return html;
        }

        // Day Based available — same compact structure as unavailable, green badge
        function renderDailyAvailableDay(date, dayIdx, isToday) {
            var cls = 'avdp-cal-day avdp-cal-day--slots avdp-cal-day--available';
            if (isToday) { cls += ' avdp-cal-day--today'; }
            var html  = '<div class="' + cls + '">';
            html += '<div class="avdp-cal-slot-header">';
            html += '<span class="avdp-cal-slot-hdr-day">'  + DAY_ABBR[dayIdx] + '</span>';
            html += '<span class="avdp-cal-slot-hdr-date">' + date.getDate() + ' ' + MONTH_ABBR[date.getMonth()] + '</span>';
            html += '</div>';
            html += '<div class="avdp-cal-unavail-body"><span class="avdp-cal-avail-label">Available</span></div>';
            html += '</div>';
            return html;
        }

        // Fixed / Flexible available — compact header + scrollable slot chips from server
        function renderSlotAvailableDay(date, dayIdx, isToday, slots) {
            var cls = 'avdp-cal-day avdp-cal-day--slots avdp-cal-day--available';
            if (isToday) { cls += ' avdp-cal-day--today'; }
            var html  = '<div class="' + cls + '">';
            html += '<div class="avdp-cal-slot-header">';
            html += '<span class="avdp-cal-slot-hdr-day">'  + DAY_ABBR[dayIdx] + '</span>';
            html += '<span class="avdp-cal-slot-hdr-date">' + date.getDate() + ' ' + MONTH_ABBR[date.getMonth()] + '</span>';
            html += '</div>';
            html += '<div class="avdp-cal-slot-wrap">';
            html += '<div class="avdp-cal-slot-chips">';
            for (var j = 0; j < slots.length; j++) {
                html += '<span class="avdp-cal-slot-chip">' + slotToDisplay(slots[j]) + '</span>';
            }
            html += '</div>';
            html += '<div class="avdp-cal-scroll-hint avdp-cal-scroll-hint--hidden">Scroll down</div>';
            html += '</div>';
            html += '</div>';
            return html;
        }

        // ---- Main render (server-driven) ----

        function buildPreview() {
            var todayTz  = getTodayInTz();
            var todayKey = toDateKey(todayTz);
            var dates    = getWeekDates(currentWeekStart);
            var dateKeys = [];
            for (var k = 0; k < dates.length; k++) { dateKeys.push(toDateKey(dates[k])); }

            $card.find('#avdp-cal-week-label-text').text(formatWeekLabel(currentWeekStart));
            $card.find('#avdp-week-preview').html('<div class="avdp-cal-loading"></div>');
            $card.find('.avdp-cal-prev, .avdp-cal-next').prop('disabled', false);

            $.post(
                avdpAdmin.ajaxUrl,
                { action: 'avdp_get_week_preview', nonce: avdpAdmin.nonce, dates: dateKeys },
                function (response) {
                    if (!response || !response.success) { return; }
                    var bookingType = response.data.booking_type;
                    var dayData     = response.data.data;
                    var isSlotMode  = (bookingType === 'fixed' || bookingType === 'flexible');
                    var grid        = '';

                    for (var i = 0; i < 7; i++) {
                        var date    = dates[i];
                        var key     = dateKeys[i];
                        var entry   = dayData[key] || { available: false, slots: [] };
                        var isToday = (key === todayKey);

                        if (!entry.available) {
                            grid += renderUnavailableDay(date, i, isToday);
                        } else if (isSlotMode) {
                            grid += renderSlotAvailableDay(date, i, isToday, entry.slots || []);
                        } else {
                            grid += renderDailyAvailableDay(date, i, isToday);
                        }
                    }

                    $card.find('#avdp-week-preview').html(grid);
                    initScrollHints();
                }
            );
        }

        function initScrollHints() {
            $card.find('.avdp-cal-slot-chips').each(function () {
                var chipsEl = this;
                var $hint   = $(chipsEl).closest('.avdp-cal-slot-wrap').find('.avdp-cal-scroll-hint');

                function updateHint() {
                    var hasOverflow = chipsEl.scrollHeight > chipsEl.clientHeight + 2;
                    var atBottom    = chipsEl.scrollHeight - chipsEl.scrollTop <= chipsEl.clientHeight + 2;
                    if (hasOverflow && !atBottom) {
                        $hint.removeClass('avdp-cal-scroll-hint--hidden');
                    } else {
                        $hint.addClass('avdp-cal-scroll-hint--hidden');
                    }
                }

                updateHint();
                $(chipsEl).off('scroll.hint').on('scroll.hint', updateHint);
            });
        }

        // ---- State & navigation ----

        var currentWeekStart = getMonday(getTodayInTz());

        $card.html(
            '<div class="avdp-cal-nav-row">' +
                '<button type="button" class="avdp-cal-nav-btn avdp-cal-prev" title="Previous week">&#8592;</button>' +
                '<div class="avdp-cal-week-label-wrap">' +
                    '<span class="avdp-cal-week-label" id="avdp-cal-week-label-text"></span>' +
                    '<input type="date" id="avdp-cal-date-jump" class="avdp-cal-date-jump-hidden" title="Jump to date">' +
                '</div>' +
                '<button type="button" class="avdp-cal-nav-btn avdp-cal-next" title="Next week">&#8594;</button>' +
            '</div>' +
            '<div class="avdp-week-preview" id="avdp-week-preview"></div>' +
            '<p class="avdp-cal-save-note">Save changes to reflect any availability updates in this preview.</p>'
        );

        $card.on('click', '.avdp-cal-prev', function () {
            currentWeekStart = new Date(
                currentWeekStart.getFullYear(),
                currentWeekStart.getMonth(),
                currentWeekStart.getDate() - 7
            );
            buildPreview();
        });

        $card.on('click', '.avdp-cal-next', function () {
            currentWeekStart = new Date(
                currentWeekStart.getFullYear(),
                currentWeekStart.getMonth(),
                currentWeekStart.getDate() + 7
            );
            buildPreview();
        });

        $card.on('click', '.avdp-cal-week-label-wrap', function () {
            var input = $card.find('#avdp-cal-date-jump')[0];
            if (!input) { return; }
            try {
                input.showPicker();
            } catch (e) {
                input.click();
            }
        });

        $card.on('change', '#avdp-cal-date-jump', function () {
            var val = $(this).val();
            if (val) {
                var parts = val.split('-');
                var picked = new Date(
                    parseInt(parts[0], 10),
                    parseInt(parts[1], 10) - 1,
                    parseInt(parts[2], 10)
                );
                currentWeekStart = getMonday(picked);
                buildPreview();
            }
        });

        buildPreview();
    }

    /**
     * Detect browser name/version and OS from the user agent string.
     *
     * @return {{ browser: string, os: string }}
     */
    function detectBrowserInfo() {
        var ua      = navigator.userAgent;
        var browser = 'Unknown';
        var os      = 'Unknown';

        // Browser detection (order matters — Edge/Opera contain "Chrome")
        if ( /Edg\//.test(ua) ) {
            var m = ua.match(/Edg\/([\d.]+)/);
            browser = 'Microsoft Edge ' + ( m ? m[1] : '' );
        } else if ( /OPR\//.test(ua) || /Opera/.test(ua) ) {
            var m = ua.match(/OPR\/([\d.]+)/);
            browser = 'Opera ' + ( m ? m[1] : '' );
        } else if ( /Chrome\//.test(ua) ) {
            var m = ua.match(/Chrome\/([\d.]+)/);
            browser = 'Google Chrome ' + ( m ? m[1] : '' );
        } else if ( /Firefox\//.test(ua) ) {
            var m = ua.match(/Firefox\/([\d.]+)/);
            browser = 'Mozilla Firefox ' + ( m ? m[1] : '' );
        } else if ( /Safari\//.test(ua) ) {
            var m = ua.match(/Version\/([\d.]+)/);
            browser = 'Apple Safari ' + ( m ? m[1] : '' );
        }

        // OS detection
        if ( /Windows NT/.test(ua) ) {
            var m      = ua.match(/Windows NT ([\d.]+)/);
            var winMap = { '10.0': '10 / 11', '6.3': '8.1', '6.2': '8', '6.1': '7' };
            os = 'Windows ' + ( m ? ( winMap[m[1]] || m[1] ) : '' );
        } else if ( /Mac OS X/.test(ua) ) {
            var m = ua.match(/Mac OS X ([\d_]+)/);
            os = 'macOS ' + ( m ? m[1].replace(/_/g, '.') : '' );
        } else if ( /Android/.test(ua) ) {
            var m = ua.match(/Android ([\d.]+)/);
            os = 'Android ' + ( m ? m[1] : '' );
        } else if ( /iPhone|iPad|iPod/.test(ua) ) {
            var m = ua.match(/OS ([\d_]+)/);
            os = 'iOS ' + ( m ? m[1].replace(/_/g, '.') : '' );
        } else if ( /Linux/.test(ua) ) {
            os = 'Linux';
        }

        return { browser: browser.trim(), os: os.trim() };
    }

    /**
     * Initialize Support Page
     * Injects browser / OS info and renders JS diagnostics from localStorage.
     */
    function initSupportPage() {
        var $textarea = $('#avdp-system-info-raw');
        if ( $textarea.length === 0 ) { return; }

        // --- Browser / OS injection ---
        var info    = detectBrowserInfo();
        var current = $textarea.val();

        current = current.replace('| Browser | [Detecting...] |', '| Browser | ' + info.browser + ' |');
        current = current.replace('| Operating System | [Detecting...] |', '| Operating System | ' + info.os + ' |');
        $textarea.val(current);

        $('#avdp-browser-info-cell').text(info.browser);
        $('#avdp-os-info-cell').text(info.os);

        // --- JS Diagnostics from localStorage ---
        renderJsDiagnostics();
    }

    /**
     * Read avdp_diagnostics from localStorage and populate the #avdp-js-diagnostics container.
     */
    function renderJsDiagnostics() {
        var $container = $('#avdp-js-diagnostics');
        if ( $container.length === 0 ) { return; }

        var sessions = [];
        try {
            var raw = localStorage.getItem('avdp_diagnostics');
            if ( raw ) { sessions = JSON.parse(raw); }
        } catch(e) {}

        if ( !Array.isArray(sessions) || sessions.length === 0 ) {
            $container.html(
                '<p class="avdp-diag-pending">' +
                'No frontend sessions captured yet. Visit a page containing the datepicker, then return here.' +
                '</p>'
            );
            return;
        }

        var session = sessions[0];
        var html    = '';

        // Session URL + age
        var ageStr = formatSessionAge(session.ts);
        html += '<ul class="avdp-diag-list">';
        html += '<li class="avdp-diag-row avdp-diag-ok">' +
                    '<strong>Page:</strong> <a href="' + escHtml(session.url) + '" target="_blank">' + escHtml(session.url) + '</a>' +
                    ' &mdash; ' + escHtml(ageStr) +
                '</li>';

        // Selector match counts — only show selectors that found at least one element.
        // If nothing matched at all, show a single warning instead.
        if ( session.matched && typeof session.matched === 'object' ) {
            var selectorKeys  = Object.keys(session.matched);
            var anyMatched    = false;
            var matchedRows   = '';

            for ( var i = 0; i < selectorKeys.length; i++ ) {
                var key   = selectorKeys[i];
                var count = session.matched[key];
                if ( count > 0 ) {
                    anyMatched = true;
                    matchedRows += '<li class="avdp-diag-row avdp-diag-ok">' +
                                       '<strong>' + escHtml(key.replace(/_/g, ' ')) + ':</strong> ' +
                                       escHtml(String(count)) + ' element' + ( count === 1 ? '' : 's' ) + ' matched' +
                                   '</li>';
                }
            }

            if ( anyMatched ) {
                html += matchedRows;
            } else if ( selectorKeys.length > 0 ) {
                html += '<li class="avdp-diag-row avdp-diag-warning">' +
                            '<strong>CSS Selectors:</strong> No matching elements found — the datepicker may not be attached to any field on this page.' +
                        '</li>';
            }
        }

        // JS errors
        if ( session.errors && session.errors.length > 0 ) {
            for ( var j = 0; j < session.errors.length; j++ ) {
                var err = session.errors[j];
                html += '<li class="avdp-diag-row avdp-diag-error">' +
                            '<strong>JS Error:</strong> ' + escHtml(err.msg) +
                            ( err.file ? ' &mdash; ' + escHtml(err.file) + ':' + escHtml(String(err.line)) : '' ) +
                        '</li>';
            }
        } else {
            html += '<li class="avdp-diag-row avdp-diag-ok"><strong>JS Errors:</strong> None captured</li>';
        }

        html += '</ul>';

        if ( sessions.length > 1 ) {
            html += '<p class="avdp-diag-pending">Showing most recent of ' + sessions.length + ' recorded sessions.</p>';
        }

        $container.html(html);
    }

    /** Return a human-readable "X minutes ago" string. */
    function formatSessionAge(ts) {
        var diffMs  = Date.now() - ts;
        var diffMin = Math.floor(diffMs / 60000);
        if ( diffMin < 1 )  { return 'just now'; }
        if ( diffMin < 60 ) { return diffMin + ' minute' + (diffMin === 1 ? '' : 's') + ' ago'; }
        var diffHr = Math.floor(diffMin / 60);
        if ( diffHr < 24 )  { return diffHr + ' hour' + (diffHr === 1 ? '' : 's') + ' ago'; }
        var diffDay = Math.floor(diffHr / 24);
        return diffDay + ' day' + (diffDay === 1 ? '' : 's') + ' ago';
    }

    /** Minimal HTML-escape helper. */
    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    /**
     * Initialize on document ready
     */
    $(document).ready(function () {
        initAlerts();
        initWeeklyHours();
        initDateOverrides();
        initBookingWindow();
        initFormChangeTracking();
        initCopyButtons();
        initTabSwitching();
        initAvailabilityMethodUI();
        initSettingsThemeSwitcher();
        initRestoreDefaults();
        initPresetSelector();
        initAvailabilityPreview();
        initSupportPage();
    });

})(jQuery);
