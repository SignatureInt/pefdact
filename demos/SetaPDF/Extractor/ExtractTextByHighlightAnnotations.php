<?php
/**
 * This demo extracts text which are marked with highlight annotations.
 */
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);
ini_set('memory_limit', '128M');
date_default_timezone_set('Europe/Berlin');

header("Content-Type: text/html; charset=utf-8");

// load and register the autoload function
require_once('../../../library/SetaPDF/Autoload.php');

// create a document instance
$document = SetaPDF_Core_Document::loadByFilename('../_files/pdfs/Brand-Guide - with-comments.pdf');
// initate an extractor instance
$extractor = new SetaPDF_Extractor($document);

// get page documents pages object
$pages = $document->getCatalog()->getPages();

// we are going to save the results in this variable
$results = array();

// iterate over all pages
for ($pageNo = 1, $pageCount = $pages->count(); $pageNo <= $pageCount; $pageNo++) {
    // get the page object
    $page = $pages->getPage($pageNo);
    // get the highlight annotations
    $annotations = $page->getAnnotations()->getAll(SetaPDF_Core_Document_Page_Annotation::TYPE_HIGHLIGHT);

    // create a strategy instance
    $strategy = new SetaPDF_Extractor_Strategy_ExactPlain();
    // create a multi filter instance
    $filter = new SetaPDF_Extractor_Filter_Multi();
    // and pass it to the strategy
    $strategy->setFilter($filter);

    // iterate over all highlight annotations
    foreach ($annotations AS $tmpId => $annotation) {
        /**
         * @var SetaPDF_Core_Document_Page_Annotation_Highlight $annotation
         */
        $name = 'P#' . $pageNo . '/HA#' . $tmpId;
        if ($annotation->getName()) {
            $name .= ' (' . $annotation->getName() . ')';
        }

        // iterate over the quad points to setup our filter instances
        $quadpoints = $annotation->getQuadPoints();
        for ($pos = 0, $c = count($quadpoints); $pos < $c; $pos += 8) {
            $llx = min($quadpoints[$pos + 0], $quadpoints[$pos + 2], $quadpoints[$pos + 4], $quadpoints[$pos + 6]) - 1;
            $urx = max($quadpoints[$pos + 0], $quadpoints[$pos + 2], $quadpoints[$pos + 4], $quadpoints[$pos + 6]) + 1;
            $lly = min($quadpoints[$pos + 1], $quadpoints[$pos + 3], $quadpoints[$pos + 5], $quadpoints[$pos + 7]) - 1;
            $ury = max($quadpoints[$pos + 1], $quadpoints[$pos + 3], $quadpoints[$pos + 5], $quadpoints[$pos + 7]) + 1;

            // Add a new rectangle filter to the multi filter instance
            $filter->addFilter(
                new SetaPDF_Extractor_Filter_Rectangle(
                    new SetaPDF_Core_Geometry_Rectangle($llx, $lly, $urx, $ury),
                    SetaPDF_Extractor_Filter_Rectangle::MODE_CONTACT,
                    $name
                )
            );
        }
    }

    // if no filters for this page defined, ignore it
    if (0 === count($filter->getFilters())) {
        continue;
    }

    // pass the strategy to the extractor instance
    $extractor->setStrategy($strategy);
    // and get the results by the current page number
    $result = $extractor->getResultByPageNumber($pageNo);
    if ($result === '')
        continue;

    $results[$pageNo] = $result;
}

// debug output
foreach ($results AS $pageNo => $annotationResults) {
    echo '<h1>Page No #' . $pageNo . '</h1>';
    echo '<table border="1"><tr><th>Name</th><th>Text</th></tr>';
    foreach ($annotationResults AS $name => $text) {
        echo '<tr>';
        echo '<td>' . $name . '</td>';
        echo '<td><pre>' . $text . '</pre></td>';
        echo '</tr>';
    }

    echo '</table>';
}
