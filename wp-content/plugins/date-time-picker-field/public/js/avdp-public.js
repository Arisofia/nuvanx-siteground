/**
 * Public JavaScript for Availability Datepicker (Base)
 *
 * @package Availability_Datepicker
 * @since 2.4.0.13
 */

(function ($) {
    'use strict';

    // ----- Passive diagnostics (stored in localStorage for Support page) -----

    var _avdpSession = {
        url: window.location.href,
        ts: Date.now(),
        matched: {},
        errors: []
    };

    // Capture uncaught errors that originate from plugin scripts.
    window.addEventListener('error', function (e) {
        if ( e.filename && e.filename.indexOf('avdp') !== -1 ) {
            _avdpSession.errors.push({
                msg:  e.message,
                file: e.filename.split('/').pop(),
                line: e.lineno,
                col:  e.colno,
                ts:   Date.now()
            });
        }
    });

    /**
     * Persist the current session diagnostics to localStorage (rolling, last 5).
     */
    function _avdpSaveDiagnostics() {
        try {
            var stored = JSON.parse( localStorage.getItem('avdp_diagnostics') || '[]' );
            if ( !Array.isArray(stored) ) { stored = []; }
            stored.unshift(_avdpSession);
            localStorage.setItem( 'avdp_diagnostics', JSON.stringify( stored.slice(0, 5) ) );
        } catch (e) {}
    }

    // -------------------------------------------------------------------------

    $(document).ready(function () {
        initDatepickers();
    });

    /**
     * Initialize datepickers based on CSS selectors
     */
    function initDatepickers() {
        if (!avdpPublic || !avdpPublic.selectors || !avdpPublic.config) {
            return;
        }

        var config = avdpPublic.config;
        var selectors = avdpPublic.selectors;

        var $fields = $();

        // Collect all fields matching any selector and record match counts.
        $.each(selectors, function (key, sel) {
            if (sel) {
                var $matched = $(sel);
                _avdpSession.matched[key] = $matched.length;
                $fields = $fields.add($matched);
            }
        });

        _avdpSaveDiagnostics();

        if ($fields.length === 0) {
            return;
        }

        $fields.each(function () {
            var $field = $(this);
            if ($field.hasClass('avdp-initialized')) {
                return;
            }

            initLibrary($field, config);

            $field.addClass('avdp-initialized');

            // Add inline mode class to parent for CSS styling
            if (config.inline) {
                $field.parent().addClass('avdp-inline-mode');
            }
        });

        $(document).on('click', '[data-avdp-picker] .xdsoft_date:not(.xdsoft_disabled)', function() {
            var $cell = $(this);
            var $picker = $cell.closest('.xdsoft_datetimepicker');
            if ($picker.length) {
                $picker.find('.xdsoft_date').removeClass('xdsoft_selected');
                $cell.addClass('xdsoft_selected');
            }
        });
    }

    var parseFlexibleDate = AVDPAvailabilityCore.parseFlexibleDate;
    var getFirstAvailableDate = AVDPAvailabilityCore.getFirstAvailableDate;
    var calculateSlots = AVDPAvailabilityCore.calculateSlots;
    var calculateSlotsFromCustomRange = AVDPAvailabilityCore.calculateSlotsFromCustomRange;
    var canStartSequentialBooking = AVDPAvailabilityCore.canStartSequentialBooking;
    var validateSequence = AVDPAvailabilityCore.validateSequence;
    var countAvailableDays = AVDPAvailabilityCore.countAvailableDays;
    var filterTodaySlots = AVDPAvailabilityCore.filterTodaySlots;

    function initLibrary($field, config) {
        var library = (config && config.library) || 'xdsoft';

        if (library === 'xdsoft') {
            initXdsoft($field, config);
        }
    }

    /**
     * Initialize XDSoft DateTimePicker on a field
     */
    function initXdsoft($field, config) {
        // Calculate options
        var format = config.format || 'Y-m-d H:i';
        var timepicker = config.timepicker !== false; // Default true unless specified
        var datepicker = true;

        // Determine type based on selector match
        if ($field.is(avdpPublic.selectors.start_date) || $field.is(avdpPublic.selectors.end_date)) {
            // Date Only
            format = config.formatDate || 'Y-m-d';
            timepicker = false;
        } else if ($field.is(avdpPublic.selectors.start_time) || $field.is(avdpPublic.selectors.end_time)) {
            // Time Only
            format = config.formatTime || 'H:i';
            datepicker = false;
        }

        // Calculate the first available date (not disabled weekday, not blocked date)
        var firstAvailableDate = getFirstAvailableDate(config);

        var minDateObj = false;
        var maxDateObj = false;

        if (config.minDate) {
            var minParts = config.minDate.split('-');
            if (minParts.length === 3) {
                minDateObj = new Date(parseInt(minParts[0]), parseInt(minParts[1]) - 1, parseInt(minParts[2]));
            }
        }

        if (config.maxDate) {
            var maxParts = config.maxDate.split('-');
            if (maxParts.length === 3) {
                maxDateObj = new Date(parseInt(maxParts[0]), parseInt(maxParts[1]) - 1, parseInt(maxParts[2]));
            }
        }

        // Snapshot original bounds — beforeShowDay uses these to keep non-exempted gap dates disabled
        var bookingWindowMin = minDateObj ? new Date(minDateObj.getTime()) : false;
        var bookingWindowMax = maxDateObj ? new Date(maxDateObj.getTime()) : false;

        // Extend minDate/maxDate to cover exempted dates so XDSoft doesn't block them independently
        if (config.exemptedDates && config.exemptedDates.length > 0) {
            config.exemptedDates.forEach(function (ds) {
                var ep = ds.split('-');
                if (ep.length !== 3) { return; }
                var ed = new Date(parseInt(ep[0]), parseInt(ep[1]) - 1, parseInt(ep[2]));
                ed.setHours(0, 0, 0, 0);
                if (maxDateObj && ed > maxDateObj) { maxDateObj = ed; }
                if (minDateObj && ed < minDateObj) { minDateObj = ed; }
            });
        }

        // Check if there's a pending minDate from start date selection (for end date pickers)
        var pendingMinDate = $field.data('avdp-pending-min-date');
        if (pendingMinDate && pendingMinDate instanceof Date && !isNaN(pendingMinDate.getTime())) {
            // Use the pending minDate if it's later than the config minDate
            if (!minDateObj || pendingMinDate > minDateObj) {
                minDateObj = pendingMinDate;
            }
            // Clear the pending minDate
            $field.removeData('avdp-pending-min-date');
        }

        var xdsoftOptions = {
            format: format,
            formatTime: config.formatTime || 'H:i',
            formatDate: config.formatDate || 'Y-m-d',
            timepicker: timepicker,
            datepicker: datepicker,
            minDate: minDateObj,
            maxDate: maxDateObj,
            step: config.step || 30,
            inline: config.inline || false,
            dayOfWeekStart: 1, // Monday
            lazyInit: true,

            // Set default date to first available date (not today if today is disabled)
            defaultDate: firstAvailableDate,
            defaultSelect: false, // Don't auto-select, just show this date

            validateOnBlur: false,

            // Keep picker open until time is selected (for datetime mode)
            closeOnDateSelect: timepicker ? false : true,

            // Close on time selection in datetime mode
            closeOnTimeSelect: timepicker && datepicker,

            // Initial allowTimes from weekly_hours (onShow/onSelectDate update when date changes)
            allowTimes: (config.weekly_hours && timepicker) ? calculateSlots(firstAvailableDate, config) : [],

            // Dynamic Logic
            beforeShowDay: function (date) {
                var y = date.getFullYear();
                var m = String(date.getMonth() + 1).padStart(2, '0');
                var d = String(date.getDate()).padStart(2, '0');
                var dateStr = y + '-' + m + '-' + d;

                var dayOfWeek = date.getDay();
                var checkDate = new Date(date);
                checkDate.setHours(0, 0, 0, 0);

                // Get start and end field references
                var $startField = null;
                var $endField = null;
                var isEndPicker = $field.is(avdpPublic.selectors.end_date) || $field.is(avdpPublic.selectors.end_datetime);
                var isStartPicker = $field.is(avdpPublic.selectors.start_date) || $field.is(avdpPublic.selectors.start_datetime);

                // Scope to the same form to handle multiple forms on the page
                var $form = $field.closest('form');

                if ($field.is(avdpPublic.selectors.end_date) || $field.is(avdpPublic.selectors.start_date)) {
                    $startField = $form.length ? $form.find(avdpPublic.selectors.start_date) : $(avdpPublic.selectors.start_date);
                    $endField = $form.length ? $form.find(avdpPublic.selectors.end_date) : $(avdpPublic.selectors.end_date);
                } else if ($field.is(avdpPublic.selectors.end_datetime) || $field.is(avdpPublic.selectors.start_datetime)) {
                    $startField = $form.length ? $form.find(avdpPublic.selectors.start_datetime) : $(avdpPublic.selectors.start_datetime);
                    $endField = $form.length ? $form.find(avdpPublic.selectors.end_datetime) : $(avdpPublic.selectors.end_datetime);
                }

                // RANGE LOGIC: For end date pickers, disable dates before start date
                if (isEndPicker && $startField && $startField.length && $startField.val()) {
                    var startDate = parseFlexibleDate($startField.val(), config.formatDate);
                    if (startDate && !isNaN(startDate.getTime())) {
                        startDate.setHours(0, 0, 0, 0);
                        if (checkDate < startDate) {
                            return [false, 'avdp-before-start'];
                        }
                    }
                }

                var isAvailable = true;
                var cssClass = '';

                // Exempted dates override - always available regardless of booking window
                if (config.exemptedDates && config.exemptedDates.includes(dateStr)) {
                    cssClass = 'avdp-exempted';
                }
                else if (bookingWindowMax && checkDate > bookingWindowMax) {
                    isAvailable = false;
                }
                else if (bookingWindowMin && checkDate < bookingWindowMin) {
                    isAvailable = false;
                }
                else if (config.disabledDates && config.disabledDates.includes(dateStr)) {
                    isAvailable = false;
                }
                else if (config.disabledWeekDays && config.disabledWeekDays.length > 0) {
                    var disabledDays = config.disabledWeekDays.map(function (dd) {
                        return parseInt(dd, 10);
                    });
                    if (disabledDays.indexOf(dayOfWeek) !== -1) {
                        isAvailable = false;
                    }
                }

                // For timepicker fields: disable today if all time slots have already passed
                if (isAvailable && timepicker) {
                    var nowBSD = new Date();
                    var isTodayBSD = (checkDate.getFullYear() === nowBSD.getFullYear() &&
                                     checkDate.getMonth()    === nowBSD.getMonth()    &&
                                     checkDate.getDate()     === nowBSD.getDate());
                    if (isTodayBSD) {
                        var todaySlots = calculateSlots(checkDate, config);
                        var noticeMinBSD = parseInt(config.minimum_notice) || 0;
                        var remainingBSD = filterTodaySlots(todaySlots, noticeMinBSD, nowBSD);
                        if (remainingBSD.length === 0) {
                            isAvailable = false;
                            cssClass = 'avdp-no-times-today';
                        }
                    }
                }

                // For START pickers in Day Based mode: check if this date can lead to
                // min_days consecutive available days. Flexible Range uses duration (hours)
                // constraints instead — min_days is not applicable there.
                if (isAvailable && isStartPicker && config.booking_type !== 'flexible') {
                    var minDays = parseInt(config.min_days) || 0;
                    if (minDays > 0 && !canStartSequentialBooking(checkDate, minDays, config)) {
                        isAvailable = false;
                        cssClass = 'avdp-insufficient-days';
                    }
                }

                // Range highlighting: add xdsoft_current class to dates within selected range
                if (isAvailable && $startField && $endField && $startField.length && $endField.length) {
                    var startVal = $startField.val();
                    var endVal = $endField.val();

                    if (startVal && endVal) {
                        var rangeStart = parseFlexibleDate(startVal, config.formatDate);
                        var rangeEnd = parseFlexibleDate(endVal, config.formatDate);

                        if (rangeStart && rangeEnd && !isNaN(rangeStart.getTime()) && !isNaN(rangeEnd.getTime())) {
                            rangeStart.setHours(0, 0, 0, 0);
                            rangeEnd.setHours(0, 0, 0, 0);

                            if (checkDate >= rangeStart && checkDate <= rangeEnd) {
                                cssClass = (cssClass ? cssClass + ' ' : '') + 'xdsoft_current';
                            }
                        }
                    }
                }

                return [isAvailable, cssClass];
            },

            onShow: function (ct) {
                $(this).attr('data-avdp-picker', '1');

                var pickerInstance = $field.data('xdsoft_datetimepicker');

                // For date pickers, ensure visual state matches field value when reopening
                if (!timepicker && pickerInstance && $field.val()) {
                    var fieldValue = $field.val();
                    var parsedDate = parseFlexibleDate(fieldValue, config.formatDate);
                    if (parsedDate && !isNaN(parsedDate.getTime())) {
                        setTimeout(function() {
                            if (pickerInstance) {
                                pickerInstance.setOptions({ value: fieldValue });
                                var $picker = $('.xdsoft_datetimepicker:visible').last();
                                if ($picker.length) {
                                    var targetDay = parsedDate.getDate();
                                    $picker.find('.xdsoft_date').removeClass('xdsoft_selected');
                                    $picker.find('.xdsoft_date').each(function() {
                                        var $cell = $(this);
                                        if (!$cell.hasClass('xdsoft_disabled') && parseInt($cell.text().trim(), 10) === targetDay) {
                                            $cell.addClass('xdsoft_selected');
                                            return false;
                                        }
                                    });
                                }
                            }
                        }, 100);
                    }
                }

                if (timepicker) {
                    // For time-only fields (separate date/time), get the date from the corresponding date field
                    if ($field.is(avdpPublic.selectors.start_time) || $field.is(avdpPublic.selectors.end_time)) {
                        var $dateField = null;
                        if ($field.is(avdpPublic.selectors.start_time)) {
                            $dateField = $(avdpPublic.selectors.start_date);
                        } else if ($field.is(avdpPublic.selectors.end_time)) {
                            $dateField = $(avdpPublic.selectors.end_date);
                        }

                        if ($dateField && $dateField.length && $dateField.val()) {
                            var dateStr = $dateField.val();
                            var selectedDate = parseFlexibleDate(dateStr, config.formatDate);
                            if (selectedDate && !isNaN(selectedDate.getTime())) {
                                var instance = $field.data('xdsoft_datetimepicker');
                                updateAllowTimes(selectedDate, instance || this, config);
                                return;
                            }
                        }
                        // If no date selected yet, use today as fallback
                        var instance = $field.data('xdsoft_datetimepicker');
                        updateAllowTimes(new Date(), instance || this, config);
                    } else {
                        // For datetime fields, ct represents what the user is viewing
                        var instance = $field.data('xdsoft_datetimepicker');
                        updateAllowTimes(ct, instance || this, config);
                    }
                }

                // For end date pickers, navigate to the start date when opening
                var isEndPicker = $field.is(avdpPublic.selectors.end_date) || $field.is(avdpPublic.selectors.end_datetime);

                if (isEndPicker) {
                    var $startField = null;
                    var $form = $field.closest('form');

                    if ($field.is(avdpPublic.selectors.end_date)) {
                        $startField = $form.length ? $form.find(avdpPublic.selectors.start_date) : $(avdpPublic.selectors.start_date);
                    } else if ($field.is(avdpPublic.selectors.end_datetime)) {
                        $startField = $form.length ? $form.find(avdpPublic.selectors.start_datetime) : $(avdpPublic.selectors.start_datetime);
                    }

                    var startDate = null;

                    // First, check if there's a pending minDate stored
                    var pendingMinDate = $field.data('avdp-pending-min-date');

                    if (pendingMinDate && pendingMinDate instanceof Date && !isNaN(pendingMinDate.getTime())) {
                        startDate = new Date(pendingMinDate.getFullYear(), pendingMinDate.getMonth(), pendingMinDate.getDate());
                        $field.removeData('avdp-pending-min-date');
                    } else if ($startField && $startField.length && $startField.val()) {
                        var parsedStartDate = parseFlexibleDate($startField.val(), config.formatDate);
                        if (parsedStartDate && !isNaN(parsedStartDate.getTime())) {
                            startDate = new Date(parsedStartDate.getFullYear(), parsedStartDate.getMonth(), parsedStartDate.getDate());
                        }
                    }

                    if (startDate && pickerInstance) {
                        try {
                            var currentMinDate = null;
                            try {
                                currentMinDate = pickerInstance.getOptions('minDate');
                            } catch (e) {}

                            var finalMinDate = startDate;
                            if (currentMinDate && currentMinDate > startDate) {
                                finalMinDate = currentMinDate;
                            }

                            pickerInstance.setOptions({
                                minDate: finalMinDate,
                                defaultDate: !$field.val() ? startDate : null
                            });

                            // Force calendar refresh
                            setTimeout(function() {
                                pickerInstance.trigger('xchange.xdsoft');
                            }, 50);
                        } catch (e) {}
                    }
                }
            },

            onSelectDate: function (ct, $input) {
                var pickerInstance = $field.data('xdsoft_datetimepicker');

                // Validate BEFORE setting any date values.
                // For end date pickers, prevent selection of dates before start date.
                if (!timepicker && ct && ($field.is(avdpPublic.selectors.end_date) || $field.is(avdpPublic.selectors.end_datetime))) {
                    var $startField = null;
                    var $form = $field.closest('form');
                    if ($field.is(avdpPublic.selectors.end_date)) {
                        $startField = $form.length ? $form.find(avdpPublic.selectors.start_date) : $(avdpPublic.selectors.start_date);
                    } else if ($field.is(avdpPublic.selectors.end_datetime)) {
                        $startField = $form.length ? $form.find(avdpPublic.selectors.start_datetime) : $(avdpPublic.selectors.start_datetime);
                    }

                    if ($startField && $startField.length && $startField.val()) {
                        var parsedStartDate = parseFlexibleDate($startField.val(), config.formatDate);
                        if (parsedStartDate && !isNaN(parsedStartDate.getTime())) {
                            var startDate = new Date(parsedStartDate.getFullYear(), parsedStartDate.getMonth(), parsedStartDate.getDate());
                            var selectedDate = new Date(ct.getFullYear(), ct.getMonth(), ct.getDate());

                            if (selectedDate < startDate) {
                                $field.val('');
                                return false; // Prevent further processing
                            }
                        }
                    }
                }

                // When user selects a date in the calendar, update allowed times
                if (timepicker) {
                    var instance = $field.data('xdsoft_datetimepicker');
                    var pickerToUpdate = instance || this;
                    var ctSnapshot = ct ? new Date(ct.getTime()) : new Date();
                    setTimeout(function () {
                        updateAllowTimes(ctSnapshot, pickerToUpdate, config);
                    }, 0);
                } else if (!$field.is(avdpPublic.selectors.start_time) && !$field.is(avdpPublic.selectors.end_time)) {
                    // For date-only fields, update the corresponding time picker
                    updateTimePickerForDateField($field, ct, config);
                }

                // Update dependent pickers: if start date is selected, update end date minDate
                if ($field.is(avdpPublic.selectors.start_date) || $field.is(avdpPublic.selectors.start_datetime)) {
                    updateDependentPickers($field, ct, config);
                }
            },

            onChangeDateTime: function (dp, $input) {
                var pickerInstance = $field.data('xdsoft_datetimepicker');
                var fieldValue = $field.val();
                var pickerDate = pickerInstance ? pickerInstance.getValue() : null;

                // For end date pickers, ensure selected date is not before start date
                if (!timepicker && pickerDate && ($field.is(avdpPublic.selectors.end_date) || $field.is(avdpPublic.selectors.end_datetime))) {
                    var $startField = null;
                    var $form = $field.closest('form');
                    if ($field.is(avdpPublic.selectors.end_date)) {
                        $startField = $form.length ? $form.find(avdpPublic.selectors.start_date) : $(avdpPublic.selectors.start_date);
                    } else if ($field.is(avdpPublic.selectors.end_datetime)) {
                        $startField = $form.length ? $form.find(avdpPublic.selectors.start_datetime) : $(avdpPublic.selectors.start_datetime);
                    }

                    if ($startField && $startField.length && $startField.val()) {
                        var parsedStartDate = parseFlexibleDate($startField.val(), config.formatDate);
                        if (parsedStartDate && !isNaN(parsedStartDate.getTime())) {
                            var startDate = new Date(parsedStartDate.getFullYear(), parsedStartDate.getMonth(), parsedStartDate.getDate());
                            var selectedDate = new Date(pickerDate.getFullYear(), pickerDate.getMonth(), pickerDate.getDate());

                            if (selectedDate < startDate) {
                                $field.val('');
                                return;
                            }
                        }
                    }
                }

                // For date-only pickers, ensure visual selection state is updated
                if (!timepicker && pickerInstance && fieldValue) {
                    var parsedDate = parseFlexibleDate(fieldValue, config.formatDate);
                    if (parsedDate && !isNaN(parsedDate.getTime())) {
                        pickerInstance.setOptions({ value: fieldValue });
                    }
                }

                // If date changed, update allowed times
                if (timepicker) {
                    var selectedDate = this.getValue();
                    if (selectedDate) {
                        updateAllowTimes(selectedDate, this, config);
                    }
                } else if (!$field.is(avdpPublic.selectors.start_time) && !$field.is(avdpPublic.selectors.end_time)) {
                    var selectedDate = this.getValue();
                    if (selectedDate) {
                        updateTimePickerForDateField($field, selectedDate, config);
                    }
                }

                // Update dependent pickers: if start date is selected, update end date minDate
                if ($field.is(avdpPublic.selectors.start_date) || $field.is(avdpPublic.selectors.start_datetime)) {
                    var selectedDate = this.getValue();
                    if (selectedDate) {
                        updateDependentPickers($field, selectedDate, config);
                    }
                }

                validateSelection($input, config);
            }
        };

        if (avdpPublic.config.lang) {
            $.datetimepicker.setLocale(avdpPublic.config.lang);
        }

        try {
            $field.datetimepicker(xdsoftOptions);
        } catch (e) {
            _avdpSession.errors.push({
                msg:  e.message || String(e),
                file: 'avdp-public.js',
                line: 0,
                col:  0,
                ts:   Date.now()
            });
            _avdpSaveDiagnostics();
        }

        // Add direct change event listener for end date fields to validate against start date
        if (!timepicker && ($field.is(avdpPublic.selectors.end_date) || $field.is(avdpPublic.selectors.end_datetime))) {
            $field.on('change', function() {
                var $endField = $(this);
                var endValue = $endField.val();
                if (!endValue) {
                    return;
                }

                var pickerInstance = $endField.data('xdsoft_datetimepicker');
                var pickerDate = pickerInstance ? pickerInstance.getDate() : null;
                if (!pickerDate) {
                    return;
                }

                var $form = $endField.closest('form');
                var $startField = null;
                if ($endField.is(avdpPublic.selectors.end_date)) {
                    $startField = $form.length ? $form.find(avdpPublic.selectors.start_date) : $(avdpPublic.selectors.start_date);
                } else if ($endField.is(avdpPublic.selectors.end_datetime)) {
                    $startField = $form.length ? $form.find(avdpPublic.selectors.start_datetime) : $(avdpPublic.selectors.start_datetime);
                }

                if ($startField && $startField.length && $startField.val()) {
                    var parsedStartDate = parseFlexibleDate($startField.val(), config.formatDate);
                    if (parsedStartDate && !isNaN(parsedStartDate.getTime())) {
                        var startDate = new Date(parsedStartDate.getFullYear(), parsedStartDate.getMonth(), parsedStartDate.getDate());
                        var selectedDate = new Date(pickerDate.getFullYear(), pickerDate.getMonth(), pickerDate.getDate());

                        if (selectedDate < startDate) {
                            $endField.val('');
                        }
                    }
                }
            });
        }
    }

    /**
     * Update dependent pickers (e.g. Start Date sets End Date minDate)
     */
    function updateDependentPickers($startField, selectedDate, config) {
        if (!selectedDate || !(selectedDate instanceof Date) || isNaN(selectedDate.getTime())) {
            return;
        }

        var $form = $startField.closest('form');
        var $endField = null;
        if ($startField.is(avdpPublic.selectors.start_date)) {
            $endField = $form.length ? $form.find(avdpPublic.selectors.end_date) : $(avdpPublic.selectors.end_date);
        } else if ($startField.is(avdpPublic.selectors.start_datetime)) {
            $endField = $form.length ? $form.find(avdpPublic.selectors.end_datetime) : $(avdpPublic.selectors.end_datetime);
        }

        if (!$endField || !$endField.length) {
            return;
        }

        var endPickerInstance = $endField.data('xdsoft_datetimepicker');

        var year = selectedDate.getFullYear();
        var month = selectedDate.getMonth();
        var day = selectedDate.getDate();
        var minDate = new Date(year, month, day);

        if (!endPickerInstance) {
            $endField.data('avdp-pending-min-date', minDate);
            return;
        }

        var opts = endPickerInstance.data('options') || {};
        var currentMinDate = opts.minDate || false;

        var finalMinDate = minDate;
        if (currentMinDate && currentMinDate > minDate) {
            finalMinDate = currentMinDate;
        }

        endPickerInstance.setOptions({
            minDate: finalMinDate
        });

        var endDate = endPickerInstance.getValue();
        if (endDate && endDate < finalMinDate) {
            $endField.val('');
        }
    }

    /**
     * Validate selection against Min/Max limits.
     * Uses parseFlexibleDate so non-ISO date formats (e.g. d/m/Y) work correctly.
     */
    function validateSelection($input, config) {
        // Daily Validation (Days) — Count only AVAILABLE days
        if ($input.is(avdpPublic.selectors.start_date) || $input.is(avdpPublic.selectors.end_date)) {
            var $start = $(avdpPublic.selectors.start_date);
            var $end = $(avdpPublic.selectors.end_date);

            if ($start.length && $end.length && $start.val() && $end.val()) {
                var d1 = parseFlexibleDate($start.val(), config.formatDate);
                var d2 = parseFlexibleDate($end.val(), config.formatDate);

                if (!d1 || !d2 || isNaN(d1.getTime()) || isNaN(d2.getTime())) {
                    return;
                }

                // Validate sequence — check for blocked dates that break the booking
                var seqResult = validateSequence(d1, d2, config);
                if (!seqResult.valid) {
                    alert('Your selected range contains an unavailable date (' + seqResult.blockedDate + ') that breaks the booking sequence. Please select a continuous range of available dates.');
                    $input.val('');
                    return;
                }

                // Flexible Range with date-only fields: enforce min/max duration in hours.
                // Two midnight timestamps give exact 24h-per-night arithmetic.
                if (config.booking_type === 'flexible') {
                    var diffHours = Math.abs(d2 - d1) / (1000 * 60 * 60);
                    var minDur = parseFloat(config.min_duration) || 0;
                    var maxDur = parseFloat(config.max_duration) || 0;

                    if (minDur > 0 && diffHours < minDur) {
                        alert('Minimum booking is ' + (minDur / 24) + ' night(s). Please select a longer stay.');
                        $input.val('');
                        return;
                    } else if (maxDur > 0 && diffHours > maxDur) {
                        alert('Maximum booking is ' + (maxDur / 24) + ' night(s). Please select a shorter stay.');
                        $input.val('');
                        return;
                    }
                } else {
                    var availableDays = countAvailableDays(d1, d2, config);

                    var min = parseInt(config.min_days) || 0;
                    var max = parseInt(config.max_days) || 0;

                    if (min > 0 && availableDays < min) {
                        alert('Minimum booking is ' + min + ' available days. Your selection has only ' + availableDays + ' available days.');
                        $input.val('');
                    } else if (max > 0 && availableDays > max) {
                        alert('Maximum booking is ' + max + ' available days. Your selection has ' + availableDays + ' available days.');
                        $input.val('');
                    }
                }
            }
        }

        // Flexible Validation (Duration in Hours)
        if ($input.is(avdpPublic.selectors.start_datetime) || $input.is(avdpPublic.selectors.end_datetime)) {
            var $start = $(avdpPublic.selectors.start_datetime);
            var $end = $(avdpPublic.selectors.end_datetime);

            if ($start.length && $end.length && $start.val() && $end.val()) {
                var d1 = parseFlexibleDate($start.val(), config.formatDate);
                var d2 = parseFlexibleDate($end.val(), config.formatDate);

                if (!d1 || !d2 || isNaN(d1.getTime()) || isNaN(d2.getTime())) {
                    return;
                }

                var seqResult = validateSequence(d1, d2, config);
                if (!seqResult.valid) {
                    alert('Your selected range contains an unavailable date (' + seqResult.blockedDate + ') that breaks the booking sequence. Please select a continuous range of available dates.');
                    $input.val('');
                    return;
                }

                var diffTime = Math.abs(d2 - d1);
                var diffHours = diffTime / (1000 * 60 * 60);

                var min = parseFloat(config.min_duration) || 0;
                var max = parseFloat(config.max_duration) || 0;

                if (min > 0 && diffHours < min) {
                    alert('Minimum duration is ' + min + ' hours.');
                    $input.val('');
                } else if (max > 0 && diffHours > max) {
                    alert('Maximum duration is ' + max + ' hours.');
                    $input.val('');
                }
            }
        }
    }

    /**
     * Update the time picker when a date-only field changes.
     * Used for separate date/time field integrations.
     */
    function updateTimePickerForDateField($dateField, selectedDate, config) {
        if (!selectedDate || !(selectedDate instanceof Date) || isNaN(selectedDate.getTime())) {
            return;
        }

        var $timeField = null;
        if ($dateField.is(avdpPublic.selectors.start_date)) {
            $timeField = $(avdpPublic.selectors.start_time);
        } else if ($dateField.is(avdpPublic.selectors.end_date)) {
            $timeField = $(avdpPublic.selectors.end_time);
        }

        if (!$timeField || !$timeField.length) {
            return;
        }

        var timePickerInstance = $timeField.data('xdsoft_datetimepicker');
        if (!timePickerInstance) {
            return;
        }

        updateAllowTimes(selectedDate, timePickerInstance, config);
    }

    /**
     * Update allowed times based on Weekly Hours, Buffers, and Minimum Notice.
     *
     * Logic:
     * - IF TODAY: filter out slots before max(now + notice, now)
     * - IF NOT TODAY: show all available slots — notice doesn't apply
     */
    function updateAllowTimes(date, pickerInstance, config) {
        if (!config.weekly_hours) return;

        if (!date || !(date instanceof Date) || isNaN(date.getTime())) {
            date = new Date();
        }

        var now = new Date();
        var isToday = (date.getFullYear() === now.getFullYear() &&
            date.getMonth() === now.getMonth() &&
            date.getDate() === now.getDate());

        var availableSlots = calculateSlots(date, config);
        var noticeMinutes = parseInt(config.minimum_notice) || 0;

        // When selecting TODAY, filter out past slots (and any within minimum_notice).
        // filterTodaySlots is a pure function from avdp-availability-core.js.
        if (isToday && availableSlots.length > 0) {
            availableSlots = filterTodaySlots(availableSlots, noticeMinutes, now);
        }

        if (availableSlots.length === 0) {
            return;
        }

        pickerInstance.setOptions({
            allowTimes: availableSlots
        });

        if (pickerInstance && pickerInstance.trigger) {
            var _capturedInstance = pickerInstance;
            setTimeout(function () {
                _capturedInstance.trigger('afterOpen.xdsoft');
            }, 25);
        }
    }

})(jQuery);
