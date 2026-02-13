MooProof - AI-Powered Paper Proofreading Resource for Moodle
==============================================================

Version: 1.0
Requires: Moodle 4.0 or higher
License: GNU GPL v3 or later

Description
-----------
MooProof is a Moodle resource module that allows students to submit papers for AI-powered proofreading and feedback. The AI provides grade-appropriate suggestions and corrections without rewriting the student's work.

Features
--------
* Grade level selection (grades 3-12) for appropriate feedback
* Customizable proofing instructions for teachers
* Support for pasted text or file uploads (.txt, .doc, .docx, .pdf)
* Rate limiting to control usage (per hour or per day)
* Word count limits
* Submission tracking and history
* Uses Moodle's core AI system for processing

Installation
------------
1. Extract the zip file to your Moodle's mod/ directory
2. Rename the folder to 'mooproof' if necessary
3. Visit Site Administration > Notifications to complete installation
4. Ensure you have configured an AI provider in Moodle's AI settings

Usage
-----
Teachers:
1. Add a MooProof resource to your course
2. Configure the grade level and proofing instructions
3. Set rate limits if desired
4. Students can now access the resource to submit papers

Students:
1. Click on the MooProof resource
2. Either paste your paper or upload a file
3. Click "Submit for Proofing"
4. Review the AI feedback
5. Submit additional papers as allowed by rate limits

Configuration Options
--------------------
* Resource Name: Identify the proofreader
* Grade Level: Select appropriate educational level (3-12)
* Proofing Instructions: Customize AI behavior
* Rate Limiting: Control submission frequency
* Maximum Words: Limit paper length
* Temperature: Adjust AI creativity level

Requirements
------------
* Moodle 4.0 or higher
* PHP 8.0 or higher
* Configured AI provider in Moodle

Support
-------
For issues or questions, please contact your Moodle administrator.

License
-------
This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

Credits
-------
Developed as a companion to MooChat activity module.
Icons from Feather Icons (https://feathericons.com/)
