<?php

class Kaba_PdfBeautifier_Model_Sales_Order_Pdf_Invoice extends Mage_Sales_Model_Order_Pdf_Invoice
{
    const FONT_DIR = '/media/pdfbeautifier/font/';
    const STANDARD_COLOR = '#0099ff';

    /**
     * Retrieve color from config
     */
    public function getColor()
    {
        $color = Mage::getStoreConfig('pdfbeautifier_config/frontend/color');
        return (!is_null($color)) ? $color : self::STANDARD_COLOR;
    }

    /**
     * Set font as regular
     *
     * @param Zend_Pdf_Page $object
     * @param int $size
     * @return Zend_Pdf_Resource_Font
     */
    protected function _setFontRegular($object, $size = 7)
    {
        $font = Mage::getStoreConfig('pdfbeautifier_config/frontend/font_regular');
        $fontUploaded = (!is_null($font)) ? $font : 'OpenSans-Regular.ttf';

        $font = Zend_Pdf_Font::fontWithPath(Mage::getBaseDir() . self::FONT_DIR . $fontUploaded);
        $object->setFont($font, $size);

        return $font;
    }

    /**
     * Set font as bold
     *
     * @param Zend_Pdf_Page $object
     * @param int $size
     * @return Zend_Pdf_Resource_Font
     */
    protected function _setFontBold($object, $size = 7)
    {
        $font = Mage::getStoreConfig('pdfbeautifier_config/frontend/font_bold');
        $fontUploaded = (!is_null($font)) ? $font : 'OpenSans-Bold.ttf';

        $font = Zend_Pdf_Font::fontWithPath(Mage::getBaseDir() . self::FONT_DIR . $fontUploaded);
        $object->setFont($font, $size);

        return $font;
    }

    /**
     * Set font as italic
     *
     * @param Zend_Pdf_Page $object
     * @param int $size
     * @return Zend_Pdf_Resource_Font
     */
    protected function _setFontItalic($object, $size = 7)
    {
        $font = Mage::getStoreConfig('pdfbeautifier_config/frontend/font_italic');
        $fontUploaded = (!is_null($font)) ? $font : 'OpenSans-Italic.ttf';

        $font = Zend_Pdf_Font::fontWithPath(Mage::getBaseDir() . self::FONT_DIR . $fontUploaded);
        $object->setFont($font, $size);

        return $font;
    }

    /**
     * Insert logo to pdf page
     *
     * @param Zend_Pdf_Page $page
     * @param null $store
     */
    protected function insertLogo(&$page, $store = null)
    {
        $this->y = $this->y ? $this->y : 815;
        $image = Mage::getStoreConfig('sales/identity/logo', $store);
        if ($image) {
            $image = Mage::getBaseDir('media') . '/sales/store/logo/' . $image;
            if (is_file($image)) {
                $image = Zend_Pdf_Image::imageWithPath($image);
                $top = 830; //top border of the page
                $widthLimit = 270; //half of the page width
                $heightLimit = 270; //assuming the image is not a "skyscraper"
                $width = $image->getPixelWidth();
                $height = $image->getPixelHeight();

                //preserving aspect ratio (proportions)
                $ratio = $width / $height;
                if ($ratio > 1 && $width > $widthLimit) {
                    $width = $widthLimit;
                    $height = $width / $ratio;
                } elseif ($ratio < 1 && $height > $heightLimit) {
                    $height = $heightLimit;
                    $width = $height * $ratio;
                } elseif ($ratio == 1 && $height > $heightLimit) {
                    $height = $heightLimit;
                    $width = $widthLimit;
                }

                $y1 = $top - $height;
                $y2 = $top;
                $x1 = ($page->getWidth() / 2) - ($width / 2);
                $x2 = $x1 + $width;

                $page->drawImage($image, $x1, $y1, $x2, $y2);

                $this->y = $y1 - 10;
            }
        }
    }

    protected function _insertInvoiceHeadline(&$page, $incrementId)
    {
        $invoiceHeadline = strtoupper(Mage::helper('sales')->__('Invoice'));
        $invoicetext = Mage::helper('sales')->__('Invoice #') . $incrementId;

        $font = $this->_setFontBold($page, 16);
        $page->drawText($invoiceHeadline,
            $this->getAlignRight($invoiceHeadline, 45, 500, $font, 10),
            $page->getHeight() - 120,
            'UTF-8');

        $font = $this->_setFontItalic($page, 10);
        $page->drawText($invoicetext,
            $this->getAlignRight($invoicetext, 70, 500, $font, 10),
            $page->getHeight() - 135,
            'UTF-8');
    }

