//Add any of your own step definitions here
const { Given, defineParameterType } = require('@badeball/cypress-cucumber-preprocessor')


defineParameterType({
    name: 'externalOption',
    regexp: /Enable|Delete Version|Request Activation/
})

defineParameterType({
    name: 'formStatusIcon',
    regexp: /red|yellow|green/
})

defineParameterType({
    name: 'monTable',
    regexp: /monitoring|monitoring history|monitoring logging/
})

defineParameterType({
    name: 'dqrTable',
    regexp: /Data quality error table|Data quality exclusion table/
})

defineParameterType({
    name: 'emTableName',
    regexp: /monitoring logging|data entry log|system changes|project changes|user role changes/
})

emTableName = {
    'monitoring logging' : '#monitor-query-data-log-table',
    'data entry log' : '#log-data-entry-event-table',
    'system changes' : '#system-changes-table',
    'project changes' : '#project-changes-table',
    'user role changes' : '#user-role-changes-table',
}

dqrTable = {
    'Data quality error table' : '#form-instance-rule-errors',
    'Data quality exclusion table' : '#form-instance-rule-exclusions'
}

monTable = {
    'monitoring' : '#mon-q-fields-table',
    'monitoring history' : '#monitor-query-data-log-table',
    'monitoring logging' : '#monitor-query-data-log-table'
}


formStatusIcon = {
    'red' : 'img[src*=circle_red]',
    'yellow' : 'img[src*=circle_yellow]',
    'green' : 'img[src*=circle_green]'
}


// /**
//  * @module ExternalModule
//  * @author Mintoo Xavier <min2xavier@gmail.com>
//  * @example I {externalOption} the external module named {string}
//  * @param {string} externalOption - available options - 'Enable', 'Delete Version'
//  * @param {string} label - name of external module
//  * @description Enable/Disable external module
//  */
// Given("I click on the button labeled {externalOption} for the external module named {string}", (option, label) => {
//     cy.get('#external-modules-disabled-table').find('td').contains(label).parents('tr').within(() => {
//         cy.get('button').contains(option).click()
//     })
// })


// /**
//  * @module ExternalModule
//  * @author Mintoo Xavier <min2xavier@gmail.com>
//  * @example I click on the button labeled {string} for the field labeled {string} in the external module configuration
//  * @param {string} buttonLabel - Label on button
//  * @param {string} field - Field Label
//  * @description Clicks on the button for the field in the external module configuration
//  */
// Given("I click on the button labeled {string} for the field labeled {string} in the external module configuration", (buttonLabel, field) => {
//     cy.get('.table-no-top-row-border').find('td').contains(field).parents('tr').within(() => {
//         cy.get('button').contains(buttonLabel).click()
//     })
// })


/**
 * @module ExternalModule
 * @author Mintoo Xavier <min2xavier@gmail.com>
 * @example I close the dialog box for the external module {string}
 * @param {string} name - Name of external module
 * @description Close the dialog box for the external module
 */
Given("I close the dialog box for the external module {string}", (name) => {
    cy.get('.modal-dialog').contains(name).parents('div[class="modal-header"]').within(() => {
        cy.get('button[class=close]').click()
    })
})


/**
 * @module ControlCenter
 * @author Mintoo Xavier <min2xavier@gmail.com>
 * @example I click on the {toDoTableIcons} icon for the {string} request created for the project named {string} within the {toDoTableTypes} table
 * @param {string} toDoTableIcons - available options: 'process request', 'get more information', 'add or edit a comment', 'Move to low priority section', 'archive request notification'
 * @param {string} request_name - Name of request
 * @param {string} project_name - the text value of project name you want to target
 * @param {string} toDoTableTypes - available options: 'Pending Requests', 'Low Priority Pending Requests', 'Completed & Archived Requests'
 * @description Clicks on an icon within the To-Do-List page based upon Icon, Request Name, Project Name, and Table Name specified.
 */
Given('I click on the {toDoTableIcons} icon for the {string} request created for the project named {string} within the {toDoTableTypes} table', (icon, request_name, project_name, table_name) => {
    cy.get(`.${window.toDoListTables[table_name]}`).within(() => {
        cy.get(`.request-container:contains("${project_name}"):has(.type:contains("${request_name}"))`).within(() => {
            cy.get(`button[data-tooltip="${icon}"]`).click()
        })
    })
})


