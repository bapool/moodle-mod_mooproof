<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.
/**
 * Privacy Subsystem implementation for mod_mooproof
 *
 * @package    mod_mooproof
 * @copyright  2025 Brian A. Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mooproof\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\deletion_criteria;
use core_privacy\local\request\helper;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider for mod_mooproof
 */
class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider,
        \core_privacy\local\request\core_userlist_provider {

    /**
     * Return the fields which contain personal data.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection): collection {
        
        // Data stored in mooproof_usage table
        $collection->add_database_table(
            'mooproof_usage',
            [
                'userid' => 'privacy:metadata:mooproof_usage:userid',
                'submissioncount' => 'privacy:metadata:mooproof_usage:submissioncount',
                'firstsubmission' => 'privacy:metadata:mooproof_usage:firstsubmission',
                'lastsubmission' => 'privacy:metadata:mooproof_usage:lastsubmission',
            ],
            'privacy:metadata:mooproof_usage'
        );

        // Data stored in mooproof_submissions table
        $collection->add_database_table(
            'mooproof_submissions',
            [
                'userid' => 'privacy:metadata:mooproof_submissions:userid',
                'papertext' => 'privacy:metadata:mooproof_submissions:papertext',
                'feedback' => 'privacy:metadata:mooproof_submissions:feedback',
                'filename' => 'privacy:metadata:mooproof_submissions:filename',
                'wordcount' => 'privacy:metadata:mooproof_submissions:wordcount',
                'gradelevel' => 'privacy:metadata:mooproof_submissions:gradelevel',
                'timecreated' => 'privacy:metadata:mooproof_submissions:timecreated',
            ],
            'privacy:metadata:mooproof_submissions'
        );

        // Data sent to external AI provider via Moodle AI subsystem
        $collection->add_external_location_link(
            'ai_provider',
            [
                'papertext' => 'privacy:metadata:ai_provider:papertext',
                'feedback' => 'privacy:metadata:ai_provider:feedback',
                'chatmessages' => 'privacy:metadata:ai_provider:chatmessages',
                'gradelevel' => 'privacy:metadata:ai_provider:gradelevel',
            ],
            'privacy:metadata:ai_provider'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        // Find all mooproof contexts where this user has submissions
        $sql = "SELECT c.id
                  FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {mooproof} mp ON mp.id = cm.instance
             LEFT JOIN {mooproof_usage} mpu ON mpu.mooproofid = mp.id
             LEFT JOIN {mooproof_submissions} mps ON mps.mooproofid = mp.id
                 WHERE mpu.userid = :userid1 OR mps.userid = :userid2";

        $params = [
            'modname' => 'mooproof',
            'contextlevel' => CONTEXT_MODULE,
            'userid1' => $userid,
            'userid2' => $userid,
        ];

        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        // Find users from usage table
        $sql = "SELECT mpu.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {mooproof} mp ON mp.id = cm.instance
                  JOIN {mooproof_usage} mpu ON mpu.mooproofid = mp.id
                 WHERE cm.id = :cmid";

        $params = [
            'cmid' => $context->instanceid,
            'modname' => 'mooproof',
        ];

        $userlist->add_from_sql('userid', $sql, $params);

        // Find users from submissions table
        $sql = "SELECT mps.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {mooproof} mp ON mp.id = cm.instance
                  JOIN {mooproof_submissions} mps ON mps.mooproofid = mp.id
                 WHERE cm.id = :cmid";

        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();

        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        // Export usage data
        $sql = "SELECT cm.id AS cmid,
                       mpu.submissioncount,
                       mpu.firstsubmission,
                       mpu.lastsubmission
                  FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {mooproof} mp ON mp.id = cm.instance
            INNER JOIN {mooproof_usage} mpu ON mpu.mooproofid = mp.id
                 WHERE c.id {$contextsql}
                   AND mpu.userid = :userid
              ORDER BY cm.id";

        $params = [
            'modname' => 'mooproof',
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $user->id,
        ] + $contextparams;

        $usages = $DB->get_records_sql($sql, $params);

        foreach ($usages as $usage) {
            $context = \context_module::instance($usage->cmid);
            $contextdata = helper::get_context_data($context, $user);

            $contextdata->submissioncount = $usage->submissioncount;
            $contextdata->firstsubmission = \core_privacy\local\request\transform::datetime($usage->firstsubmission);
            $contextdata->lastsubmission = \core_privacy\local\request\transform::datetime($usage->lastsubmission);

            writer::with_context($context)->export_data([], $contextdata);
        }

        // Export submissions data
        $sql = "SELECT cm.id AS cmid,
                       mps.id,
                       mps.papertext,
                       mps.feedback,
                       mps.filename,
                       mps.wordcount,
                       mps.gradelevel,
                       mps.timecreated
                  FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {mooproof} mp ON mp.id = cm.instance
            INNER JOIN {mooproof_submissions} mps ON mps.mooproofid = mp.id
                 WHERE c.id {$contextsql}
                   AND mps.userid = :userid
              ORDER BY cm.id, mps.timecreated";

        $submissions = $DB->get_records_sql($sql, $params);

        foreach ($submissions as $submission) {
            $context = \context_module::instance($submission->cmid);

            $submissiondata = (object)[
                'papertext' => $submission->papertext,
                'feedback' => $submission->feedback,
                'filename' => $submission->filename,
                'wordcount' => $submission->wordcount,
                'gradelevel' => $submission->gradelevel,
                'timecreated' => \core_privacy\local\request\transform::datetime($submission->timecreated),
            ];

            writer::with_context($context)
                ->export_data(['submissions', $submission->id], $submissiondata);
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if (!$context instanceof \context_module) {
            return;
        }

        $cm = get_coursemodule_from_id('mooproof', $context->instanceid);
        if (!$cm) {
            return;
        }

        $DB->delete_records('mooproof_usage', ['mooproofid' => $cm->instance]);
        $DB->delete_records('mooproof_submissions', ['mooproofid' => $cm->instance]);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }

            $cm = get_coursemodule_from_id('mooproof', $context->instanceid);
            if (!$cm) {
                continue;
            }

            $DB->delete_records('mooproof_usage', [
                'mooproofid' => $cm->instance,
                'userid' => $userid,
            ]);

            $DB->delete_records('mooproof_submissions', [
                'mooproofid' => $cm->instance,
                'userid' => $userid,
            ]);
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        $cm = get_coursemodule_from_id('mooproof', $context->instanceid);
        if (!$cm) {
            return;
        }

        $userids = $userlist->get_userids();

        list($usersql, $userparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        $params = ['mooproofid' => $cm->instance] + $userparams;

        $DB->delete_records_select('mooproof_usage',
            "mooproofid = :mooproofid AND userid {$usersql}", $params);

        $DB->delete_records_select('mooproof_submissions',
            "mooproofid = :mooproofid AND userid {$usersql}", $params);
    }
}
