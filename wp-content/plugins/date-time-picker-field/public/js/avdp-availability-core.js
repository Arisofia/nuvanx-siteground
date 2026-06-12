/**
 * Availability Core - Pure slot calculation and date logic for datepicker.
 * Extracted for testability; used by avdp-public.js.
 *
 * @package Availability_Datepicker
 * @since 2.4.0.13
 */

(function (global) {
    'use strict';

    var AVDPAvailabilityCore = {};

    /**
     * Parse a date string flexibly, handling various formats.
     *
     * @param {string}      dateStr  Date string to parse.
     * @param {string|null} [format] Optional format hint (e.g. 'd/m/Y', 'd-m-Y', 'd.m.Y', 'Y-m-d').
     * @returns {Date|null}
     */
    function parseFlexibleDate(dateStr, format) {
        if (!dateStr) return null;

        var parsedDate;
        var parts;

        var isDayFirst = format && /^d[\/\-\.]m[\/\-\.]Y/.test(format);

        if (!isDayFirst) {
            parsedDate = new Date(dateStr);

            if (isNaN(parsedDate.getTime())) {
                parsedDate = new Date(dateStr.replace(/\//g, '-'));
            }

            if (!isNaN(parsedDate.getTime())) {
                return parsedDate;
            }
        }

        parts = dateStr.split(/[-\/.]/);
        if (parts.length >= 3) {
            if (isDayFirst) {
                parsedDate = new Date(
                    parseInt(parts[2], 10),
                    parseInt(parts[1], 10) - 1,
                    parseInt(parts[0], 10)
                );
            } else if (parts[0].length === 4) {
                parsedDate = new Date(
                    parseInt(parts[0], 10),
                    parseInt(parts[1], 10) - 1,
                    parseInt(parts[2], 10)
                );
            } else if (parts[2].length === 4) {
                parsedDate = new Date(
                    parseInt(parts[2], 10),
                    parseInt(parts[1], 10) - 1,
                    parseInt(parts[0], 10)
                );
            }
        }

        return parsedDate || new Date(NaN);
    }

    /**
     * Find the first available date based on weekly_hours, disabled dates, etc.
     */
    function getFirstAvailableDate(config) {
        var dayMap = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        var weekly = config.weekly_hours || {};
        var disabledDates = config.disabledDates || [];
        var exemptedDates = config.exemptedDates || [];
        var noticeMinutes = parseInt(config.minimum_notice, 10) || 0;
        var bookingType = config.booking_type || 'fixed';

        var startDate = new Date();
        startDate.setTime(startDate.getTime() + (noticeMinutes * 60 * 1000));

        for (var i = 0; i < 365; i++) {
            var checkDate = new Date(startDate);
            checkDate.setDate(startDate.getDate() + i);

            var dayName = dayMap[checkDate.getDay()];
            var dateStr = checkDate.getFullYear() + '-' +
                String(checkDate.getMonth() + 1).padStart(2, '0') + '-' +
                String(checkDate.getDate()).padStart(2, '0');

            if (exemptedDates.indexOf(dateStr) !== -1) {
                return checkDate;
            }

            if (disabledDates.indexOf(dateStr) !== -1) {
                continue;
            }

            if (weekly[dayName]) {
                var dayConfig = weekly[dayName];
                var isEnabled = dayConfig.enabled === true ||
                    dayConfig.enabled === 1 ||
                    dayConfig.enabled === '1' ||
                    dayConfig.enabled === 'true';

                if (bookingType === 'daily') {
                    if (isEnabled) {
                        return checkDate;
                    }
                } else {
                    if (isEnabled && dayConfig.slots && dayConfig.slots.length > 0) {
                        // Ensure at least one slot survives the earliest possible booking time
                        // (startDate = now + noticeMinutes). This guards both the case where today's
                        // slots have passed and the case where minimum_notice pushes startDate into a
                        // future day whose slots are also entirely past that shifted start time.
                        var isStartDay = (
                            checkDate.getFullYear() === startDate.getFullYear() &&
                            checkDate.getMonth()    === startDate.getMonth()    &&
                            checkDate.getDate()     === startDate.getDate()
                        );
                        if (isStartDay) {
                            var candidateSlots = calculateSlots(checkDate, config);
                            var remaining = filterTodaySlots(candidateSlots, 0, startDate);
                            if (remaining.length === 0) {
                                continue;
                            }
                        }
                        return checkDate;
                    }
                }
            }
        }

        return new Date();
    }

    /**
     * Calculate slots from a custom time range (for allowed dates override).
     */
    function calculateSlotsFromCustomRange(date, start, end, config) {
        var allowed = [];
        var step = parseInt(config.step, 10) || 30;
        var bufferBefore = parseInt(config.buffer_before, 10) || 0;
        var bufferAfter = parseInt(config.buffer_after, 10) || 0;
        var totalCycle = bufferBefore + step + bufferAfter;

        var startParts = start.split(':');
        var endParts = end.split(':');
        var startMins = (parseInt(startParts[0], 10) * 60) + parseInt(startParts[1], 10);
        var endMins = (parseInt(endParts[0], 10) * 60) + parseInt(endParts[1], 10);

        var isCross = endMins < startMins;
        var limit = isCross ? 1440 : endMins;

        for (var slotStart = startMins + bufferBefore; slotStart + step <= limit; slotStart += totalCycle) {
            var h = Math.floor(slotStart / 60);
            var m = slotStart % 60;
            allowed.push(
                String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0')
            );
        }

        if (isCross) {
            for (var slotStart = bufferBefore; slotStart + step <= endMins; slotStart += totalCycle) {
                var h = Math.floor(slotStart / 60);
                var m = slotStart % 60;
                var timeStr = String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0');
                if (allowed.indexOf(timeStr) === -1) {
                    allowed.push(timeStr);
                }
            }
        }

        allowed.sort();
        return allowed;
    }

    /**
     * Calculate slots client-side from weekly hours or allowed_dates_with_times
     */
    function calculateSlots(date, config) {
        var y = date.getFullYear();
        var m = String(date.getMonth() + 1).padStart(2, '0');
        var d = String(date.getDate()).padStart(2, '0');
        var dateStr = y + '-' + m + '-' + d;

        var allowedDatesWithTimes = config.allowed_dates_with_times || [];
        for (var i = 0; i < allowedDatesWithTimes.length; i++) {
            var allowedDate = allowedDatesWithTimes[i];
            if (allowedDate.date === dateStr && allowedDate.start && allowedDate.end) {
                return calculateSlotsFromCustomRange(date, allowedDate.start, allowedDate.end, config);
            }
        }

        var dayMap = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        var dayName = dayMap[date.getDay()];
        var weekly = config.weekly_hours;

        if (!weekly || !weekly[dayName]) {
            return [];
        }

        var dayConfig = weekly[dayName];
        var isEnabled = dayConfig.enabled === true ||
            dayConfig.enabled === 1 ||
            dayConfig.enabled === '1' ||
            dayConfig.enabled === 'true';

        if (!isEnabled) {
            return [];
        }

        var slotsData = dayConfig.slots;
        if (!slotsData || slotsData.length === 0) {
            return [];
        }

        var allowed = [];
        var step = parseInt(config.step, 10) || 30;
        var bufferBefore = parseInt(config.buffer_before, 10) || 0;
        var bufferAfter = parseInt(config.buffer_after, 10) || 0;
        var totalCycle = bufferBefore + step + bufferAfter;

        slotsData.forEach(function (slotRange) {
            var startParts = slotRange.start.split(':');
            var endParts = slotRange.end.split(':');

            var startMins = (parseInt(startParts[0], 10) * 60) + parseInt(startParts[1], 10);
            var endMins = (parseInt(endParts[0], 10) * 60) + parseInt(endParts[1], 10);

            var isCross = endMins < startMins;
            var limit = isCross ? 1440 : endMins;

            for (var slotStart = startMins + bufferBefore; slotStart + step <= limit; slotStart += totalCycle) {
                var h = Math.floor(slotStart / 60);
                var m = slotStart % 60;
                allowed.push(
                    String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0')
                );
            }
        });

        var yestDate = new Date(date);
        yestDate.setDate(date.getDate() - 1);
        var yestDayName = dayMap[yestDate.getDay()];

        if (weekly[yestDayName] && weekly[yestDayName].enabled && weekly[yestDayName].slots) {
            weekly[yestDayName].slots.forEach(function (slotRange) {
                var startParts = slotRange.start.split(':');
                var endParts = slotRange.end.split(':');
                var startMins = (parseInt(startParts[0], 10) * 60) + parseInt(startParts[1], 10);
                var endMins = (parseInt(endParts[0], 10) * 60) + parseInt(endParts[1], 10);

                if (endMins < startMins) {
                    for (var slotStart = bufferBefore; slotStart + step <= endMins; slotStart += totalCycle) {
                        var h = Math.floor(slotStart / 60);
                        var m = slotStart % 60;
                        var timeStr = String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0');
                        if (allowed.indexOf(timeStr) === -1) {
                            allowed.push(timeStr);
                        }
                    }
                }
            });
        }

        allowed.sort();

        return allowed;
    }

    /**
     * Check if a start date can begin a booking that spans min_days consecutive
     * calendar days that are all available.
     *
     * A disabled weekday inside the window breaks the consecutive run and returns
     * false immediately — matching the user expectation that a 5-night stay cannot
     * start on Wednesday when the property is closed on weekends.
     * An exempted date overrides both disabled-weekday and blocked-date rules and
     * counts as an available day in the sequence.
     */
    function canStartSequentialBooking(startDate, minDays, config) {
        if (!minDays || minDays <= 0) {
            return true;
        }

        var disabledWeekDays = (config.disabledWeekDays || []).map(function (d) {
            return parseInt(d, 10);
        });
        var blockedDates = config.disabledDates || [];
        var exemptedDates = config.exemptedDates || [];

        var current = new Date(startDate);
        current.setHours(0, 0, 0, 0);

        for (var i = 0; i < minDays; i++) {
            var y = current.getFullYear();
            var m = String(current.getMonth() + 1).padStart(2, '0');
            var d = String(current.getDate()).padStart(2, '0');
            var dateStr = y + '-' + m + '-' + d;
            var dayOfWeek = current.getDay();

            // Exempted dates are always available — they override every other rule.
            if (exemptedDates.indexOf(dateStr) !== -1) {
                current.setDate(current.getDate() + 1);
                continue;
            }

            // A disabled weekday in the middle of the required span breaks the sequence.
            if (disabledWeekDays.indexOf(dayOfWeek) !== -1) {
                return false;
            }

            // An explicitly blocked date breaks the sequence.
            if (blockedDates.indexOf(dateStr) !== -1) {
                return false;
            }

            current.setDate(current.getDate() + 1);
        }

        return true;
    }

    /**
     * Validate that the date range has a continuous sequence of available working days
     */
    function validateSequence(startDate, endDate, config) {
        var disabledWeekDays = (config.disabledWeekDays || []).map(function (d) {
            return parseInt(d, 10);
        });
        var blockedDates = config.disabledDates || [];
        var exemptedDates = config.exemptedDates || [];

        var current = new Date(startDate);
        current.setHours(0, 0, 0, 0);
        var end = new Date(endDate);
        end.setHours(0, 0, 0, 0);

        while (current <= end) {
            var y = current.getFullYear();
            var m = String(current.getMonth() + 1).padStart(2, '0');
            var d = String(current.getDate()).padStart(2, '0');
            var dateStr = y + '-' + m + '-' + d;
            var dayOfWeek = current.getDay();

            if (disabledWeekDays.indexOf(dayOfWeek) !== -1) {
                current.setDate(current.getDate() + 1);
                continue;
            }

            if (exemptedDates.indexOf(dateStr) !== -1) {
                current.setDate(current.getDate() + 1);
                continue;
            }

            if (blockedDates.indexOf(dateStr) !== -1) {
                return {
                    valid: false,
                    blockedDate: dateStr
                };
            }

            current.setDate(current.getDate() + 1);
        }

        return { valid: true, blockedDate: null };
    }

    /**
     * Count available days between two dates (inclusive), excluding unavailable days.
     *
     * Exempted dates override both disabled-weekday and blocked-date rules and are
     * counted as available. Used by avdp-public.js validateSelection to check
     * min/max days against only the selectable days in a range.
     *
     * Pure function — no side effects, no DOM access.
     *
     * @param {Date}   startDate
     * @param {Date}   endDate
     * @param {Object} config    Requires disabledDates, disabledWeekDays, exemptedDates.
     * @returns {number} Number of available days.
     */
    function countAvailableDays(startDate, endDate, config) {
        var disabledDates = config.disabledDates || [];
        var disabledWeekDays = (config.disabledWeekDays || []).map(function (d) {
            return parseInt(d, 10);
        });
        var exemptedDates = config.exemptedDates || [];

        var count = 0;
        var current = new Date(startDate);
        current.setHours(0, 0, 0, 0);
        var end = new Date(endDate);
        end.setHours(0, 0, 0, 0);

        while (current <= end) {
            var y = current.getFullYear();
            var m = String(current.getMonth() + 1).padStart(2, '0');
            var d = String(current.getDate()).padStart(2, '0');
            var dateStr = y + '-' + m + '-' + d;
            var dayOfWeek = current.getDay();

            var isAvailable;
            if (exemptedDates.indexOf(dateStr) !== -1) {
                isAvailable = true;
            } else if (disabledDates.indexOf(dateStr) !== -1) {
                isAvailable = false;
            } else if (disabledWeekDays.indexOf(dayOfWeek) !== -1) {
                isAvailable = false;
            } else {
                isAvailable = true;
            }

            if (isAvailable) {
                count++;
            }

            current.setDate(current.getDate() + 1);
        }

        return count;
    }

    /**
     * Filter time slots for today, removing any that fall before (now + noticeMinutes).
     *
     * Pure function — no side effects, no DOM access.
     * Extracted here for testability; called by avdp-public.js updateAllowTimes.
     *
     * @param {string[]} slots          Array of 'HH:MM' strings.
     * @param {number}   noticeMinutes  Minimum notice in minutes (0 = filter past only).
     * @param {Date}     now            Current time (defaults to new Date()).
     * @returns {string[]} Filtered slots.
     */
    function filterTodaySlots(slots, noticeMinutes, now) {
        if (!slots || slots.length === 0) return slots;
        var reference = now instanceof Date ? now : new Date();
        var minAvailableTime = new Date(reference.getTime() + ((noticeMinutes || 0) * 60 * 1000));

        // If now + notice crosses midnight, every slot for today is already past.
        if (minAvailableTime.getDate()     !== reference.getDate()     ||
            minAvailableTime.getMonth()    !== reference.getMonth()    ||
            minAvailableTime.getFullYear() !== reference.getFullYear()) {
            return [];
        }

        var minMins = (minAvailableTime.getHours() * 60) + minAvailableTime.getMinutes();

        return slots.filter(function (timeStr) {
            var parts = timeStr.split(':');
            var slotMins = (parseInt(parts[0], 10) * 60) + parseInt(parts[1], 10);
            return slotMins >= minMins;
        });
    }

    AVDPAvailabilityCore.parseFlexibleDate = parseFlexibleDate;
    AVDPAvailabilityCore.getFirstAvailableDate = getFirstAvailableDate;
    AVDPAvailabilityCore.calculateSlots = calculateSlots;
    AVDPAvailabilityCore.calculateSlotsFromCustomRange = calculateSlotsFromCustomRange;
    AVDPAvailabilityCore.canStartSequentialBooking = canStartSequentialBooking;
    AVDPAvailabilityCore.validateSequence = validateSequence;
    AVDPAvailabilityCore.countAvailableDays = countAvailableDays;
    AVDPAvailabilityCore.filterTodaySlots = filterTodaySlots;

    if (typeof module !== 'undefined' && module.exports) {
        module.exports = AVDPAvailabilityCore;
    } else {
        global.AVDPAvailabilityCore = AVDPAvailabilityCore;
    }

})(typeof window !== 'undefined' ? window : this);
