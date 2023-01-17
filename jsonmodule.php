<?php
/**
 * Copyright (C) 2017 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to contact@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @copyright 2017 thirty bees
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

/** @noinspection PhpUndefinedClassInspection */
/** @noinspection PhpUndefinedNamespaceInspection */

use ProductCommentsModule\ProductComment;
use ProductCommentsModule\ProductCommentCriterion;

if (!defined('_TB_VERSION_')) {
    exit;
}

class jsonModule extends Module
{

    const JSONMODULE_CONFIG = 'JSONMODULE_CONFIG';
    const ORGANIZATION_JSON = 'ORGANIZATION_JSON';
    const PRODUCT_JSON = 'PRODUCT_JSON';

    /**
     * @var array
     */
    protected $_errors = [];

    /**
     * @var string
     */
    protected $_html = '';

    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->name = 'jsonmodule';
        $this->tab = 'front_office_features';
        $this->version = '1.0.4';
        $this->author = 'thirty bees';
        $this->need_instance = 0;

        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Google JSON+LD');
        $this->description = $this->l('Adds Google rich snippets in json+LD format for richer snippets');
        $this->confirmUninstall = $this->l('Are you sure you want to delete this module?');
    }

    /**
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function install()
    {
        if (!parent::install() or
            !$this->registerHook('displayHeader')
        ) {
            return false;
        }
        return true;
    }

    /**
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function uninstall()
    {
        if (!parent::uninstall() or
            !$this->_eraseConfig()
        ) {
            return false;
        }
        return true;
    }

    /**
     * @return true
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function _eraseConfig()
    {
        Configuration::deleteByName(static::ORGANIZATION_JSON);
        Configuration::deleteByName(static::JSONMODULE_CONFIG);
        return true;
    }

    /**
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function getContent()
    {
        $this->_postProcess();
        $this->_displayForm();
        $this->context->controller->addJS($this->_path . 'js/admin.js');

        return $this->_html;
    }

    /**
     * @return void
     * @throws PrestaShopException
     */
    private function _postProcess()
    {
        if (
            Tools::isSubmit('submitCompanyInformation')
            || Tools::isSubmit('submitSocialMediaCompany')
            || Tools::isSubmit('submitReviews')
            || Tools::isSubmit('submitFounding')
            || Tools::isSubmit('submitFounder')
            || Tools::isSubmit('submitLocations')
            || Tools::isSubmit('submitContactPoints')
        ) {
            // create a config json to store in database
            $config = [];

            // company information section
            $config['companyInformation']['companyName'] = Tools::getValue('companyName');
            $config['companyInformation']['companyLegalName'] = Tools::getValue('companyLegalName');
            $config['companyInformation']['alternativeName'] = Tools::getValue('alternativeName');
            $config['companyInformation']['description'] = Tools::getValue('description');
            $config['companyInformation']['websiteUrl'] = Tools::getValue('websiteUrl');
            $config['companyInformation']['companyFacebook'] = Tools::getValue('companyFacebook');
            $config['companyInformation']['companyTwitter'] = Tools::getValue('companyTwitter');
            $config['companyInformation']['companyYoutube'] = Tools::getValue('companyYoutube');
            $config['companyInformation']['companyLinkedin'] = Tools::getValue('companyLinkedin');
            if ($logo = $this->_uploadAnyFile('logo')) {
                $config['companyInformation']['logo'] = $logo;
            }
            if (isset($config['companyInformation']['logo']) && $config['companyInformation']['logo'] == '') {
                $config['companyInformation']['logo'] = Tools::getValue('logo_old');
            }

            // founding section
            $config['founding']['foundingDate'] = Tools::getValue('foundingDate');
            $config['founding']['foundingStreetAddress'] = Tools::getValue('foundingStreetAddress');
            $config['founding']['foundingLocality'] = Tools::getValue('foundingLocality');
            $config['founding']['foundingRegion'] = Tools::getValue('foundingRegion');
            $config['founding']['foundingCountry'] = Tools::getValue('foundingCountry');
            $config['founding']['foundingPostalCode'] = Tools::getValue('foundingPostalCode');

            // reviews section
            $config['reviews']['review_type'] = Tools::getValue('review_type');
            $config['reviews']['yotpo_app_id'] = Tools::getValue('yotpo_app_id');

            // determine founder and contactpoint fieldsets count
            $founderCount = 0;
            $contactPointsCount = 0;
            foreach (array_keys($_POST) as $key) {
                if (strpos($key, 'founderName_') === 0) {
                    $founderCount++;
                }
                if (strpos($key, 'contactPointsType_') === 0) {
                    $contactPointsCount++;
                }
            }

            // founders section
            $founders = [];
            for ($i = 0; $i < $founderCount; $i++) {
                $founder = [
                    'founderName' => Tools::getValue('founderName_' . $i),
                    'founderTitle' => Tools::getValue('founderTitle_' . $i),
                    'founderLinkedin' => Tools::getValue('founderLinkedin_' . $i),
                    'founderTwitter' => Tools::getValue('founderTwitter_' . $i),
                    'founderPicture' => '',
                ];
                if ($img = $this->_uploadAnyFile('founderPicture_' . $i)) {
                    $founder['founderPicture'] = $img;
                }
                if ($founder['founderPicture'] == '') {
                    $founder['founderPicture'] = Tools::getValue('founderPicture_' . $i . '_old');
                }
                $founders[] = $founder;
            }
            $config['founders'] = $founders;

            // locations section
            if (Tools::getValue('useInternalStoreInfo_0')) {
                $config['locations']['useInternalStoreInfo'] = true;
            } else {
                $config['locations']['useInternalStoreInfo'] = false;
                $config['locations']['locStreetAddress'] = Tools::getValue('locStreetAddress');
                $config['locations']['locLocality'] = Tools::getValue('locLocality');
                $config['locations']['locRegion'] = Tools::getValue('locRegion');
                $config['locations']['locCountry'] = Tools::getValue('locCountry');
                $config['locations']['locPostalCode'] = Tools::getValue('locPostalCode');
            }

            // contact points section
            $contactPoints = [];
            for ($i = 0; $i < $contactPointsCount; $i++) {
                $contactPoint = [
                    'contactPointsTel' => Tools::getValue('contactPointsTel_' . $i),
                    'contactPointsEmail' => Tools::getValue('contactPointsEmail_' . $i),
                    'contactPointsUrl' => Tools::getValue('contactPointsUrl_' . $i),
                    'contactPointsType' => Tools::getValue('contactPointsType_' . $i),
                ];
                if (Tools::isSubmit('useActiveCountries_' . $i . '_0')) {
                    $contactPoint['useActiveCountries'] = true;
                } else {
                    $contactPoint['useActiveCountries'] = false;
                    $contactPoint['contactPointsCountries'] = Tools::getValue('contactPointsCountries_' . $i);
                }
                if (Tools::isSubmit('useActiveLanguages_' . $i . '_0')) {
                    $contactPoint['useActiveLanguages'] = true;
                } else {
                    $contactPoint['useActiveLanguages'] = false;
                    $contactPoint['contactPointsLanguages'] = Tools::getValue('contactPointsLanguages_' . $i);
                }
                $contactPoints[] = $contactPoint;
            }
            $config['contactPoints'] = $contactPoints;

            Configuration::updateValue(static::JSONMODULE_CONFIG, json_encode($config));

            // Error handling
            if ($this->_errors) {
                $this->_html .= $this->displayError(implode('<br />', $this->_errors));
            } else {
                // build organization json and store
                if (Configuration::updateValue(static::ORGANIZATION_JSON, $this->_buildJson())) {
                    $this->_html .= $this->displayConfirmation($this->l('Settings Updated!'));
                } else {
                    $this->_html .= $this->displayError($this->l('An error occurred. Please try again.'));
                }
            }
        }
    }

    /**
     * @param string $key
     *
     * @return bool|string|null
     */
    private function _uploadAnyFile($key)
    {
        $fileName = null;
        if (isset($_FILES[$key])
            && isset($_FILES[$key]['tmp_name'])
            && !empty($_FILES[$key]['tmp_name'])
        ) {
            if ($error = ImageManager::validateUpload($_FILES[$key], 4000000)) {
                return $error;
            } else {
                $ext = substr($_FILES[$key]['name'], strrpos($_FILES[$key]['name'], '.') + 1);
                $fileName = md5($_FILES[$key]['name']) . '.' . $ext;

                if (!move_uploaded_file($_FILES[$key]['tmp_name'],
                    dirname(__FILE__) . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . $fileName)
                ) {
                    $this->_errors[] = $this->l('Some files could not be uploaded.');
                }
            }
        }
        return $fileName;
    }

    /**
     * @return false|string
     * @throws PrestaShopException
     */
    private function _buildJson()
    {
        $json = [];
        $config = json_decode(Configuration::get(static::JSONMODULE_CONFIG), true);

        if ($companyInformation = $config['companyInformation']) {
            $companyInfoIndices = [
                'name' => 'companyName',
                'legalName' => 'companyLegalName',
                'url' => 'websiteUrl',
                'alternateName' => 'alternativeName',
                'description' => 'description',
            ];
            foreach ($companyInfoIndices as $key => $value) {
                if ($this->_checkVariable($companyInformation, $value)) {
                    $json[$key] = $companyInformation[$value];
                }
            }
            if ($this->_checkVariable($companyInformation, 'logo')) {
                $json['image'] = _PS_BASE_URL_ . $this->_path . 'img' . DIRECTORY_SEPARATOR . $companyInformation['logo'];
            }
            $sameAs = [];
            $socialIndices = [
                'companyFacebook',
                'companyTwitter',
                'companyYoutube',
                'companyLinkedin',
                'companyInstagram',
            ];
            foreach ($socialIndices as $index) {
                if ($this->_checkVariable($companyInformation, $index)) {
                    $sameAs[] = $companyInformation[$index];
                }
            }
            if (count($sameAs) > 0) {
                $json['sameAs'] = $sameAs;
            }
        }

        if ($founding = $config['founding']) {
            if ($this->_checkVariable($founding, 'foundingDate')) {
                $json['foundingDate'] = $founding['foundingDate'];
            }
            $arrFounding = [];
            $foundingIndices = [
                'streetAddress' => 'foundingStreetAddress',
                'addressLocality' => 'foundingLocality',
                'addressRegion' => 'foundingRegion',
                'addressCountry' => 'foundingCountry',
                'postalCode' => 'foundingPostalCode',
            ];
            foreach ($foundingIndices as $key => $value) {
                if ($this->_checkVariable($founding, $value)) {
                    $arrFounding[$key] = $founding[$value];
                }
            }
            if (count($arrFounding) > 0) {
                $arrFounding['@type'] = 'PostalAddress';
                $json['foundingLocation'] = [
                    "@type" => "Place",
                    'address' => $arrFounding,
                ];
            }
        }

        if ($founders = $config['founders']) {
            $arrFounder = [];
            foreach ($founders as $founder) {
                $aFounder = [];
                $founderIndices = [
                    'name' => 'founderName',
                    'jobTitle' => 'founderTitle',
                ];
                foreach ($founderIndices as $key => $value) {
                    if ($this->_checkVariable($founder, $value)) {
                        $aFounder[$key] = $founder[$value];
                    }
                }
                if ($this->_checkVariable($founder, 'founderPicture')) {
                    $aFounder['image'] = _PS_BASE_URL_ . $this->_path . 'img' . DIRECTORY_SEPARATOR . $founder['founderPicture'];
                }

                $sameAs = [];
                $socialIndices = [
                    'founderLinkedin',
                    'founderTwitter',
                ];
                foreach ($socialIndices as $index) {
                    if ($this->_checkVariable($founder, $index)) {
                        $sameAs[] = $founder[$index];
                    }
                }
                if (count($sameAs) > 0) {
                    $aFounder['sameAs'] = $sameAs;
                }

                if (count($aFounder) > 0) {
                    $aFounder['@type'] = 'Person';
                    $arrFounder[] = $aFounder;
                }
            }
            if (count($arrFounder) > 0) {
                $json['founders'] = $arrFounder;
            }
        }

        if ($contactPoints = $config['contactPoints']) {
            $arrContactPoint = [];
            foreach ($contactPoints as $cPoint) {
                $aCPoint = [];
                $founderIndices = [
                    'telephone' => 'contactPointsTel',
                    'email' => 'contactPointsEmail',
                    'url' => 'contactPointsUrl',
                ];
                foreach ($founderIndices as $key => $value) {
                    if ($this->_checkVariable($cPoint, $value)) {
                        $aCPoint[$key] = $cPoint[$value];
                    }
                }
                if ($this->_checkVariable($cPoint, 'contactPointsType')) {
                    $aCPoint['contactType'] = strtolower($cPoint['contactPointsType']);
                } else {
                    continue;
                }

                // TODO
                // contactPointsCountries
                // contactPointsLanguages

                if (count($aCPoint) > 0) {
                    $aCPoint['@type'] = 'ContactPoint';
                    $arrContactPoint[] = $aCPoint;
                }
            }
            if (count($arrContactPoint) > 0) {
                $json['contactPoint'] = $arrContactPoint;
            }
        }

        if ($locations = $config['locations']) {
            if ($this->_checkVariable($locations, 'useInternalStoreInfo') && $locations['useInternalStoreInfo']) {
                // TODO: fetch info from store
            } else {
                $address = [];
                $addressIndices = [
                    'streetAddress' => 'locStreetAddress',
                    'addressLocality' => 'locLocality',
                    'addressRegion' => 'locRegion',
                    'addressCountry' => 'locCountry',
                    'postalCode' => 'locPostalCode',
                ];
                foreach ($addressIndices as $key => $value) {
                    if ($this->_checkVariable($locations, $value)) {
                        $address[$key] = $locations[$value];
                    }
                }
                if (count($address) > 0) {
                    $address['@type'] = 'PostalAddress';
                    $json['address'] = $address;
                }
            }
        }
        if (count($json) > 0) {

            $json['@context'] = 'http://schema.org';
            $json['@type'] = 'Organization';
        }
        return json_encode($json);
    }

    /**
     * @param array $arr
     * @param string $var
     *
     * @return bool
     */
    private function _checkVariable($arr, $var)
    {
        if (array_key_exists($var, $arr)) {
            if ($value = $arr[$var]) {
                if (! empty($value)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    private function _displayForm()
    {
        $this->_html .= $this->_generateForm();
    }

    /**
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    private function _generateForm()
    {
        $fields = [];
        $fieldsValue = $this->_getConfig();

        // https://app.moqups.com/dh42/DmzGhQopRb/view/page/aa9df7b72
        // https://search.google.com/structured-data/testing-tool
        // https://docs.google.com/spreadsheets/d/1Ed6RmI01rx4UdW40ciWgz2oS_Kx37_-sPi7sba_jC3w/edit#gid=0
        // https://developers.google.com/search/docs/guides/intro-structured-data

        // input group 1
        $inputs1 = [];
        // company name (textfield)
        $inputs1[] = [
            'type' => 'text',
            'label' => $this->l('Company Name'),
            'name' => 'companyName',
            'desc' => $this->l('Enter company\'s common name'),
        ];
        // legal name (textfield)
        $inputs1[] = [
            'type' => 'text',
            'label' => $this->l('Company Legal Name'),
            'name' => 'companyLegalName',
            'desc' => $this->l('Enter company\'s legal name'),
        ];
        // alternative name (textfield)
        $inputs1[] = [
            'type' => 'text',
            'label' => $this->l('Alternative Name'),
            'name' => 'alternativeName',
            'desc' => $this->l('Enter company\'s alternative name -if any'),
        ];
        // description (textfield)
        $inputs1[] = [
            'type' => 'text',
            'label' => $this->l('Description'),
            'name' => 'description',
            'desc' => $this->l('Enter a short description about your company'),
        ];
        // website url (textfield)
        $inputs1[] = [
            'type' => 'text',
            'label' => $this->l('Website URL'),
            'name' => 'websiteUrl',
        ];
        // logo preview
        if (isset($fieldsValue['logo']) && !empty($fieldsValue['logo'])) {
            $logoFile = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . $fieldsValue['logo'];
            if (file_exists($logoFile)) {
                $inputs1[] = [
                    'type' => 'html',
                    'html_content' => '<img src="' . $this->_path . '/img/' . DIRECTORY_SEPARATOR . $fieldsValue['logo'] . '" /><span class="remove-image">(x)</span>',
                ];
            }
        }
        // logo (file/imageupload)
        $inputs1[] = [
            'type' => 'file',
            'label' => $this->l('Logo'),
            'name' => 'logo',
        ];
        $inputs1[] = [
            'type' => 'hidden',
            'name' => 'logo_old',
        ];
        // company information
        $fieldsForm1 = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Company Information'),
                    'icon' => 'icon-building',
                ],
                'input' => $inputs1,
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right',
                    'name' => 'submitCompanyInformation',
                ],
            ],
        ];
        $fields[] = $fieldsForm1;

        // input group 1-social media
        $inputs1_ = [];
        $inputs1_[] = [
            'type' => 'text',
            'label' => $this->l('Facebook Page'),
            'name' => 'companyFacebook',
        ];
        $inputs1_[] = [
            'type' => 'text',
            'label' => $this->l('Twitter URL'),
            'name' => 'companyTwitter',
        ];
        $inputs1_[] = [
            'type' => 'text',
            'label' => $this->l('YouTube Channel'),
            'name' => 'companyYoutube',
        ];
        $inputs1_[] = [
            'type' => 'text',
            'label' => $this->l('LinkedIn Page'),
            'name' => 'companyLinkedin',
        ];
        $inputs1_[] = [
            'type' => 'text',
            'label' => $this->l('Instagram Page'),
            'name' => 'companyInstagram',
        ];
        // company information-social media
        $fieldsForm1_ = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Social Media (Company)'),
                    'icon' => 'icon-building',
                ],
                'input' => $inputs1_,
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right',
                    'name' => 'submitSocialMediaCompany',
                ],
            ],
        ];
        $fields[] = $fieldsForm1_;


        // input group 1-social media
        $inputs1_ = [];
        $inputs1_[] = [
            'type' => 'radio',
            'label' => $this->l('Reviews snippet'),
            'name' => 'review_type',
            'values' => [
                [
                    'id' => 'type_none',
                    'value' => 0,
                    'label' => $this->l('No snippets'),
                ],
                [
                    'id' => 'type_comment',
                    'value' => 1,
                    'label' => $this->l('Native comments Module'),
                ],
                [
                    'id' => 'type_yotpo',
                    'value' => 2,
                    'label' => $this->l('Yotpo reviews Module'),
                ],
            ]
        ];
        $inputs1_[] = [
            'type' => 'text',
            'label' => $this->l('Yotpo App ID'),
            'name' => 'yotpo_app_id',
        ];

        // company information-social media
        $fieldsForm1_ = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Comments'),
                    'icon' => 'icon-building',
                ],
                'input' => $inputs1_,
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right',
                    'name' => 'submitReviews',
                ],
            ],
        ];
        $fields[] = $fieldsForm1_;

        // input group 2
        $inputs2 = [];
        // founding date (date picker)
        $inputs2[] = [
            'type' => 'date',
            'label' => $this->l('Founding Date'),
            'name' => 'foundingDate',
            'desc' => $this->l('This value must be in ISO 8601 format (YYYY-MM-DD)'),
        ];
        // founding street address (textfield)
        $inputs2[] = [
            'type' => 'text',
            'label' => $this->l('Founding Street Address'),
            'name' => 'foundingStreetAddress',
        ];
        // founding locality (textfield)
        $inputs2[] = [
            'type' => 'text',
            'label' => $this->l('Founding Town/Locality'),
            'name' => 'foundingLocality',
        ];
        // founding region (textfield)
        $inputs2[] = [
            'type' => 'text',
            'label' => $this->l('Founding State/Region'),
            'name' => 'foundingRegion',
        ];
        // founding country (textfield)
        $inputs2[] = [
            'type' => 'text',
            'label' => $this->l('Founding Country'),
            'name' => 'foundingCountry',
        ];
        // founding postal code (textfield)
        $inputs2[] = [
            'type' => 'text',
            'label' => $this->l('Founding Postal Code'),
            'name' => 'foundingPostalCode',
        ];
        // founding
        $fieldsForm2 = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Founding'),
                    'icon' => 'icon-briefcase',
                ],
                'input' => $inputs2,
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right',
                    'name' => 'submitFounding',
                ],
            ],
        ];
        $fields[] = $fieldsForm2;

        // input group 2+
        $inputs2_ = [];
        // add founder fields
        for ($i = 0; $i < $fieldsValue['countFounders']; $i++) {
            $headerHtml = '<b class="founder-field-' . $i . ' jsonmodule-fieldset-header">Founder #<span class="founder-number">' . ($i + 1) . '</span>';
            if ($i > 0) {
                $headerHtml .= '<span class="founder-remove">(x)</span>';
            }
            $headerHtml .= '</b>';
            $inputs2_[] = [
                'type' => 'html',
                'name' => 'html_data_' . $i,
                'html_content' => $headerHtml,
            ];
            // founder name (textfield)
            $inputs2_[] = [
                'type' => 'text',
                'label' => $this->l('Founder Name'),
                'name' => 'founderName_' . $i,
                'id' => 'founder_name_' . $i,
                'class' => 'founder-field founder-field-' . $i,
            ];
            // title (textfield)
            $inputs2_[] = [
                'type' => 'text',
                'label' => $this->l('Title'),
                'name' => 'founderTitle_' . $i,
                'id' => 'founder_title_' . $i,
                'class' => 'founder-field founder-field-' . $i,
            ];
            // linkedin page (textfield)
            $inputs2_[] = [
                'type' => 'text',
                'label' => $this->l('LinkedIn Page'),
                'name' => 'founderLinkedin_' . $i,
                'id' => 'founder_linkedin_' . $i,
                'class' => 'founder-field founder-field-' . $i,
            ];
            // twitter profile (textfield)
            $inputs2_[] = [
                'type' => 'text',
                'label' => $this->l('Twitter URL'),
                'name' => 'founderTwitter_' . $i,
                'id' => 'founder_twitter_' . $i,
                'class' => 'founder-field founder-field-' . $i,
            ];
            // picture preview
            if (isset($fieldsValue['founderPicture_' . $i]) && !empty($fieldsValue['founderPicture_' . $i])) {
                $imageFile = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . $fieldsValue['founderPicture_' . $i];
                if (file_exists($imageFile)) {
                    $inputs2_[] = [
                        'type' => 'html',
                        'html_content' => '<img src="' . $this->_path . '/img/' . DIRECTORY_SEPARATOR . $fieldsValue['founderPicture_' . $i] . '" /><span class="remove-image">(x)</span>',
                    ];
                    $inputs2_[] = [
                        'type' => 'hidden',
                        'name' => 'founderPicture_' . $i . '_old',
                    ];
                }
            }
            // picture (file/imageupload)
            $inputs2_[] = [
                'type' => 'file',
                'label' => $this->l('Picture'),
                'name' => 'founderPicture_' . $i,
                'id' => 'founder_picture_' . $i,
                'class' => 'founder-field founder-field-' . $i,
            ];
        }
        // add founder
        $fieldsForm2_ = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Founder'),
                    'icon' => 'icon-user',
                ],
                'input' => $inputs2_,
                'buttons' => [
                    'newBlock' => [
                        'title' => $this->l('Add Founder'),
                        'class' => 'pull-right',
                        'id' => 'btn-add-founder',
                        'icon' => 'process-icon-new',
                    ]
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right',
                    'name' => 'submitFounder',
                ],
            ],
        ];
        $fields[] = $fieldsForm2_;

        // input group 3
        $inputs3 = [];
        // use internal store finder information (checkbox, if checked gray out the rest of the fields in this group)
        if (isset($fieldsValue['useInternalStoreInfo']) && $fieldsValue['useInternalStoreInfo']) {
            $inputs3[] = [
                'type' => 'hidden',
                'name' => 'use-internal-store-info-value',
                'id' => 'use-internal-store-info-value',
            ];
        }
        $inputs3[] = [
            'type' => 'checkbox',
            'class' => 'use-internal-store-info',
            'name' => 'useInternalStoreInfo',
            'values' => [
                'query' => [
                    [
                        'id_option' => 0,
                        'name' => $this->l('Use Internal Store Finder Information'),
                    ],
                ],
                'id' => 'id_option',
                'name' => 'name',
            ],
        ];
        // street address (textfield)
        $inputs3[] = [
            'type' => 'text',
            'label' => $this->l('Street Address'),
            'name' => 'locStreetAddress',
            'id' => 'loc-street-address',
            'class' => 'internal-store-info-field',
        ];
        // city (textfield)
        $inputs3[] = [
            'type' => 'text',
            'label' => $this->l('Town/Locality'),
            'name' => 'locLocality',
            'id' => 'loc-locality',
            'class' => 'internal-store-info-field',
        ];
        // state/province (textfield)
        $inputs3[] = [
            'type' => 'text',
            'label' => $this->l('State/Region'),
            'name' => 'locRegion',
            'id' => 'loc-region',
            'class' => 'internal-store-info-field',
        ];
        // country (textfield)
        $inputs3[] = [
            'type' => 'text',
            'label' => $this->l('Country'),
            'name' => 'locCountry',
            'id' => 'loc-country',
            'class' => 'internal-store-info-field',
            'desc' => $this->l('This value must be in ISO 3166-1 ALPHA 2 format'),
        ];
        // postal code (textfield)
        $inputs3[] = [
            'type' => 'text',
            'label' => $this->l('Postal Code'),
            'name' => 'locPostalCode',
            'id' => 'loc-postal-code',
            'class' => 'internal-store-info-field',
        ];
        // locations
        $fieldsForm3 = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Locations'),
                    'icon' => 'icon-location-arrow',
                ],
                'input' => $inputs3,
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right',
                    'name' => 'submitLocations',
                ],
            ],
        ];
        $fields[] = $fieldsForm3;

        // input group 4
        $inputs4[] = [];
        // add contact point fields
        for ($i = 0; $i < $fieldsValue['countContactPoints']; $i++) {
            $headerHtml = '<b class="contact-point-field-' . $i . ' jsonmodule-fieldset-header">Contact Point #<span class="contact-point-number">' . ($i + 1) . '</span>';
            if ($i > 0) {
                $headerHtml .= '<span class="contact-point-remove">(x)</span>';
            }
            $headerHtml .= '</b>';
            $inputs4[] = [
                'type' => 'html',
                'name' => 'html_data_contact_point_' . $i,
                'html_content' => $headerHtml,
            ];
            // telephone (textfield)
            $inputs4[] = [
                'type' => 'text',
                'label' => $this->l('Telephone'),
                'name' => 'contactPointsTel_' . $i,
                'id' => 'contact_points_tel_' . $i,
                'class' => 'contact-point-field-' . $i,
            ];
            // email (textfield)
            $inputs4[] = [
                'type' => 'text',
                'label' => $this->l('E-Mail Address'),
                'name' => 'contactPointsEmail_' . $i,
                'id' => 'contact_points_email_' . $i,
                'class' => 'contact-point-field-' . $i,
            ];
            // url (textfield)
            $inputs4[] = [
                'type' => 'text',
                'label' => $this->l('URL'),
                'name' => 'contactPointsUrl_' . $i,
                'id' => 'contact_points_url_' . $i,
                'class' => 'contact-point-field-' . $i,
            ];
            // contact type (textfield)
            $inputs4[] = [
                'type' => 'select',
                'label' => $this->l('Contact Point Type'),
                'name' => 'contactPointsType_' . $i,
                'id' => 'contact_points_type_' . $i,
                'class' => 'contact-point-field-' . $i,
                'options' => [
                    'query' => $this->_getContactPointTypes(),
                    'id' => 'id_type',
                    'name' => 'name',
                ],
            ];
            // use active countries (checkbox)
            $countriesCbClass = 'contact-point-field-' . $i;
            if (! isset($fieldsValue['useActiveCountries_' . $i]) || !$fieldsValue['useActiveCountries_' . $i]) {
                $countriesCbClass = 'off ' . $countriesCbClass;
            }
            $inputs4[] = [
                'type' => 'checkbox',
                'name' => 'useActiveCountries_' . $i,
                'id' => 'use_active_countries_' . $i,
                'class' => $countriesCbClass,
                'label' => $this->l('Countries Served'),
                'values' => [
                    'query' => [
                        [
                            'id_option_use_countries' => 0,
                            'name' => $this->l('Use Active Countries'),
                        ],
                    ],
                    'id' => 'id_option_use_countries',
                    'name' => 'name',
                ],
            ];

            // countries served (multi select box)
            $inputs4[] = [
                'type' => 'select',
                'multiple' => true,
                'class' => 'chosen contact-point-field-' . $i,
                'name' => 'contactPointsCountries_' . $i . '[]',
                'id' => 'contact_points_countries_' . $i,
                'options' => [
                    'query' => Country::getCountries((int)Context::getContext()->language->id),
                    'id' => 'id_country',
                    'name' => 'name',
                ],
            ];
            // use active languages (checkbox)
            $languagesCbClass = 'contact-point-field-' . $i;
            if (!$fieldsValue['useActiveLanguages_' . $i]) {
                $languagesCbClass = 'off ' . $languagesCbClass;
            }
            $inputs4[] = [
                'type' => 'checkbox',
                'name' => 'useActiveLanguages_' . $i,
                'id' => 'use_active_languages_' . $i,
                'class' => $languagesCbClass,
                'label' => $this->l('Languages'),
                'values' => [
                    'query' => [
                        [
                            'id_option_use_lang' => 0,
                            'name' => $this->l('Use Active Languages'),
                        ],
                    ],
                    'id' => 'id_option_use_lang',
                    'name' => 'name',
                ],
            ];
            // languages (multi select box)
            $inputs4[] = [
                'type' => 'select',
                'multiple' => true,
                'class' => 'chosen contact-point-field-' . $i,
                'name' => 'contactPointsLanguages_' . $i,
                'id' => 'contact_points_languages_' . $i,
                'options' => [
                    'query' => Language::getLanguages(true, $this->context->shop->id),
                    'id' => 'id_lang',
                    'name' => 'name',
                ],
            ];
        }

        // hardfix empty input
        if (!$inputs4[0]) {
            unset($inputs4[0]);
        }

        // contact points
        $fieldsForm4 = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Contact Points'),
                    'icon' => 'icon-credit-card',
                ],
                'input' => $inputs4,
                'buttons' => [
                    'newBlock' => [
                        'title' => $this->l('Add Contact Point'),
                        'class' => 'pull-right',
                        'id' => 'btn-add-contact-point',
                        'icon' => 'process-icon-new',
                    ]
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right',
                    'name' => 'submitContactPoints',
                ],
            ],
        ];
        $fields[] = $fieldsForm4;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        /** @var AdminController $controller */
        $controller = $this->context->controller;
        $helper = new HelperForm();
        $helper->default_form_language = $lang->id;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $fieldsValue,
            'languages' => $controller->getLanguages(),
            'id_language' => $this->context->language->id
        ];

        $this->context->controller->addCSS(($this->_path) . 'css/jsonmodule_global.css', 'all');

        return $helper->generateForm($fields);
    }

    /**
     * @return array
     * @throws PrestaShopException
     */
    private function _getConfig()
    {
        $config = json_decode(Configuration::get(static::JSONMODULE_CONFIG), true);

        // rebuild the configuration array tree
        $retArr = [
            'companyName' => '',
            'companyLegalName' => '',
            'alternativeName' => '',
            'description' => '',
            'websiteUrl' => '',
            'logo' => '',
            'logo_old' => '',
            'companyFacebook' => '',
            'companyTwitter' => '',
            'companyYoutube' => '',
            'companyInstagram' => '',
            'companyLinkedin' => '',
            'review_type' => '',
            'yotpo_app_id' => '',
            'foundingDate' => '',
            'foundingStreetAddress' => '',
            'foundingLocality' => '',
            'foundingRegion' => '',
            'foundingCountry' => '',
            'foundingPostalCode' => '',
            'locStreetAddress' => '',
            'locLocality' => '',
            'locRegion' => '',
            'locCountry' => '',
            'locPostalCode' => '',
            'countFounders' => 1,
            'countContactPoints' => 1,
            'useActiveLanguages_0' => false,
            'founderName_0' => '',
            'founderTitle_0' => '',
            'founderLinkedin_0' => '',
            'founderTwitter_0' => '',
            'contactPointsTel_0' => '',
            'contactPointsEmail_0' => '',
            'contactPointsUrl_0' => '',
            'contactPointsType_0' => '',
            'contactPointsCountries_0[]' => [],
            'contactPointsLanguages_0[]' => [],

        ];

        foreach (['companyInformation', 'founding', 'locations', 'reviews'] as $configKey) {
            if (isset($config[$configKey]) && is_array($config[$configKey])) {
                foreach ($config[$configKey] as $key => $value) {
                    $retArr[$key] = $value;
                    if ($key == 'logo') {
                        $retArr[$key . '_old'] = $value;
                    }
                }
            }
        }

        // founders
        if (isset($config['founders']) && is_array($config['founders'])) {
            for ($i = 0; $i < count($config['founders']); $i++) {
                $founder = $config['founders'][$i];
                foreach (array_keys($founder) as $key) {
                    $retArr[$key . '_' . $i] = $founder[$key];
                    if ($key == 'founderPicture') {
                        $retArr[$key . '_' . $i . '_old'] = $founder[$key];
                    }
                }
            }
            $retArr['countFounders'] = count($config['founders']) > 0
                ? count($config['founders'])
                : 1;
        }

        // contact points
        if (isset($config['contactPoints']) && is_array($config['contactPoints'])) {
            for ($i = 0; $i < count($config['contactPoints']); $i++) {
                $contactPoint = $config['contactPoints'][$i];
                foreach (array_keys($contactPoint) as $key) {
                    $retArr[$key . '_' . $i] = $contactPoint[$key];
                    if ($key == 'contactPointsCountries' || $key == 'contactPointsLanguages') {
                        $retArr[$key . '_' . $i . '[]'] = $contactPoint[$key];
                    }
                }
            }
            $retArr['countContactPoints'] = count($config['contactPoints']) > 0
                ? count($config['contactPoints'])
                : 1;
        }

        return $retArr;
    }

    /**
     * @return array[]
     */
    private function _getContactPointTypes()
    {
        return [
            [
                'id_type' => "customer support",
                'name' => "Customer Support",
            ],
            [
                'id_type' => "technical support",
                'name' => "Technical Support",
            ],
            [
                'id_type' => "billing support",
                'name' => "Billing Support",
            ],
            [
                'id_type' => "bill payment",
                'name' => "Bill Payment",
            ],
            [
                'id_type' => "sales",
                'name' => "Sales",
            ],
            [
                'id_type' => "reservations",
                'name' => "Reservations",
            ],
            [
                'id_type' => "credit card support",
                'name' => "Credit Card Support",
            ],
            [
                'id_type' => "emergency",
                'name' => "Emergency",
            ],
            [
                'id_type' => "baggage tracking",
                'name' => "Baggage Tracking",
            ],
            [
                'id_type' => "roadside assistance",
                'name' => "Roadside Assistance",
            ],
            [
                'id_type' => "package tracking",
                'name' => "Package Tracking",
            ],
        ];
    }

    /**
     * @param array $params
     *
     * @return false|string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookDisplayHeader($params)
    {

        $path = [];
        $config = json_decode(Configuration::get(static::JSONMODULE_CONFIG), true);
        if (isset($this->context->controller->php_self) && 'product' == $this->context->controller->php_self) {

            $product = new Product((int)Tools::getValue('id_product'), true, $this->context->language->id);
            if (!Validate::isLoadedObject($product)) {
                return false;
            }

            $cover = Product::getCover($product->id);
            $image = $this->context->link->getImageLink($product->link_rewrite, $cover['id_image']);
            $this->context->smarty->assign([
                'product' => $product,
            ]);


            // reviews
            $nbReviews = 0;
            $avgDecimal = 0;
            $review_result = [];
            if (isset($config['reviews'])) {
                if ($config['reviews']['review_type'] == 2 && $config['reviews']['yotpo_app_id'] && Module::isInstalled('yotpo')) {
                    // Yotpo reviews
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL,
                        'https://api.yotpo.com/v1/widget/' . $config['reviews']['yotpo_app_id'] . '/products/' . $product->id . '/reviews.json?star=5&sort[]=date&sort[]=votes_up');
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $result = curl_exec($ch);
                    curl_close($ch);

                    if ($result) {
                        $review_result = json_decode($result, true);
                    }

                    if ($review_result && $review_result['status']['code'] == 200) {
                        $nbReviews = $review_result['response']['bottomline']['total_review'];
                        $avgDecimal = Tools::ps_round($review_result['response']['bottomline']['average_score'], 1);
                    }
                } else {
                    if ($config['reviews']['review_type'] == 1 && Module::isInstalled('productcomments')) {


                        $avgDecimal = ProductComment::getAverageGrade($product->id);
                        $nbReviews = (int)ProductComment::getCommentNumber($product->id);
                    }
                }
            }

            $this->context->smarty->assign([
                'isproduct' => 1
            ]);
            $path = $this->getPath($product->id_category_default);

            // build json for product page
            $arrProduct = [];
            if (!empty($product->name)) {
                $arrProduct['name'] = $product->name;
            }
            if (!empty($image)) {
                $arrProduct['image'] = $image;
            }
            if (!empty($product->description_short)) {
                $arrProduct['description'] = strip_tags($product->description_short);
            }
            if (!empty($product->upc)) {
                $arrProduct['sku'] = $product->upc;
            }
            if (!empty($product->ean13)) {
                $arrProduct['gtin13'] = $product->ean13;
            }
            if (!empty($product->supplier_reference)) {
                $arrProduct['mpn'] = $product->supplier_reference;
            }
            if (!empty($product->manufacturer_name)) {
                $arrProduct['brand'] = [
                    '@type' => 'brand',
                    'name' => $product->manufacturer_name,
                    'logo' => $this->context->link->getBaseLink() . 'img/m/' . $product->id_manufacturer . '.jpg',
                ];
            }
            if ($nbReviews > 0 && $avgDecimal > 0) {
                $arrProduct['aggregateRating'] = [
                    '@type' => 'AggregateRating',
                    'reviewCount' => $nbReviews,
                    'ratingValue' => $avgDecimal,
                ];
            }

            if (!empty($product->price)) {
                $offers = [
                    '@type' => 'Offer',
                    'priceCurrency' => $this->context->currency->iso_code,
                    'price' => $product->getPrice(),
                    'itemCondition' => 'http://schema.org/' . ucfirst(strtolower($product->condition)) . 'Condition',
                    'seller' => [
                        '@type' => 'Organization',
                        'name' => Configuration::get('PS_SHOP_NAME'),
                    ],
                ];
                if (!empty($product->quantity) && $product->quantity > 0) {
                    $offers['availability'] = 'http://schema.org/InStock';
                } else {
                    $offers['availability'] = 'http://schema.org/OutOfStock';
                }
                if (!empty($product->specificPrice) && !empty($product->specificPrice->to)) {
                    $offers['priceValidUntil'] = Tools::dateFormat($product->specificPrice->to, Context::getContext()->smarty);
                }
                $arrProduct['offers'] = $offers;
            }

            if (count($arrProduct) > 0) {
                $arrProduct['@context'] = 'http://schema.org';
                $arrProduct['@type'] = 'Product';
            }

        } // end if product

        if ($id_category = Tools::getValue('id_category')) {
            $path = $this->getPath((int)$id_category);
        }


        if (is_array($path) && $path) {
            $this->context->smarty->assign([
                'path' => $path
            ]);
        }

        $this->context->smarty->assign([
            static::ORGANIZATION_JSON => Configuration::get(static::ORGANIZATION_JSON),
            static::PRODUCT_JSON => isset($arrProduct) ? json_encode($arrProduct) : '',
        ]);

        return $this->display(__FILE__, 'jsonmodule.tpl');


    }

    /**
     * @param int $id_category
     * @param string $path
     * @param bool $link_on_the_item
     * @param string $category_type
     * @param Context|null $context
     *
     * @return array|string|void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getPath(
        $id_category,
        $path = [],
        $link_on_the_item = false,
        $category_type = 'products',
        Context $context = null
    )
    {
        if (!$context) {
            $context = Context::getContext();
        }

        $id_category = (int)$id_category;
        if ($id_category == 1) {
            return '<span class="navigation_end">' . $path . '</span>';
        }

        $full_path = [];
        if ($category_type === 'products') {
            $interval = Category::getInterval($id_category);
            $id_root_category = $context->shop->getCategory();
            $interval_root = Category::getInterval($id_root_category);
            if ($interval) {
                $sql = 'SELECT c.id_category, cl.name, cl.link_rewrite
						FROM ' . _DB_PREFIX_ . 'category c
						LEFT JOIN ' . _DB_PREFIX_ . 'category_lang cl ON (cl.id_category = c.id_category' . Shop::addSqlRestrictionOnLang('cl') . ')
						' . Shop::addSqlAssociation('category', 'c') . '
						WHERE c.nleft <= ' . $interval['nleft'] . '
							AND c.nright >= ' . $interval['nright'] . '
							AND c.nleft >= ' . $interval_root['nleft'] . '
							AND c.nright <= ' . $interval_root['nright'] . '
							AND cl.id_lang = ' . (int)$context->language->id . '
							AND c.active = 1
							AND c.level_depth > ' . (int)$interval_root['level_depth'] . '
						ORDER BY c.level_depth ASC';
                $categories = Db::getInstance()->executeS($sql);

                foreach ($categories as $category) {
                    $full_path[] = [
                        'name' => $category['name'],
                        'url' => $context->link->getCategoryLink((int)$category['id_category'],
                            $category['link_rewrite'])
                    ];
                }

                return array_merge($full_path, $path);
            }
        } elseif ($category_type === 'CMS') {
            $category = new CMSCategory($id_category, $context->language->id);
            if (!Validate::isLoadedObject($category)) {
                die(Tools::displayError());
            }
            $category_link = $context->link->getCMSCategoryLink($category);

            if ($path != $category->name) {
                $full_path[] = ['name' => $category->name, 'url' => $category_link];
            } else {
                $full_path[] = ['name' => $path, 'url' => $category_link];
            }

            return $this->getPath($category->id_parent, $full_path, $link_on_the_item, $category_type);
        }
    }

}
