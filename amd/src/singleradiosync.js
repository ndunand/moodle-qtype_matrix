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
 * Used in single type questions with radio button groups so that each cell radio button
 * that is selected syncs its selected column with the hidden inputs that hold the cell values
 * stored for an attempt.
 * E.g. a matrix has 4 columns, so 4 radio buttons per row.The radio button group name is row0.
 * Each hidden cell input bears the cell's name (e.g. row0col2) and is either true or not.
 * Those hidden input values are the response values that will be saved in attempt steps.
 * This allows single and multiple to have the same response format and allows easier switching and
 * helps during the regrading workflow.
 * @copyright  2025
 * @author Daniel Poggenpohl <daniel.poggenpohl@fernuni hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 *
 * @param {Event} click
 */
const propagateChangedValue = (click) => {
    let clickedElement = click.target;
    if (clickedElement.tagName !== 'INPUT' || clickedElement.type !== 'radio') {
        return;
    }
    let hiddenCheckboxForRadio = clickedElement.parentElement.querySelector('input[type="checkbox"]');
    let hiddenCheckboxesForRow = clickedElement.closest('tr').querySelectorAll('input[type="checkbox"]');
    hiddenCheckboxesForRow.forEach((hiddenCheckboxForRow) => {
        hiddenCheckboxForRow.checked = false;
    });
    hiddenCheckboxForRadio.checked = true;
};

export const init = (matrixTableId) => {
    let matrixTable = document.getElementById(matrixTableId);
    let matrixRows = matrixTable.querySelectorAll('tbody tr');
    matrixRows.forEach((matrixRow) => {
        matrixRow.addEventListener('click', propagateChangedValue);
    });
};
