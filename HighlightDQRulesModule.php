<?php

namespace CCTC\HighlightDQRulesModule;

use DataQuality;
use REDCap;
use ExternalModules\AbstractExternalModule;
use UserRights;


class HighlightDQRulesModule extends AbstractExternalModule
{
    //potential improvement: some of the logic (a lot!) could be rewritten to use the inbuilt methods
    //leaving as is for now as it works


    const DataEntryFilePath = APP_PATH_DOCROOT . "/Classes/DataEntry.php";
    const DataEntryCode =
        '//****** inserted by Monitoring QR module ******
        Hooks::call(\'redcap_save_record_highlight_dqr\', array($field_values_changed, PROJECT_ID, $fetched, $_GET[\'page\'], $_GET[\'event_id\'], $group_id, ($isSurveyPage ? $_GET[\'s\'] : null), $response_id, $_GET[\'instance\']));
        //****** end of insert ******' . PHP_EOL;
    const DataEntrySearchTerm = '            if (!is_numeric($group_id)) $group_id = null;
            Hooks::call(\'redcap_save_record\', array(PROJECT_ID, $fetched, $_GET[\'page\'], $_GET[\'event_id\'], $group_id, ($isSurveyPage ? $_GET[\'s\'] : null), $response_id, $_GET[\'instance\']));
        }';

    //adds the $insertCode into the $filePath after the $searchTerm
    function addCodeToFile($filePath, $searchTerm, $insertCode) : void
    {
        $file_contents = file($filePath);
        $found = false;

        $searchArray = explode("\n", $searchTerm);
        $matched = 0;

        foreach ($file_contents as $index => $line) {
            //increment $matched so checks next line on next iteration
            if (str_contains($line, $searchArray[$matched])) {
                $matched++;
            }

            //if all the lines were found then mark as found
            if($matched == count($searchArray) - 1) {
                array_splice($file_contents, $index + 1, 0, $insertCode);
                $found = true;
                break;
            }
        }

        //write it back if was found
        if ($found) {
            file_put_contents($filePath, implode('', $file_contents));
        }
    }

    //removes the inserted hook in the Hooks.php file
    function removeCodeFromFile($filePath, $removeCode) : void
    {
        $file_contents = file_get_contents($filePath);
        if(str_contains($file_contents, $removeCode)) {
            $modified_contents = str_replace($removeCode, "", $file_contents);
            file_put_contents($filePath, $modified_contents);
        }
    }

    function redcap_module_system_enable($version): void
    {
        //adds the code to the files as needed
        self::addCodeToFile(self::DataEntryFilePath, self::DataEntrySearchTerm, self::DataEntryCode);
    }


    function redcap_module_system_disable($version): void
    {
        //removes the previously added code
        self::removeCodeFromFile(self::DataEntryFilePath, self::DataEntryCode);
    }

    function getBaseUrl(): string
    {
        //returns something like https://localhost:8443/redcap_v13.8.1
        $url = $this->getUrl("somepage.php");

        //use regex to pull everything prior to the ExternalModules part
        $basePat = "/https?:\/\/.*(?=\/ExternalModules)/";
        preg_match($basePat, $url, $urlMatches);

        return $urlMatches[0];
    }

    public function validateSettings($settings): ?string
    {
        if (array_key_exists("user-roles-can-view", $settings)) {
            $lastIndex = array_key_last($settings['user-roles-can-view']);
            if(empty($settings['user-roles-can-view'][$lastIndex])) {
                return "User roles in Highlight DQ Rules External Module should not be empty";
            }
        }
        return null;
    }

    function MakeDQLink($projectId, $rule, $val): string
    {
        //https://localhost:8443/redcap_v13.8.1/DataQuality/index.php?pid=28#ruleorder_12
        $baseUrl = $this->getBaseUrl();

        return "<a href='{$baseUrl}/DataQuality/index.php?pid={$projectId}#{$rule}'>{$val}</a>";
    }

    // queries the db for the rule details for rules with the given array of $ruleIds
    function GetDQRulesDetails($projId, $ruleIds): array
    {
        $rs = implode(",", $ruleIds);

        $query = "
            select
                rule_id,
                rule_order,
                rule_name,
                rule_logic,
                real_time_execute
            from
                redcap_data_quality_rules
            where
                project_id = $projId
                and rule_id in (" . $rs . ")
            order by
                rule_order;
            ";

        $result = db_query($query);
        $ruleDetails = [];

        while ($row = db_fetch_assoc($result)) {
            $ruleDetails[$row['rule_id']] =
                array(
                    "rule_order" => $row['rule_order'],
                    "rule_name" => $row['rule_name'],
                    "rule_logic" => $row['rule_logic'],
                    "real_time_execute" => $row['real_time_execute']);
        }

        return $ruleDetails;
    }

    function GetRoleNameFromIds($projId, $roleIds, $userName): int
    {
        $rs = implode(",", $roleIds);

        $query = "
            select count(*) as count                
            from
                redcap_user_roles a
                inner join redcap_user_rights b
                on 
                    a.project_id = b.project_id 
                    and a.role_id = b.role_id            
            where
                a.project_id = ?
                and a.role_id in (" . $rs . ")
                and b.username = ?
            ;
            ";

        $result = db_query($query, [$projId, $userName]);
        $row = db_fetch_assoc($result);
        return $row['count'];
    }

    public function redcap_data_entry_form($project_id, $record, $instrument, $event_id, $group_id, $repeat_instance)
    {
        
        if (empty($project_id)) return;

        global $Proj;
        $super = $this->isSuperUser();
        //get the current username
        $user = $this->getUser();
        $userName = $user->getUserName();

        //get the allowed roles
        $allowedRoles = $this->getProjectSetting('user-roles-can-view');

        //if allowed roles are not set, then do nothing
        if (empty($allowedRoles[0])) {
            echo "<script type='text/javascript'>
                    alert('Please ensure the mandatory fields in the Highlight DQ Rules External Module are configured.');
                </script>";
            return;
        }

        $cnt = self::GetRoleNameFromIds($project_id, $allowedRoles, $userName);

        //NOTE: for testing purposes, need to log in and log out to check this works correctly (rather than view as user)
        //current user must be in one of permitted roles or be a superuser
        if ($cnt > 0 || $super) {
            echo "
                <script type='text/javascript'>

                    let arrMatches = {};

                    function matchField(ruleId, dq) {
                        let matches = dq.match(/\[(.*?)\]/g);

                        matches.forEach(function(match) {
                            // remove leading digits to ensure valid CSS selector
                            let cleaned = match.substring(1, match.length - 1).replace(/^\d+/, '');
                            let selector = '#' + cleaned + '-tr';

                            let ele = document.querySelector(selector);
                            if(ele) {
                                if(arrMatches[selector]) {
                                    if(!arrMatches[selector].includes(ruleId)) {
                                        arrMatches[selector].push(ruleId);
                                    }
                                } else {
                                    arrMatches[selector] = [ruleId];
                                }
                            }
                        })
                    }
                    
                    let arrIconMatches = [];
                    
                    function addSingleFieldIconUpdate(fieldName) {
                        arrIconMatches.push(fieldName);
                    }
                </script>
                ";

            $dq = new DataQuality();
            $repeat_instrument = $Proj->isRepeatingForm($event_id, $instrument) ? $instrument : "";
            $repeat_instance = ($Proj->isRepeatingEvent($event_id) || $Proj->isRepeatingForm($event_id, $instrument)) ? $repeat_instance : 0;

            list ($dq_errors, $dq_errors_excluded) = $dq->checkViolationsSingleRecord($record, $event_id, $instrument, array(), $repeat_instance, $repeat_instrument);
            $errors_to_include = array_diff($dq_errors, $dq_errors_excluded);
            $allErrs = self::GetDQRulesDetails($project_id, $errors_to_include);

            // in response to #115, before simply returning the errors first check whether the rule applies to a single
            // field. If it does, its status may need to be updated as currently the checkViolationsSingleRecord
            // function returns a different response when the rule includes a single field compared to multiple fields
            // basically, if there is a rule which is in error, and the rule applies to a single field, then the
            // function getExclusionsSingleRecord should be run to see if the field is verified and therefore excluded

            $js = "";

            $allErrsClone = $allErrs;

            foreach ($allErrsClone as $ruleId => $err) {
                $dq_rule_fields = array_keys(getBracketedFields($err["rule_logic"], true, true, true));

                //check if only one field in the rule so could have been verified (or then subsequently deverified)
                if (count($dq_rule_fields) == 1) {
                    //check if the error has been excluded
                    $excludedFields = $dq->getExclusionsSingleRecord($record, $event_id, $repeat_instance, $instrument);
                    if(in_array($dq_rule_fields[0], $excludedFields)) {
                        //add the error field as an exclusion
                        $dq_errors_excluded[] = $ruleId;

                        //remove the error from allErrs
                        unset($allErrs[$ruleId]);

                        //remove the error from $errors_to_include
                        $key = array_search($ruleId, $errors_to_include);
                        if ($key !== false) {
                            unset($errors_to_include[$key]);
                        }
                    }
                }

                //revert the icon in the field that shows a green tick or red exclamation by adding this rule to the array
                $js .= "<script type='text/javascript'> addSingleFieldIconUpdate('$dq_rule_fields[0]') </script>";
            }

            echo "$js";

            $js = "";

            if (count($allErrs) > 0) {
                echo "<br>
                <div class='red' style='width: 800px;'>Data quality errors for current form</div>
                <table id='form-instance-rule-errors' class='red' style='table-layout: fixed;width:800px;'><tr>
                    <th class='red' style='width:20px;'>Rule ID</th>
                    <th class='red' style='width:20px;'>Rule Order</th>
                    <th class='red' style='width:120px;'>Rule Name</th>
                    <th class='red' style='width:170px;'>Rule Logic</th>
                    </tr>";


                foreach ($errors_to_include as $ruleId) {
                    $rule = $allErrs[$ruleId];
                    $realtime = $rule["real_time_execute"] == 1 ? 'yes' : 'no';
                    $escapedRuleLogic = json_encode($rule["rule_logic"]);
                    $ruleNameId = "rulename_" . $ruleId;
                    $makeLink = $this->MakeDQLink($project_id, $ruleNameId, $rule["rule_name"]);

                    echo "<tr><td>{$ruleId}</td><td>{$rule["rule_order"]}</td><td>{$makeLink}</td><td>{$rule["rule_logic"]}</td></tr>";
                    $js .= "<script type='text/javascript'> matchField($ruleId, $escapedRuleLogic) </script>";
                }

                echo "$js";

                echo "
                </table>
                
                <div class='red' style='width: 800px'>
                    <div><small>rule order - the order as given in the Rule # column in the Data Quality page</small></div>
                    <div><small>rule id - the internal, unique id of the rule in the database</small></div>                               
                </div>
                <style>
                    #form-instance-rule-errors th, #form-instance-rule-errors td {
                        border-width:1px;
                        text-align:left;
                        padding:2px 4px 2px 4px;
                    }
                    #form-instance-rule-errors th {
                        font-weight: bold;
                    }                    
                    div[err-data-rule-id] {
                        margin-top: 3px;                        
                        padding-top: 2px;
                        padding-left: 6px;
                        background-color: rgb(255, 33, 0);
                        color: white;
                        border-top-right-radius: 4px;
                    }

                </style>
    ";

                if ($this->getProjectSetting('highlight-dq-inline')) {
                    echo "
                <script type='text/javascript'>
                    Object.keys(arrMatches).forEach(key => {
                        let ele = document.querySelector(key);

                        ele.style.borderWidth = '2px';
                        ele.style.borderColor = 'rgb(255, 33, 0)';

                        const errRuleIds = document.createElement('div');
                        errRuleIds.setAttribute('err-data-rule-id', key.substring(1, key.length));
                        errRuleIds.textContent = 'related rule ids: ' + arrMatches[key].join(', ');

                        ele.insertAdjacentElement('beforebegin', errRuleIds);
                    });

                </script>
                ";
                }
            }

            //gets the exclusions
            $allExcluded = self::GetDQRulesDetails($project_id, $dq_errors_excluded);

            //fix the icons
            if (!$this->getProjectSetting('dont-reset-field-data-icon')) {
                echo "
                        <script type='text/javascript'>
                        
                            //replaces the data status icon for any single fields that have been excluded with the standard
                            //grey balloon i.e. if a single field check, by default REDCap will mark the field with a green
                            //tick icon - DMs didn't want this, so below will reset to default icon            
                            //will also replace the red exclamation icon  
                            arrIconMatches.forEach(function(item) {
                                
                                ///redcap_v13.8.1/Resources/images/tick_circle.png
                                ///redcap_v13.8.1/Resources/images/balloon_left_bw2.gif
                                let ele = document.getElementById('dc-icon-' + item);
                                if(ele) {
                                    let curr = ele.src;                            
                                    //replace the green tick
                                    if(ele.src.endsWith('tick_circle.png')) {
                                        ele.src = curr.replace('tick_circle.png', 'balloon_left_bw2.gif');    
                                    }                                    
                                    //replace the red exclamation
                                    if(ele.src.endsWith('exclamation_red.png')) {
                                        ele.src = curr.replace('exclamation_red.png', 'balloon_left_bw2.gif');
                                    }
                                }                           
                            });                        
                        </script>
                        ";
            }

            //show the excluded
            if (count($allExcluded) > 0) {
                if (!$this->getProjectSetting('dont-show-excluded-table')) {
                    echo "<br>
                    <div class='green' style='width: 800px;'>Data quality errors that have been excluded for the current form</div>
                    <table id='form-instance-rule-exclusions' class='green' style='table-layout: fixed;width:800px;'><tr>
                        <th class='green' style='width:20px;'>Rule ID</th>
                        <th class='green' style='width:20px;'>Rule Order</th>
                        <th class='green' style='width:120px;'>Rule Name</th>
                        <th class='green' style='width:170px;'>Rule Logic</th>
                        </tr>";


                    foreach ($dq_errors_excluded as $ruleId) {
                        $rule = $allExcluded[$ruleId];
                        $ruleNameId = "rulename_" . $ruleId;
                        $makeLink = $this->MakeDQLink($project_id, $ruleNameId, $rule["rule_name"]);

                        echo "<tr><td>{$ruleId}</td><td>{$rule["rule_order"]}</td><td>{$makeLink}</td><td>{$rule["rule_logic"]}</td></tr>";
                    }

                echo "
                </table>
                
                <div class='green' style='width: 800px'>
                    <div><small>rule order - the order as given in the Rule # column in the Data Quality page</small></div>
                    <div><small>rule id - the internal, unique id of the rule in the database</small></div>                               
                </div>
                <style>
                    #form-instance-rule-exclusions th, #form-instance-rule-exclusions td {
                        border-width:1px;
                        text-align:left;
                        padding:2px 4px 2px 4px;
                    }
                    #form-instance-rule-exclusions th {
                        font-weight: bold;
                    }                    
                </style>
    ";
                }
            }
        }
    }

    /**
     * Hook that fires after record is saved
     * Auto-un-excludes DQRs when their associated fields are modified
     */
    function redcap_save_record_highlight_dqr($field_values_changed, $project_id, $record, $instrument,
                                              $event_id, $group_id, $survey_hash, $response_id, $repeat_instance)
    {
        // Check if feature is enabled
        if (!$this->getProjectSetting('auto-unexclude-on-change')) {
            return;
        }

        // Only proceed if fields were actually changed
        if (empty($field_values_changed)) {
            return;
        }

        // Get changed field names
        $changed_fields = array_keys($field_values_changed);

        // Auto-un-exclude any excluded rules that reference these fields
        $this->autoUnexcludeRules($project_id, $record, $event_id, $changed_fields,
                                   $repeat_instance, $instrument);
    }

    /**
     * Auto-un-excludes DQRs when their associated fields are modified
     *
     * @param int $project_id Project ID
     * @param string $record Record ID
     * @param int $event_id Event ID
     * @param array $changed_fields Array of field names that were changed
     * @param int $repeat_instance Repeat instance (0 if not repeating)
     * @param string $repeat_instrument Repeating instrument name (empty if not repeating)
     * @return int Number of rules un-excluded
     */
    private function autoUnexcludeRules($project_id, $record, $event_id, $changed_fields,
                                        $repeat_instance = 0, $repeat_instrument = "")
    {
        global $data_resolution_enabled;

        // Determine instance value (use 1 for non-repeating)
        $instance = ($repeat_instance > 0) ? $repeat_instance : 1;

        // Build query to find excluded DQRs for this record/event
        $sql = "SELECT DISTINCT dqs.status_id, dqs.rule_id, dqr.rule_logic, dqs.query_status, dqs.exclude
                FROM redcap_data_quality_status dqs
                LEFT JOIN redcap_data_quality_rules dqr ON dqs.rule_id = dqr.rule_id
                WHERE dqs.project_id = ?
                AND dqs.record = ?
                AND dqs.event_id = ?
                AND dqs.instance = ?
                AND dqs.rule_id IS NOT NULL
                AND dqr.rule_logic IS NOT NULL";

        // Add exclusion condition based on mode
        if ($data_resolution_enabled == '2') {
            // Data Query Resolution mode
            $sql .= " AND dqs.query_status IN ('CLOSED', 'VERIFIED')";
        } else if ($data_resolution_enabled == '1') {
            // Old exclude mode
            $sql .= " AND dqs.exclude = 1";
        }

        // Handle repeating forms/events
        if (!empty($repeat_instrument)) {
            $sql .= " AND (dqs.repeat_instrument = ? OR dqs.repeat_instrument IS NULL)";
            $params = [$project_id, $record, $event_id, $instance, $repeat_instrument];
        } else {
            $params = [$project_id, $record, $event_id, $instance];
        }

        $result = db_query($sql, $params);

        $unexcluded_count = 0;
        $rules_to_unexclude = [];

        // Check each excluded rule to see if it references any changed fields
        while ($row = db_fetch_assoc($result)) {
            $rule_logic = $row['rule_logic'];

            // Extract field names from rule logic using REDCap's built-in function
            $rule_fields = array_keys(getBracketedFields($rule_logic, true, true, true));

            // Check if any changed field is in this rule
            $fields_intersect = array_intersect($changed_fields, $rule_fields);

            if (!empty($fields_intersect)) {
                // This rule should be un-excluded
                $rules_to_unexclude[] = [
                    'status_id' => $row['status_id'],
                    'rule_id' => $row['rule_id'],
                    'fields' => $fields_intersect
                ];
            }
        }

        // Un-exclude the rules
        foreach ($rules_to_unexclude as $rule_info) {
            $status_id = $rule_info['status_id'];

            // Update based on mode
            if ($data_resolution_enabled == '2') {
                // Data Query Resolution mode: set status to DEVERIFIED
                $sql = "UPDATE redcap_data_quality_status
                        SET query_status = 'DEVERIFIED'
                        WHERE status_id = ?";
                db_query($sql, [$status_id]);

                // Insert resolution record
                $user = $this->getUser();
                $user_id = $user->getIID();
                $sql = "INSERT INTO redcap_data_quality_resolutions
                        (status_id, ts, user_id, current_query_status, response_requested)
                        VALUES (?, ?, ?, 'DEVERIFIED', NULL)";
                db_query($sql, [$status_id, NOW, $user_id]);
            } else if ($data_resolution_enabled == '1') {
                // Old mode: set exclude = 0
                $sql = "UPDATE redcap_data_quality_status
                        SET exclude = 0
                        WHERE status_id = ?";
                db_query($sql, [$status_id]);
            }

            // Log the action
            $fields_str = implode(', ', $rule_info['fields']);
            $log_description = "Auto un-excluded DQ rule {$rule_info['rule_id']} for record '$record' " .
                              "because field(s) [$fields_str] were modified";

            \Logging::logEvent(
                "",
                "redcap_data_quality_status",
                "MANAGE",
                $record,
                "rule_id = {$rule_info['rule_id']}",
                $log_description
            );

            $unexcluded_count++;
        }

        return $unexcluded_count;
    }
}