<?php
/**
 * This demo extracts text from single pages from a PDF document.
 */
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);
date_default_timezone_set('Europe/Berlin');

// list some files
$files = glob('../_files/pdfs/*.pdf');
$files = array_merge($files, glob('../_files/pdfs/tektown/*.pdf'));
$files = array_merge($files, glob('../_files/pdfs/tektown/products/*.pdf'));

header("Content-Type: text/html; charset=utf-8");
foreach ($files AS $path) {
    echo '<a href="GetText.php?f=' . urlencode($path) . '#txt">' . htmlspecialchars(basename($path)) . '</a><br />';
}

if (!isset($_GET['f']) || !in_array($_GET['f'], $files)) {
    die();
}

// load and register the autoload function
require_once('../../../library/SetaPDF/Autoload.php');

// load the document
$document = SetaPDF_Core_Document::loadByFilename($_GET['f']);
// get access to its pages
$pages = $document->getCatalog()->getPages();

// ensure a valid page number
$currentPageNo = max(isset($_GET['p']) ? (int)$_GET['p'] : 1, 1);
$currentPageNo = min($currentPageNo, $pages->count());

// display a page number picker
?>
<form type="get" action="#txt">
    <input type="hidden" name="f" value="<?php echo htmlspecialchars($_GET['f']);?>" />

    <h1 id="txt">
        <?php echo htmlspecialchars(basename($_GET['f'])) ?> -
        Page #

        <select name="p" style="font-size: inherit;" onchange="this.form.submit();">
            <?php for ($i = 1; $i <= $pages->count(); $i++): ?>
            <option value="<?php echo $i;?>"<?php if ($i === $currentPageNo):?> selected="selected"<?php endif;?>>
                <?php echo $i;?>
            </option>
            <?php endfor; ?>
        </select>
    </h1>
</form>

<?php
// the interresting part: initate an extractor instance
$extractor = new SetaPDF_Extractor($document);
// get the text of a page
$text = $extractor->getResultByPageNumber($currentPageNo);
?>
<pre><?php echo htmlspecialchars($text); ?></pre>