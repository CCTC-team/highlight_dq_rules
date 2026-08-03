<?php

namespace CCTC\HighlightDQRulesModule;

use DataQuality;
use REDCap;
use ExternalModules\AbstractExternalModule;
use UserRights;


class HighlightDQRulesModule extends AbstractExternalModule
{
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

    // Generate a link to the Data Quality rule page
    private function makeDQLink(int $projectId, string $rule, string $val): string
    {
        return "<a href='" . APP_PATH_WEBROOT . "/DataQuality/index.php?pid={$projectId}#{$rule}'>{$val}</a>";
    }

    // Queries the db for the rule details for rules with the given array of $ruleIds
    private function getDQRulesDetails(int $projId, array $ruleIds): array
    {
        if (empty($ruleIds)) {
            return [];
        }

        try {
            // Use parameterized query to prevent SQL injection
            $placeholders = implode(',', array_fill(0, count($ruleIds), '?'));
            $params = array_merge([$projId], array_map('intval', $ruleIds));

            $query = "
                SELECT
                    rule_id,
                    rule_order,
                    rule_name,
                    rule_logic,
                    real_time_execute
                FROM
                    redcap_data_quality_rules
                WHERE
                    project_id = ?
                    AND rule_id IN ($placeholders)
                ORDER BY
                    rule_order
            ";

            $result = $this->query($query, $params);
            $ruleDetails = [];

            while ($row = $result->fetch_assoc()) {
                $ruleDetails[$row['rule_id']] = [
                    "rule_order" => $row['rule_order'],
                    "rule_name" => $row['rule_name'],
                    "rule_logic" => $row['rule_logic'],
                    "real_time_execute" => $row['real_time_execute']
                ];
            }

            return $ruleDetails;
        } catch (\Exception $e) {
            error_log("Highlight DQ Rules: Error fetching rule details - " . $e->getMessage());
            return [];
        }
    }

    // Check if user belongs to any of the specified roles
    private function getRoleNameFromIds(int $projId, array $roleIds, string $userName): int
    {
        if (empty($roleIds)) {
            return 0;
        }

        try {
            // Use parameterized query to prevent SQL injection
            $placeholders = implode(',', array_fill(0, count($roleIds), '?'));
            $params = array_merge([$projId], array_map('intval', $roleIds), [$userName]);

            $query = "
                SELECT COUNT(*) as count
                FROM
                    redcap_user_roles a
                    INNER JOIN redcap_user_rights b
                    ON a.project_id = b.project_id
                    AND a.role_id = b.role_id
                WHERE
                    a.project_id = ?
                    AND a.role_id IN ($placeholders)
                    AND b.username = ?
            ";

            $result = $this->query($query, $params);
            $row = $result->fetch_assoc();
            return (int) $row['count'];
        } catch (\Exception $e) {
            error_log("Highlight DQ Rules: Error checking user roles - " . $e->getMessage());
            return 0;
        }
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

        $cnt = $this->getRoleNameFromIds($project_id, $allowedRoles, $userName);

        //NOTE: for testing purposes, need to log in and log out to check this works correctly (rather than view as user)
        //current user must be in one of permitted roles or be a superuser
        if ($cnt > 0 || $super) {
            // Include external CSS and JS files
            echo '<link rel="stylesheet" href="' . $this->getUrl('css/styles.css') . '">';
            echo '<script src="' . $this->getUrl('js/highlight.js') . '"></script>';

            $dq = new DataQuality();
            $repeat_instrument = $Proj->isRepeatingForm($event_id, $instrument) ? $instrument : "";
            $repeat_instance = ($Proj->isRepeatingEvent($event_id) || $Proj->isRepeatingForm($event_id, $instrument)) ? $repeat_instance : 0;

            list ($dq_errors, $dq_errors_excluded) = $dq->checkViolationsSingleRecord($record, $event_id, $instrument, array(), $repeat_instance, $repeat_instrument);
            $errors_to_include = array_diff($dq_errors, $dq_errors_excluded);
            $allErrs = $this->getDQRulesDetails($project_id, $errors_to_include);

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
                    $makeLink = $this->makeDQLink($project_id, $ruleNameId, htmlspecialchars($rule["rule_name"], ENT_QUOTES, 'UTF-8'));

                    // Escape rule_logic to prevent XSS
                    $safeRuleLogic = htmlspecialchars($rule["rule_logic"], ENT_QUOTES, 'UTF-8');
                    echo "<tr><td>{$ruleId}</td><td>{$rule["rule_order"]}</td><td>{$makeLink}</td><td>{$safeRuleLogic}</td></tr>";
                    $js .= "<script type='text/javascript'> matchField($ruleId, $escapedRuleLogic) </script>";
                }

                echo "$js";

                echo "
                </table>

                <div class='red' style='width: 800px'>
                    <div><small>rule order - the order as given in the Rule # column in the Data Quality page</small></div>
                    <div><small>rule id - the internal, unique id of the rule in the database</small></div>
                </div>
    ";

                if ($this->getProjectSetting('highlight-dq-inline')) {
                    echo "<script type='text/javascript'>highlightInlineErrors();</script>";
                }
            }

