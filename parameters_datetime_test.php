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

        file_put_contents(BP.'/var/log/sl_logs/test_timestamp.dat', 'modelData last_sync: '.print_r($modelData,1).PHP_EOL, FILE_APPEND);

        $time_lapsed = '';
        if(!empty($modelData['last_sync'])){
            // $time_lapsed = '<br><small>Since last sync of this connector step: '.$this->elapsed_time(strtotime( $modelData['last_sync'])).'</small>';

            // $timezone = $this->getConfiguration()['timezone'];
            // $datetimeformat = $this->timezone->getDateTimeFormat(\IntlDateFormatter::MEDIUM);
            // file_put_contents(BP.'/var/log/sl_logs/test_timestamp.dat', 'datetimeformat: '.print_r($datetimeformat,1).PHP_EOL, FILE_APPEND);
    
            // $default_timezone = $this->timezone->getDefaultTimezone();
            // file_put_contents(BP.'/var/log/sl_logs/test_timestamp.dat', 'default_timezone: '.print_r($default_timezone,1).PHP_EOL, FILE_APPEND);
            $config_timezone = $this->timezone->getConfigTimezone();
            file_put_contents(BP.'/var/log/sl_logs/test_timestamp.dat', 'config_timezone: '.print_r($config_timezone,1).PHP_EOL, FILE_APPEND);
            // $last_sync_timezoned = new DateTime(strtotime($modelData['last_sync']), new DateTimeZone($this->timezone->getConfigTimezone()));
            // file_put_contents(BP.'/var/log/sl_logs/test_timestamp.dat', 'last_sync_timezoned: '.print_r($last_sync_timezoned,1).PHP_EOL, FILE_APPEND);
            // $now = new \DateTime(null, new \DateTimeZone($this->timezone->getConfigTimezone()));
            // file_put_contents(BP.'/var/log/sl_logs/test_timestamp.dat', 'now timezoned: '.print_r($now,1).PHP_EOL, FILE_APPEND);

            // $now = new \DateTime(null, timezone_open('Pacific/Nauru'));
            // $now::setTimezone($config_timezone);
            // $now = new \DateTime();
            // file_put_contents(BP.'/var/log/sl_logs/test_timestamp.dat', 'now: '.print_r($now,1).PHP_EOL, FILE_APPEND);
            // $chicaco = new \DateTime('now', new \DateTimeZone($config_timezone));
            // file_put_contents(BP.'/var/log/sl_logs/test_timestamp.dat', 'chicaco: '.print_r($chicaco,1).PHP_EOL, FILE_APPEND);
            // $timezone = \DateTimeZone::listIdentifiers(\DateTimeZone::PER_COUNTRY, 'US');
            // file_put_contents(BP.'/var/log/sl_logs/test_timestamp.dat', 'timezone: '.print_r($timezone,1).PHP_EOL, FILE_APPEND);
            // $datetimezone = new \DateTimeZone($config_timezone);
            // file_put_contents(BP.'/var/log/sl_logs/test_timestamp.dat', 'datetimezone: '.print_r($datetimezone,1).PHP_EOL, FILE_APPEND);


            
            
            // $original = new DateTime("now", new DateTimeZone('UTC'));
            // $timezoneName = timezone_name_from_abbr("", 3*3600, false);
            // $modified = $original->setTimezone(new DateTimezone($timezoneName));
            
            
            $last_sync = new \DateTime($modelData['last_sync']);
            file_put_contents(BP.'/var/log/sl_logs/test_timestamp.dat', 'last_sync: '.print_r($last_sync,1).PHP_EOL, FILE_APPEND);
            $last_sync_timezoned = new \DateTime($modelData['last_sync'], new \DateTimeZone($config_timezone));
            file_put_contents(BP.'/var/log/sl_logs/test_timestamp.dat', 'last_sync_timezoned: '.print_r($last_sync_timezoned,1).PHP_EOL, FILE_APPEND);
            
            $last_sync_format = $this->timezone->formatDate($last_sync, 'Y-m-d H:i:s', true);
            file_put_contents(BP.'/var/log/sl_logs/test_timestamp.dat', 'last_sync_format: '.print_r($last_sync_format,1).PHP_EOL, FILE_APPEND);
            // $time_lapsed = "<br><small>Connector's last synchronization: ".$last_sync_timezoned."</small>";
            // $time_lapsed = "<br><small>Connector's last synchronization: ".$last_sync_timezoned."</small>";


            $test = $this->timezone->date($modelData['last_sync'])->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
            file_put_contents(BP.'/var/log/sl_logs/test_timestamp.dat', 'test: '.print_r($test,1).PHP_EOL, FILE_APPEND);
            $test2 = $this->timezone->date($modelData['last_sync'])->format('M d, Y, H:i:s A');
            file_put_contents(BP.'/var/log/sl_logs/test_timestamp.dat', 'test2: '.print_r($test2,1).PHP_EOL, FILE_APPEND);
            // echo $dateObject->format('M d, Y, H:i:s A');


        }


        // if(!empty($modelData['last_sync'])){

        //     $fieldset->addField(
        //             'conn_last_sync',
        //             'date',
        //             [
        //                 'name' => 'conn_last_sync',
        //                 'label' => __("Connector's last synchronization"),
        //                 'title' => __("Connector's last synchronization"),
        //                 'required' => true,
        //                 'date_format' => 'yyyy-MM-dd',
        //                 'time_format' => 'hh:mm:ss',
        //                 'value' => strtotime($modelData['last_sync'])
        //             ]
        //     );

        // }

        // $date = $this->timezone->date(new \DateTime($item[$this->getData('name')]));
        // $timezone = isset($this->getConfiguration()['timezone'])
        //     ? $this->booleanUtils->convert($this->getConfiguration()['timezone'])
        //     : true;
        // if (!$timezone) {
        //     $date = new \DateTime($item[$this->getData('name')]);
        // }
        // $item[$this->getData('name')] = $date->format('Y-m-d H:i:s');

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
                'after_element_html' => $time_lapsed,
                'class' => 'conn_field'
            ]
        );
        
        if(!empty($modelData) && isset($modelData['auto_sync']) && $modelData['auto_sync']>= 24){
            $hour_input_disabled = false;
        }else{
            $hour_input_disabled = true;
        }

        $auto_sync_hour_options = [];
        $hours_range = range(0, 23);
        foreach($hours_range as $hour){
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

        } else {
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
    private function elapsed_time($timestamp, $precision = 2) {
        
        file_put_contents(BP.'/var/log/sl_logs/test_timestamp.dat', 'elapsed_time() - timestamp: '.print_r($timestamp,1).PHP_EOL, FILE_APPEND);
        file_put_contents(BP.'/var/log/sl_logs/test_timestamp.dat', 'elapsed_time() - precision: '.print_r($precision,1).PHP_EOL, FILE_APPEND);
        $time = time() - $timestamp;
        file_put_contents(BP.'/var/log/sl_logs/test_timestamp.dat', 'elapsed_time() - time: '.print_r($time,1).PHP_EOL, FILE_APPEND);
        $result = '';
        $a = array('decade' => 315576000, 'year' => 31557600, 'month' => 2629800, 'week' => 604800, 'day' => 86400, 'hour' => 3600, 'min' => 60, 'sec' => 1);
        $i = 0;
        foreach($a as $k => $v) {
            $$k = floor($time/$v);
            if ($$k) $i++;
            $time = $i >= $precision ? 0 : $time - $$k * $v;
            $s = $$k > 1 ? 's' : '';
            $$k = $$k ? $$k.' '.$k.$s.' ' : '';
            $result .= $$k;
        }
        file_put_contents(BP.'/var/log/sl_logs/test_timestamp.dat', 'elapsed_time() - result: '.print_r($result,1).PHP_EOL, FILE_APPEND);
        return $result ? $result.'ago' : '1 sec to go';
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
