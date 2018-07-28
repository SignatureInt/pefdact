<?php
/**
 * This demo extracts data of various documents of flat PDF forms (non dynamic forms).
 * The field locations are resolved by a real PDF form template.
 */
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);
date_default_timezone_set('Europe/Berlin');

// load and register the autoload function
require_once('../../../library/SetaPDF/Autoload.php');

// Let's load a template in which we'd drawn simple form fields to get the names and
// coordinates of the areas we want to extract
$template = SetaPDF_Core_Document::loadByFilename('../_files/pdfs/Subscription-tekMag-form-template.pdf');
$pages = $template->getCatalog()->getPages();

// group the found fields by pages
$fieldsPerPage = [];

for ($pageNo = 1, $pageCount = $pages->count(); $pageNo <= $pageCount; $pageNo++) {
    $fieldsPerPage[$pageNo] = [];
    $page = $pages->getPage($pageNo);
    $annotations = $page->getAnnotations();

    // get all widget annotations
    $widgetAnnotations = $annotations->getAll(SetaPDF_Core_Document_Page_Annotation::TYPE_WIDGET);
    foreach ($widgetAnnotations AS $widgetAnnotation) {
        $fieldName = SetaPDF_Core_Document_Catalog_AcroForm::resolveFieldName($widgetAnnotation->getDictionary());
        $fieldsPerPage[$pageNo][$fieldName] = $widgetAnnotation->getRect()->getRectangle();
    }
}

// clean up
$template->cleanUp();
unset($page, $pages, $template);

// let's extract the data from these filese...
foreach ([
    '../_files/pdfs/lenstown/Subscription-tekMag-filled-flat.pdf',
    '../_files/pdfs/camtown/Subscription-tekMag-filled-flat.pdf',
    '../_files/pdfs/etown/Subscription-tekMag-filled-flat.pdf',
] AS $file) {

    // load the document
    $document = SetaPDF_Core_Document::loadByFilename($file);

    // create a plain strategy
    $strategy = new SetaPDF_Extractor_Strategy_Plain();

    // create an extractor instance
    $extractor = new SetaPDF_Extractor($document, $strategy);

    // iterate through the pages we want to extract data from.
    foreach ($fieldsPerPage AS $pageNo => $fields) {

        // define a multi filter
        $filter = new SetaPDF_Extractor_Filter_Multi();
        // create additional rectangle filters named by the found fields and ...
        foreach ($fields AS $name => $rect) {
            $fieldFilter = new SetaPDF_Extractor_Filter_Rectangle(
                $rect, SetaPDF_Extractor_Filter_Rectangle::MODE_CONTACT, $name
            );

            // ...pass them to the multi filter
            $filter->addFilter($fieldFilter);
        }
        // set the filter
        $strategy->setFilter($filter);

        // get the result
        $result = $extractor->getResultByPageNumber($pageNo);

        // clean up the result because the values lay on top of other text which needs to be removed
        $result = array_map(function ($s) {
            $s = str_replace("", '', $s);
            return trim($s, "\n.");
        }, $result);

        echo "<pre>";
        var_dump($result);
        echo "</pre>";
    }
}