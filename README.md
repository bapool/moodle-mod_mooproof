# MooProof - AI-Powered Paper Proofreading for Moodle

MooProof is a Moodle resource module that helps students improve their writing through AI-powered proofreading and interactive feedback.

## Description

MooProof provides grade-appropriate feedback on student papers without rewriting them. Students submit papers via paste or file upload, receive AI-generated feedback tailored to their grade level (3-12), and can ask follow-up questions through an integrated chat interface.

### Key Features

- **Grade-Level Feedback**: Teachers select grade levels (3-12) for age-appropriate proofreading
- **Flexible Input**: Students can paste text or upload files (.txt, .doc, .docx, .pdf)
- **Custom Instructions**: Teachers customize AI proofing instructions per resource
- **Interactive Chat**: Students ask questions about feedback they receive
- **Rate Limiting**: Control submission frequency (per hour or per day)
- **Word Limits**: Set maximum word counts per submission
- **Session-Based Chat**: Temporary Q&A to encourage independent learning
- **Submission Tracking**: Monitor student usage and submissions
- **Privacy Compliant**: Full GDPR support with Privacy API
- **Backup/Restore**: Complete course backup and restore support

## Requirements

- **Moodle**: 4.0 or higher (tested on 4.5.7)
- **PHP**: 8.0 or higher
- **Moodle AI Subsystem**: Must be configured with an AI provider
- **AI Provider**: One of the following:
  - OpenAI (GPT-3.5, GPT-4)
  - Anthropic (Claude)
  - Azure OpenAI
  - Local LLM via Ollama
  - Any AI provider supported by Moodle's AI subsystem

## Installation

### Method 1: Via Moodle Interface (Recommended)

1. Download the latest release ZIP file
2. Log into Moodle as administrator
3. Navigate to **Site administration → Plugins → Install plugins**
4. Upload the ZIP file
5. Click **Install plugin from the ZIP file**
6. Follow the on-screen instructions
7. Click **Upgrade Moodle database now**

### Method 2: Manual Installation

1. Extract the ZIP file
2. Copy the `mooproof` folder to `/path/to/moodle/mod/`
3. Visit **Site administration → Notifications**
4. Complete the installation process

### Post-Installation

1. Configure an AI provider:
   - Go to **Site administration → AI → AI providers**
   - Configure and enable your preferred AI provider
   - Test the AI provider to ensure it's working

2. Verify installation:
   - Create a test course
   - Add a MooProof resource
   - Submit a test paper

## Usage

### For Teachers

#### Creating a MooProof Resource

1. Turn editing on in your course
2. Click **Add an activity or resource**
3. Select **MooProof** from the Resources section
4. Configure settings:
   - **Name**: Give the resource a descriptive name
   - **Grade Level**: Select the appropriate grade (3-12)
   - **Proofing Instructions**: Customize or use the default
   - **Rate Limiting**: Set submission limits (optional)
   - **Chat Message Limit**: Set Q&A question limits (default: 10)
   - **Maximum Words**: Set word count limit (default: 5000)

#### Customizing Proofing Instructions

Use the `{gradelevel}` placeholder in your instructions:
```
Proof this paper for grade {gradelevel}. Focus on:
- Grammar and spelling errors
- Sentence structure and clarity
- Paragraph organization
- Citation format (if applicable)

Provide constructive feedback without rewriting the paper.
```

#### Recommended Settings

**For large classes or limited AI budget:**
- Rate Limiting: Enabled
- Submissions per day: 3-5
- Chat Message Limit: 5
- Maximum Words: 3000

**For small classes or intensive support:**
- Rate Limiting: Disabled or generous
- Submissions per day: 10+
- Chat Message Limit: 15-20
- Maximum Words: 5000+

### For Students

#### Submitting a Paper

1. Open the MooProof resource
2. Choose submission method:
   - **Paste Text**: Copy and paste your paper
   - **Upload File**: Upload a .txt, .doc, .docx, or .pdf file
3. Click **Submit for Proofing**
4. Wait for AI feedback (typically 10-30 seconds)

#### Using the Chat Feature

After receiving feedback:

1. Review the AI's suggestions
2. Ask follow-up questions in the chat box:
   - "Can you explain the passive voice issues?"
   - "What's wrong with my thesis statement?"
   - "How can I improve my paragraph structure?"
3. The AI responds with context of your paper and feedback
4. Continue asking until you understand (up to your message limit)

**Note**: Chat is temporary and resets when you submit a new paper or close the page. Take notes on important feedback!

#### Tips for Best Results

- Submit complete drafts, not fragments
- Be specific in your chat questions
- Review feedback before asking questions
- Use the feedback to revise your own work
- Don't ask the AI to rewrite your paper

## Configuration Options

### Resource Settings

| Setting | Description | Default |
|---------|-------------|---------|
| Grade Level | Target grade for feedback (3-12) | 9 |
| Proofing Instructions | Custom AI instructions | See default below |
| Rate Limiting | Enable submission limits | Disabled |
| Rate Limit Period | Hour or day | Day |
| Rate Limit Count | Max submissions per period | 5 |
| Chat Message Limit | Max questions per submission | 10 |
| Maximum Words | Max word count | 5000 |
| Temperature | AI creativity (0.3-0.7) | 0.5 |