/**
 * @module ControlCenter
 * @author Mintoo Xavier <min2xavier@gmail.com>
 * @example I (should )see the {string} request created for the project named {string} within the {toDoTableTypes} table
 * @param {string} request_name - Name of request
 * @param {string} project_name - the text value of project name you want to target
 * @param {string} toDoTableTypes - available options: 'Pending Requests', 'Low Priority Pending Requests', 'Completed & Archived Requests'
 * @description Identifies Request Name within the To-Do-List page based upon Project Name, and Table Name specified.
 */
Given('I (should )see the {string} request created for the project named {string} within the {toDoTableTypes} table', (request_name, project_name, table_name) => {
    cy.get(`.${window.toDoListTables[table_name]}`).within(() => {
        cy.get(`.request-container:contains("${project_name}"):has(.type:contains("${request_name}"))`)
    })
})


/**
 * @module Versioning/MonitoringQR
 * @author Mintoo Xavier <min2xavier@gmail.com>
 * @example I should see the field labeled {string} disabled
 * @param {string} field_name - name of field
 * @description Verify field is disabled
 */
Given("I should see the field labeled {string} disabled", (field_name) => {
    let contains = ''
    let last_label = field_name
    field_name.split(' ').forEach((val) => {
        contains += `:has(:contains(${JSON.stringify(val)}))`
        last_label = val
    })
    let outer_element = `tr${contains}:has(input[type=text]):visible:first`

    cy.get(outer_element).within(() => {
        cy.get(`label:contains(${JSON.stringify(last_label)})`)
            .invoke('attr', 'id')
            .then(($id) => {
                let elm = cy.get('[name="' + $id.split('label-')[1] + '"]')
                elm.should('have.attr', 'disabled')
            })
    })
})


/**
 * @module MonitoringQR
 * @author Mintoo Xavier <min2xavier@gmail.com>
 * @example I should NOT see {string} within the data entry field labeled {string}
 * @param {string} fieldOptions - field options visible
 * @param {string} label - Field Label
 * @description verifies data entry field does not contain text
 */
Given("I should NOT see {string} within the data entry field labeled {string}", (fieldOptions, label) => {
    cy.get('#questiontable').find('td').contains(label).parents('tr').should('not.contain', fieldOptions)
})


/**
 * @module MonitoringQR
 * @author Mintoo Xavier <min2xavier@gmail.com>
 * @example I enter {string} in the column {string} for the field {string}
 * @param {string} text - text to enter
 * @param {string} col - column in monitoring table
 * @param {string} fieldLabel - field label
 * @description enters text in the input field for the specified row and column in the monitoring table
 */
Given('I enter {string} in the column {string} for the field {string}', (text, col, fieldLabel) => {
    cy.table_cell_by_column_and_row_label(col, fieldLabel, '#mon-q-fields-table').find('textarea').clear().type(text)
})


/**
 * @module MonitoringQR
 * @author Mintoo Xavier <min2xavier@gmail.com>
 * @example I should NOT see {string} in the monitoring table
 * @param {string} text - text that should not be seen in the monitoring table
 * @description verifies text is not visible in the monitoring table
 */
Given('I should NOT see {string} in the monitoring table', (text) => {
    cy.get('#mon-q-fields-table').should('not.contain', text)
})


/**
 * @module MonitoringQR
 * @author Mintoo Xavier <min2xavier@gmail.com>
 * @example I select {string} in the dropdown field in column {string} for the field {string}
 * @param {string} option - option to select
 * @param {string} col - column in monitoring table
 * @param {string} fieldLabel - field label
 * @description selects the dropdown option for the specified row and column in the monitoring table
 */
Given('I select {string} in the dropdown field in column {string} for the field {string}', (option, col, fieldLabel) => {
    cy.table_cell_by_column_and_row_label(col, fieldLabel, '#mon-q-fields-table').find('select').select(option)  
})


