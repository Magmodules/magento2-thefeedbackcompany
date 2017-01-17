<?php
/**
 * Copyright © 2016 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\TheFeedbackCompany\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Helper\Image;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\StoreManagerInterface;
use Magmodules\TheFeedbackCompany\Helper\General as GeneralHelper;

class Invitation extends AbstractHelper
{

    const POST_ACTION = 'sendInvitation';
    const XML_PATH_INVITATION_ENABLED = 'magmodules_thefeedbackcompany/invitation/enabled';
    const XML_PATH_INVITATION_CONNECTOR = 'magmodules_thefeedbackcompany/invitation/connector';
    const XML_PATH_INVITATION_STATUS = 'magmodules_thefeedbackcompany/invitation/status';
    const XML_PATH_INVITATION_DELAY = 'magmodules_thefeedbackcompany/invitation/delay';
    const XML_PATH_INVITATION_REMIND_DELAY = 'magmodules_thefeedbackcompany/invitation/remind_delay';
    const XML_PATH_INVITATION_BACKLOG = 'magmodules_thefeedbackcompany/invitation/backlog';
    const XML_PATH_INVITATION_RESEND = 'magmodules_thefeedbackcompany/invitation/resend';
    const XML_PATH_INVITATION_PREVIEWS = 'magmodules_thefeedbackcompany/invitation/product_reviews';
    const XML_PATH_INVITATION_DEBUG = 'magmodules_thefeedbackcompany/invitation/debug';

    protected $productRepository;
    protected $imgHelper;
    protected $general;
    protected $storeManager;

    /**
     * Invitation constructor.
     * @param Context $context
     * @param ProductRepository $productRepository
     * @param StoreManagerInterface $storeManager
     * @param Image $imgHelper
     * @param General $generalHelper
     */
    public function __construct(
        Context $context,
        ProductRepository $productRepository,
        StoreManagerInterface $storeManager,
        Image $imgHelper,
        GeneralHelper $generalHelper
    ) {
        $this->productRepository = $productRepository;
        $this->imgHelper = $imgHelper;
        $this->general = $generalHelper;
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    /**
     * Create array of invitation config data
     * @param $storeId
     * @return array|bool
     */
    public function getConfigData($storeId)
    {
        if ($this->getEnabledInvitation($storeId)) {
            $config = [];
            $config['connector'] = $this->general->getStoreValue(self::XML_PATH_INVITATION_CONNECTOR, $storeId);
            $config['status'] = $this->general->getStoreValue(self::XML_PATH_INVITATION_STATUS, $storeId);
            $config['delay'] = $this->general->getStoreValue(self::XML_PATH_INVITATION_DELAY, $storeId);
            $config['remind_delay'] = $this->general->getStoreValue(self::XML_PATH_INVITATION_REMIND_DELAY, $storeId);
            $config['resend'] = $this->general->getStoreValue(self::XML_PATH_INVITATION_RESEND, $storeId);
            $config['backlog'] = ($this->general->getStoreValue(self::XML_PATH_INVITATION_BACKLOG, $storeId) * 86400);
            $config['product_reviews'] = $this->general->getStoreValue(self::XML_PATH_INVITATION_PREVIEWS, $storeId);
            $config['debug'] = $this->general->getStoreValue(self::XML_PATH_INVITATION_DEBUG, $storeId);
            $config['action'] = self::POST_ACTION;
            if (empty($config['backlog'])) {
                $config['backlog'] = (30 * 86400);
            }

            return $config;
        }

        return false;
    }

    /**
     * Check if Invitation is enabled on store level
     * @param $storeId
     * @return bool|mixed
     */
    public function getEnabledInvitation($storeId)
    {
        if ($this->getEnabled()) {
            return $this->general->getStoreValue(self::XML_PATH_INVITATION_ENABLED, $storeId);
        }

        return true;
    }

    /**
     * Check if extension is enabled
     * @return mixed
     */
    public function getEnabled()
    {
        return $this->general->getEnabled();
    }

    /**
     * Create checksum of email string
     * @param $email
     * @return int
     */
    public function getChecksum($email)
    {
        $check_sum = 0;
        $email_lenght = strlen($email);
        for ($i = 0; $email_lenght > $i; $i++) {
            $check_sum += ord($email[$i]);
        }

        return $check_sum;
    }

    /**
     * Create product data array
     * @param $products
     * @param $storeId
     * @return array
     * @internal param $product_reviews
     */
    public function getProductData($products, $storeId)
    {
        $i = 1;
        $product_data = [];
        foreach ($products as $item) {
            $this->storeManager->setCurrentStore($storeId);
            $product = $this->productRepository->getById($item->getProductId());
            $product_data['filtercode'][] = trim($product->getSku());
            if ($product->getStatus() == '1') {
                $img = $this->imgHelper->init($product, 'product_thumbnail_image')->getUrl();
                $product_data['product_url[' . $i . ']'] = $product->getProductUrl();
                $product_data['product_text[' . $i . ']'] = $item->getName();
                $product_data['product_ids[' . $i . ']'] = 'SKU=' . $product->getSku();
                $product_data['product_photo[' . $i . ']'] = $img;
            }
        }

        return $product_data;
    }
}