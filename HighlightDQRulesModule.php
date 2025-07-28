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
                            let cleaned = match.substring(1, match.length - 1);
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
}

