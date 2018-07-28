<?php
/**
 * This demo shows you how you can add real clickable link annotations on URLs and e-mail addresses in
 * PDF documents.
 */
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);
date_default_timezone_set('Europe/Berlin');

// load and register the autoload function
require_once('../../../library/SetaPDF/Autoload.php');

// initiate a document instance
$document = SetaPDF_Core_Document::loadByFilename('../_files/pdfs/tektown/Letterhead.pdf');
$document->setWriter(new SetaPDF_Core_Writer_Http('with-links.pdf'));
$pages = $document->getCatalog()->getPages();

// initiate an extractor instance
$extractor = new SetaPDF_Extractor($document);

// define the word strategy and
$strategy = new SetaPDF_Extractor_Strategy_Word();
// set the detail level
$strategy->setDetailLevel(SetaPDF_Extractor_Strategy_Word::DETAIL_LEVEL_GLYPHS);
// ...pass it to the extractor instance
$extractor->setStrategy($strategy);

// get access to the sorter instance of the strategy
$sorter = $strategy->getSorter();

/**
 * Proxy method for itemsJoining() method of the sorter class.
 *
 * @param SetaPDF_Extractor_Result_WordWithGlyphs $left
 * @param SetaPDF_Extractor_Result_WordWithGlyphs $right
 * @return bool
 */
$wordsJoining = function(
    SetaPDF_Extractor_Result_WordWithGlyphs $left, SetaPDF_Extractor_Result_WordWithGlyphs $right
) use ($sorter)
{
    return $sorter->itemsJoining(
        $left->getGlyphs()[count($left->getGlyphs()) - 1],
        $right->getGlyphs()[0]
    );
};

for ($pageNo = 1; $pageNo <= $pages->count(); $pageNo++) {
    /**
     * @var SetaPDF_Extractor_Result_Word $words[]
     */
    $words = $extractor->getResultByPageNumber($pageNo);

    // get access to the page annotations
    $annotations = $pages->getPage($pageNo)->getAnnotations();

    // let's try to find the links
    /**
     * @var SetaPDF_Extractor_Result_WordWithGlyphs[] $words
     */
    for ($i = 0; $i < count($words); $i++) {
        $word = $words[$i];

        switch (strtolower($word->getString())) {
            case 'www':
            case 'http':
            case 'https':
            case 'ftp':
            case 'sftp':
                $linkItems = array($words[$i]);
                while (isset($words[$i + 1]) && $wordsJoining($words[$i], $words[$i + 1])) {
                    $linkItems[] = $words[++$i];
                }

                // if the link ends with a dot or a comma, left it...
                $lastItemString = $linkItems[count($linkItems) - 1]->getString();
                if (strlen($lastItemString) === 1 && strspn($lastItemString, ',.') === 1) {
                    array_pop($linkItems);
                }

                // get the final link target and do some checks...
                $link = join('', $linkItems);

                if ($link === 'www' || $link === 'http')
                    continue;

                $url = parse_url($link);
                if (false === $url) {
                    continue;
                }

                if (!isset($url['scheme'])) {
                    $link = 'http://' . $link;
                }

                $link = filter_var($link, FILTER_VALIDATE_URL);
                if (false === $link) {
                    continue;
                }

                // we have a link, now get the bounds of it and...
                $linkItems = new SetaPDF_Extractor_Result_Collection($linkItems);
                $bounds = $linkItems->getBounds();
                $ll = $bounds[0]->getLl();
                $ur = $bounds[0]->getUr();

                // ...add a link annotation
                $annotation = new SetaPDF_Core_Document_Page_Annotation_Link(
                    array($ll->getX(), $ll->getY(), $ur->getX(), $ur->getY()),
                    $link
                );

                // add a border, to show the link
                $annotation->setColor(array(1, 0, 0));
                $annotation->getBorderStyle()
                    ->setWidth(1)
                    ->setStyle(SetaPDF_Core_Document_Page_Annotation_BorderStyle::DASHED)
                    ->setDashPattern(array(2, 2));

                $annotations->add($annotation);
                break;

            // check for an email
            case '@':
                $emailItems = array();
                // get the left part before the @-sign
                $a = $i;
                while (isset($words[$a - 1]) && $wordsJoining($words[$a - 1], $words[$a])) {
                    $emailItems[] = $words[--$a];
                }

                // re-order
                $emailItems = array_reverse($emailItems);
                $emailItems[] = $words[$i];

                // get the right part after the @-sign
                while (isset($words[$i + 1]) && $wordsJoining($words[$i], $words[$i + 1])) {
                    $emailItems[] = $words[++$i];
                }

                // if the email address ends with a dot or a comma, left it...
                $lastItemString = $emailItems[count($emailItems) - 1]->getString();
                if (strlen($lastItemString) === 1 && strspn($lastItemString, ',.') === 1) {
                    array_pop($emailItems);
                }

                // get the final email and do some checks...
                $email = join('', $emailItems);

                $email = filter_var($email, FILTER_VALIDATE_EMAIL);
                if (false === $email) {
                    continue;
                }

                // we have a valid email address, so get the bounds and...
                $emailItems = new SetaPDF_Extractor_Result_Collection($emailItems);
                $bounds = $emailItems->getBounds();
                $ll = $bounds[0]->getLl();
                $ur = $bounds[0]->getUr();

                // ...add a link annotation
                $annotation = new SetaPDF_Core_Document_Page_Annotation_Link(
                    array($ll->getX(), $ll->getY(), $ur->getX(), $ur->getY()),
                    'mailto:' . $email
                );

                // add a border, to show the link
                $annotation->setColor(array(0, 1, 0));
                $annotation->getBorderStyle()
                    ->setWidth(1)
                    ->setStyle(SetaPDF_Core_Document_Page_Annotation_BorderStyle::DASHED)
                    ->setDashPattern(array(2, 2));

                $annotations->add($annotation);
                break;
        }
    }
}

// save and finish
$document->save()->finish();