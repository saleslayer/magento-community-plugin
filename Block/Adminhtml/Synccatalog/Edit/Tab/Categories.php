<?php
namespace Saleslayer\Synccatalog\Block\Adminhtml\Synccatalog\Edit\Tab;

use Saleslayer\Synccatalog\Helper\slConnection as slConnection;
use \Magento\Backend\Block\Widget\Form\Generic as generic;
use \Magento\Backend\Block\Widget\Tab\TabInterface as tabInterface;

/**
 * Synccatalog page edit form Parameters tab
 */
class Categories extends generic implements tabInterface
{

    protected $categoryModel;
    protected $booleanSource;
    protected $layoutSource;
    protected $eavConfig;
    protected $resourceConnection;
    protected $slConnection;

    /**
     * @param \Magento\Backend\Block\Template\Context                 $context
     * @param \Magento\Framework\Registry                             $registry
     * @param \Magento\Framework\Data\FormFactory                     $formFactory
     * @param \Magento\Catalog\Model\Category                         $categoryModel
     * @param \Magento\Eav\Model\Config                               $eavConfig
     * @param \Magento\Framework\App\ResourceConnection               $resourceConnection
     * @param \Magento\Eav\Model\Entity\Attribute\Source\Boolean      $booleanSource
     * @param \Magento\Catalog\Model\Category\Attribute\Source\Layout $layoutSource
     * @param Saleslayer\Synccatalog\Helper\slConnection              $slConnection
     * @param array                                                   $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Catalog\Model\Category $categoryModel,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Eav\Model\Entity\Attribute\Source\Boolean $booleanSource,
        \Magento\Catalog\Model\Category\Attribute\Source\Layout $layoutSource,
        slConnection $slConnection,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
        $this->categoryModel        = $categoryModel;
        $this->eavConfig            = $eavConfig;
        $this->resourceConnection   = $resourceConnection;
        $this->booleanSource        = $booleanSource;
        $this->layoutSource         = $layoutSource;
        $this->slConnection         = $slConnection;
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

        /**
         *
         *
         * @var \Magento\Framework\Data\Form $form
         */
        $form = $this->_formFactory->create();

        $form->setHtmlIdPrefix('synccatalog_main_');

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Categories parameters')]);

        if ($model->getId()) {
            $fieldset->addField('id', 'hidden', ['name' => 'id']);
        }

        $modelData = $model->getData();

        if(empty($modelData)) {
            $modelData['category_page_layout'] = '1column';
        }

        $root_categories = $this->getRootCategories();

        $fieldset->addField(
            'default_cat_id',
            'select',
            [
                'name' => 'default_cat_id',
                'label' => __('Default category'),
                'title' => __('Default category'),
                'required' => false,
                'values' => $root_categories,
                'disabled' => false,
                'class' => 'conn_field'
            ]
        );

        $fieldset->addField(
            'category_is_anchor',
            'select',
            [
                'type' => 'int',
                'name' => 'category_is_anchor',
                'label' => 'Is Anchor',
                'title' => 'Is Anchor',
                'required' => false,
                'values' => array(array('label'=>__('Yes'),'value'=>$this->booleanSource::VALUE_YES),array('label'=>__('No'),'value'=>$this->booleanSource::VALUE_NO)),
                'disabled' => false,
                'class' => 'conn_field'
            ]
        );

        $fieldset->addField(
            'category_page_layout',
            'select',
            [
                'name' => 'category_page_layout',
                'label' => 'Layout',
                'title' => 'Layout',
                'required' => false,
                'values' => $this->layoutSource->getAllOptions(),
                'disabled' => false,
                'class' => 'conn_field'
            ]
        );

        $this->_eventManager->dispatch('adminhtml_synccatalog_edit_tab_categories_prepare_form', ['form' => $form]);
        
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
        return __('Category parameters');
    }

    /**
     * Prepare title for tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return __('Category parameters');
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

    /**
     * Function to get Magento root categories
     *
     * @return array $root_categories               Magento root categories
     */
    private function getRootCategories()
    {

        $root_categories = [];

        $this->connection = $this->resourceConnection->getConnection();

        $category_entity_type_id = $this->eavConfig->getEntityType($this->categoryModel::ENTITY)->getEntityTypeId();

        $name_attribute = $this->getAttribute('name', $category_entity_type_id);

        if (empty($name_attribute) || !isset($name_attribute[\Magento\Eav\Api\Data\AttributeInterface::BACKEND_TYPE])) {
            return $root_categories;
        }

        $category_table = $this->slConnection->getTable('catalog_category_entity');
        $category_name_table = $this->slConnection->getTable('catalog_category_entity_' . $name_attribute[\Magento\Eav\Api\Data\AttributeInterface::BACKEND_TYPE]);
        
        if (null !== $category_name_table) {

            if (version_compare($this->slConnection->mg_version, '2.3.0') < 0) {
            
                $key_parent_id = \Magento\Catalog\Model\Category::KEY_PARENT_ID;

            }else{

                $key_parent_id = \Magento\Catalog\Api\Data\CategoryInterface::KEY_PARENT_ID;

            }
            
            $root_categories = $this->connection->fetchAll(
                $this->connection->select()
                    ->from(
                        ['c1' => $category_table],
                        ['value' => 'c1.entity_id']
                    )
                    ->where('c1.'.$key_parent_id . ' = ?', 1)
                    ->where('c2.'.\Magento\Eav\Api\Data\AttributeOptionLabelInterface::STORE_ID . ' = ?', 0)
                    ->where('c2.'.\Magento\Eav\Api\Data\AttributeInterface::ATTRIBUTE_ID . ' = '.$name_attribute[\Magento\Eav\Api\Data\AttributeInterface::ATTRIBUTE_ID])
                    ->joinLeft(
                        ['c2' => $category_name_table], 
                        'c1.entity_id = c2.entity_id',
                        ['label' => 'c2.value']
                    )
                    ->group('c1.entity_id')
            );

        }

        return $root_categories;

    }

    /**
     * Function to get attribute
     *
     * @param  string $code         attribute code to search
     * @param  int    $entityTypeId entity type id of item
     * @return boolean|array                        attribute found
     */
    private function getAttribute($code, $entityTypeId)
    {
        
        $attribute = $this->connection->fetchRow(
            $this->connection->select()
                ->from(
                    $this->slConnection->getTable('eav_attribute'),
                    [
                        \Magento\Eav\Api\Data\AttributeInterface::ATTRIBUTE_ID,
                        \Magento\Eav\Api\Data\AttributeInterface::BACKEND_TYPE,
                    ]
                )
                ->where(\Magento\Eav\Api\Data\AttributeInterface::ENTITY_TYPE_ID . ' = ?', $entityTypeId)
                ->where(\Magento\Eav\Api\Data\AttributeInterface::ATTRIBUTE_CODE . ' = ?', $code)
                ->limit(1)
        );

        if (empty($attribute)) {

            return false;

        }

        return $attribute;
    }  

}
