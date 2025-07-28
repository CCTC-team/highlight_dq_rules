### Highlight DQ rules ###

The Highlight DQ rules module provides a simple mechanism to highlight rules that are in error within the data entry
screens for selected user roles. Only rules that execute in real-time are shown. 

#### Set up and configuration ####

Project settings

- `user-roles-can-view` - one or more roles can be selected for whom the errored data quality rules are shown. If no
  roles are selected, the module is effectively disabled. For example, in most cases, it may simply be the Data Manager
  role that has the highlighting enabled
- `highlight-dq-inline` - when checked, the field in the data entry page is highlighted as being affected by an errored
  data quality rule

#### Usage ####

The list of errored fields is simply created from any referenced fields within the Data Quality resolution logic; 
therefore, a highlighted field should not be read to 'be in error' - rather, it should be read to be 'referenced within
a failing data quality rule'.

The table of data quality errors includes a rule name that provides a link to the relevant rule within the Data Quality
page. The user requires Data Quality Rule access to view the target of the link.

If the rule is highlighted in line, the annotation includes the rule ids of the related rule.