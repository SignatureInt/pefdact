<?php
/**
 * This demo allows you to highlight words in a PDF document.
 */
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);
ini_set('memory_limit', '128M');
date_default_timezone_set('Europe/Berlin');

// load and register the autoload function
require_once('../../../library/SetaPDF/Autoload.php');

// we use a session to cache the words temporary to speed things up
// Do NOT do this in a production environment, it's just a quick idea for caching the result temporarily!
session_start();

// let's display a text field for the search word
if (!isset($_GET['word'])): ?>
    <input type="text" id="word" />
    <input type="button" value="search" onclick="document.getElementById('viewer').src='HighlightWords.php?word=' + document.getElementById('word').value;" />
    <embed id="viewer" src="HighlightWords.php?word=" type="application/pdf" style="width:100%; height: 90%;"></embed>

<?php
    die();
endif;

// load the document
$document = SetaPDF_Core_Document::loadByFilename('../_files/pdfs/tektown/Terms-and-Conditions.pdf');

// check if there's a word given
$word = (string)$_GET['word'];
if ($word != '') {
    // initate an extractor instance
    $extractor = new SetaPDF_Extractor($document);

    // create the word extraction strategy and pass it to the extractor instance
    $strategy = new SetaPDF_Extractor_Strategy_Word();
    $extractor->setStrategy($strategy);

    // get access to the documents pages instance
    $pages = $document->getCatalog()->getPages();

    // check if the words are saved in the temporary cache
    if (isset($_SESSION['wordsPerPage'])) {
        $wordsPerPage = $_SESSION['wordsPerPage'];
    // otherwise...
    } else {
        $wordsPerPage = $_SESSION['wordsPerPage'] = array();

        // walk through the pages and extract the word
        for ($pageNo = 1; $pageNo <= $pages->count(); $pageNo++) {
            $words = $extractor->getResultByPageNumber($pageNo);
            // restrucutre the data to be less memory intensive in the "cache"
            foreach ($words AS $_word) {
                $wordsPerPage[$pageNo][] = array(
                    'string' => $_word->getString(),
                    'bounds' => $_word->getBounds()
                );
            }
        }

        // cache the words per page
        $_SESSION['wordsPerPage'] = $wordsPerPage;
        unset($words);
    }

    // a simple counter
    $found = 0;

    // walk through the pages...
    for ($pageNo = 1; $pageNo <= $pages->count(); $pageNo++) {
        // get access to the pages annotations instance
        $annotations = $pages->getPage($pageNo)->getAnnotations();

        // iterate over the words
        foreach ($wordsPerPage[$pageNo] AS $_word) {
            // check for a match
            if ($_word['string'] != $word) {
                continue;
            }

            // if a match occurs, create a highlight annotation and add it to the pages annotations instance
            $bounds = $_word['bounds'];
            foreach ($bounds AS $bound) {
                $rect = new SetaPDF_Core_Geometry_Rectangle($bound->getLl(), $bound->getUr());
                $rect = SetaPDF_Core_DataStructure_Rectangle::byRectangle($rect);

                $annotation = new SetaPDF_Core_Document_Page_Annotation_Highlight($rect);
                $annotation->setColor(array(1, 1, 0));
                $annotation->setContents('Match #' . (++$found));
                $annotations->add($annotation);
            }
        }
    }
}

// set a writer
$document->setWriter(new SetaPDF_Core_Writer_Http('document.pdf', true));
// save the resulting document
$document->save()->finish();