    /**
     * Insert address to pdf page
     *
     * @param Zend_Pdf_Page $page
     * @param null $store
     */
    protected function insertAddress(&$page, $store = null)
    {
        $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
        $font = $this->_setFontRegular($page, 10);
        $page->setLineWidth(0);
        $this->y = $this->y ? $this->y : 815;
        $top = 815;
        foreach (explode("\n", Mage::getStoreConfig('sales/identity/address', $store)) as $value) {
            if ($value !== '') {
                $value = preg_replace('/<br[^>]*>/i', "\n", $value);
                foreach (Mage::helper('core/string')->str_split($value, 45, true, true) as $_value) {
                    $page->drawText(trim(strip_tags($_value)),
                        $this->getAlignRight($_value, 130, 440, $font, 10),
                        $top,
                        'UTF-8');
                    $top -= 14;
                }
            }
        }
        $this->y = ($this->y > $top) ? $top : $this->y;
    }

    /**
     * Draw header for item table
     *
     * @param Zend_Pdf_Page $page
     * @return void
     */
    protected function _drawHeader(Zend_Pdf_Page $page)
    {
        /* Add table head */
        $page->setLineColor(new Zend_Pdf_Color_Html($this->getColor()));
        $page->setLineWidth(2);
        $page->drawLine(25, $this->y - 15, 570, $this->y - 15);
        $this->y -= 10;
        $page->setFillColor(new Zend_Pdf_Color_RGB(0, 0, 0));

        //columns headers
        $lines[0][] = array(
            'text' => strtoupper(Mage::helper('sales')->__('Products')),
            'feed' => 35,
            'font' => 'bold',
            'font_size' => 10,
        );

        $lines[0][] = array(
            'text' => strtoupper(Mage::helper('sales')->__('SKU')),
            'feed' => 290,
            'align' => 'right',
            'font' => 'bold',
            'font_size' => 10,
        );

        $lines[0][] = array(
            'text' => strtoupper(Mage::helper('sales')->__('Qty')),
            'feed' => 435,
            'align' => 'right',
            'font' => 'bold',
            'font_size' => 10,
        );

        $lines[0][] = array(
            'text' => strtoupper(Mage::helper('sales')->__('Price')),
            'feed' => 360,
            'align' => 'right',
            'font' => 'bold',
            'font_size' => 10,
        );

        $lines[0][] = array(
            'text' => strtoupper(Mage::helper('sales')->__('Tax')),
            'feed' => 495,
            'align' => 'right',
            'font' => 'bold',
            'font_size' => 10,
        );

        $lines[0][] = array(
            'text' => strtoupper(Mage::helper('sales')->__('Subtotal')),
            'feed' => 565,
            'align' => 'right',
            'font' => 'bold',
            'font_size' => 10,
        );

        $lineBlock = array(
            'lines' => $lines,
            'height' => 5
        );

        $this->drawLineBlocks($page, array($lineBlock), array('table_header' => true));
        $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
        $this->y -= 20;
    }

    /**
     * Insert address into footer
     *
     * @param Zend_Pdf_Page $page
     * @param Mage_Core_Model_Store $store
     */
    protected function _drawFooter($page, $store = null)
    {
        $font = $this->_setFontRegular($page, 8);
        $page->setLineColor(new Zend_Pdf_Color_Html($this->getColor()));
        $page->setLineWidth(2);
        $page->drawLine(25, 25, $page->getWidth() - 25, 25);

        $textArray = array();
        foreach (explode("\n", Mage::getStoreConfig('sales/identity/address', $store)) as $value) {
            if ($value !== '') {
                $textArray[] = $value;
            }
        }

        $text = implode(' - ', $textArray);

        $rendererPdf = new Zend_Barcode_Renderer_Pdf();
        $textWidth = $rendererPdf->widthForStringUsingFontSize($text, $font, 8);

        $page->drawText($text, ($page->getWidth() / 2) - ($textWidth / 2), 10, 'UTF-8');
    }

    /**
     * Insert title and number for concrete document type
     *
     * @param Zend_Pdf_Page $page
     * @param string $text
     * @return void
     */
    public function insertDocumentNumber(Zend_Pdf_Page $page, $text)
    {
        #$page->setFillColor(new Zend_Pdf_Color_GrayScale(1));
        $this->_setFontItalic($page, 10);
        $docHeader = $this->getDocHeaderCoordinates();
        $page->drawText($text, 35, $docHeader[1] - 15, 'UTF-8');
    }

