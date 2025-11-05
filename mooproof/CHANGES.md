# MooProof Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Planned
- Mobile app support
- Save chat history (optional setting)
- Export chat transcripts
- Teacher dashboard for viewing submissions
- Integration with Moodle gradebook
- Additional file format support (ODT, RTF)

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

- **1.2 (2025-11-05)**: Added 60-day automatic cleanup
- **1.1 (2025-11-04)**: Added chat feature, Privacy API, Backup/Restore
- **1.0 (2025-11-03)**: Initial release with core proofreading features

## Migration Notes

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

### Version 1.2
- None currently

### Version 1.1
- Chat history is not saved (by design - session-based only)
- Chat messages cannot be exported
- Teachers cannot view student chat sessions

### Version 1.0
- No interactive follow-up questions (resolved in 1.1)

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