            //gets the exclusions
            $allExcluded = $this->getDQRulesDetails($project_id, $dq_errors_excluded);

            //fix the icons
            if (!$this->getProjectSetting('dont-reset-field-data-icon')) {
                echo "<script type='text/javascript'>resetFieldIcons();</script>";
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
                        $makeLink = $this->makeDQLink($project_id, $ruleNameId, htmlspecialchars($rule["rule_name"], ENT_QUOTES, 'UTF-8'));

                        // Escape rule_logic to prevent XSS
                        $safeRuleLogic = htmlspecialchars($rule["rule_logic"], ENT_QUOTES, 'UTF-8');
                        echo "<tr><td>{$ruleId}</td><td>{$rule["rule_order"]}</td><td>{$makeLink}</td><td>{$safeRuleLogic}</td></tr>";
                    }

                echo "
                </table>

                <div class='green' style='width: 800px'>
                    <div><small>rule order - the order as given in the Rule # column in the Data Quality page</small></div>
                    <div><small>rule id - the internal, unique id of the rule in the database</small></div>
                </div>
    ";
                }
            }
        }
    }

    // ---------------------------------------------------------------------
    // Configuration audit log
    //
    // REDCap core already logs *which* keys changed (and who/when) to the
    // project Logging page. What core cannot do is record old->new VALUES on
    // this module's own "View logging" page. This generic, config-driven hook
    // fills that gap: it diffs each saved setting against a snapshot kept under
    // a reserved key and writes one entry per changed setting via $this->log().
    // The block is identical across all CCTC EM repos (config-driven, no
    // per-repo edits) and handles both project and system scopes.
    // ---------------------------------------------------------------------
    public function redcap_module_save_configuration($project_id): void
    {
        $this->auditConfigurationChange($project_id);
    }

    private function auditConfigurationChange($project_id): void
    {
        $config   = $this->getConfig();
        $isSystem = empty($project_id);
        $scope    = $isSystem ? 'system' : 'project';
        $keys     = $this->collectSettingKeys($config[$scope . '-settings'] ?? []);
        if (empty($keys)) return;

        $snapshotKey = 'audit-snapshot-' . $scope;
        $read  = fn($k)     => $isSystem ? $this->getSystemSetting($k)    : $this->getProjectSetting($k);
        $write = fn($k, $v) => $isSystem ? $this->setSystemSetting($k, $v) : $this->setProjectSetting($k, $v);

        $new = [];
        foreach ($keys as $k) $new[$k] = $this->normaliseSetting($read($k));

        $rawOld = $read($snapshotKey);
        $old = is_string($rawOld) ? json_decode($rawOld, true) : null;
        // First save has no prior snapshot: treat the baseline as empty so the
        // initial configuration's real values are still logged ((empty) -> value),
        // while settings left blank stay '' vs '' and produce no noise.
        if (!is_array($old)) $old = [];

        $changed = false;
        foreach ($keys as $k) {
            $before = $this->normaliseSetting($old[$k] ?? null);
            $after  = $new[$k];
            if ($before !== $after) {
                $changed = true;
                $this->log("Configuration changed ($scope)", [
                    'project_id' => $project_id,
                    'setting'    => $k,
                    'old_value'  => $before === '' ? '(empty)' : $before,
                    'new_value'  => $after  === '' ? '(empty)' : $after,
                ]);
            }
        }
        if ($changed) $write($snapshotKey, json_encode($new));
    }

    private function collectSettingKeys(array $settings): array
    {
        $keys = [];
        foreach ($settings as $s) {
            if (!isset($s['key'])) continue;
            if (($s['type'] ?? '') === 'descriptive') continue;
            $keys[] = $s['key'];
        }
        return $keys;
    }

    private function normaliseSetting($v): string
    {
        if ($v === null || $v === false) return '';
        if ($v === true) return '1';
        if (is_array($v)) {
            // A repeatable setting the admin never filled comes back as an array
            // of empty entries (e.g. [null]), not as null. Treat that as unset,
            // otherwise the first save logs a phantom "(empty) -> [null]" change
            // for every blank repeatable.
            foreach ($v as $entry) {
                $hasValue = is_array($entry) ? !empty($entry) : trim((string) ($entry ?? '')) !== '';
                if ($hasValue) return json_encode($v);
            }
            return '';
        }
        return trim((string) $v);
    }
}