/**
 * @module MonitoringQR
 * @author Mintoo Xavier <min2xavier@gmail.com>
 * @example I should NOT see the {monTable} table
 * @param {string} monTable - available options: 'monitoring', 'monitoring history', 'monitoring logging'
 * @description verifies monitoring table does not exists
 */
Given('I should NOT see the monitoring table', () => {
    cy.get('#mon-q-fields-table').should('not.exist')
})


/**
 * @module MonitoringQR
 * @author Mintoo Xavier <min2xavier@gmail.com>
 * @example I click on the {string} view icon
 * @param {string} ordinal - available options: 'first', 'second', 'third', 'fourth', 'fifth', 'sixth', 'seventh', 'eighth', 'ninth', 'tenth', 'eleventh', 'twelfth', 'thirteenth', 'fourteenth', 'fifteenth', 'sixteenth', 'seventeenth', 'eighteenth', 'nineteenth', 'twentieth', 'last'
 * @description clicks on the view icon in the monitoring table
 */
Given('I click on the {string} view icon', (ordinal) => {
    index = window.ordinalChoices[ordinal]
    cy.get('.fa-eye').eq(index).click()
})


/**
 * @module MonitoringQR
 * @author Mintoo Xavier <min2xavier@gmail.com>
 * @example I should see a button labeled {string} disabled
 * @param {string} label - label on button
 * @description verifies the button is disabled
 */
Given('I should see a button labeled {string} disabled', (label) => {
    cy.get('input[type=button][value*="' + label +'"]').should('be.disabled')
})


/**
 * @module MonitoringQR
 * @author Mintoo Xavier <min2xavier@gmail.com>
 * @example I should see the monitoring status {string}
 * @param {string} label - monitoring status
 * @description verifies the monitoring status
 */
Given('I should see the monitoring status {string}', (label) => {
    cy.get('tr[class=labelrc]').contains(label)
})


/**
 * @module EmbellishFields/MonitoringQR
 * @author Mintoo Xavier <min2xavier@gmail.com>
 * @example I should see {string} within the data entry field labeled {string}
 * @param {string} fieldOptions - field options visible
 * @param {string} label - Field Label
 * @description verifies data entry field contains text
 */
Given("I should see {string} within the data entry field labeled {string}", (fieldOptions, label) => {
    cy.get('#questiontable').find('td').contains(label).parents('tr').should('contain', fieldOptions)
})


/**
 * @module HightlightDQR
 * @author Mintoo Xavier <min2xavier@gmail.com>
 * @example I (should )see {string} in the {dqrTable}
 * @param {string} text - text to view
 * @param {string} dqrTable - available options: 'Data quality error table', 'Data quality exclusion table'
 * @description verify text is visible in the Data quality errors/exclusion table
 */
Given('I (should )see {string} in the {dqrTable}', (text, tableName) => {
    cy.get(dqrTable[tableName]).contains(text)
})


/**
 * @module HightlightDQR
 * @author Mintoo Xavier <min2xavier@gmail.com>
 * @example I should NOT see {string} in the {dqrTable}
 * @param {string} text - text to view 
 * @param {string} dqrTable - available options: 'Data quality error table', 'Data quality exclusion table'
 * @description verify text is not visible in the Data quality errors/exclusion table
 */
Given('I should NOT see {string} in the {dqrTable}', (text, tableName) => {
    cy.get(dqrTable[tableName]).should('not.contain', text)
})


/**
 * @module HightlightDQR
 * @author Mintoo Xavier <min2xavier@gmail.com>
 * @example I (should )see the field labeled {string} highlighed in red
 * @param {string} label - field label
 * @description verify field is highlighted in red
 */
Given('I (should )see the field labeled {string} highlighed in red', (label) => {
    cy.get('#questiontable').find('tr').contains(label).parents('tr').should('have.attr', 'style')
        .and('include', 'border-width: 2px')
        .and('include', 'border-color: rgb(255, 33, 0)')
})


/**
 * @module HightlightDQR
 * @author Mintoo Xavier <min2xavier@gmail.com>
 * @example I should NOT see the field labeled {string} highlighed in red
 * @param {string} label - field label
 * @description verify field is not highlighted in red
 */
