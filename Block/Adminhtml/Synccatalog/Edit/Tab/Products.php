<?php
namespace Saleslayer\Synccatalog\Block\Adminhtml\Synccatalog\Edit\Tab;

use Saleslayer\Synccatalog\Helper\slJson as slJson;

/**
 * Synccatalog page edit form Parameters tab
 */
class Products extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{

    /**
     * @var \Magento\Catalog\Model\Category
     */
    protected $categoryModel;
    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection $collectionAttribute
     */
    protected $collectionAttribute; 
    /**
     * @var \Magento\Catalog\Model\Product
     */
    protected $productModel;
    /**
     * @var $setAttribute
     */
    protected $setAttribute;
    /**
     * @var \Magento\ConfigurableProduct\Model\ConfigurableAttributeHandler
     */
    protected $configurableAttributeHandler;
    protected $slJson;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\ConfigurableProduct\Ui\DataProvider\Attributes $attributesDataProvider
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection $collectionAttribute
     * @param \Magento\Catalog\Model\Product $productModel,
     * @param \Magento\Eav\Model\Entity\Attribute\Set $setAttribute,
     * @param \Magento\ConfigurableProduct\Model\ConfigurableAttributeHandler $configurableAttributeHandler
     * @param Saleslayer\Synccatalog\Helper\slJson $slJson
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection $collectionAttribute,
        \Magento\Catalog\Model\Product $productModel,
        \Magento\Eav\Model\Entity\Attribute\Set $setAttribute,
        \Magento\ConfigurableProduct\Model\ConfigurableAttributeHandler $configurableAttributeHandler,
        slJson $slJson,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
        $this->collectionAttribute          = $collectionAttribute;
        $this->productModel                 = $productModel;
        $this->setAttribute                 = $setAttribute;
        $this->configurableAttributeHandler = $configurableAttributeHandler;
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

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Products parameters')]);

        if ($model->getId()) {
            $fieldset->addField('id', 'hidden', ['name' => 'id']);
        }

        $modelData = $model->getData();

        $avoid_stock_update = false;
        $products_previous_categories = true;

        if (empty($modelData)){
            $modelData['store_view_ids'] = array('0') ;
        }else{
            if (isset($modelData['store_view_ids'])
                && !is_array($modelData['store_view_ids'])
                && null !== $modelData['store_view_ids']) {
                $modelData['store_view_ids'] = $this->slJson->unserialize($modelData['store_view_ids']);
            }else{
                $modelData['store_view_ids'] = array('0') ;
            }
            if (isset($modelData['format_configurable_attributes'])
                && !is_array($modelData['format_configurable_attributes'])
                && null !== $modelData['format_configurable_attributes']){
                $modelData['format_configurable_attributes'] = $this->slJson->unserialize($modelData['format_configurable_attributes']);
            }
            if (isset($modelData['avoid_stock_update']) && $modelData['avoid_stock_update'] == '1'){
                $avoid_stock_update = true;
            }
            if (isset($modelData['products_previous_categories']) && $modelData['products_previous_categories'] == '0'){
                $products_previous_categories = false;
            }
        }

        $fieldset->addField(
            'avoid_stock_update',
            'checkbox',
            [
                'name' => 'avoid_stock_update',
                'label' => __('Avoid stock update'),
                'title' => __('Avoid stock update'),
                'required' => false,
                'checked' => $avoid_stock_update,
                'disabled' => false,
                'class' => 'conn_field'
            ]
        );

        $fieldset->addField(
            'products_previous_categories',
            'checkbox',
            [
                'name' => 'products_previous_categories',
                'label' => __('Product in Previous Categories'),
                'title' => __('Product in Previous Categories'),
                'required' => false,
                'checked' => $products_previous_categories,
                'disabled' => false,
                'class' => 'conn_field'
            ]
        );

        $configurable_attributes = $all_attributes = $attributes_multi = [];

        $entityType = $this->productModel->getResource()->getTypeId();
        $this->collectionAttribute->addFieldToFilter($this->setAttribute::KEY_ENTITY_TYPE_ID, $entityType);
        $collection = $this->configurableAttributeHandler->getApplicableAttributes();

        if (!empty($collection)){

            foreach ($collection->getItems() as $attribute) {
            
                if ($this->configurableAttributeHandler->isAttributeApplicable($attribute)) {
            
                    $configurable_attributes[] = $attribute;
            
                }
            
            }

        }

        if (!empty($configurable_attributes)){

            foreach ($configurable_attributes as $configurable_attribute) {
                
                array_push($attributes_multi, array('label' => $configurable_attribute->getFrontendLabel(), 'value' => $configurable_attribute->getAttributeId()));
            
            }

        }else{

            $attributes = $this->collectionAttribute->load()->getItems();

            foreach ($attributes as $attribute) {
                
                array_push($attributes_multi, array('label' => $attribute->getFrontendLabel(), 'value' => $attribute->getAttributeId()));
            
            }

        }

        $fieldset->addField(
            'format_configurable_attributes',
            'multiselect',
            [
                'name' => 'format_configurable_attributes[]',
                'label' => __('Format Configurable Attributes'),
                'title' => __('Format Configurable Attributes'),
                'required' => false,
                'values' => $attributes_multi,
                'disabled' => false,
                'class' => 'conn_field'
            ]
        );

        $this->_eventManager->dispatch('adminhtml_synccatalog_edit_tab_products_prepare_form', ['form' => $form]);

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
        return __('Product parameters');
    }

    /**
     * Prepare title for tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return __('Product parameters');
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