### Default Instructions
```
Proof this paper for grade {gradelevel}. Provide the student appropriate 
feedback for this grade level, focusing on grammar, spelling, punctuation, 
and clarity. Do not rewrite the paper - instead, point out areas that need 
improvement and explain why.
```

## Database Tables

MooProof creates three database tables:

- **mdl_mooproof**: Main resource instances
- **mdl_mooproof_usage**: Rate limiting tracking
- **mdl_mooproof_submissions**: Submission history and feedback

## Privacy

MooProof is fully GDPR compliant:

- Implements Moodle Privacy API
- Supports data export for users
- Supports data deletion on request
- Documents all data sent to AI providers
- Stores minimal personal information

### Data Collected

- Paper text (for proofing)
- AI-generated feedback
- Submission timestamps
- Usage statistics (for rate limiting)
- Submissions are automatically deleted after 60 days.

### Data Sent to AI Provider

Via Moodle's AI subsystem:
- Paper text (truncated to prevent token overflow)
- Grade level
- Proofing instructions
- Chat messages (with context)

**Important**: Data handling depends on your configured AI provider. Review your AI provider's privacy policy and terms of service.

## Technical Details

### Plugin Information

- **Type**: mod (Activity/Resource Module)
- **Component**: mod_mooproof
- **Frankenstyle**: mooproof
- **Maturity**: BETA
- **Version**: 1.1 (2025110401)

### APIs Implemented

- ✅ Privacy API (GDPR compliance)
- ✅ Backup/Restore API
- ✅ Settings API
- ✅ Capabilities API
- ✅ Language API

### Supported Features

- Module intro (description)
- Backup and restore
- Privacy API (data export/deletion)
- Resource archetype
- Activity completion (basic)

### File Structure
```
mooproof/
├── amd/                    # JavaScript (AMD format)
│   ├── build/             # Compiled JS
│   └── src/               # Source JS
├── backup/                # Backup and restore
│   └── moodle2/          # Moodle 2.x format
├── classes/               # Auto-loaded classes
│   └── privacy/          # Privacy API
├── db/                    # Database definitions
├── lang/                  # Language strings
│   └── en/               # English (required)
├── pix/                   # Icons
├── chat_service.php      # Chat AJAX handler
├── index.php             # Course listing
├── lib.php               # Core functions
├── mod_form.php          # Settings form
├── proof_service.php     # Proofing AJAX handler
├── styles.css            # CSS styles
├── version.php           # Version info
└── view.php              # Main view
```

## Troubleshooting

### AI Provider Issues

**Problem**: "AI generation failed" error

**Solutions**:
1. Verify AI provider is enabled: Site admin → AI → AI providers
2. Check API key is valid
3. Test AI provider with Moodle's test tool
4. Check error logs for specific error messages

### Chat Not Working

**Problem**: Chat interface doesn't appear or shows "NaN"

**Solutions**:
1. Purge all caches: Site admin → Development → Purge all caches
2. Hard refresh browser (Ctrl+F5)
3. Check JavaScript console for errors (F12)
4. Verify chat_service.php exists and is readable

### File Upload Issues

**Problem**: Can't upload files

**Solutions**:
1. Check PHP settings: `upload_max_filesize` and `post_max_size`
2. Verify file type is supported (.txt, .doc, .docx, .pdf)
3. Check file permissions on moodledata directory

### Rate Limiting Not Working

**Problem**: Rate limits not enforced

**Solutions**:
1. Verify rate limiting is enabled in resource settings
2. Check database table `mdl_mooproof_usage` exists
3. Verify user timezone is set correctly
4. Check automatic cleanup is working (7-day old records)

## Support

- **Bug Tracker**: GitHub Issues (when repository is public)
- **Documentation**: GitHub Wiki (when available)
- **Moodle.org**: Plugin page (after approval)

## Contributing

Contributions are welcome! Areas for improvement:

- Mobile app support
- Additional file format support
- Enhanced analytics/reporting
- Integration with gradebook
- Multi-language support (translations)
- Unit and Behat tests

## License

Copyright © 2025 Brian A. Pool

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.

## Credits

- **Author**: Brian A. Pool
- **Icons**: Feather Icons (https://feathericons.com/) - MIT License
- **Based on**: MooChat activity module concept

## Changelog

### Version 1.1 (2025-11-04)

- Added interactive chat feature for feedback Q&A
- Added configurable chat message limits
- Implemented Privacy API for GDPR compliance
- Implemented Backup/Restore API
- Enhanced AI instructions to prevent paper rewriting
- Improved rate limiting display
- Bug fixes and UI improvements

### Version 1.0 (2025-11-03)

- Initial release
- Grade-level paper proofing (grades 3-12)
- Paste or upload paper submission
- Customizable proofing instructions
- Rate limiting support
- Word count limits
- Submission tracking

## Roadmap

### Planned Features

- [ ] Save chat history (optional setting)
- [ ] Export chat transcripts
- [ ] Teacher dashboard for viewing submissions
- [ ] Submission analytics and reports
- [ ] Integration with Moodle gradebook
- [ ] Mobile app support
- [ ] Additional file formats (ODT, RTF)
- [ ] Plagiarism detection hooks
- [ ] Peer review integration

### Future Considerations

- Advanced AI model selection
- Custom rubric support
- Citation checking
- Style guide enforcement (APA, MLA, Chicago)
- Collaborative editing features

---

**Questions?** Check the documentation or report issues on GitHub (link coming soon).

**Ready to improve student writing?** Install MooProof today!
