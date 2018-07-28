<?php
/**
 * This demo extracts customer data and invoice numbers from a "big" PDF document
 * and groups this information by the customers name.
 *
 * The example file includes 10 different invoices for 3 different customers in a random order.
 */
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);
date_default_timezone_set('Europe/Berlin');
ini_set('memory_limit', '256M');

// load and register the autoload function
require_once('../../../library/SetaPDF/Autoload.php');

// initiate a document instance
$document = SetaPDF_Core_Document::loadByFilename('../_files/pdfs/tektown/invoices/All.pdf');

// initiate an extractor instance
$extractor = new SetaPDF_Extractor($document);

// get the plain strategy shich is the default strategy
$strategy = $extractor->getStrategy();

// define a rectangle filter for the invoice recipient name
$recipientNameFilter = new SetaPDF_Extractor_Filter_Rectangle(
    new SetaPDF_Core_Geometry_Rectangle(40, 665, 260, 700),
    SetaPDF_Extractor_Filter_Rectangle::MODE_CONTACT
);

// define another rectangle filter for the invoice number
$invoiceNofilter = new SetaPDF_Extractor_Filter_Rectangle(
    new SetaPDF_Core_Geometry_Rectangle(512, 520, 580, 540),
    SetaPDF_Extractor_Filter_Rectangle::MODE_CONTACT
);

// pass the filters to the strategy by using a filter chain
$strategy->setFilter(new SetaPDF_Extractor_Filter_Multi(array($recipientNameFilter, $invoiceNofilter)));

// prepare the resulting array
$invoicesByCustomerName = array();

// now walk through the pages and ...
$pages = $document->getCatalog()->getPages();
for ($pageNo = 1; $pageNo <= $pages->count(); $pageNo++) {

    // extract the content found by the specific filters.
    $result = $extractor->getResultByPageNumber($pageNo);
    // create single lines
    $result = explode("\n", $result);

    // the invoice number can be found in the last item
    $invoiceNo = array_pop($result);
    // the name can be found in the first item
    $name = array_shift($result);
    // the optinal company name is left over
    $companyName = array_shift($result);

    // create a unique key
    $key = $name . '|' . $companyName;

    // save the name and company data and prepare the reuslt
    if (!isset($invoicesByCustomerName[$key])) {
        $invoicesByCustomerName[$key] = array(
            'name'        => $name,
            'companyName' => $companyName,
            'invoices'    => array()
        );
    }

    // add the invoice and page number to the result
    $invoicesByCustomerName[$key]['invoices'][] = array(
        'invoiceNo' => $invoiceNo,
        'pageNo' => $pageNo
    );
}

// output the resolved data:
foreach ($invoicesByCustomerName AS $customerData):?>

    <h1>Customer: <?php echo htmlentities($customerData['name']);?> / <?php echo htmlentities($customerData['companyName']);?></h1>
    <ul>
        <?php foreach($customerData['invoices'] AS $invoice):?>
        <li>
            Invoice Number #<?php echo htmlentities($invoice['invoiceNo']); ?>
            on page #<?php echo $invoice['pageNo']; ?>.
        </li>

        <?php endforeach; ?>
    </ul>

<?php endforeach; ?>