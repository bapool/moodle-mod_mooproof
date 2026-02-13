// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 *
 * @package
 * @copyright  2025 Brian A. Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/notification', 'core/str'], function($, Ajax, Notification, Str) {

    return {
        init: function(mooproofid, maxwords, chatmessagelimit) {

            var currentTab = 'paste';
            var fileContent = null;
            var fileName = null;
            // Chat variables
            var currentPaper = null;
            var currentFeedback = null;
            var chatHistory = [];
            var chatMessageCount = 0;

            // Tab switching
            $('.mooproof-tab').on('click', function() {
                var tab = $(this).data('tab');

                // Update active tab
                $('.mooproof-tab').removeClass('active');
                $(this).addClass('active');

                // Show corresponding content
                $('.mooproof-tab-content').removeClass('active');
                $('#' + tab + '-tab').addClass('active');

                currentTab = tab;
            });

            // Word count for paste tab
            $('#mooproof-text-input').on('input', function() {
                var text = $(this).val();
                var words = text.trim().split(/\s+/).filter(function(word) {
                    return word.length > 0;
                }).length;

                $('#word-count').text(words);

                if (maxwords > 0 && words > maxwords) {
                    $('#word-count').css('color', 'red');
                } else {
                    $('#word-count').css('color', '#333');
                }
            });

            // File selection
            $('#select-file-btn').on('click', function() {
                $('#file-input').click();
            });

            $('#file-input').on('change', function() {
                var file = this.files[0];
                if (file) {
                    // Check file extension
                    var ext = file.name.split('.').pop().toLowerCase();
                    if (ext !== 'txt' && ext !== 'docx') {
                        Str.get_string('unsupportedfiletype', 'mooproof').done(function(str) {
                            alert(str);
                        });
                        $(this).val(''); // Clear the input
                        $('#mooproof-file-name').html('');
                        return;
                    }

                    fileName = file.name;
                    Str.get_string('selected', 'mooproof').done(function(str) {
                        $('#mooproof-file-name').html('<strong>' + str + '</strong> ' + fileName);
                    });

                    // Read file content
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        var content = e.target.result;

                        if (ext === 'txt') {
                            // Plain text - use as is
                            fileContent = content;
                        } else if (ext === 'docx') {
                            // DOCX - send as base64
                            fileContent = content.split(',')[1]; // Remove data:... prefix
                            Str.get_string('fileuploadedextract', 'mooproof').done(function(str) {
                                $('#mooproof-file-name').append('<br><em>' + str + '</em>');
                            });
                        }
                    };

                    // Read file appropriately
                    if (ext === 'txt') {
                        reader.readAsText(file);
                    } else {
                        reader.readAsDataURL(file);
                    }
                }
            });

            // Submit for proofing
            $('#mooproof-submit').on('click', function() {
                var papertext = '';
                var filename = '';

                if (currentTab === 'paste') {
                    papertext = $('#mooproof-text-input').val().trim();
                    if (papertext === '') {
                        Str.get_strings([
                            {key: 'error', component: 'mooproof'},
                            {key: 'pleaseentertext', component: 'mooproof'}
                        ]).done(function(strings) {
                            Notification.alert(strings[0], strings[1]);
                        });
                        return;
                    }
                } else {
                    if (!fileContent) {
                        Str.get_strings([
                            {key: 'error', component: 'mooproof'},
                            {key: 'pleaseselectfile', component: 'mooproof'}
                        ]).done(function(strings) {
                            Notification.alert(strings[0], strings[1]);
                        });
                        return;
                    }
                    papertext = fileContent;
                    filename = fileName;
                }

                // Check word count
                var wordCount = papertext.trim().split(/\s+/).filter(function(word) {
                    return word.length > 0;
                }).length;

                if (maxwords > 0 && wordCount > maxwords) {
                    Str.get_strings([
                        {key: 'wordlimitexceededtitle', component: 'mooproof'},
                        {key: 'wordlimitexceeded', component: 'mooproof'}
                    ]).done(function(strings) {
                        var message = strings[1].replace('{$a->count}', wordCount).replace('{$a->max}', maxwords);
                        Notification.alert(strings[0], message);
                    });
                    return;
                }

                // Disable submit button and show loading
                $('#mooproof-submit').prop('disabled', true);
                $('#mooproof-loading-indicator').show();
                $('.mooproof-input-section').hide();

                // Call API using Moodle's Ajax API
                Ajax.call([{
                    methodname: 'mod_mooproof_submit_paper',
                    args: {
                        mooproofid: mooproofid,
                        papertext: papertext,
                        filename: filename
                    }
                }])[0].done(function(response) {
                    $('#mooproof-loading-indicator').hide();

                    if (!response.success || response.error) {
                        Str.get_string('error', 'mooproof').done(function(str) {
                            Notification.alert(str, response.error);
                        });
                        $('.mooproof-input-section').show();

                        // Check if rate limit reached
                        if (response.remaining === 0) {
                            $('#mooproof-submit').prop('disabled', true);
                        } else {
                            $('#mooproof-submit').prop('disabled', false);
                        }

                        // Update the submissions remaining display
                        updateRemainingDisplay(response.remaining);
                    } else if (response.success && response.feedback) {
                        // Store paper and feedback for chat
                        currentPaper = papertext;
                        currentFeedback = response.feedback;
                        chatHistory = [];
                        chatMessageCount = 0;

                        // Display results
                        var formattedFeedback = formatFeedback(response.feedback);
                        $('#proofing-results').html(formattedFeedback);
                        $('#results-section').show();

                        // Show chat section
                        $('#chat-section').show();
                        $('#chat-messages').html('');
                        $('#chat-input').val('').prop('disabled', false);
                        $('#chat-send').prop('disabled', false);
                        updateChatRemaining();

                        // Update the submissions remaining display
                        updateRemainingDisplay(response.remaining);
                    }
                }).fail(function() {
                    $('#mooproof-loading-indicator').hide();
                    Str.get_strings([
                        {key: 'error', component: 'mooproof'},
                        {key: 'failedconnectproofing', component: 'mooproof'}
                    ]).done(function(strings) {
                        Notification.alert(strings[0], strings[1]);
                    });
                    $('.mooproof-input-section').show();
                    $('#mooproof-submit').prop('disabled', false);
                });
            });

            // Update remaining display
            var updateRemainingDisplay = function(remaining) {
                if (remaining >= 0) {
                    Str.get_string('submissionsremaining', 'mooproof', remaining).done(function(str) {
                        var alertDivs = $('#results-section .alert-info').filter(function() {
                            return $(this).text().toLowerCase().indexOf('submission') !== -1;
                        });

                        if (alertDivs.length > 0) {
                            alertDivs.first().html('<strong>' + str + '</strong>');
                        }
                    });
                }
            };

            // Reset button
            $('#mooproof-reset').on('click', function() {
                $('#results-section').hide();
                $('#chat-section').hide();
                $('.mooproof-input-section').show();

                // Clear inputs
                $('#mooproof-text-input').val('');
                $('#word-count').text('0');
                fileContent = null;
                $('#mooproof-file-name').html('');
                $('#file-input').val('');

                // Reset to paste tab
                $('.mooproof-tab').removeClass('active');
                $('.mooproof-tab[data-tab="paste"]').addClass('active');
                $('.mooproof-tab-content').removeClass('active');
                $('#paste-tab').addClass('active');
                currentTab = 'paste';

                // Re-enable submit button if there are submissions remaining
                if ($('#results-section .alert-info:contains("Submissions remaining: 0")').length === 0 &&
                    $('#results-section .alert-info:contains("0")').length === 0) {
                    $('#mooproof-submit').prop('disabled', false);
                }
            });

            // Format feedback with proper HTML
            var formatFeedback = function(text) {
                // Escape HTML first
                var escaped = escapeHtml(text);

                // Handle markdown bold
                escaped = escaped.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
                escaped = escaped.replace(/__([^_]+)__/g, '<strong>$1</strong>');

                // Handle markdown italic
                escaped = escaped.replace(/\*([^*\n]+)\*/g, '<em>$1</em>');

                // Convert double line breaks to paragraphs
                escaped = escaped.replace(/\n\n+/g, '</p><p>');

                // Convert single line breaks to <br>
                escaped = escaped.replace(/\n/g, '<br>');

                // Wrap in paragraph tags
                escaped = '<p>' + escaped + '</p>';

                // Handle numbered lists
                escaped = escaped.replace(/(\d+)\.\s/g, '<br><strong>$1.</strong> ');

                // Handle bullet points
                escaped = escaped.replace(/<br>[-*]\s+/g, '<br>• ');
                escaped = escaped.replace(/<p>[-*]\s+/g, '<p>• ');

                return escaped;
            };

            // Escape HTML
            var escapeHtml = function(text) {
                var div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            };

            // Chat send button
            $('#chat-send').on('click', function() {
                sendChatMessage();
            });

            // Chat input enter key
            $('#chat-input').on('keypress', function(e) {
                if (e.which === 13 && !e.shiftKey) {
                    e.preventDefault();
                    sendChatMessage();
                }
            });

            // Send chat message function
            var sendChatMessage = function() {
                var message = $('#chat-input').val().trim();

                if (message === '' || !currentPaper || !currentFeedback) {
                    return;
                }

                // Check message limit
                if (chatMessageCount >= chatmessagelimit) {
                    Str.get_strings([
                        {key: 'limitreached', component: 'mooproof'},
                        {key: 'maxquestionsreached', component: 'mooproof'}
                    ]).done(function(strings) {
                        Notification.alert(strings[0], strings[1]);
                    });
                    return;
                }

                // Disable input while processing
                $('#chat-input').prop('disabled', true);
                $('#chat-send').prop('disabled', true);

                // Add user message to display
                addChatMessage('user', message);

                // Add to history
                chatHistory.push({
                    role: 'user',
                    content: message
                });

                chatMessageCount++;

                // Clear input
                $('#chat-input').val('');

                // Show thinking indicator
                var thinkingId = 'chat-thinking-' + Date.now();
                Str.get_string('thinking', 'mooproof').done(function(str) {
                    $('#chat-messages').append(
                        '<div class="mooproof-chat-message mooproof-chat-assistant" id="' + thinkingId + '">' +
                        '<em>' + str + '</em></div>'
                    );
                    scrollChatToBottom();
                });

                // Call chat API using Moodle's Ajax API
                Ajax.call([{
                    methodname: 'mod_mooproof_send_chat_message',
                    args: {
                        mooproofid: mooproofid,
                        message: message,
                        papertext: currentPaper,
                        feedback: currentFeedback,
                        chathistory: JSON.stringify(chatHistory)
                    }
                }])[0].done(function(response) {
                    // Remove thinking indicator
                    $('#' + thinkingId).remove();

                    if (!response.success || response.error) {
                        Str.get_string('error', 'mooproof').done(function(str) {
                            Notification.alert(str, response.error);
                        });
                        if (response.remaining === 0) {
                            $('#chat-input').prop('disabled', true);
                            $('#chat-send').prop('disabled', true);
                        }
                    } else if (response.success && response.reply) {
                        // Add assistant reply
                        addChatMessage('assistant', response.reply);

                        // Add to history
                        chatHistory.push({
                            role: 'assistant',
                            content: response.reply
                        });

                        // Update remaining count
                        updateChatRemaining();
                    }

                    // Re-enable input (unless disabled by limit)
                    if (chatMessageCount < chatmessagelimit) {
                        $('#chat-input').prop('disabled', false);
                        $('#chat-send').prop('disabled', false);
                        $('#chat-input').focus();
                    } else {
                        $('#chat-input').prop('disabled', true);
                        $('#chat-send').prop('disabled', true);
                    }
                }).fail(function() {
                    $('#' + thinkingId).remove();
                    Str.get_strings([
                        {key: 'error', component: 'mooproof'},
                        {key: 'failedconnectchat', component: 'mooproof'}
                    ]).done(function(strings) {
                        Notification.alert(strings[0], strings[1]);
                    });
                    $('#chat-input').prop('disabled', false);
                    $('#chat-send').prop('disabled', false);
                });
            };

            // Add message to chat display
            var addChatMessage = function(role, content) {
                var messageClass = role === 'user' ? 'mooproof-chat-user' : 'mooproof-chat-assistant';
                var formattedContent = formatFeedback(content);
                var messageHtml = '<div class="mooproof-chat-message ' + messageClass + '">' +
                                 formattedContent + '</div>';
                $('#chat-messages').append(messageHtml);
                scrollChatToBottom();
            };

            // Update chat remaining count
            var updateChatRemaining = function() {
                var remaining = chatmessagelimit - chatMessageCount;
                Str.get_string('questionsremaining', 'mooproof', remaining).done(function(str) {
                    $('#chat-remaining').text(str);
                });
            };

            // Scroll chat to bottom
            var scrollChatToBottom = function() {
                var chatMessages = $('#chat-messages');
                chatMessages.scrollTop(chatMessages[0].scrollHeight);
            };
        }
    };
});