Given('I should NOT see the field labeled {string} highlighed in red', (label) => {
    cy.get('#questiontable').find('tr').contains(label).parents('tr').should('not.have.attr', 'style', 'border-width: 2px; border-color: rgb(255, 33, 0);')
})


/**
 * @module MonitoringQR/DataEntryLog/ConfigurationMonitor
 * @author Mintoo Xavier <min2xavier@gmail.com>
 * @example I should see {int} row(s) in the {emTableName} table
 * @param {int} num - number of row(s)
 * @param {string} emTableName - available options: 'monitoring logging', 'data entry log', 'system changes', 'project changes', 'user role changes'
 * @description verifies the table contains the specified number of row(s)
 */
Given('I should see {int} row(s) in the {emTableName} table', (num, tableName) => {
    element = emTableName[tableName] + ' tbody tr'
    cy.get(element).its('length').then ((rowCount) => {
        // Subtracting 1 for header
        if (tableName === 'data entry log') {
            rowCount = rowCount-1
        }

        if (tableName === 'monitoring logging') {
             // Subtracting 1 for header and dividing by 2 as each entry has 2 rows
            rowCount = (rowCount-1)/2
        }

        expect(rowCount).to.be.equal(num)
    })
})


/**
 * @module EnhaceReasonForChange
 * @author Mintoo Xavier <min2xavier@gmail.com>
 * @example I should see the field labeled {string} with a {int}px {string} right border in {string} color
 * @param {string} label - field label
 * @param {int} num - right border style in px
 * @param {string} lineType - right border style - solid/dashed
 * @param {string} color - color of right border
 * @description verify field has a right border of specified style
 */
Given('I should see the field labeled {string} with a {int}px {string} right border in {string} color', (label, num, lineType, color) => {
    cy.get('#questiontable').find('tr').contains(label).parents('tr').should('have.attr', 'style')
        .and('include', 'border-right: ' + num + 'px ' + lineType + ' ' + color)
})


/**
 * @module EnhaceReasonForChange
 * @author Mintoo Xavier <min2xavier@gmail.com>
 * @example I should NOT see the field labeled {string} with a colored right border
 * @param {string} label - field label
 * @description verify field does not have a right border
 */
Given('I should NOT see the field labeled {string} with a colored right border', (label) => {
    cy.get('#questiontable').find('tr').contains(label).parents('tr').should('not.have.attr', 'style', 'border-right')
})


/**
 * @module EnhanceFormStatus
 * @author Mintoo Xavier <min2xavier@gmail.com>
 * @example I should see {formStatusIcon} bubble with the form status {string}
 * @param {string} formStatusIcon - form status icon - available options: 'red', 'yellow', 'green'
 * @param {string} status - form status
 * @description verifies the form status and color of bubble
 */
Given('I should see {formStatusIcon} bubble with the form status {string}', (icon, status) => {
    cy.get('#questiontable').find(formStatusIcon[icon]).next().contains(status)
})


/**
 * @module EnhanceFormStatus
 * @author Mintoo Xavier <min2xavier@gmail.com>
 * @example I should NOT see {formStatusIcon} form status bubble
 * @param {string} formStatusIcon - form status icon - available options: 'red', 'yellow', 'green'
 * @description verifies form status bubble is not visible
 */
Given('I should NOT see {formStatusIcon} form status bubble', (icon) => {
    cy.get('#questiontable').should('not.contain', formStatusIcon[icon])
})


/**
 * @module Visibility
 * @author Mintoo Xavier <min2xavier@gmail.com>
 * @example I should see a table header and rows with rowspan containing the following values in a table:
 * @param {dataTable} options the Data Table of values specified
 * @description Verifies a table contains the specified headers and rows, handling columns with rowspan that spans across multiple rows.
 * This step definition is designed for tables where columns have variable positions due to rowspan attributes.
 * It expands cells with rowspan to cover all affected rows during verification.
 */
