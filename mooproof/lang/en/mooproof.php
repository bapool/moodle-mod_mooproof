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

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'MooProof';
$string['modulename'] = 'MooProof';
$string['modulenameplural'] = 'MooProofs';
$string['modulename_help'] = 'The MooProof resource allows students to submit papers for AI-powered proofreading and feedback. The AI provides grade-appropriate suggestions without rewriting the paper.';
$string['mooproof:addinstance'] = 'Add a new MooProof resource';
$string['mooproof:view'] = 'View MooProof resource';
$string['mooproof:submit'] = 'Submit papers to MooProof';

// Settings
$string['proofname'] = 'Resource Name';
$string['proofname_help'] = 'Give this proofreading resource a name (e.g., "Essay Proofreader", "Writing Assistant")';
$string['gradelevel'] = 'Grade Level';
$string['gradelevel_help'] = 'Select the grade level for appropriate feedback. The AI will tailor its suggestions to this educational level.';
$string['grade3'] = 'Grade 3';
$string['grade4'] = 'Grade 4';
$string['grade5'] = 'Grade 5';
$string['grade6'] = 'Grade 6';
$string['grade7'] = 'Grade 7';
$string['grade8'] = 'Grade 8';
$string['grade9'] = 'Grade 9';
$string['grade10'] = 'Grade 10';
$string['grade11'] = 'Grade 11';
$string['grade12'] = 'Grade 12';

$string['proofinstructions'] = 'Proofing Instructions';
$string['proofinstructions_help'] = 'Customize the instructions given to the AI. You can use {gradelevel} as a placeholder for the selected grade level.';
$string['defaultinstructions'] = 'Proof this paper for grade {gradelevel}. Provide the student appropriate feedback for this grade level, focusing on grammar, spelling, punctuation, and clarity. Do not rewrite the paper - instead, point out areas that need improvement and explain why.';

// Rate limiting
$string['ratelimiting'] = 'Rate Limiting';
$string['ratelimit_enable'] = 'Enable Rate Limiting';
$string['ratelimit_enable_help'] = 'When enabled, students will be limited to a specific number of submissions per time period.';
$string['ratelimit_period'] = 'Rate Limit Period';
$string['ratelimit_period_help'] = 'Choose whether to limit submissions per hour or per day.';
$string['ratelimit_count'] = 'Maximum Submissions';
$string['ratelimit_count_help'] = 'Number of papers a student can submit during the selected time period.';
$string['period_hour'] = 'Per Hour';
$string['period_day'] = 'Per Day';
$string['submissionsremaining'] = 'Submissions remaining: {$a}';
$string['ratelimitreached'] = 'You have reached your limit of {$a->limit} submissions {$a->period}. Please try again later.';
$string['ratelimitreached_hour'] = 'per hour';
$string['ratelimitreached_day'] = 'per day';

// Advanced settings
$string['advancedsettings'] = 'Advanced Settings';
$string['maxwords'] = 'Maximum Words';
$string['maxwords_help'] = 'Maximum number of words allowed in a submission (0 = unlimited).';
$string['chatmessagelimit'] = 'Chat Message Limit';
$string['chatmessagelimit_help'] = 'Maximum number of chat messages students can send per submission to ask questions about their feedback.';
$string['temperature'] = 'AI Response Style';
$string['temperature_help'] = 'Lower values make feedback more focused and consistent. Higher values make feedback more varied.';
$string['wordlimitexceeded'] = 'Your paper has {$a->count} words, which exceeds the maximum of {$a->max} words.';

// Interface
$string['submitpaper'] = 'Submit Your Paper';
$string['pastetext'] = 'Paste Text';
$string['uploadfile'] = 'Upload File';
$string['pasteplaceholder'] = 'Paste or type your paper here...';
$string['uploaddesc'] = 'Upload a text file (.txt or .docx)';
$string['selectfile'] = 'Select File';
$string['submitforproofing'] = 'Submit for Proofing';
$string['proofingresults'] = 'Proofing Results';
$string['submitanother'] = 'Submit Another Paper';
$string['proofing'] = 'Proofing your paper, please wait...';
// Chat feature
$string['askquestions'] = 'Ask Questions About Your Feedback';
$string['chatplaceholder'] = 'Ask a question about the feedback...';
$string['sendmessage'] = 'Send';
$string['messagesremaining'] = 'Questions remaining: {$a}';
$string['chatlimitreached'] = 'You have reached the maximum number of questions for this submission.';
$string['chatsessionwarning'] = 'Note: This chat is temporary and will disappear when you close this page or submit another paper.';
$string['thinking'] = 'Thinking...';
// Messages
$string['intro'] = 'Introduction';
$string['nomorproofs'] = 'There are no MooProof resources in this course';

// Privacy
$string['privacy:metadata:mooproof_usage'] = 'Information about user submissions to MooProof resources';
$string['privacy:metadata:mooproof_usage:userid'] = 'The ID of the user';
$string['privacy:metadata:mooproof_usage:submissioncount'] = 'Number of papers submitted';
$string['privacy:metadata:mooproof_usage:firstsubmission'] = 'Timestamp of first submission in the current rate limit period';
$string['privacy:metadata:mooproof_usage:lastsubmission'] = 'Timestamp of most recent submission';

$string['privacy:metadata:mooproof_submissions'] = 'Submitted papers and AI-generated feedback';
$string['privacy:metadata:mooproof_submissions:userid'] = 'The ID of the user who submitted the paper';
$string['privacy:metadata:mooproof_submissions:papertext'] = 'The full text of the submitted paper';
$string['privacy:metadata:mooproof_submissions:feedback'] = 'AI-generated feedback on the paper';
$string['privacy:metadata:mooproof_submissions:filename'] = 'Original filename if paper was uploaded as a file';
$string['privacy:metadata:mooproof_submissions:wordcount'] = 'Word count of the submitted paper';
$string['privacy:metadata:mooproof_submissions:gradelevel'] = 'Grade level used for proofing';
$string['privacy:metadata:mooproof_submissions:timecreated'] = 'When the paper was submitted';

$string['privacy:metadata:ai_provider'] = 'MooProof sends paper content to an AI provider via Moodle\'s AI subsystem for proofreading analysis';
$string['privacy:metadata:ai_provider:papertext'] = 'The student\'s paper text is sent to the AI provider for analysis';
$string['privacy:metadata:ai_provider:feedback'] = 'AI-generated feedback is received and stored';
$string['privacy:metadata:ai_provider:chatmessages'] = 'Questions asked in the chat feature are sent to the AI provider with paper and feedback context';
$string['privacy:metadata:ai_provider:gradelevel'] = 'The grade level setting is included to provide age-appropriate feedback';
$string['cleanupoldsubmissions'] = 'Clean up old MooProof submissions';
// Document parser errors
$string['unsupportedfiletype'] = 'Unsupported file type: {$a}';
$string['cannotreadfile'] = 'Cannot read the uploaded file';
$string['cannotopendocx'] = 'Cannot open DOCX file - file may be corrupted';
$string['invaliddocx'] = 'Invalid DOCX file format';
$string['notextextracted'] = 'Could not extract text from {$a} file. The file may be empty, corrupted, or contain only images';
