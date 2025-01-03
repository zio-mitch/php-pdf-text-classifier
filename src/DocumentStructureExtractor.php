<?php

namespace Multidialogo\PdfTextClassifier;


use Smalot\PdfParser\Parser;

/**
 * TODO:
 *
 * Error Handling: Add try-catch blocks around file parsing and element extraction to manage exceptions gracefully.
 * Encoding and Special Characters: Ensure text encoding is handled correctly, especially for multilingual or special character content.
 * Improved Table Detection: If the document contains complex tables, consider implementing grid detection using bounding boxes or external libraries.
 * Memory Efficiency: Large PDFs may consume significant memory. Optimizations like streaming or chunk-based processing might help.
 * Extensibility: Allow configuration for font-size thresholds and table detection parameters.
 */

class DocumentStructureExtractor
{
    private const int MIN_SIZE_FOR_TITLE = 14;

    private const int MAX_SIZE_FOR_NOTE = 10;

    public static function getStructuredPages($filePath): array
    {
        // Initialize the PDF parser
        $parser = new Parser();

        // Parse the PDF file
        $pdf = $parser->parseFile($filePath);

        // Get the pages of the PDF
        $pages = $pdf->getPages();

        $structuredPages = [];

        foreach ($pages as $pageNumber => $page) {

            $structuredText = new StructuredText($page->getText());

            // Optionally, get the positions, fonts, etc., if available
            $elements = $page->getElements();

            foreach ($elements as $element) {
                $textContent = $element->getText();
                $fontSize = $element->getFontSize();

                // Check if the element might be a title (based on font size or position)
                if ($fontSize > static::MIN_SIZE_FOR_TITLE) {
                    $structuredText->addTitle($textContent);
                } elseif ($fontSize < static::MAX_SIZE_FOR_NOTE) {
                    $structuredText->addNote($textContent);
                } else {
                    // Consider this regular text or part of a table
                    $structuredText->addText($textContent);
                }

                // Handle table data if it's detected (based on positions, etc.)
                // For example, check if text is arranged in a grid-like structure
                $position = $element->getPosition();

                if (isset($position['x']) && isset($position['y']) && $position['x'] < 100) {
                    $structuredText->addTable($textContent);
                }
            }

            $structuredPages[] = new StructuredPage($pageNumber, $structuredText);
        }

        return $structuredPages;
    }
}