Given('I should see a table header and rows with rowspan containing the following values in a table:', (dataTable) => {
    const rows = dataTable.rawTable
    const expectedHeaders = rows[0]
    const expectedRows = rows.slice(1)

    cy.get('table').should('be.visible').then(($table) => {
        // Find all rows in the table
        const $allRows = $table.find('tr')

        // Find header row
        let $headerCells
        const $theadHeaders = $table.find('thead tr:first th, thead tr:first td')

        if ($theadHeaders.length > 0) {
            $headerCells = $theadHeaders
        } else {
            // Headers might be in first tbody row
            const $firstRow = Cypress.$($allRows[0])
            $headerCells = $firstRow.find('th, td')
        }

        // Verify headers
        expectedHeaders.forEach((expectedHeader, index) => {
            const headerText = Cypress.$($headerCells[index]).text().trim()
            expect(headerText, `Header at position ${index}`).to.include(expectedHeader.trim())
        })

        // Get data rows (skip header row)
        let $dataRows = $allRows.slice(1)
        if ($theadHeaders.length > 0) {
            $dataRows = $table.find('tbody tr')
        }

        // Build a normalized grid that expands rowspan cells
        const normalizedGrid = []
        const rowspanTracker = [] // Tracks which columns are occupied by rowspan from previous rows

        $dataRows.each((rowIndex, htmlRow) => {
            const $row = Cypress.$(htmlRow)
            const $cells = $row.find('td, th')

            // Initialize tracker for this row if needed
            if (!rowspanTracker[rowIndex]) {
                rowspanTracker[rowIndex] = []
            }

            const normalizedRow = []
            let cellIndex = 0

            // Fill in cells, accounting for rowspan from previous rows
            for (let colIndex = 0; colIndex < expectedHeaders.length; colIndex++) {
                // Check if this column is occupied by a rowspan from a previous row
                if (rowspanTracker[rowIndex][colIndex]) {
                    normalizedRow[colIndex] = rowspanTracker[rowIndex][colIndex]
                } else {
                    // Use the current cell
                    const $cell = Cypress.$($cells[cellIndex])
                    if ($cell.length > 0) {
                        const cellText = $cell.text().trim()
                        const rowspan = parseInt($cell.attr('rowspan') || '1')

                        normalizedRow[colIndex] = cellText

                        // If this cell has rowspan > 1, mark it for subsequent rows
                        if (rowspan > 1) {
                            for (let r = 1; r < rowspan; r++) {
                                const targetRowIndex = rowIndex + r
                                if (!rowspanTracker[targetRowIndex]) {
                                    rowspanTracker[targetRowIndex] = []
                                }
                                rowspanTracker[targetRowIndex][colIndex] = cellText
                            }
                        }

                        cellIndex++
                    } else {
                        normalizedRow[colIndex] = ''
                    }
                }
            }

            normalizedGrid.push(normalizedRow)
        })

        // Verify each expected row exists in the normalized grid
        expectedRows.forEach((expectedRow) => {
            let rowFound = false

            normalizedGrid.forEach((normalizedRow) => {
                // Only check rows that have the same number of cells
                if (normalizedRow.length !== expectedRow.length) {
                    return
                }

                let cellsMatch = true

                expectedRow.forEach((expectedCell, cellIndex) => {
                    if (cellIndex >= normalizedRow.length) {
                        cellsMatch = false
                        return
                    }

                    const cellText = normalizedRow[cellIndex]
                    const expectedText = expectedCell.trim()

                    // Handle date/time pattern matching
                    if (expectedText === 'mm/dd/yyyy hh:mm' || expectedText.match(/^mm\/dd\/yyyy/)) {
                        const dateTimePattern = /\d{1,2}\/\d{1,2}\/\d{4}\s+\d{1,2}:\d{2}\s*(am|pm)?/i
                        if (!dateTimePattern.test(cellText)) {
                            cellsMatch = false
                        }
                    } else if (expectedText === '') {
                        // Empty cell - allow any whitespace or truly empty
                        if (cellText !== '' && cellText !== ' ') {
                            cellsMatch = false
                        }
                    } else {
                        // Exact match or contains match
                        if (!cellText.includes(expectedText) && cellText !== expectedText) {
                            cellsMatch = false
                        }
                    }
                })

                if (cellsMatch) {
                    rowFound = true
                }
            })

            expect(rowFound, `Expected row not found: ${expectedRow.join(' | ')}`).to.be.true
        })
    })
})


