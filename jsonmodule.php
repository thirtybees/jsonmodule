<?php

use ProductCommentsModule\ProductComment;
use ProductCommentsModule\ProductCommentCriterion;

if (!defined('_PS_VERSION_'))
	exit;

class jsonModule extends Module
{

	protected $_errors = array();
	protected $_html = '';

	/* Set default configuration values here */
	protected $_config = array(
		'jsonld_legal_name' => ''
		);

	const JSONMODULE_CONFIG = 'JSONMODULE_CONFIG';

	const ORGANIZATION_JSON = 'ORGANIZATION_JSON';
	const PRODUCT_JSON = 'PRODUCT_JSON';

	public function __construct()
	{
		$this->name = 'jsonmodule';
		$this->tab = 'front_office_features';
		$this->version = '1.0.1';
		$this->author = 'thirty bees';
		$this->need_instance = 0;

		$this->bootstrap = true;

	 	parent::__construct();

		$this->displayName = $this->l('Google JSON+LD');
		$this->description = $this->l('Adds Google rich snippets in json+LD format for richer snippets');
		$this->confirmUninstall = $this->l('Are you sure you want to delete this module?');
	}

	public function install()
	{
		if (!parent::install() OR
			!$this->_installConfig() OR
			!$this->registerHook('displayHeader')
			)
			return false;
		return true;
	}

	public function uninstall()
	{
		if (!parent::uninstall() OR
			!$this->_eraseConfig()
			)
			return false;
		return true;
	}

	private function _installConfig()
	{
		foreach ($this->_config as $keyname => $value) {
			Configuration::updateValue($keyname, $value);
		}
		return true;
	}


	private function _eraseConfig()
	{
		foreach ($this->_config as $keyname => $value) {
			Configuration::deleteByName($keyname);
		}
		return true;
	}

	public function getContent()
	{
		$this->_postProcess();
		$this->_displayForm();
		$this->context->controller->addJS($this->_path.'js/admin.js');

		return	$this->_html;
	}

	private function _displayForm()
	{
		$this->_html .= $this->_generateForm();
		// With Template
		// $this->context->smarty->assign(array(
		// 	'variable'=> 1
		// ));
		// $this->_html .= $this->display(__FILE__, 'backoffice.tpl');
	}

