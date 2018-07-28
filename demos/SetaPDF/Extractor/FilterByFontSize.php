<?php
/**
 * Filters words by a specific font size.
 */
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);
date_default_timezone_set('Europe/Berlin');

header("Content-Type: text/html; charset=utf-8");

// load and register the autoload function
require_once('../../../library/SetaPDF/Autoload.php');

// create a document instance
$document = SetaPDF_Core_Document::loadByFilename('../_files/pdfs/Brand-Guide.pdf');

// create an extractor instance
$extractor = new SetaPDF_Extractor($document);

// create the word strategy...
$strategy = new SetaPDF_Extractor_Strategy_Word();
// ...and pass it to the extractor
$extractor->setStrategy($strategy);

// creat an instance of the font size filter
$filter = new SetaPDF_Extractor_Filter_FontSize(24); // try 24, 18, 12
// ...pass it to the strategy
$strategy->setFilter($filter);

// get access to the document pages
$pages = $document->getCatalog()->getPages();

// iterate over the pages and extract the words:
for ($pageNo = 1; $pageNo <= $pages->count(); $pageNo++) {

    echo '<h1>Page #' . $pageNo . '</h1>';
    $words = $extractor->getResultByPageNumber($pageNo);

    foreach ($words as $word) {
        echo '<li>' . htmlspecialchars($word->getString()) . '</li>';
    }
}