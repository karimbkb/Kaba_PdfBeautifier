<?php

class Kaba_PdfBeautifier_Model_Sales_Order_Pdf_Shipment extends Mage_Sales_Model_Order_Pdf_Shipment
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

                //coordinates after transformation are rounded by Zend
                $page->drawImage($image, $x1, $y1, $x2, $y2);

                $this->y = $y1 - 10;
            }
        }
    }

    protected function _insertShipmentHeadline(&$page, $incrementId)
    {
        $invoiceHeadline = strtoupper(Mage::helper('sales')->__('Shipment'));
        $invoicetext = Mage::helper('sales')->__('Shipment #') . $incrementId;

        $font = $this->_setFontBold($page, 16);
        $page->drawText($invoiceHeadline,
            $this->getAlignRight($invoiceHeadline, 45, 500, $font, 10),
            $page->getHeight() - 120,
            'UTF-8');

        $font = $this->_setFontItalic($page, 10);
        $page->drawText($invoicetext,
            $this->getAlignRight($invoicetext, 70, 505, $font, 10),
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
     * Draw table header for product items
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
            'feed' => 100,
            'font' => 'bold',
            'font_size' => 10,
        );

        $lines[0][] = array(
            'text' => strtoupper(Mage::helper('sales')->__('Qty')),
            'feed' => 35,
            'font' => 'bold',
            'font_size' => 10,
        );

        $lines[0][] = array(
            'text' => strtoupper(Mage::helper('sales')->__('SKU')),
            'feed' => 565,
            'align' => 'right',
            'font' => 'bold',
            'font_size' => 10,
        );

        $lineBlock = array(
            'lines' => $lines,
            'height' => 10
        );

        $this->drawLineBlocks($page, array($lineBlock), array('table_header' => true));
        $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
        $this->y -= 20;
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
     * Return PDF document
     *
     * @param array $shipments
     * @return Zend_Pdf
     */
    public function getPdf($shipments = array())
    {
        $this->_beforeGetPdf();
        $this->_initRenderer('shipment');

        $pdf = new Zend_Pdf();
        $this->_setPdf($pdf);
        $style = new Zend_Pdf_Style();
        $this->_setFontBold($style, 10);
        foreach ($shipments as $shipment) {
            if ($shipment->getStoreId()) {
                Mage::app()->getLocale()->emulate($shipment->getStoreId());
                Mage::app()->setCurrentStore($shipment->getStoreId());
            }
            $page = $this->newPage();
            $order = $shipment->getOrder();
            /* Add image */
            $this->insertLogo($page, $shipment->getStore());
            /* Add address */
            $this->insertAddress($page, $shipment->getStore());
            /* Add head */
            $this->insertOrder(
                $page,
                $shipment,
                Mage::getStoreConfigFlag(self::XML_PATH_SALES_PDF_SHIPMENT_PUT_ORDER_ID, $order->getStoreId())
            );
            /* Add document text and number */
            $this->_insertShipmentHeadline($page, $shipment->getIncrementId());
            /* Add table */
            $this->_drawHeader($page);
            /* Add body */
            foreach ($shipment->getAllItems() as $item) {
                if ($item->getOrderItem()->getParentItem()) {
                    continue;
                }
                /* Draw item */
                $this->_drawItem($item, $page, $order);
                $page = end($pdf->pages);
            }
        }

        /* Add footer */
        $this->_drawFooter($page, $shipment->getStore());

        $this->_afterGetPdf();
        if ($shipment->getStoreId()) {
            Mage::app()->getLocale()->revert();
        }
        return $pdf;
    }
}