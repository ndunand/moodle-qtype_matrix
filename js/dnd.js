// mod_ND : BEGIN

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    qtype_matrix
 * @copyright  2015  Université de Lausanne
 * @author Nicolas Dunand <nicolas.dunand@unil.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


(function ($) {

    $(document).ready(function () {

        var n = 0; // matrix question number (1..)
        var dnduistr = 'qtype_matrix_dndui';

        var wasDropped = function (ismultiple, $tr, $cell, text, $draggable, check_boxes) {
            if (!ismultiple) {
                if (check_boxes) {
                    // 1. uncheck all other boxes
                    $tr.find('input').prop('checked', false);
                    // 2. remove all other dropped items
                    $cell.parents('tr').find('.cell:has("input") span').remove();
                }
                // 3. disable $draggable
                $draggable.draggable('disable');
                // 4. gray out $draggable
                $draggable.addClass('disabled');
            }
            if (check_boxes) {
                $cell.find('input').prop('checked', true); // check the checkbox
            }
            var $newspan = $('<span>').text(text); // fill in the receptacle with a clue that it is checked
            var $deletebutton = $('<span>').text('X'); // button to uncheck a checkbox
            $deletebutton.click(function () {
                var $btn = $(this);
                // 1. uncheck the box
                $btn.parents('td').find('input').prop('checked', false);
                // 2. (re-)enable the $draggable
                $draggable.draggable('enable');
                // 3. un-gray out the $draggable
                $draggable.removeClass('disabled');
                // 4. remove the whole <span>
                $btn.parent().remove();
            });
            $deletebutton.appendTo($newspan);
            $newspan.appendTo($cell);
        };

        $('.que.matrix').each(function () {
            n++;
            var $question = $(this); // question display DOM element
            var $matrix = $question.find('table.matrix'); // question table
            if (!$matrix.hasClass('uses_dndui')) {
                return;
            }
            var $baskets = $question.find("th:has('span.title')"); // table header cells, i.e. categories
            var $receptacles = $matrix.find('.cell:has("input")'); // all cells with a checkbox or a radio button
            var $items = $question.find('.cell.c0'); // first column, i.e. items
            var ismultiple = $matrix.find('input[type=checkbox]').length ? true : false; // multiple choice allowed?
            //var $trs = $question.find('tr:has(input)');

            $question.addClass('clearfix').addClass(dnduistr); // to make sure we only only for activated dndui
            $baskets.addClass('outerwalled'); // to allow for simple CSS categories boundaries
            $receptacles.addClass('outerwalled'); // ditto

            var it = 0; // item number (1..)
            $items.each(function () {
                it++;
                // each item has to be draggable, but only to a sortable in its own row
                var $item = $(this);
                var text = $item.find('span.title').text();
                var $tr = $matrix.find('tbody tr').eq(it - 1); // table row
                var $draggable = $(this).find('span.title'); // draggable item

                $draggable.attr('class', dnduistr + '_item' + ' ' + dnduistr + '_' + n + '_item_' + it);
                $draggable.draggable({
                    'helper':      'clone', // we drag a clone
                    'revert':      'invalid', // revert if not dropped onto a valid target
                    'opacity':     0.5, // opacity while dragging
                    'containment': $matrix, // can only be dragged this far
                    'scope':       dnduistr + '_' + n + '_item_' + it // can only be dragged to its own row's droppables
                });

                $tr.find('.cell:has("input")').each(function () { // this row's each possible cell containing a checkbox
                    var $cell = $(this);
                    $cell.droppable({
                        'activeClass': 'activated', // to hint the user where it can drop the currently dragged item
                        'hoverClass':  'hovered', // to hint the user what will happen if dropped now
                        'tolerance':   'pointer', // drop accepted if pointer within droppable boundaries (easiest UX)
                        'scope':       dnduistr + '_' + n + '_item_' + it, // only accept draggable from this row
                        'drop':        function (event, ui) {
                            wasDropped(ismultiple, $tr, $cell, text, $draggable, true);
                        }
                    });
                    if ($cell.find('input').prop('checked')) {
                        // this checkbox is already checked (by Moodle's back-end), so drop a draggable into it
                        wasDropped(ismultiple, $tr, $cell, text, $draggable, false); // but don't activate the checkboxes again!
                    }
                });

            });

            // disable dragging if checkboxes are disabled
            if ($receptacles.eq(0).find('input').prop('disabled')) {
                $items.find('span.title').draggable('disable').addClass('disabled');
                $receptacles.find('span span').remove();
            }

        });

    });

})(jQuery);

// mod_ND : END
