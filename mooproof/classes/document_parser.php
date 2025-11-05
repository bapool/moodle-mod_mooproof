<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Document text extraction for MooProof
 *
 * @package    mod_mooproof
 * @copyright  2025 Brian A. Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mooproof;

defined('MOODLE_INTERNAL') || die();

/**
 * Parser for extracting text from various document formats
 */
class document_parser {

    /**
     * Extract text from a file based on its type
     *
     * @param string $filepath Full path to the file
     * @param string $filename Original filename (for extension detection)
     * @return string Extracted text
     * @throws \moodle_exception If file type is unsupported or extraction fails
     */
    public static function extract_text($filepath, $filename) {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        switch ($extension) {
            case 'txt':
                return self::extract_from_txt($filepath);
            case 'docx':
                return self::extract_from_docx($filepath);
            default:
                throw new \moodle_exception('unsupportedfiletype', 'mod_mooproof', '', $extension);
        }
    }

    /**
     * Extract text from plain text file
     *
     * @param string $filepath Path to file
     * @return string Text content
     */
    private static function extract_from_txt($filepath) {
        $content = file_get_contents($filepath);
        
        // Try to detect and convert encoding to UTF-8
        if (function_exists('mb_detect_encoding')) {
            $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
            if ($encoding && $encoding !== 'UTF-8') {
                $content = mb_convert_encoding($content, 'UTF-8', $encoding);
            }
        }
        
        return $content;
    }

    /**
     * Extract text from old Word .doc format
     *
     * @param string $filepath Path to file
     * @return string Text content
     */
    private static function extract_from_doc($filepath) {
        // Old .doc format is complex binary format
        // This is a basic extraction that gets readable text
        $content = file_get_contents($filepath);
        
        if ($content === false) {
            throw new \moodle_exception('cannotreadfile', 'mod_mooproof');
        }

        // Remove binary junk and extract readable text
        // This is not perfect but works for most cases
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $content = preg_replace('/[^\x20-\x7E\n]/', '', $content);
        
        // Clean up multiple spaces and empty lines
        $content = preg_replace('/[ \t]+/', ' ', $content);
        $content = preg_replace('/\n{3,}/', "\n\n", $content);
        
        $content = trim($content);
        
        if (empty($content)) {
            throw new \moodle_exception('notextextracted', 'mod_mooproof', '', 'DOC');
        }
        
        return $content;
    }

    /**
     * Extract text from Word .docx format
     *
     * @param string $filepath Path to file
     * @return string Text content
     */
    private static function extract_from_docx($filepath) {
        // .docx is a ZIP archive containing XML files
        $zip = new \ZipArchive();
        
        if ($zip->open($filepath) !== true) {
            throw new \moodle_exception('cannotopendocx', 'mod_mooproof');
        }

        // The main document content is in word/document.xml
        $content = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($content === false) {
            throw new \moodle_exception('notextextracted', 'mod_mooproof', '', 'DOCX');
        }

        // Parse XML and extract text from <w:t> tags
        $xml = simplexml_load_string($content);
        if ($xml === false) {
            throw new \moodle_exception('invaliddocx', 'mod_mooproof');
        }

        // Register namespace
        $xml->registerXPathNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        
        // Extract all text nodes
        $textnodes = $xml->xpath('//w:t');
        $text = '';
        
        foreach ($textnodes as $textnode) {
            $text .= (string)$textnode;
        }

        // Clean up
        $text = trim($text);
        
        if (empty($text)) {
            throw new \moodle_exception('notextextracted', 'mod_mooproof', '', 'DOCX');
        }

        return $text;
    }

    /**
     * Extract text from PDF format
     *
     * @param string $filepath Path to file
     * @return string Text content
     */
    private static function extract_from_pdf($filepath) {
        // PDF parsing is complex - this is a basic implementation
        // For production use, consider using a library like TCPDF or Smalot\PdfParser
        
        $content = file_get_contents($filepath);
        
        if ($content === false) {
            throw new \moodle_exception('cannotreadfile', 'mod_mooproof');
        }

        // Basic PDF text extraction
        // This works for simple PDFs but may not work for complex ones
        $text = '';
        
        // Split by object streams
        if (preg_match_all("/\(([^)]+)\)/", $content, $matches)) {
            foreach ($matches[1] as $match) {
                // Decode PDF string encoding
                $decoded = self::decode_pdf_string($match);
                $text .= $decoded . ' ';
            }
        }

        // Also try BT/ET blocks (text blocks)
        if (preg_match_all("/BT\s+(.*?)\s+ET/s", $content, $matches)) {
            foreach ($matches[1] as $match) {
                // Extract text from Tj operators
                if (preg_match_all("/\(([^)]+)\)/", $match, $textmatches)) {
                    foreach ($textmatches[1] as $textmatch) {
                        $text .= self::decode_pdf_string($textmatch) . ' ';
                    }
                }
            }
        }

        $text = trim($text);
        
        if (empty($text)) {
            // If we couldn't extract text, inform user
            throw new \moodle_exception('notextextracted', 'mod_mooproof', '', 'PDF');
        }

        return $text;
    }

    /**
     * Decode PDF string encoding
     *
     * @param string $string PDF encoded string
     * @return string Decoded string
     */
    private static function decode_pdf_string($string) {
        // Handle common PDF escape sequences
        $string = str_replace('\\n', "\n", $string);
        $string = str_replace('\\r', "\r", $string);
        $string = str_replace('\\t', "\t", $string);
        $string = str_replace('\\(', '(', $string);
        $string = str_replace('\\)', ')', $string);
        $string = str_replace('\\\\', '\\', $string);
        
        return $string;
    }
}
