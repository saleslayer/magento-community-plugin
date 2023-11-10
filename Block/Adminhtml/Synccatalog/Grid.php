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
    protected $_systemStore;

    /**
     * @param \Magento\Backend\Block\Template\Context                                   $context
     * @param \Magento\Backend\Helper\Data                                              $backendHelper
     * @param \Saleslayer\Synccatalog\Model\Synccatalog                                 $synccatalog
     * @param \Saleslayer\Synccatalog\Model\ResourceModel\Synccatalog\CollectionFactory $collectionFactory
     * @param array                                                                     $data
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
        $this->addColumn(
            'id', [
            'header'    => __('ID'),
            'index'     => 'id',
            ]
        );
        
        $this->addColumn('connector_id', ['header' => __('Connector ID'), 'index' => 'connector_id']);

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

        return parent::_prepareColumns();
    }

    /**
     * Row click url
     *
     * @param  \Magento\Framework\Object $row
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
