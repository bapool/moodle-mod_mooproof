// This file is part of Moodle - http://moodle.org/

define(['jquery', 'core/ajax', 'core/notification'], function($, Ajax, Notification) {
    
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
            console.log('Chat message limit:', chatmessagelimit);
            
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
                        alert('Only .txt and .docx files are supported. Please convert your document to .docx format.');
                        $(this).val(''); // Clear the input
                        $('#file-name').html('');
                        return;
                    }
                    
                    fileName = file.name;
                    $('#file-name').html('<strong>Selected:</strong> ' + fileName);
                    
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
                            $('#file-name').append('<br><em>File uploaded - text will be extracted on server</em>');
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
                        Notification.alert('Error', 'Please enter some text to proof.', 'OK');
                        return;
                    }
                } else {
                    if (!fileContent) {
                        Notification.alert('Error', 'Please select a file to upload.', 'OK');
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
                    Notification.alert('Word Limit Exceeded', 
                        'Your paper has ' + wordCount + ' words, which exceeds the maximum of ' + maxwords + ' words.', 
                        'OK');
                    return;
                }
                
                // Disable submit button and show loading
                $('#mooproof-submit').prop('disabled', true);
                $('#loading-indicator').show();
                $('.mooproof-input-section').hide();
                
                // Call API
                $.ajax({
                    url: M.cfg.wwwroot + '/mod/mooproof/proof_service.php',
                    method: 'POST',
                    data: {
                        mooproofid: mooproofid,
                        papertext: papertext,
                        filename: filename,
                        sesskey: M.cfg.sesskey
                    },
                    dataType: 'json',
                    success: function(response) {
                        $('#loading-indicator').hide();
                        
                        if (response.error) {
                            Notification.alert('Error', response.error, 'OK');
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
                            
                            // *** FIX: Update the submissions remaining display ***
                            updateRemainingDisplay(response.remaining);
                        }
                    },
                    error: function() {
                        $('#loading-indicator').hide();
                        $('.mooproof-input-section').show();
                        $('#mooproof-submit').prop('disabled', false);
                        Notification.alert('Error', 'Failed to connect to proofing service', 'OK');
                    }
                });
            });
            
            // *** NEW FUNCTION: Update the submissions remaining display ***
            var updateRemainingDisplay = function(remaining) {
                if (remaining >= 0) {
                    // Find the alert div that shows submissions remaining
                    var alertDiv = $('.alert.alert-info').filter(function() {
                        return $(this).text().toLowerCase().indexOf('submission') !== -1;
                    });
                    
                    if (alertDiv.length > 0) {
                        // Update the text - you may need to adjust this based on your language string
                        var newText = 'Submissions remaining: ' + remaining;
                        alertDiv.text(newText);
                        
                        // If no submissions left, disable the submit button
                        if (remaining === 0) {
                            $('#mooproof-submit').prop('disabled', true);
                        }
                    }
                }
            };
            
            // Reset button
            $('#mooproof-reset').on('click', function() {
                // Hide results and show input
                $('#results-section').hide();
                $('.mooproof-input-section').show();
                
                // Clear inputs
                $('#mooproof-text-input').val('');
                $('#word-count').text('0');
                $('#file-input').val('');
                $('#file-name').html('');
                fileContent = null;
                fileName = null;
                
                // Reset to paste tab
                $('.mooproof-tab').removeClass('active');
                $('.mooproof-tab[data-tab="paste"]').addClass('active');
                $('.mooproof-tab-content').removeClass('active');
                $('#paste-tab').addClass('active');
                currentTab = 'paste';
                
                // Re-enable submit button if there are submissions remaining
                // Check if the alert shows 0 remaining
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
                    Notification.alert('Limit Reached', 
                        'You have reached the maximum number of questions for this submission.', 
                        'OK');
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
                $('#chat-messages').append(
                    '<div class="mooproof-chat-message mooproof-chat-assistant" id="' + thinkingId + '">' +
                    '<em>Thinking...</em></div>'
                );
                scrollChatToBottom();
                
                // Call chat API
                $.ajax({
                    url: M.cfg.wwwroot + '/mod/mooproof/chat_service.php',
                    method: 'POST',
                    data: {
                        mooproofid: mooproofid,
                        message: message,
                        papertext: currentPaper,
                        feedback: currentFeedback,
                        chathistory: JSON.stringify(chatHistory),
                        sesskey: M.cfg.sesskey
                    },
                    dataType: 'json',
                    success: function(response) {
                        // Remove thinking indicator
                        $('#' + thinkingId).remove();
                        
                        if (response.error) {
                            Notification.alert('Error', response.error, 'OK');
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
                        
                        
                    },
                    error: function() {
                        $('#' + thinkingId).remove();
                        Notification.alert('Error', 'Failed to connect to chat service', 'OK');
                        $('#chat-input').prop('disabled', false);
                        $('#chat-send').prop('disabled', false);
                    }
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
                $('#chat-remaining').text('Questions remaining: ' + remaining);
            };
            
            // Scroll chat to bottom
            var scrollChatToBottom = function() {
                var chatMessages = $('#chat-messages');
                chatMessages.scrollTop(chatMessages[0].scrollHeight);
            };
        }
    };
});
