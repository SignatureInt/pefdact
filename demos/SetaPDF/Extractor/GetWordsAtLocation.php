<?php
/**
 * This demo extracts words in a specific location of several PDF documents.
 *
 * In this example we extract the invoice number and mark the words and searched location in the PDF document.
 */
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);
date_default_timezone_set('Europe/Berlin');

// list some files
$files = glob('../_files/pdfs/*/eBook-Invoice.pdf');

if (!isset($_GET['display'])) {
    header("Content-Type: text/html; charset=utf-8");
    foreach ($files AS $path) {
        echo '<a href="GetWordsAtLocation.php?f=' . urlencode($path) . '">' . htmlspecialchars($path) . '</a><br />';
    }
}

if (!isset($_GET['f']) || !in_array($_GET['f'], $files)) {
    die();
}

// load and register the autoload function
require_once('../../../library/SetaPDF/Autoload.php');

// load the document
$document = SetaPDF_Core_Document::loadByFilename($_GET['f']);

// the interresting part: initiate an extractor instance
$extractor = new SetaPDF_Extractor($document);

// create a word strategy
$strategy = new SetaPDF_Extractor_Strategy_Word();

// define filter areas
$senderFilter = new SetaPDF_Extractor_Filter_Rectangle(
    new SetaPDF_Core_Geometry_Rectangle(40, 705, 220, 720),
    SetaPDF_Extractor_Filter_Rectangle::MODE_CONTACT
);

// define filter areas
$invoiceNoFilter = new SetaPDF_Extractor_Filter_Rectangle(
    new SetaPDF_Core_Geometry_Rectangle(512, 520, 580, 540),
    SetaPDF_Extractor_Filter_Rectangle::MODE_CONTACT
);

// pass them to the strategy
$strategy->setFilter(new SetaPDF_Extractor_Filter_Multi(array($senderFilter, $invoiceNoFilter)));

// set the strategy
$extractor->setStrategy($strategy);

$words = $extractor->getResultByPageNumber(1);

if (!isset($_GET['display'])) {
    echo '<h1>' . htmlspecialchars($_GET['f']) . '</h1>';

    echo '<table border="1" style="width:50%;float:left;">';
    echo '<tr><th>Word</th><th>llx</th><th>lly</th><th>urx</th><th>ury</th></tr>';

    foreach ($words AS $word) {
        $bounds = $word->getBounds();
        $bounds = $bounds[0];
        echo '<tr>';
        echo '<td>' . htmlentities($word) . '</td>';
        echo '<td>' . $bounds->getLl()->getX() . '</td>';
        echo '<td>' . $bounds->getLl()->getY() . '</td>';
        echo '<td>' . $bounds->getUr()->getX() . '</td>';
        echo '<td>' . $bounds->getUr()->getY() . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    echo '<embed src="GetWordsAtLocation.php?f=' . urlencode($_GET['f']) . '&display=pdf" type="application/pdf" style="width:50%; height: 500px;float:left;"></embed>';

} else {
    // mark the filter areas and words:
    $canvas = $document->getCatalog()->getPages()->getPage(1)->getCanvas();

    // draw the filter rectangles
    $rect = $invoiceNoFilter->getRectangle();
    $canvas
        ->setStrokingColor(array(1, 0, 1))
        ->draw()->rect($rect->getLl()->getX(), $rect->getLl()->getY(), $rect->getWidth(), $rect->getHeight());
    $rect = $senderFilter->getRectangle();
    $canvas
        ->setStrokingColor(array(1, 0, 1))
        ->draw()->rect($rect->getLl()->getX(), $rect->getLl()->getY(), $rect->getWidth(), $rect->getHeight());

    // draw the word boundaries
    foreach ($words AS $word) {
        foreach ($word->getBounds() AS $boundary) {
            $canvas
                ->setStrokingColor(array(0, 1, 0))
                ->draw()->rect(
                    $boundary->getLl()->getX(),
                    $boundary->getLl()->getY(),
                    $boundary->getUr()->getX() - $boundary->getLl()->getX(),
                    $boundary->getUr()->getY() -  $boundary->getLl()->getY()
                );
        }
    }

    $document->setWriter(new SetaPDF_Core_Writer_Http('document.pdf', true));
    $document->save()->finish();
}
