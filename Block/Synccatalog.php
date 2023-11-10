<?php
namespace Saleslayer\Synccatalog\Block;

/**
 * Synccatalog contentf block
 */
class Synccatalog extends \Magento\Framework\View\Element\Template
{
    /**
     * Synccatalog object collection
     *
     * @var object $_synccatalogCollection
     */
    protected $_synccatalogCollection = null;
    
    /**
     * Synccatalog factory
     *
     * @var \Saleslayer\Synccatalog\Model\ResourceModel\Synccatalog\Collection
     */
    protected $_synccatalogCollectionFactory;
    
    /**
     * Synccatalog Data helper
     *
     * @var \Saleslayer\Synccatalog\Helper\Data
     */
    protected $_dataHelper;
    
    /**
     * @param \Magento\Framework\View\Element\Template\Context                     $context
     * @param \Saleslayer\Synccatalog\Model\Resource\Synccatalog\CollectionFactory $synccatalogCollectionFactory
     * @param \Saleslayer\Synccatalog\Helper\Data                                  $dataHelper
     * @param array                                                                $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Saleslayer\Synccatalog\Model\ResourceModel\Synccatalog\CollectionFactory $synccatalogCollectionFactory,
        \Saleslayer\Synccatalog\Helper\Data $dataHelper,
        array $data = []
    ) {
        $this->_synccatalogCollectionFactory = $synccatalogCollectionFactory;
        $this->_dataHelper = $dataHelper;
        parent::__construct(
            $context,
            $data
        );
    }
    
    /**
     * Retrieve synccatalog collection
     *
     * @return Saleslayer_Synccatalog_Model_ResourceModel_Synccatalog_Collection
     */
    protected function _getCollection()
    {
        $collection = $this->_synccatalogCollectionFactory->create();
        return $collection;
    }
    
    /**
     * Retrieve prepared synccatalog collection
     *
     * @return Saleslayer_Synccatalog_Model_Resource_Synccatalog_Collection
     */
    public function getCollection()
    {
        if (null === $this->_synccatalogCollection) {
            $this->_synccatalogCollection = $this->_getCollection();
            $this->_synccatalogCollection->setCurPage($this->getCurrentPage());
            $this->_synccatalogCollection->setOrder('last_update', 'asc');
        }

        return $this->_synccatalogCollection;
    }
    
    /**
     * Fetch the current page for the synccatalog list
     *
     * @return int
     */
    public function getCurrentPage()
    {
        return $this->getData('current_page') ? $this->getData('current_page') : 1;
    }
    
    /**
     * Return URL to item's view page
     *
     * @param  Saleslayer_Synccatalog_Model_Synccatalog $synccatalogItem
     * @return string
     */
    public function getItemUrl($synccatalogItem)
    {
        return $this->getUrl('*/*/view', ['id' => $synccatalogItem->getId()]);
    }
}
