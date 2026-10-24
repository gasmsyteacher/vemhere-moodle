// This file is part of the Vemhere plugins for Moodle - https://vemhere.com
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Focus monitor on a quiz attempt page: notices when the student leaves the page, reports the exit on return,
 * and warns or submits as the server says.
 *
 * Leaving = the page is hidden (another app or tab, a locked phone) or the window loses focus to something
 * outside it. Moving between quiz pages and submitting are not exits.
 *
 * @module     quizaccess_vemfocus/monitor
 * @copyright  2026 Virtual Minds Hub Enterprise
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/ajax', 'core/notification', 'core/str'], function(Ajax, Notification, Str) {
    var cfg = null;
    var leftAt = null;
    var navigating = false;
    var done = false;

    /**
     * Submits the attempt with the answers on this page saved first.
     */
    function submitAttempt() {
        navigating = true;
        var form = document.getElementById('responseform');
        if (!form) {
            // Summary page: post the finish request directly.
            form = document.createElement('form');
            form.method = 'post';
            form.action = cfg.processurl;
            [['attempt', cfg.attemptid], ['sesskey', M.cfg.sesskey]].forEach(function(f) {
                var i = document.createElement('input');
                i.type = 'hidden'; i.name = f[0]; i.value = f[1]; form.appendChild(i);
            });
            document.body.appendChild(form);
        }
        var fin = document.createElement('input');
        fin.type = 'hidden'; fin.name = 'finishattempt'; fin.value = '1';
        form.appendChild(fin);
        HTMLFormElement.prototype.submit.call(form);
    }

    /**
     * Tells the server about one exit and acts on its answer.
     *
     * @param {number} away seconds away
     */
    function report(away) {
        Ajax.call([{methodname: 'quizaccess_vemfocus_record_exit', args: {attemptid: cfg.attemptid, awayseconds: away}}])[0]
            .then(function(r) {
                var a = {exits: r.exits, max: r.maxexits};
                if (r.action === 'submit') {
                    done = true;
                    return Str.get_strings([{key: 'submittitle', component: 'quizaccess_vemfocus'},
                        {key: 'submitmessage', component: 'quizaccess_vemfocus', param: a}]).then(function(s) {
                        Notification.alert(s[0], s[1]);
                        window.setTimeout(submitAttempt, 2500);
                        return null;
                    });
                }
                if (r.action === 'warn') {
                    return Str.get_strings([{key: 'warntitle', component: 'quizaccess_vemfocus'},
                        {key: r.maxexits ? 'warnmessage' : 'warnmessage_nomax', component: 'quizaccess_vemfocus', param: a}])
                        .then(function(s) {
                            Notification.alert(s[0], s[1]);
                            return null;
                        });
                }
                return null;
            }).catch(Notification.exception);
    }

    /**
     * The student has left the page.
     */
    function left() {
        if (navigating || done || leftAt !== null) {
            return;
        }
        leftAt = Date.now();
    }

    /**
     * The student is back.
     */
    function back() {
        if (leftAt === null || navigating || done) {
            return;
        }
        var away = Math.round((Date.now() - leftAt) / 1000);
        leftAt = null;
        report(away);
    }

    return {
        /**
         * @param {Object} config {attemptid, mode, maxexits, processurl}
         */
        init: function(config) {
            cfg = config;
            document.addEventListener('visibilitychange', function() {
                if (document.visibilityState === 'hidden') {
                    left();
                } else {
                    back();
                }
            });
            // A desktop window that loses focus to another program, not to a frame inside the quiz.
            window.addEventListener('blur', function() {
                window.setTimeout(function() {
                    var inside = document.activeElement && document.activeElement.tagName === 'IFRAME';
                    if (!document.hasFocus() && document.visibilityState === 'visible' && !inside) {
                        left();
                    }
                }, 300);
            });
            window.addEventListener('focus', back);
            // Moving between pages, finishing and saving are not exits.
            document.addEventListener('submit', function() {
                navigating = true;
            }, true);
            window.addEventListener('beforeunload', function() {
                navigating = true;
            });
        }
    };
});
