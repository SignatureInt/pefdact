<?php
/**
 * This demo rearranges found words in word groups which belong together.
 * The used word group strategy will allow you to extract e.g. columns of text.
 */
// list some files
$files = glob('../_files/pdfs/*.pdf');
$files = array_merge($files, glob('../_files/pdfs/lenstown/*.pdf'));
$files = array_merge($files, glob('../_files/pdfs/lenstown/products/*.pdf'));


if (!isset($_GET['f']) || !in_array($_GET['f'], $files)) {
    foreach ($files AS $path) {
        $name = basename($path);
        echo '<a href="ExtractWordGroups.php?f=' . urlencode($path) . '" target="_blank">';
        echo htmlspecialchars($name);
        echo '</a><br />';
    }

    echo '<br />';
    die();
}

$markOption = 'rectangle';
// $markOption = 'annotation'; // to use annotations, uncomment this line

// load and register the autoload function
require_once('../../../library/SetaPDF/Autoload.php');

// initiate a document instance
$document = SetaPDF_Core_Document::loadByFilename($_GET['f']);

// get the pages
$pages = $document->getCatalog()->getPages();
// initiate a extractor instance
$extractor = new SetaPDF_Extractor($document, new SetaPDF_Extractor_Strategy_WordGroup());

// prepare an array to store the words
$groupsPerPage = [];

// to show the functionality we will mark all the groups on each page
$colors = array(
    array(0, 1, 0),
    array(0, 0, 1),
    array(1, 1, 0),
    array(0, 1, 1),
    array(1, 0, 1),
    array(1, 0, 0),
    array(1, 0, 1)
);

// iterate through all the pages
for ($pageNo = 1, $pageCount = $pages->count(); $pageNo <= $pageCount; $pageNo++) {
    // get the page
    $page = $pages->getPage($pageNo);
    // ensure a clean transformation matrix
    $page->getContents()->encapsulateExistingContentInGraphicState();

    // reset the current color
    reset($colors);

    // iterate through each group
    foreach ($extractor->getResultByPageNumber($pageNo) as $group) {
        // get the group bounds
        $bounds = $group->getBounds()[0];

        // draw a rectangle
        if ($markOption === 'rectangle') {
            // get canvas object for the current page
            $canvas = $page->getCanvas();

            // prepare the canvas to draw lines
            $path = $canvas->path();
            $path->setLineWidth(.5);

            // set the color
            $canvas->setStrokingColor(current($colors));

            $path->moveTo($bounds->getUr()->getX(), $bounds->getUr()->getY())
                ->lineTo($bounds->getUl()->getX(), $bounds->getUl()->getY())
                ->lineTo($bounds->getLl()->getX(), $bounds->getLl()->getY())
                ->lineTo($bounds->getLr()->getX(), $bounds->getLr()->getY())
                ->closeAndStroke();

        // add highlight annotations
        } else {
            $annotation = new SetaPDF_Core_Document_Page_Annotation_Highlight(
                SetaPDF_Core_DataStructure_Rectangle::byRectangle($bounds->getRectangle())
            );
            $annotation->setColor(current($colors));

            // iterate through the group to access the individual words
            $words = [];
            foreach ($group as $word) {
                $words[] = $word->getString();
            }

            $annotation->setContents(implode(' ', $words));
            $page->getAnnotations()->add($annotation);
        }

        // select the next color
        if (next($colors) === false) {
            reset($colors);
        }
    }
}

// set a writer
$document->setWriter(new SetaPDF_Core_Writer_Http(basename($_GET['f']), true));
// save the resulting document
$document->save()->finish();