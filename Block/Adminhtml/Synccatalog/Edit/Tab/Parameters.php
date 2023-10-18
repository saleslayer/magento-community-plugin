<?php
namespace Saleslayer\Synccatalog\Block\Adminhtml\Synccatalog\Edit\Tab;

use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Saleslayer\Synccatalog\Helper\slJson as slJson;

/**
 * Synccatalog page edit form Parameters tab
 */
class Parameters extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;
    protected $slJson;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param Saleslayer\Synccatalog\Helper\slJson $slJson
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        TimezoneInterface $timezone,
        slJson $slJson,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
        $this->_systemStore = $systemStore;
        $this->timezone = $timezone;
        $this->slJson = $slJson;
    }

    /**
     * Prepare form
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        /* @var $model \Magento\Cms\Model\Page */
        $model = $this->_coreRegistry->registry('synccatalog');

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();

        $form->setHtmlIdPrefix('synccatalog_main_');

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('General Parameters')]);

        if ($model->getId()) {
            $fieldset->addField('id', 'hidden', ['name' => 'id']);
        }

        $modelData = $model->getData();

        if (empty($modelData)){
            $modelData['store_view_ids'] = array('0') ;
        }else{
            $modelData['store_view_ids'] = $this->slJson->unserialize($modelData['store_view_ids']);
        }

        $auto_sync_options = [];
        $auto_sync_values = array(0, 1, 3, 6, 8, 12, 15, 24, 48, 72);
        
        foreach ($auto_sync_values as $auto_sync_value) {
            if ($auto_sync_value == 0){
                array_push($auto_sync_options, array('label' => ' ', 'value' => $auto_sync_value));
            }else{
                array_push($auto_sync_options, array('label' => $auto_sync_value.'H', 'value' => $auto_sync_value));
            }
        }

        $datetime_last_sync = '';
        
        if (!empty($modelData['last_sync'])){
            $last_sync_timezoned = $this->timezone->date($modelData['last_sync'])->format('M d, Y, H:i:s A');
            $datetime_last_sync = "<br><small>Connector's last auto-synchronization: ".$last_sync_timezoned."</small>";
            $datetime_last_sync .= "<br><small><strong>*Note: This synchronization will be executed according to the server time.</strong></small>";
        }

        $fieldset->addField(
            'auto_sync',
            'select',
            [
                'name' => 'auto_sync',
                'label' => __('Auto Synchronization Every'),
                'title' => __('Auto Synchronization Every'),
                'id' => 'auto_sync',
                'required' => false,
                'values' => $auto_sync_options,
                'disabled' => false,
                'after_element_html' => $datetime_last_sync,
                'class' => 'conn_field'
            ]
        );
        
        if (!empty($modelData) && isset($modelData['auto_sync']) && $modelData['auto_sync'] >= 24){
            $hour_input_disabled = false;
        }else{
            $hour_input_disabled = true;
        }

        $auto_sync_hour_options = [];
        $hours_range = range(0, 23);
        foreach ($hours_range as $hour){
            $auto_sync_hour_options[$hour] = array('label' => (strlen($hour) == 1 ? '0'.$hour : $hour).':00', 'value' => $hour);
        }

        $fieldset->addField(
            'auto_sync_hour',
            'select',
            [
                'name' => 'auto_sync_hour',
                'label' => __('Preferred auto-sync hour'),
                'title' => __('Preferred auto-sync hour'),
                'id' => 'auto_sync_hour',
                'required' => false,
                'values' => $auto_sync_hour_options,
                'disabled' =>  $hour_input_disabled,
                'after_element_html' =>'<br><small id="servertime">Current server time: '.date('H:i').'</small>',
                'class' => 'conn_field'
            ]
        );

        if (!$this->_storeManager->isSingleStoreMode()) {
            $fieldset->addField(
                'store_view_ids',
                'multiselect',
                [
                    'name' => 'store_view_ids[]',
                    'label' => __('Store View'),
                    'title' => __('Store View'),
                    'required' => false,
                    'values' => $this->_systemStore->getStoreValuesForForm(false, true),
                    'disabled' => false,
                    'after_element_html' => "<br><small>If only 'All Store Views' is selected, the information will by synchronized at all store views, otherwise only in the selected ones.</small>",
                    'class' => 'conn_field'
                ]
            );

        }else{
            $fieldset->addField(
                'store_view_ids',
                'hidden',
                ['name' => 'store_view_ids[]', 'value' => $this->_storeManager->getStore(true)->getId()]
            );
            $model->setStoreIds($this->_storeManager->getStore(true)->getId());
        }

        $this->_eventManager->dispatch('adminhtml_synccatalog_edit_tab_parameters_prepare_form', ['form' => $form]);

        $form->setValues($modelData);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * Prepare label for tab
     *
     * @return string
     */
    public function getTabLabel()
    {
        return __('General parameters');
    }

    /**
     * Prepare title for tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return __('General parameters');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }

}