    /**
     * Insert order to pdf page
     *
     * @param Zend_Pdf_Page $page
     * @param Mage_Sales_Model_Order $obj
     * @param bool $putOrderId
     */
    protected function insertOrder(&$page, $obj, $putOrderId = true)
    {
        if ($obj instanceof Mage_Sales_Model_Order) {
            $shipment = null;
            $order = $obj;
        } elseif ($obj instanceof Mage_Sales_Model_Order_Shipment) {
            $shipment = $obj;
            $order = $shipment->getOrder();
        }

        $this->y = $this->y ? $this->y : 815;
        $top = $this->y;

        $this->setDocHeaderCoordinates(array(25, $top, 570, $top - 55));
        $this->_setFontItalic($page, 10);

        if ($putOrderId) {
            $page->drawText(
                Mage::helper('sales')->__('Order: #') . $order->getRealOrderId(), 35, ($top -= 30), 'UTF-8'
            );
        }
        $page->drawText(
            Mage::helper('sales')->__('Order Date: ') . Mage::helper('core')->formatDate(
                $order->getCreatedAtStoreDate(), 'medium', false
            ),
            35,
            ($top -= 15),
            'UTF-8'
        );

        $top -= 10;
        $page->setFillColor(new Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
        $page->setLineColor(new Zend_Pdf_Color_Html($this->getColor()));
        $page->setLineWidth(2);
        $page->drawLine(25, ($top - 25), 275, ($top - 25));
        $page->drawLine(275, ($top - 25), 570, ($top - 25));

        /* Calculate blocks info */

        /* Billing Address */
        $billingAddress = $this->_formatAddress($order->getBillingAddress()->format('pdf'));

        /* Payment */
        $paymentInfo = Mage::helper('payment')->getInfoBlock($order->getPayment())
            ->setIsSecureMode(true)
            ->toPdf();
        $paymentInfo = htmlspecialchars_decode($paymentInfo, ENT_QUOTES);
        $payment = explode('{{pdf_row_separator}}', $paymentInfo);
        foreach ($payment as $key => $value) {
            if (strip_tags(trim($value)) == '') {
                unset($payment[$key]);
            }
        }
        reset($payment);

        /* Shipping Address and Method */
        if (!$order->getIsVirtual()) {
            /* Shipping Address */
            $shippingAddress = $this->_formatAddress($order->getShippingAddress()->format('pdf'));
            $shippingMethod = $order->getShippingDescription();
        }

        $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
        $this->_setFontBold($page, 10);
        $page->drawText(strtoupper(Mage::helper('sales')->__('Sold to:')), 35, ($top - 20), 'UTF-8');

        if (!$order->getIsVirtual()) {
            $page->drawText(strtoupper(Mage::helper('sales')->__('Ship to:')), 285, ($top - 20), 'UTF-8');
        } else {
            $page->drawText(strtoupper(Mage::helper('sales')->__('Payment Method:')), 285, ($top - 20), 'UTF-8');
        }

        $addressesHeight = $this->_calcAddressHeight($billingAddress);
        if (isset($shippingAddress)) {
            $addressesHeight = max($addressesHeight, $this->_calcAddressHeight($shippingAddress));
        }

        $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
        $this->_setFontRegular($page, 10);
        $this->y = $top - 40;
        $addressesStartY = $this->y;

        foreach ($billingAddress as $value) {
            if ($value !== '') {
                $text = array();
                foreach (Mage::helper('core/string')->str_split($value, 45, true, true) as $_value) {
                    $text[] = $_value;
                }
                foreach ($text as $part) {
                    $page->drawText(strip_tags(ltrim($part)), 35, $this->y, 'UTF-8');
                    $this->y -= 15;
                }
            }
        }

        $addressesEndY = $this->y;

        if (!$order->getIsVirtual()) {
            $this->y = $addressesStartY;
            foreach ($shippingAddress as $value) {
                if ($value !== '') {
                    $text = array();
                    foreach (Mage::helper('core/string')->str_split($value, 45, true, true) as $_value) {
                        $text[] = $_value;
                    }
                    foreach ($text as $part) {
                        $page->drawText(strip_tags(ltrim($part)), 285, $this->y, 'UTF-8');
                        $this->y -= 15;
                    }
                }
            }

            $addressesEndY = min($addressesEndY, $this->y);
            $this->y = $addressesEndY;

            $page->setFillColor(new Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
            $page->setLineColor(new Zend_Pdf_Color_Html($this->getColor()));
            $page->setLineWidth(2);
            $page->drawLine(25, $this->y - 25, 275, $this->y - 25);
            $page->drawLine(275, $this->y - 25, 570, $this->y - 25);

            $this->y -= 15;
            $this->_setFontBold($page, 10);
            $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
            $page->drawText(strtoupper(Mage::helper('sales')->__('Payment Method')), 35, $this->y - 5, 'UTF-8');
            $page->drawText(strtoupper(Mage::helper('sales')->__('Shipping Method:')), 285, $this->y - 5, 'UTF-8');

            $this->y -= 10;
            $page->setFillColor(new Zend_Pdf_Color_GrayScale(1));

            $this->_setFontRegular($page, 10);
            $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));

            $paymentLeft = 35;
            $yPayments = $this->y - 15;
        } else {
            $yPayments = $addressesStartY;
            $paymentLeft = 285;
        }

        foreach ($payment as $value) {
            if (trim($value) != '') {
                //Printing "Payment Method" lines
                $value = preg_replace('/<br[^>]*>/i', "\n", $value);
                foreach (Mage::helper('core/string')->str_split($value, 45, true, true) as $_value) {
                    $page->drawText(strip_tags(trim($_value)), $paymentLeft, $yPayments, 'UTF-8');
                    $yPayments -= 15;
                }
            }
        }

        if ($order->getIsVirtual()) {
            // replacement of Shipments-Payments rectangle block
            $yPayments = min($addressesEndY, $yPayments);
            $page->setLineColor(new Zend_Pdf_Color_Html($this->getColor()));
            $page->drawLine(25, ($top - 25), 25, $yPayments);
            $page->drawLine(570, ($top - 25), 570, $yPayments);
            $page->drawLine(25, $yPayments, 570, $yPayments);

            $this->y = $yPayments - 15;
        } else {
            $topMargin = 15;
            $methodStartY = $this->y;
            $this->y -= 15;

            foreach (Mage::helper('core/string')->str_split($shippingMethod, 45, true, true) as $_value) {
                $page->drawText(strip_tags(trim($_value)), 285, $this->y, 'UTF-8');
                $this->y -= 15;
            }

            $yShipments = $this->y;
            $totalShippingChargesText = "(" . Mage::helper('sales')->__('Total Shipping Charges') . " "
                . $order->formatPriceTxt($order->getShippingAmount()) . ")";

            $page->drawText($totalShippingChargesText, 285, $yShipments - $topMargin, 'UTF-8');
            $yShipments -= $topMargin + 10;

            $tracks = array();
            if ($shipment) {
                $tracks = $shipment->getAllTracks();
            }
            if (count($tracks)) {
                $page->setFillColor(new Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
                $page->setLineColor(new Zend_Pdf_Color_Html('#000000'));
                $page->setLineWidth(0.5);
                $page->drawRectangle(285, $yShipments, 510, $yShipments - 10);
                $page->drawLine(400, $yShipments, 400, $yShipments - 10);
                //$page->drawLine(510, $yShipments, 510, $yShipments - 10);

                $this->_setFontRegular($page, 9);
                $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
                //$page->drawText(Mage::helper('sales')->__('Carrier'), 290, $yShipments - 7 , 'UTF-8');
                $page->drawText(Mage::helper('sales')->__('Title'), 290, $yShipments - 7, 'UTF-8');
                $page->drawText(Mage::helper('sales')->__('Number'), 410, $yShipments - 7, 'UTF-8');

                $yShipments -= 20;
                $this->_setFontRegular($page, 8);
                foreach ($tracks as $track) {

                    $CarrierCode = $track->getCarrierCode();
                    if ($CarrierCode != 'custom') {
                        $carrier = Mage::getSingleton('shipping/config')->getCarrierInstance($CarrierCode);
                        $carrierTitle = $carrier->getConfigData('title');
                    } else {
                        $carrierTitle = Mage::helper('sales')->__('Custom Value');
                    }

                    //$truncatedCarrierTitle = substr($carrierTitle, 0, 35) . (strlen($carrierTitle) > 35 ? '...' : '');
                    $maxTitleLen = 45;
                    $endOfTitle = strlen($track->getTitle()) > $maxTitleLen ? '...' : '';
                    $truncatedTitle = substr($track->getTitle(), 0, $maxTitleLen) . $endOfTitle;
                    //$page->drawText($truncatedCarrierTitle, 285, $yShipments , 'UTF-8');
                    $page->drawText($truncatedTitle, 292, $yShipments, 'UTF-8');
                    $page->drawText($track->getNumber(), 410, $yShipments, 'UTF-8');
                    $yShipments -= $topMargin - 5;
                }
            } else {
                $yShipments -= $topMargin - 5;
            }

            $currentY = min($yPayments, $yShipments);

            $this->y = $currentY;
            $this->y -= 15;
        }
    }

    /**
     * Insert totals to pdf page
     *
     * @param Zend_Pdf_Page $page
     * @param Mage_Sales_Model_Abstract $source
     * @return Zend_Pdf_Page
     */
    protected function insertTotals($page, $source)
    {
        $order = $source->getOrder();
        $totals = $this->_getTotalsList($source);
        $lineBlock = array(
            'lines' => array(),
            'height' => 25
        );
        foreach ($totals as $total) {
            $total->setOrder($order)
                ->setSource($source);

            if ($total->canDisplay()) {
                $total->setFontSize(10);
                foreach ($total->getTotalsForDisplay() as $totalData) {
                    $lineBlock['lines'][] = array(
                        array(
                            'text' => strtoupper($totalData['label']),
                            'feed' => 475,
                            'align' => 'right',
                            'font_size' => $totalData['font_size'],
                            'font' => 'bold',
                        ),
                        array(
                            'text' => $totalData['amount'],
                            'feed' => 565,
                            'align' => 'right',
                            'font_size' => $totalData['font_size'],
                            'font' => 'bold',
                        ),
                    );
                }
            }
        }

        $this->y -= 20;
        $page = $this->drawLineBlocks($page, array($lineBlock));
        return $page;
    }

    /**
     * Draw lines
     *
     * draw items array format:
     * lines        array;array of line blocks (required)
     * shift        int; full line height (optional)
     * height       int;line spacing (default 10)
     *
     * line block has line columns array
     *
     * column array format
     * text         string|array; draw text (required)
     * feed         int; x position (required)
     * font         string; font style, optional: bold, italic, regular
     * font_file    string; path to font file (optional for use your custom font)
     * font_size    int; font size (default 7)
     * align        string; text align (also see feed parametr), optional left, right
     * height       int;line spacing (default 10)
     *
     * @param Zend_Pdf_Page $page
     * @param array $draw
     * @param array $pageSettings
     * @return Zend_Pdf_Page
     * @throws Mage_Core_Exception
     */
    public function drawLineBlocks(Zend_Pdf_Page $page, array $draw, array $pageSettings = array())
    {
        $totalStart = false;
        foreach ($draw as $itemsProp) {
            if (!isset($itemsProp['lines']) || !is_array($itemsProp['lines'])) {
                Mage::throwException(Mage::helper('sales')->__('Invalid draw line data. Please define "lines" array.'));
            }
            $lines = $itemsProp['lines'];
            $height = isset($itemsProp['height']) ? $itemsProp['height'] : 10;

            if (empty($itemsProp['shift'])) {
                $shift = 0;
                foreach ($lines as $line) {
                    $maxHeight = 0;
                    foreach ($line as $column) {
                        $lineSpacing = !empty($column['height']) ? $column['height'] : $height;
                        if (!is_array($column['text'])) {
                            $column['text'] = array($column['text']);
                        }
                        $top = 0;
                        foreach ($column['text'] as $part) {
                            $top += $lineSpacing;
                        }

                        $maxHeight = $top > $maxHeight ? $top : $maxHeight;
                    }
                    $shift += $maxHeight;
                }
                $itemsProp['shift'] = $shift;
            }

            if ($this->y - $itemsProp['shift'] < 15) {
                $page = $this->newPage($pageSettings);
            }

            foreach ($lines as $line) {
                $maxHeight = 0;
                foreach ($line as $column) {
                    $fontSize = empty($column['font_size']) ? 10 : $column['font_size'];
                    if (!empty($column['font_file'])) {
                        $font = Zend_Pdf_Font::fontWithPath($column['font_file']);
                        $page->setFont($font, $fontSize);
                    } else {
                        $fontStyle = empty($column['font']) ? 'regular' : $column['font'];
                        switch ($fontStyle) {
                            case 'bold':
                                $font = $this->_setFontBold($page, $fontSize);
                                break;
                            case 'italic':
                                $font = $this->_setFontItalic($page, $fontSize);
                                break;
                            default:
                                $font = $this->_setFontRegular($page, $fontSize);
                                break;
                        }
                    }

                    if (!is_array($column['text'])) {
                        $column['text'] = array($column['text']);
                    }

                    $lineSpacing = !empty($column['height']) ? $column['height'] : $height;
                    $top = 0;
                    foreach ($column['text'] as $part) {
                        if ($this->y - $lineSpacing < 15) {
                            $page = $this->newPage($pageSettings);
                        }

                        $feed = $column['feed'];
                        $textAlign = empty($column['align']) ? 'left' : $column['align'];
                        $width = empty($column['width']) ? 0 : $column['width'];
                        switch ($textAlign) {
                            case 'right':
                                if ($width) {
                                    $feed = $this->getAlignRight($part, $feed, $width, $font, $fontSize);
                                } else {
                                    $feed = $feed - $this->widthForStringUsingFontSize($part, $font, $fontSize);
                                }
                                break;
                            case 'center':
                                if ($width) {
                                    $feed = $this->getAlignCenter($part, $feed, $width, $font, $fontSize);
                                }
                                break;
                        }

                        /**
                         * Workaround for finding position of grand total text
                         */
                        Mage::getSingleton('core/translate')->init('en_US', true);
                        $translated = Mage::helper('core')->__((string)$part);

                        if (strtolower($translated) == 'grand total:') {
                            $totalStart = true;
                            $textWidth = $this->widthForStringUsingFontSize($part, $font, $fontSize);

                            $page->setFillColor(new Zend_Pdf_Color_Html($this->getColor()));
                            $page->drawRectangle($feed - $textWidth, $this->y - 8, $feed + $textWidth + 90, $this->y + 15);
                            $page->setFillColor(new Zend_Pdf_Color_Html('#FFFFFF'));
                            $page->drawText($part, $feed, $this->y - $top, 'UTF-8');
                        } else {
                            if ($totalStart) {
                                $page->setFillColor(new Zend_Pdf_Color_Html('#FFFFFF'));
                                $totalStart = false;
                            } else {
                                $page->setFillColor(new Zend_Pdf_Color_Html('#000000'));
                            }

                            $page->drawText($part, $feed, $this->y - $top, 'UTF-8');
                            $top += $lineSpacing;
                        }
                    }

                    $maxHeight = $top > $maxHeight ? $top : $maxHeight;
                }
                $this->y -= $maxHeight;
            }
            $page->setFillColor(new Zend_Pdf_Color_Html('#000000'));
        }

        return $page;
    }

    /**
     * Return PDF document
     *
     * @param array $invoices
     * @return Zend_Pdf
     */
    public function getPdf($invoices = array())
    {
        $this->_beforeGetPdf();
        $this->_initRenderer('invoice');

        $pdf = new Zend_Pdf();
        $this->_setPdf($pdf);
        $style = new Zend_Pdf_Style();
        $this->_setFontBold($style, 9);

        foreach ($invoices as $invoice) {
            if ($invoice->getStoreId()) {
                Mage::app()->getLocale()->emulate($invoice->getStoreId());
                Mage::app()->setCurrentStore($invoice->getStoreId());
            }
            $page = $this->newPage();
            $order = $invoice->getOrder();
            /* Add image */
            $this->insertLogo($page, $invoice->getStore());
            /* Add address */
            $this->insertAddress($page, $invoice->getStore());
            /* Add head */
            $this->insertOrder(
                $page,
                $order,
                Mage::getStoreConfigFlag(self::XML_PATH_SALES_PDF_INVOICE_PUT_ORDER_ID, $order->getStoreId())
            );
            /* Add document text and number */
            $this->_insertInvoiceHeadline($page, $invoice->getIncrementId());
            /* Add table */
            $this->_drawHeader($page);
            /* Add body */
            foreach ($invoice->getAllItems() as $item) {
                if ($item->getOrderItem()->getParentItem()) {
                    continue;
                }
                /* Draw item */
                $this->_drawItem($item, $page, $order);
                $page = end($pdf->pages);
            }
            /* Add totals */
            $this->insertTotals($page, $invoice);
            if ($invoice->getStoreId()) {
                Mage::app()->getLocale()->revert();
            }
        }

        /* Add footer */
        $this->_drawFooter($page, $invoice->getStore());

        $this->_afterGetPdf();
        return $pdf;
    }
}