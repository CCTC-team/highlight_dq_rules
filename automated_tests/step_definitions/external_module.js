//Add any of your own step definitions here
const { Given, defineParameterType } = require('@badeball/cypress-cucumber-preprocessor')


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