/**
 * @module MailHog
 * @author Mintoo Xavier <min2xavier@gmail.com>
 * @example I should see a {emTableName} table in the email with the following rows:
 * @param {string} emTableName - available options: 'monitoring logging', 'data entry log', 'system changes', 'project changes', 'user role changes'
 * @param {DataTable} dataTable - Gherkin DataTable with headers and rows
 * @description Verifies a plain HTML table in emails (e.g., MailHog) contains the specified headers and rows.
 * Supports date/time patterns like mm/dd/yyyy hh:mm.
 */
Given('I should see a {emTableName} table in the email with the following rows:', (tableName, dataTable) => {
    const rows = dataTable.rawTable

    // First row contains headers
    const expectedHeaders = rows[0]
    const expectedRows = rows.slice(1)

    // MailHog displays email content in an iframe - use retriable approach to avoid DOM detachment
    cy.get('iframe#preview-html')
        .its('0.contentDocument.body')
        .should('not.be.empty')
        .then(cy.wrap)
        // Email HTML may not have the same IDs as the web page - try specific ID first, fall back to table
        .then(($body) => {
            const specificTable = $body.find(emTableName[tableName])
            if (specificTable.length > 0) {
                return cy.wrap(specificTable)
            }
            // Fall back to finding any table in the email
            return cy.wrap($body).find('table').should('exist')
        })
        .then(($table) => {
            cy.wrap($table).find('tr').then(($allRows) => {
                // Find header row - could be in thead or first row with th/td elements
                let $headerCells
                const $theadHeaders = $table.find('thead tr:first th, thead tr:first td')

                if ($theadHeaders.length > 0) {
                    // Headers are in thead
                    $headerCells = $theadHeaders
                } else {
                    // Headers are likely in first row (styled differently)
                    // Look for first row with style attribute or just use first row
                    const $firstRow = Cypress.$($allRows[0])
                    $headerCells = $firstRow.find('th, td')
                }

                // Verify headers exist
                expectedHeaders.forEach((expectedHeader, index) => {
                    const headerText = Cypress.$($headerCells[index]).text().trim()
                    expect(headerText, `Header at position ${index}`).to.include(expectedHeader.trim())
                })

                // Verify each expected row exists in the table
                // Skip first row if it contains headers
                let $dataRows = $allRows
                if ($headerCells.length > 0) {
                    $dataRows = $allRows.slice(1)
                }

                expectedRows.forEach((expectedRow) => {
                    let rowFound = false

                    $dataRows.each((_, htmlRow) => {
                        const $cells = Cypress.$(htmlRow).find('td, th')

                        // Only check rows that have the same number of cells
                        if ($cells.length !== expectedRow.length) {
                            return
                        }

                        let cellsMatch = true

                        expectedRow.forEach((expectedCell, cellIndex) => {
                            if (cellIndex >= $cells.length) {
                                cellsMatch = false
                                return
                            }

                            const cellText = Cypress.$($cells[cellIndex]).text().trim()
                            const expectedText = expectedCell.trim()

                            // Handle date/time pattern matching (mm/dd/yyyy hh:mm)
                            if (expectedText === 'mm/dd/yyyy hh:mm' || expectedText.match(/^mm\/dd\/yyyy/)) {
                                // Match date pattern: digits/digits/digits and optional time
                                const dateTimePattern = /\d{1,2}\/\d{1,2}\/\d{4}\s+\d{1,2}:\d{2}\s*(am|pm)?/i
                                if (!dateTimePattern.test(cellText)) {
                                    cellsMatch = false
                                }
                            } else if (expectedText === '') {
                                // Empty cell - allow any whitespace or truly empty
                                if (cellText !== '' && cellText !== ' ') {
                                    cellsMatch = false
                                }
                            } else {
                                // Exact match or contains match
                                if (!cellText.includes(expectedText) && cellText !== expectedText) {
                                    cellsMatch = false
                                }
                            }
                        })

                        if (cellsMatch) {
                            rowFound = true
                        }
                    })

                    expect(rowFound, `Expected row not found: ${expectedRow.join(' | ')}`).to.be.true
                })
            })
        })
})