	private function _generateForm()
	{


		$fields = array();
		$fieldsValue = $this->_getConfig();

		// https://app.moqups.com/dh42/DmzGhQopRb/view/page/aa9df7b72
		// https://search.google.com/structured-data/testing-tool
		// https://docs.google.com/spreadsheets/d/1Ed6RmI01rx4UdW40ciWgz2oS_Kx37_-sPi7sba_jC3w/edit#gid=0
		// https://developers.google.com/search/docs/guides/intro-structured-data

		// input group 1
		$inputs1 = array();
		// company name (textfield)
		$inputs1[] = array(
			'type'  => 'text',
			'label' => $this->l('Company Name'),
			'name'  => 'companyName',
			'desc'  => $this->l('Enter company\'s common name'),
		);
		// legal name (textfield)
		$inputs1[] = array(
			'type'  => 'text',
			'label' => $this->l('Company Legal Name'),
			'name'  => 'companyLegalName',
			'desc'  => $this->l('Enter company\'s legal name'),
		);
		// alternative name (textfield)
		$inputs1[] = array(
			'type'  => 'text',
			'label' => $this->l('Alternative Name'),
			'name'  => 'alternativeName',
			'desc'  => $this->l('Enter company\'s alternative name -if any'),
		);
		// description (textfield)
		$inputs1[] = array(
			'type'  => 'text',
			'label' => $this->l('Description'),
			'name'  => 'description',
			'desc'  => $this->l('Enter a short description about your company'),
		);
		// website url (textfield)
		$inputs1[] = array(
			'type'  => 'text',
			'label' => $this->l('Website URL'),
			'name'  => 'websiteUrl',
		);
		// logo preview
		if (isset($fieldsValue['logo']) && !empty($fieldsValue['logo'])) {
			$logoFile = dirname(__FILE__).DIRECTORY_SEPARATOR.'img'.DIRECTORY_SEPARATOR.$fieldsValue['logo'];
			if (file_exists($logoFile)) {
				$inputs1[] = array(
					'type'         => 'html',
					'html_content' => '<img src="'.$this->_path.'/img/'.DIRECTORY_SEPARATOR.$fieldsValue['logo'].'" /><span class="remove-image">(x)</span>',
				);
			}
		}
		// logo (file/imageupload)
		$inputs1[] = array(
			'type'  => 'file',
			'label' => $this->l('Logo'),
			'name'  => 'logo',
		);
		$inputs1[] = array(
			'type'  => 'hidden',
			'name'  => 'logo_old',
		);
		// company information
		$fieldsForm1 = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Company Information'),
					'icon'  => 'icon-building',
				),
				'input'  => $inputs1,
				'submit' => array(
					'title' => $this->l('Save'),
					'class' => 'btn btn-default pull-right',
					'name'  => 'submitCompanyInformation',
				),
			),
		);
		$fields[] = $fieldsForm1;

		// input group 1-social media
		$inputs1_ = array();
		$inputs1_[] = array(
			'type'  => 'text',
			'label' => $this->l('Facebook Page'),
			'name'  => 'companyFacebook',
		);
		$inputs1_[] = array(
			'type'  => 'text',
			'label' => $this->l('Twitter URL'),
			'name'  => 'companyTwitter',
		);
		$inputs1_[] = array(
			'type'  => 'text',
			'label' => $this->l('YouTube Channel'),
			'name'  => 'companyYoutube',
		);
		$inputs1_[] = array(
			'type'  => 'text',
			'label' => $this->l('LinkedIn Page'),
			'name'  => 'companyLinkedin',
		);
		$inputs1_[] = array(
			'type'  => 'text',
			'label' => $this->l('Google Plus Page'),
			'name'  => 'companyGooglePlus',
		);
		// //
		// company information-social media
		$fieldsForm1_ = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Social Media (Company)'),
					'icon'  => 'icon-building',
				),
				'input'  => $inputs1_,
				'submit' => array(
					'title' => $this->l('Save'),
					'class' => 'btn btn-default pull-right',
					'name'  => 'submitSocialMediaCompany',
				),
			),
		);
		$fields[] = $fieldsForm1_;


		// input group 1-social media
		$inputs1_ = array();
		$inputs1_[] = [
	        'type'   => 'radio',
            'label'  => $this->l('Reviews snippet'),
            'name'   => 'review_type',
            'values' => [
                [
                    'id'    => 'type_none',
                    'value' => 0,
                    'label' => $this->l('No snippets'),
                ],
                [
                    'id'    => 'type_comment',
                    'value' => 1,
                    'label' => $this->l('Native comments Module'),
                ],
                [
                    'id'    => 'type_yotpo',
                    'value' => 2,
                    'label' => $this->l('Yotpo reviews Module'),
                ],
            ]
        ];
        $inputs1_[] = array(
			'type'  => 'text',
			'label' => $this->l('Yotpo App ID'),
			'name'  => 'yotpo_app_id',
		);

		// //
		// company information-social media
		$fieldsForm1_ = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Comments'),
					'icon'  => 'icon-building',
				),
				'input'  => $inputs1_,
				'submit' => array(
					'title' => $this->l('Save'),
					'class' => 'btn btn-default pull-right',
					'name'  => 'submitReviews',
				),
			),
		);
		$fields[] = $fieldsForm1_;

		// input group 2
		$inputs2 = array();
		// founding date (date picker)
		$inputs2[] = array(
			'type'  => 'date',
			'label' => $this->l('Founding Date'),
			'name'  => 'foundingDate',
			'desc' => $this->l('This value must be in ISO 8601 format (YYYY-MM-DD)'),
		);
		// founding street address (textfield)
		$inputs2[] = array(
			'type'  => 'text',
			'label' => $this->l('Founding Street Address'),
			'name'  => 'foundingStreetAddress',
		);
		// founding locality (textfield)
		$inputs2[] = array(
			'type'  => 'text',
			'label' => $this->l('Founding Town/Locality'),
			'name'  => 'foundingLocality',
		);
		// founding region (textfield)
		$inputs2[] = array(
			'type'  => 'text',
			'label' => $this->l('Founding State/Region'),
			'name'  => 'foundingRegion',
		);
		// founding country (textfield)
		$inputs2[] = array(
			'type'  => 'text',
			'label' => $this->l('Founding Country'),
			'name'  => 'foundingCountry',
		);
		// founding postal code (textfield)
		$inputs2[] = array(
			'type'  => 'text',
			'label' => $this->l('Founding Postal Code'),
			'name'  => 'foundingPostalCode',
		);
		// founding
		$fieldsForm2 = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Founding'),
					'icon'  => 'icon-briefcase',
				),
				'input'  => $inputs2,
				'submit' => array(
					'title' => $this->l('Save'),
					'class' => 'btn btn-default pull-right',
					'name'  => 'submitFounding',
				),
			),
		);
		$fields[] = $fieldsForm2;

		// input group 2+
		$inputs2_ = array();
		// add founder fields
		for ($i = 0; $i < $fieldsValue['countFounders']; $i++) {
			$headerHtml = '<b class="founder-field-' . $i . ' jsonmodule-fieldset-header">Founder #<span class="founder-number">' . ($i + 1) .'</span>';
			if ($i > 0) {
				$headerHtml .= '<span class="founder-remove">(x)</span>';
			}
			$headerHtml .= '</b>';
			$inputs2_[] = array(
				'type'         => 'html',
				'name'         => 'html_data_' . $i,
				'html_content' => $headerHtml,
			);
			// founder name (textfield)
			$inputs2_[] = array(
				'type'  => 'text',
				'label' => $this->l('Founder Name'),
				'name'  => 'founderName_' . $i,
				'id'    => 'founder_name_' . $i,
				'class' => 'founder-field founder-field-' . $i,
			);
			// title (textfield)
			$inputs2_[] = array(
				'type'  => 'text',
				'label' => $this->l('Title'),
				'name'  => 'founderTitle_' . $i,
				'id'    => 'founder_title_' . $i,
				'class' => 'founder-field founder-field-' . $i,
			);
			// google+ page (textfield)
			$inputs2_[] = array(
				'type'  => 'text',
				'label' => $this->l('Google Plus Page'),
				'name'  => 'founderGooglePlus_' . $i,
				'id'    => 'founder_google_plus_' . $i,
				'class' => 'founder-field founder-field-' . $i,
			);
			// linkedin page (textfield)
			$inputs2_[] = array(
				'type'  => 'text',
				'label' => $this->l('LinkedIn Page'),
				'name'  => 'founderLinkedin_' . $i,
				'id'    => 'founder_linkedin_' . $i,
				'class' => 'founder-field founder-field-' . $i,
			);
			// twitter profile (textfield)
			$inputs2_[] = array(
				'type'  => 'text',
				'label' => $this->l('Twitter URL'),
				'name'  => 'founderTwitter_' . $i,
				'id'    => 'founder_twitter_' . $i,
				'class' => 'founder-field founder-field-' . $i,
			);
			// picture preview
			if (isset($fieldsValue['founderPicture_'.$i]) && !empty($fieldsValue['founderPicture_'.$i])) {
				$imageFile = dirname(__FILE__).DIRECTORY_SEPARATOR.'img'.DIRECTORY_SEPARATOR.$fieldsValue['founderPicture_'.$i];
				if (file_exists($imageFile)) {
					$inputs2_[] = array(
						'type'         => 'html',
						'html_content' => '<img src="'.$this->_path.'/img/'.DIRECTORY_SEPARATOR.$fieldsValue['founderPicture_'.$i].'" /><span class="remove-image">(x)</span>',
					);
					$inputs2_[] = array(
						'type'  => 'hidden',
						'name'  => 'founderPicture_' . $i . '_old',
					);
				}
			}
			// picture (file/imageupload)
			$inputs2_[] = array(
				'type'  => 'file',
				'label' => $this->l('Picture'),
				'name'  => 'founderPicture_' . $i,
				'id'    => 'founder_picture_' . $i,
				'class' => 'founder-field founder-field-' . $i,
			);
		}
		// add founder
		$fieldsForm2_ = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Founder'),
					'icon' => 'icon-user',
				),
				'input'  => $inputs2_,
				'buttons' => array(
					'newBlock' => array(
						'title' => $this->l('Add Founder'),
						'class' => 'pull-right',
						'id'    => 'btn-add-founder',
						'icon'  => 'process-icon-new',
					)
				),
				'submit' => array(
					'title' => $this->l('Save'),
					'class' => 'btn btn-default pull-right',
					'name'  => 'submitFounder',
				),
			),
		);
		$fields[] = $fieldsForm2_;

		// input group 3
		$inputs3 = array();
		// use internal store finder information (checkbox, if checked gray out the rest of the fields in this group)
		if ((bool)$fieldsValue['useInternalStoreInfo']) {
			$inputs3[] = array(
				'type'   => 'hidden',
				'name' => 'use-internal-store-info-value',
				'id' => 'use-internal-store-info-value',
			);
		}
		$inputs3[] = array(
			'type'   => 'checkbox',
			'class'  => 'use-internal-store-info',
			'name'   => 'useInternalStoreInfo',
			'values' => array(
				'query' => array(
					array(
						'id_option' => 0,
						'name'      => $this->l('Use Internal Store Finder Information'),
					),
				),
				'id'    => 'id_option',
				'name'  => 'name',
			),
		);
		// street address (textfield)
		$inputs3[] = array(
			'type'  => 'text',
			'label' => $this->l('Street Address'),
			'name'  => 'locStreetAddress',
			'id'    => 'loc-street-address',
			'class' => 'internal-store-info-field',
		);
		// city (textfield)
		$inputs3[] = array(
			'type'  => 'text',
			'label' => $this->l('Town/Locality'),
			'name'  => 'locLocality',
			'id'    => 'loc-locality',
			'class' => 'internal-store-info-field',
		);
		// state/province (textfield)
		$inputs3[] = array(
			'type'  => 'text',
			'label' => $this->l('State/Region'),
			'name'  => 'locRegion',
			'id'    => 'loc-region',
			'class' => 'internal-store-info-field',
		);
		// country (textfield)
		$inputs3[] = array(
			'type'  => 'text',
			'label' => $this->l('Country'),
			'name'  => 'locCountry',
			'id'    => 'loc-country',
			'class' => 'internal-store-info-field',
			'desc' => $this->l('This value must be in ISO 3166-1 ALPHA 2 format'),
		);
		// postal code (textfield)
		$inputs3[] = array(
			'type'  => 'text',
			'label' => $this->l('Postal Code'),
			'name'  => 'locPostalCode',
			'id'    => 'loc-postal-code',
			'class' => 'internal-store-info-field',
		);
		// locations
		$fieldsForm3 = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Locations'),
					'icon'  => 'icon-location-arrow',
				),
				'input'  => $inputs3,
				'submit' => array(
					'title' => $this->l('Save'),
					'class' => 'btn btn-default pull-right',
					'name'  => 'submitLocations',
				),
			),
		);
		$fields[] = $fieldsForm3;

		// input group 4
		$inputs4[] = array();
		// add contact point fields
		for ($i = 0; $i < $fieldsValue['countContactPoints']; $i++) {
			$headerHtml = '<b class="contact-point-field-' . $i . ' jsonmodule-fieldset-header">Contact Point #<span class="contact-point-number">' . ($i + 1) .'</span>';
			if ($i > 0) {
				$headerHtml .= '<span class="contact-point-remove">(x)</span>';
			}
			$headerHtml .= '</b>';
			$inputs4[] = array(
				'type'         => 'html',
				'name'         => 'html_data_contact_point_' . $i,
				'html_content' => $headerHtml,
			);
			// telephone (textfield)
			$inputs4[] = array(
				'type'  => 'text',
				'label' => $this->l('Telephone'),
				'name'  => 'contactPointsTel_' . $i,
				'id'    => 'contact_points_tel_' . $i,
				'class' => 'contact-point-field-' . $i,
			);
			// email (textfield)
			$inputs4[] = array(
				'type'  => 'text',
				'label' => $this->l('E-Mail Address'),
				'name'  => 'contactPointsEmail_' . $i,
				'id'    => 'contact_points_email_' . $i,
				'class' => 'contact-point-field-' . $i,
			);
			// url (textfield)
			$inputs4[] = array(
				'type'  => 'text',
				'label' => $this->l('URL'),
				'name'  => 'contactPointsUrl_' . $i,
				'id'    => 'contact_points_url_' . $i,
				'class' => 'contact-point-field-' . $i,
			);
			// contact type (textfield)
			$inputs4[] = array(
				'type'    => 'select',
				'label'   => $this->l('Contact Point Type'),
				'name'    => 'contactPointsType_' . $i,
				'id'    => 'contact_points_type_' . $i,
				'class' => 'contact-point-field-' . $i,
				'options' => array(
					'query' => $this->_getContactPointTypes(),
					'id'    => 'id_type',
					'name'  => 'name',
				),
			);
			// use active countries (checkbox)
			$countriesCbClass = 'contact-point-field-' . $i;
			if (!(bool)$fieldsValue['useActiveCountries_'.$i]) {
				$countriesCbClass = 'off '.$countriesCbClass;
			}
			$inputs4[] = array(
				'type'  => 'checkbox',
				'name'  => 'useActiveCountries_' . $i,
				'id'    => 'use_active_countries_' . $i,
				'class' => $countriesCbClass,
				'label' => $this->l('Countries Served'),
				'values' => array(
					'query' => array(
						array(
							'id_option_use_countries' => 0,
							'name'      => $this->l('Use Active Countries'),
						),
					),
					'id'    => 'id_option_use_countries',
					'name'  => 'name',
				),
			);
			// countries served (multi select box)
			$inputs4[] = array(
				'type'      => 'select',
				'multiple'  => true,
				'class'     => 'chosen contact-point-field-' . $i,
				'name'      => 'contactPointsCountries_' . $i . '[]',
				'id'        => 'contact_points_countries_' . $i,
				'options' => array(
					'query' => Country::getCountries((int) Context::getContext()->cookie->id_lang),
					'id'    => 'id_country',
					'name'  => 'name',
				),
			);
			// use active languages (checkbox)
			$languagesCbClass = 'contact-point-field-' . $i;
			if (!(bool)$fieldsValue['useActiveLanguages_'.$i]) {
				$languagesCbClass = 'off '.$languagesCbClass;
			}
			$inputs4[] = array(
				'type'      => 'checkbox',
				'name'      => 'useActiveLanguages_' . $i,
				'id'        => 'use_active_languages_' . $i,
				'class'     => $languagesCbClass,
				'label'     => $this->l('Languages'),
				'values' => array(
					'query' => array(
						array(
							'id_option_use_lang' => 0,
							'name'      => $this->l('Use Active Languages'),
						),
					),
					'id'    => 'id_option_use_lang',
					'name'  => 'name',
				),
			);
			// languages (multi select box)
			$inputs4[] = array(
				'type'      => 'select',
				'multiple'  => true,
				'class'     => 'chosen contact-point-field-' . $i,
				'name'      => 'contactPointsLanguages_' . $i,
				'id'        => 'contact_points_languages_' . $i,
				'options' => array(
					'query' => Language::getLanguages(true, $this->context->shop->id),
					'id'    => 'id_lang',
					'name'  => 'name',
				),
			);
		}
		// contact points
		$fieldsForm4 = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Contact Points'),
					'icon'  => 'icon-credit-card',
				),
				'input'  => $inputs4,
				'buttons' => array(
					'newBlock' => array(
						'title' => $this->l('Add Contact Point'),
						'class' => 'pull-right',
						'id'    => 'btn-add-contact-point',
						'icon'  => 'process-icon-new',
					)
				),
				'submit' => array(
					'title' => $this->l('Save'),
					'class' => 'btn btn-default pull-right',
					'name'  => 'submitContactPoints',
				),
			),
		);
		$fields[] = $fieldsForm4;
		$lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
		$helper = new HelperForm();
		$helper->default_form_language = $lang->id;
		// $helper->submit_action = 'submitUpdate';
		$helper->currentIndex = $this->context->link->getAdminLink('AdminModules',false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->tpl_vars = array(
			'fields_value' => $fieldsValue,
			'languages' => $this->context->controller->getLanguages(),
			'id_language' => $this->context->language->id
		);

		$this->context->controller->addCSS(($this->_path).'css/jsonmodule_global.css', 'all');

		return $helper->generateForm($fields);
	}


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
		)
		{
			// create a config json to store in database
			$config = array();

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
			$config['companyInformation']['companyGooglePlus'] = Tools::getValue('companyGooglePlus');
			if ($logo = $this->_uploadAnyFile('logo')) {
				$config['companyInformation']['logo'] = $logo;
			}
			if ($config['companyInformation']['logo'] == '') {
				$config['companyInformation']['logo'] = Tools::getValue('logo_old');
			}
			// //

			// founding section
			$config['founding']['foundingDate'] = Tools::getValue('foundingDate');
			$config['founding']['foundingStreetAddress'] = Tools::getValue('foundingStreetAddress');
			$config['founding']['foundingLocality'] = Tools::getValue('foundingLocality');
			$config['founding']['foundingRegion'] = Tools::getValue('foundingRegion');
			$config['founding']['foundingCountry'] = Tools::getValue('foundingCountry');
			$config['founding']['foundingPostalCode'] = Tools::getValue('foundingPostalCode');
			// //

			// reviews section
			$config['reviews']['review_type'] = Tools::getValue('review_type');
			$config['reviews']['yotpo_app_id'] = Tools::getValue('yotpo_app_id');
			// //

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
			// //

			// founders section
			$founders = array();
			for ($i=0; $i<$founderCount; $i++) {
				$founder = array(
					'founderName' => Tools::getValue('founderName_'.$i),
					'founderTitle' => Tools::getValue('founderTitle_'.$i),
					'founderGooglePlus' => Tools::getValue('founderGooglePlus_'.$i),
					'founderLinkedin' => Tools::getValue('founderLinkedin_'.$i),
					'founderTwitter' => Tools::getValue('founderTwitter_'.$i),
					'founderPicture' => '',
				);
				if ($img = $this->_uploadAnyFile('founderPicture_'.$i)) {
					$founder['founderPicture'] = $img;
				}
				if ($founder['founderPicture'] == '') {
					$founder['founderPicture'] = Tools::getValue('founderPicture_'.$i.'_old');
				}
				array_push($founders, $founder);
			}
			$config['founders'] = $founders;
			// //

			// locations section
			if ((bool)Tools::getValue('useInternalStoreInfo_0')) {
				$config['locations']['useInternalStoreInfo'] = true;
			} else {
				$config['locations']['useInternalStoreInfo'] = false;
				$config['locations']['locStreetAddress'] = Tools::getValue('locStreetAddress');
				$config['locations']['locLocality'] = Tools::getValue('locLocality');
				$config['locations']['locRegion'] = Tools::getValue('locRegion');
				$config['locations']['locCountry'] = Tools::getValue('locCountry');
				$config['locations']['locPostalCode'] = Tools::getValue('locPostalCode');
			}
			// //

			// contact points section
			$contactPoints = array();
			for ($i=0; $i<$contactPointsCount; $i++) {
				$contactPoint = array(
					'contactPointsTel' => Tools::getValue('contactPointsTel_'.$i),
					'contactPointsEmail' => Tools::getValue('contactPointsEmail_'.$i),
					'contactPointsUrl' => Tools::getValue('contactPointsUrl_'.$i),
					'contactPointsType' => Tools::getValue('contactPointsType_'.$i),
				);
				if (Tools::isSubmit('useActiveCountries_'.$i.'_0')) {
					$contactPoint['useActiveCountries'] = true;
				} else {
					$contactPoint['useActiveCountries'] = false;
					$contactPoint['contactPointsCountries'] = Tools::getValue('contactPointsCountries_'.$i);
				}
				if (Tools::isSubmit('useActiveLanguages_'.$i.'_0')) {
					$contactPoint['useActiveLanguages'] = true;
				} else {
					$contactPoint['useActiveLanguages'] = false;
					$contactPoint['contactPointsLanguages'] = Tools::getValue('contactPointsLanguages_'.$i);
				}
				array_push($contactPoints, $contactPoint);
			}
			$config['contactPoints'] = $contactPoints;
			// //

			Configuration::updateValue(self::JSONMODULE_CONFIG, json_encode($config));

			// Error handling
			if ($this->_errors) {
				$this->_html .= $this->displayError(implode($this->_errors, '<br />'));
			} else {
				// build organization json and store
				if (Configuration::updateValue(self::ORGANIZATION_JSON, $this->_buildJson())) {
					$this->_html .= $this->displayConfirmation($this->l('Settings Updated!'));
				} else {
					$this->_html .= $this->displayError($this->l('An error occurred. Please try again.'));
				}
			}
		}
	}

	private function _uploadAnyFile($key) {
		$fileName = null;
		if (isset($_FILES[$key])
			&& isset($_FILES[$key]['tmp_name'])
			&& !empty($_FILES[$key]['tmp_name'])
		) {
			if ($error = ImageManager::validateUpload($_FILES[$key], 4000000)) {
				return $error;
			} else {
				$ext = substr($_FILES[$key]['name'], strrpos($_FILES[$key]['name'], '.') + 1);
				$fileName = md5($_FILES[$key]['name']).'.'.$ext;

				if (!move_uploaded_file($_FILES[$key]['tmp_name'], dirname(__FILE__).DIRECTORY_SEPARATOR.'img'.DIRECTORY_SEPARATOR.$fileName)) {
					$this->_errors[] = $this->l('Some files could not be uploaded.');
				} else {
					$config[$key] = $fileName;
				}
			}
		}
		return $fileName;
	}

	private function _checkVariable($arr, $var)
	{
		if ($value = $arr[$var]) {
			if (!empty($value)) {
				return true;
			}
		}
		return false;
	}

	private function _buildJson()
	{
//		d(json_decode(Configuration::get(SELF::JSONMODULE_CONFIG), true));
		$json = array();
		$config = json_decode(Configuration::get(SELF::JSONMODULE_CONFIG), true);

		if ($companyInformation = $config['companyInformation']) {
			$companyInfoIndices = array(
				'name' => 'companyName',
				'legalName' => 'companyLegalName',
				'url' => 'websiteUrl',
				'alternateName' => 'alternativeName',
				'description' => 'description',
			);
			foreach ($companyInfoIndices as $key => $value) {
				if ($this->_checkVariable($companyInformation, $value)) {
					$json[$key] = $companyInformation[$value];
				}
			}
			if ($this->_checkVariable($companyInformation, 'logo')) {
				$json['image'] = _PS_BASE_URL_.$this->_path.'img'.DIRECTORY_SEPARATOR.$companyInformation['logo'];
			}
			$sameAs = array();
			$socialIndices = array(
				'companyFacebook',
				'companyTwitter',
				'companyYoutube',
				'companyLinkedin',
				'companyGooglePlus',
			);
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
			$arrFounding = array();
			$foundingIndices = array(
				'streetAddress' => 'foundingStreetAddress',
				'addressLocality' => 'foundingLocality',
				'addressRegion' => 'foundingRegion',
				'addressCountry' => 'foundingCountry',
				'postalCode' => 'foundingPostalCode',
			);
			foreach ($foundingIndices as $key => $value) {
				if ($this->_checkVariable($founding, $value)) {
					$arrFounding[$key] = $founding[$value];
				}
			}
			if (count($arrFounding) > 0) {
				$arrFounding['@type'] = 'PostalAddress';
				$json['foundingLocation'] = array(
					"@type" => "Place",
					'address' => $arrFounding,
				);
			}
		}

		if ($founders = $config['founders']) {
			$arrFounder = array();
			foreach ($founders as $founder) {
				$aFounder = array();
				$founderIndices = array(
					'name' => 'founderName',
					'jobTitle' => 'founderTitle',
				);
				foreach ($founderIndices as $key => $value) {
					if ($this->_checkVariable($founder, $value)) {
						$aFounder[$key] = $founder[$value];
					}
				}
				if ($this->_checkVariable($founder, 'founderPicture')) {
					$aFounder['image'] = _PS_BASE_URL_.$this->_path.'img'.DIRECTORY_SEPARATOR.$founder['founderPicture'];
				}

				$sameAs = array();
				$socialIndices = array(
					'founderGooglePlus',
					'founderLinkedin',
					'founderTwitter',
				);
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
			$arrContactPoint = array();
			foreach ($contactPoints as $cPoint) {
				$aCPoint = array();
				$founderIndices = array(
					'telephone' => 'contactPointsTel',
					'email' => 'contactPointsEmail',
					'url' => 'contactPointsUrl',
				);
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
			if ($this->_checkVariable($locations, 'useInternalStoreInfo') && (bool)$locations['useInternalStoreInfo']) {
				// TODO: fetch info from store
			} else {
				$address = array();
				$addressIndices = array(
					'streetAddress' => 'locStreetAddress',
					'addressLocality' => 'locLocality',
					'addressRegion' => 'locRegion',
					'addressCountry' => 'locCountry',
					'postalCode' => 'locPostalCode',
				);
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

	private function _getContactPointTypes()
	{
		return array(
			array(
				'id_type' => "customer support",
				'name' => "Customer Support",
			),
			array(
				'id_type' => "technical support",
				'name' => "Technical Support",
			),
			array(
				'id_type' => "billing support",
				'name' => "Billing Support",
			),
			array(
				'id_type' => "bill payment",
				'name' => "Bill Payment",
			),
			array(
				'id_type' => "sales",
				'name' => "Sales",
			),
			array(
				'id_type' => "reservations",
				'name' => "Reservations",
			),
			array(
				'id_type' => "credit card support",
				'name' => "Credit Card Support",
			),
			array(
				'id_type' => "emergency",
				'name' => "Emergency",
			),
			array(
				'id_type' => "baggage tracking",
				'name' => "Baggage Tracking",
			),
			array(
				'id_type' => "roadside assistance",
				'name' => "Roadside Assistance",
			),
			array(
				'id_type' => "package tracking",
				'name' => "Package Tracking",
			),
		);
	}

	private function _getConfig()
	{
//		$config_keys = array_keys($this->_config);
//		return Configuration::getMultiple($config_keys);
		$config = json_decode(Configuration::get(SELF::JSONMODULE_CONFIG), true);
		// rebuild the configuration array tree
		$retArr = array();
		$simpleArrays = array_merge($config['companyInformation'], $config['founding'], $config['locations'], $config['reviews']);
		foreach ($simpleArrays as $key => $value) {
			$retArr[$key] = $value;
			if ($key == 'logo') {
				$retArr[$key.'_old'] = $value;
			}
		}
		// //
		// founders
		for ($i=0; $i<count($config['founders']); $i++) {
			$founder = $config['founders'][$i];
			foreach (array_keys($founder) as $key) {
				$retArr[$key.'_'.$i] = $founder[$key];
				if ($key == 'founderPicture') {
					$retArr[$key.'_'.$i.'_old'] = $founder[$key];
				}
			}
		}
		$retArr['countFounders'] = count($config['founders']) > 0 ? count($config['founders']) : 1;
		// //
		// contact points
		for ($i=0; $i<count($config['contactPoints']); $i++) {
			$contactPoint = $config['contactPoints'][$i];
			foreach (array_keys($contactPoint) as $key) {
				$retArr[$key.'_'.$i] = $contactPoint[$key];
				if ($key == 'contactPointsCountries' || $key == 'contactPointsLanguages') {
					$retArr[$key.'_'.$i.'[]'] = $contactPoint[$key];
				}
			}
		}
		$retArr['countContactPoints'] = count($config['contactPoints']) > 0 ? count($config['contactPoints']) : 1;
		// $retArr['countLanguages'] = count($config['languages']);
		// //
//		d($retArr);
		return $retArr;
	}

    public function getPath($id_category, $path = [], $link_on_the_item = false, $category_type = 'products', Context $context = null)
    {
        if (!$context) {
            $context = Context::getContext();
        }

        $id_category = (int)$id_category;
        if ($id_category == 1) {
            return '<span class="navigation_end">'.$path.'</span>';
        }

        $pipe = Configuration::get('PS_NAVIGATION_PIPE');
        if (empty($pipe)) {
            $pipe = '>';
        }

        $full_path = [];
        if ($category_type === 'products') {
            $interval = Category::getInterval($id_category);
            $id_root_category = $context->shop->getCategory();
            $interval_root = Category::getInterval($id_root_category);
            if ($interval) {
                $sql = 'SELECT c.id_category, cl.name, cl.link_rewrite
						FROM '._DB_PREFIX_.'category c
						LEFT JOIN '._DB_PREFIX_.'category_lang cl ON (cl.id_category = c.id_category'.Shop::addSqlRestrictionOnLang('cl').')
						'.Shop::addSqlAssociation('category', 'c').'
						WHERE c.nleft <= '.$interval['nleft'].'
							AND c.nright >= '.$interval['nright'].'
							AND c.nleft >= '.$interval_root['nleft'].'
							AND c.nright <= '.$interval_root['nright'].'
							AND cl.id_lang = '.(int)$context->language->id.'
							AND c.active = 1
							AND c.level_depth > '.(int)$interval_root['level_depth'].'
						ORDER BY c.level_depth ASC';
                $categories = Db::getInstance()->executeS($sql);

                $n = 1;
                $n_categories = count($categories);
                foreach ($categories as $category) {
                	$full_path[] = ['name' => $category['name'], 'url' => $context->link->getCategoryLink((int)$category['id_category'], $category['link_rewrite'])];
                    // $full_path .=
                    // (($n < $n_categories || $link_on_the_item) ? '<a href="'.Tools::safeOutput($context->link->getCategoryLink((int)$category['id_category'], $category['link_rewrite'])).'" title="'.htmlentities($category['name'], ENT_NOQUOTES, 'UTF-8').'" data-gg="">' : '').
                    // htmlentities($category['name'], ENT_NOQUOTES, 'UTF-8').
                    // (($n < $n_categories || $link_on_the_item) ? '</a>' : '').
                    // (($n++ != $n_categories || !empty($path)) ? '<span class="navigation-pipe">'.$pipe.'</span>' : '');
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
                // $full_path .= '<a href="'.Tools::safeOutput($category_link).'" data-gg="">'.htmlentities($category->name, ENT_NOQUOTES, 'UTF-8').'</a><span class="navigation-pipe">'.$pipe.'</span>'.$path;
            } else {
            	$full_path[] = ['name' => $path, 'url' => $category_link];
                // $full_path = ($link_on_the_item ? '<a href="'.Tools::safeOutput($category_link).'" data-gg="">' : '').htmlentities($path, ENT_NOQUOTES, 'UTF-8').($link_on_the_item ? '</a>' : '');
            }

            return $this->getPath($category->id_parent, $full_path, $link_on_the_item, $category_type);
        }
    }

	public function hookDisplayHeader($params)
	{

		$path = [];
		$config = json_decode(Configuration::get(SELF::JSONMODULE_CONFIG), true);
		if(isset($this->context->controller->php_self) && 'product' == $this->context->controller->php_self) {

			$product = new Product((int)Tools::getValue('id_product'), true, $this->context->language->id);
			if (!Validate::isLoadedObject($product))
				return false;

			$cover = Product::getCover($product->id);
			$image = $this->context->link->getImageLink($product->link_rewrite, $cover['id_image']);
			$this->context->smarty->assign(array(
				'product'=> $product,

			));


			// reviews
			$nbReviews = 0;
			$avgDecimal = 0;
			$review_result = [];
			if($config['reviews']['review_type'] == 2 && $config['reviews']['yotpo_app_id'] && Module::isInstalled('yotpo'))
			{
				// Yotpo reviews
				$ch = curl_init();
		        curl_setopt($ch, CURLOPT_URL, 'https://api.yotpo.com/v1/widget/'.$config['reviews']['yotpo_app_id'].'/products/'.$product->id.'/reviews.json?star=5&sort[]=date&sort[]=votes_up');
		        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		        $result = curl_exec($ch);
		        curl_close($ch);

		        if($result)
		        	$review_result = Tools::jsonDecode($result, true);

		        if($review_result && $review_result['status']['code'] == 200)
		        {
		        	$nbReviews = $review_result['response']['bottomline']['total_review'];
		        	$avgDecimal = Tools::ps_round($review_result['response']['bottomline']['average_score'], 1);
		        }
			} else if ($config['reviews']['review_type'] == 1 && Module::isInstalled('productcomments'))
			{


				$avgDecimal = ProductComment::getAverageGrade($product->id);
				$nbReviews = (int) ProductComment::getCommentNumber($product->id);
			}




			$this->context->smarty->assign(array(
				'isproduct'=> 1
			));
			$path = $this->getPath($product->id_category_default);

			// build json for product page
			$arrProduct = array();
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
				$arrProduct['brand'] = array(
					'@type' => 'Thing',
					'name' => $product->manufacturer_name,
					'logo' => $this->context->shop->getBaseURL() . "/img/m/$product->id_manufacturer.jpg"
				);
			}
			if ($nbReviews > 0 && $avgDecimal > 0) {
				$arrProduct['aggregateRating'] = array(
					'@type' => 'AggregateRating',
					'reviewCount' => $nbReviews,
					'ratingValue' => $avgDecimal,
				);
			}

			if(!empty($product->price)) {
				$offers = array(
					'@type' => 'Offer',
					'priceCurrency' => $this->context->currency->iso_code,
					'price' => $product->price,
					'itemCondition' => 'http://schema.org/NewCondition', // TODO
					'seller' => array(
						'@type' => 'Organization',
						'name' => Configuration::get('PS_SHOP_NAME'),
					),
				);
				if (!empty($product->quantity) && $product->quantity > 0) {
					$offers['availability'] = 'http://schema.org/InStock';
				} else {
					$offers['availability'] = 'http://schema.org/OutOfStock';
				}
				if (!empty($product->specificPrice) && !empty($product->specificPrice->to)) {
					$offers['priceValidUntil'] = Tools::dateFormat($product->specificPrice->to); // TODO: format date
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


		if(is_array($path) && $path)
		{
			$this->context->smarty->assign(array(
				'path'=> $path
			));
		}

		foreach ($this->_config as $key => $unused)
			$to_assign[$key] = Configuration::get($key);

		$this->context->smarty->assign($to_assign);

		/*

		$site_url = $this->context->link->getPageLink('index');

		$needed_fields = [
		'PS_SHOP_NAME',
		'PS_SHOP_EMAIL',
		'PS_SHOP_ADDR1',
		'PS_SHOP_ADDR2',
		'PS_SHOP_CODE',
		'PS_SHOP_CITY',
		'PS_SHOP_COUNTRY_ID',
		'PS_SHOP_STATE_ID',
		'PS_SHOP_PHONE'
		];
		$config = Configuration::getMultiple($needed_fields);

		$this->context->smarty->assign($config);

		if($config['PS_SHOP_COUNTRY_ID'])
		{
			$country = new Country($config['PS_SHOP_COUNTRY_ID']);
			$this->context->smarty->assign(array(
				'country_iso' => $country->iso_code
			));
		}
		if($config['PS_SHOP_STATE_ID'])
		{
			$state = new State($config['PS_SHOP_STATE_ID']);
			$this->context->smarty->assign(array(
				'state_iso' => $state->iso_code
			));
		}

		$this->context->smarty->assign(array(
			'logo'=> $this->context->link->getMediaLink(_PS_IMG_.Configuration::get('PS_LOGO_MOBILE').'?'.Configuration::get('PS_IMG_UPDATE_TIME')),
			'site_url' => $this->context->link->getPageLink('index')
		));

		*/


		$this->context->smarty->assign(array(
			self::ORGANIZATION_JSON => Configuration::get(self::ORGANIZATION_JSON),
			self::PRODUCT_JSON => isset($arrProduct) ? json_encode($arrProduct) : '',
		));

		return $this->display(__FILE__, 'jsonmodule.tpl');



	}

}
