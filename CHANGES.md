# MooProof Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.5.3] - 2026-04-27

### Fixed
- Activity completion now triggers correctly when a student submits a paper for proofreading
  - `submit_paper.php` now calls `completion_info::update_state()` after a successful submission is saved
  - The completion trigger respects the teacher-configured "Require submission" checkbox in activity settings
  - Previously, the `completionsubmit` rule and `custom_completion.php` class were in place but were never called at submission time

## [1.5] - 2026-01-28

### Added
- **Teacher submission history tab** — Teachers and managers now see a "Student Submissions"
  tab on every MooProof activity page
  - Lists all students who have submitted, with submission count, last submission date, and a View Details link
  - Detail view groups submissions by date with expand/collapse accordions
  - Each submission shows: submitted paper text, AI feedback, and the full follow-up chat conversation
  - New `mooproof_chat_messages` database table persists all student ↔ AI chat turns per submission
  - New `mod/mooproof:viewhistory` capability (teachers, editing teachers, managers)

### Changed
- Proofing Instructions field now uses the Moodle editor (TinyMCE/Atto) instead of a plain textarea
  - Supports fullscreen editing mode for easier prompt authoring
  - Stores format alongside text (proofinstructionsformat DB column added)
  - HTML tags are stripped before instructions are sent to the AI service

## [1.4] - 2025-11-13

### Changed
- Migrated view.php to use Moodle Templates (Mustache) and Output API
  - Created view_page.mustache template for modern HTML rendering
  - Implemented renderer class (mod_mooproof\output\renderer)
  - Created view_page renderable class for data preparation
  - Removed legacy echo-based HTML generation
  - Improved code maintainability and adherence to Moodle standards
  - Resolved Moodle Plugin Contribution Checklist requirement for templates/Output API

### Fixed
- Namespaced all CSS selectors to prevent conflicts with other plugins and Moodle core
- Replaced deprecated `print_error()` function with `moodle_exception` in view.php
- Added missing 'missingidandcmid' language string
- Removed hardcoded language strings from JavaScript (proof.js)
  - All user-facing strings now use get_string() via Moodle's string API
  - Strings properly passed from PHP to JavaScript using strings_for_js()
- Migrated AJAX implementation to External Services API
  - Replaced direct PHP endpoints (proof_service.php, chat_service.php) with external services
  - Created mod_mooproof_submit_paper external service class
  - Created mod_mooproof_send_chat_message external service class
  - Updated JavaScript to use Moodle's Ajax API (core/ajax)
  - Improved security with automatic parameter validation and capability checks

### Added
- Implemented proper Moodle event logging for module access tracking
  - Added `course_module_viewed` event class
  - Added `course_module_instance_list_viewed` event class
  - Event triggers in view.php and index.php for proper activity access logging
- Added db/services.php to register external web services
- Added templates directory with Mustache template files
- Added output classes for renderer pattern implementation
- Added 'wordcount' language string

### Technical Notes
- All changes comply with Moodle Plugin Contribution Checklist requirements
- No database schema changes required
- Backward compatible with existing MooProof installations

## [1.2] - 2025-11-05

### Added
- Automatic cleanup of submissions older than 60 days
- Scheduled task to remove orphaned records from deleted resources
- Cleanup task runs daily at 2:00 AM
- Database cleanup for privacy compliance

### Changed
- Improved data retention policy documentation
- Updated Privacy API to reflect retention period

## [1.1] - 2025-11-04

### Added
- Interactive chat feature for Q&A about feedback
- Configurable chat message limits per submission (default: 10)
- Session-based temporary chat (resets on new submission)
- Privacy API implementation for GDPR compliance
- Backup and Restore API implementation
- Full context awareness in chat (paper + feedback + history)
- Warning message about temporary chat sessions
- Message counter showing remaining questions

### Changed
- Enhanced AI instructions to prevent paper rewriting in chat
- Improved rate limiting display (removed duplicate counters)
- Updated feedback display to include chat interface
- Reset button now clears chat history

### Fixed
- Chat message counter now displays correctly (fixed NaN issue)
- Chat input properly enables/disables based on message limit
- Submission counter no longer displays twice
- JavaScript initialization properly receives all parameters

### Security
- Added sesskey verification for chat requests
- Implemented capability checks for chat access
- Validated all user inputs in chat service

## [1.0] - 2025-11-03

### Added
- Initial release of MooProof
- AI-powered paper proofreading with grade-level feedback (grades 3-12)
- Two submission methods: paste text or upload file
- File format support: .txt, .doc, .docx, .pdf
- Customizable proofing instructions per resource
- Grade level selection (3-12)
- Rate limiting options (per hour or per day)
- Word count limits (default: 5000 words)
- Submission tracking and usage statistics
- Temperature control for AI creativity
- Real-time feedback display
- Activity module with proper Moodle integration
- Course module support
- Basic capability system

### Technical
- Moodle 4.0+ compatibility
- Integration with Moodle AI subsystem
- Support for multiple AI providers (OpenAI, Anthropic, Azure, Ollama)
- Database tables: mooproof, mooproof_usage, mooproof_submissions
- AMD JavaScript modules
- Responsive CSS design
- File upload with proper validation
- Cross-database compatibility using Moodle DML API

### Documentation
- English language strings
- Help buttons for all settings
- Installation instructions
- User guide

## Version History Summary

- **1.5.3 (2026-04-27)**: Fixed activity completion triggering on paper submission
- **1.5 (2026-01-28)**: Added teacher submission history tab and persistent chat history
- **1.4 (2025-11-13)**: Migrated to Mustache templates and External Services API
- **1.2 (2025-11-05)**: Added 60-day automatic cleanup
- **1.1 (2025-11-04)**: Added chat feature, Privacy API, Backup/Restore
- **1.0 (2025-11-03)**: Initial release with core proofreading features

## Migration Notes

### From 1.5 to 1.5.3
- No database changes
- No action required from administrators
- Existing completion data is unaffected

### From 1.1 to 1.2
- No database changes
- New scheduled task automatically registered
- No action required from administrators

### From 1.0 to 1.1
- Database upgrade adds `chatmessagelimit` field to mooproof table
- Existing resources get default value of 10 messages
- No data loss
- Automatic upgrade via Moodle's standard process

## Known Issues

### Version 1.5.3
- None currently

## Support

- **Bug Reports**: GitHub Issues (coming soon)
- **Documentation**: README.md and GitHub Wiki
- **Moodle.org**: Plugin page (pending approval)

## Credits

**Author**: Brian A. Pool

**Based On**: MooChat activity module concept

**AI Providers**:
- Supports any AI provider compatible with Moodle's AI subsystem
- Tested with OpenAI, Anthropic Claude, and Azure OpenAI

## License

GNU General Public License v3.0 or later

See LICENSE file for details.

---

For detailed installation and usage instructions, see [README.md](README.md)
