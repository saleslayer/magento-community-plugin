<?php
namespace Saleslayer\Synccatalog\Block\Adminhtml\Synccatalog;

/**
 * Adminhtml synccatalog pages grid
 */
class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var \Saleslayer\Synccatalog\Model\ResourceModel\Synccatalog\CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @var \Saleslayer\Synccatalog\Model\Synccatalog
     */
    protected $_synccatalog;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Saleslayer\Synccatalog\Model\Synccatalog $synccatalog
     * @param \Saleslayer\Synccatalog\Model\ResourceModel\Synccatalog\CollectionFactory $collectionFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Saleslayer\Synccatalog\Model\Synccatalog $synccatalog,
        \Saleslayer\Synccatalog\Model\ResourceModel\Synccatalog\CollectionFactory $collectionFactory,
        \Magento\Store\Model\System\Store $systemStore,
        array $data = []
    ) {
        $this->_collectionFactory = $collectionFactory;
        $this->_synccatalog = $synccatalog;
        $this->_systemStore = $systemStore;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('synccatalogGrid');
        $this->setDefaultSort('id');
        $this->setDefaultDir('DESC');
        $this->setUseAjax(true);
        $this->setSaveParametersInSession(true);
    }

    /**
     * Prepare collection
     *
     * @return \Magento\Backend\Block\Widget\Grid
     */
    protected function _prepareCollection()
    {
        $collection = $this->_collectionFactory->create();
        /* @var $collection \Saleslayer\Synccatalog\Model\ResourceModel\Synccatalog\Collection */

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * Prepare columns
     *
     * @return \Magento\Backend\Block\Widget\Grid\Extended
     */
    protected function _prepareColumns()
    {
        $this->addColumn('id', [
            'header'    => __('ID'),
            'index'     => 'id',
        ]);
        
        $this->addColumn('connector_id', ['header' => __('Connector ID'), 'index' => 'connector_id']);

        // $this->addColumn('store_view_ids', ['header' => __('Store View IDs'), 'index' => 'store_view_ids']);
        // $this->addColumn('secret_key', ['header' => __('Secret Key'), 'index' => 'secret_key']);
        
        $this->addColumn(
            'last_update',
            [
                'header' => __('Last Update'),
                'index' => 'last_update',
                'type' => 'datetime',
                'header_css_class' => 'col-date',
                'column_css_class' => 'col-date'
            ]
        );

        // $this->addColumn(
        //     'sync_data',
        //     [
        //         'header' => __('Synchronization Data'),
        //         'index' => 'sync_ata',
        //         'header_css_class' => 'col-sync',
        //         'column_css_class' => 'col-sync'
        //     ]
        // );

        $test = parent::_prepareColumns();

        // $file = BP.'/_debbug_log_saleslayer_test.txt';

        // foreach ($test as $key => $t) {
        //     // file_put_contents($file, 'test_data: '.print_r($test->getMultipleRows($test),1), FILE_APPEND);
        //     file_put_contents($file, 'test_data: '.print_r($t,1), FILE_APPEND);
        // }

        return $test;
        // return parent::_prepareColumns();
    }

    /**
     * Row click url
     *
     * @param \Magento\Framework\Object $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', ['id' => $row->getId()]);
    }

    /**
     * Get grid url
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', ['_current' => true]);
    }
}
