<?php
namespace Saleslayer\Synccatalog\Model;

use Magento\Framework\Model\Context as context;
use Magento\Framework\Registry as registry;
use Magento\Framework\Model\ResourceModel\AbstractResource as resource;
use Magento\Framework\Data\Collection\AbstractDb as resourceCollection;
use Magento\Framework\Filesystem\DirectoryList as directoryListFilesystem;
use Magento\Catalog\Model\Category as categoryModel;
use Magento\Catalog\Model\Product as productModel;
use Magento\Catalog\Api\ProductRepositoryInterface as productRepository;
use Magento\Eav\Model\Entity\Attribute as attribute;
use Magento\Eav\Model\Entity\Attribute\Set as attribute_set;
use Magento\Catalog\Api\ProductAttributeManagementInterface as productAttributeManagementInterface;
use Magento\Indexer\Model\Indexer as indexer;
use Magento\Framework\App\ResourceConnection as resourceConnection;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\Collection as collectionOption;
use Magento\Cron\Model\Schedule as cronSchedule;
use Magento\Framework\App\Config\ScopeConfigInterface as scopeConfigInterface;
use Magento\CatalogUrlRewrite\Model\CategoryUrlPathGenerator as categoryUrlPathGenerator;
use Magento\CatalogUrlRewrite\Model\ProductUrlPathGenerator as productUrlPathGenerator;
use Magento\CatalogInventory\Model\Configuration as catalogInventoryConfiguration;
use Magento\Eav\Model\Config as eavConfig;
use Magento\Framework\App\Cache\TypeListInterface as typeListInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Countryofmanufacture as countryOfManufacture;
use Magento\Catalog\Model\Category\Attribute\Source\Layout as layoutSource;
use Magento\CatalogInventory\Api\StockRegistryInterface as stockRegistryInterface;
use Saleslayer\Synccatalog\Model\SalesLayerConn as SalesLayerConn;
use Saleslayer\Synccatalog\Helper\Data as synccatalogDataHelper;
use Saleslayer\Synccatalog\Helper\slConnection as slConnection;
use Saleslayer\Synccatalog\Helper\slDebuger as slDebuger;
use Saleslayer\Synccatalog\Helper\slJson as slJson;
use Saleslayer\Synccatalog\Helper\Config as synccatalogConfigHelper;
use \Zend_Db_Expr as Expr;

/**
 * Class Saleslayer_Synccatalog_Model_Syncdatacron
 */
class Syncdatacron extends Synccatalog
{
    
    protected       $sl_time_ini_sync_data_process;
    protected       $max_execution_time                 = 290;
    protected       $end_process;
    protected       $initialized_vars                   = false;
    protected       $sql_items_delete                   = [];
    protected       $category_fields                    = [];
    protected       $product_fields                     = [];
    protected       $product_format_fields              = [];
    protected       $syncdata_pid;
    protected       $processed_items                    = [];
    protected       $cats_to_process                    = false;
    protected       $cats_corrected                     = false;
    protected       $updated_product_formats            = false;

    protected       $test_one_item                      = false;

    /**
     * Sales Layer Syncdata constructor.
     *
     * @return void
     */
    public function __construct(
        context $context,
        registry $registry,
        SalesLayerConn $salesLayerConn,
        synccatalogDataHelper $synccatalogDataHelper,
        slConnection $slConnection,
        slDebuger $slDebuger,
        slJson $slJson,
        synccatalogConfigHelper $synccatalogConfigHelper,
        directoryListFilesystem $directoryListFilesystem,
        categoryModel $categoryModel,
        productModel $productModel,
        attribute $attribute,
        attribute_set $attribute_set,
        productAttributeManagementInterface $productAttributeManagementInterface,
        indexer $indexer,
        resourceConnection $resourceConnection,
        collectionOption $collectionOption,
        cronSchedule $cronSchedule,
        scopeConfigInterface $scopeConfigInterface,
        categoryUrlPathGenerator $categoryUrlPathGenerator,
        productUrlPathGenerator $productUrlPathGenerator,
        catalogInventoryConfiguration $catalogInventoryConfiguration,
        eavConfig $eavConfig,
        typeListInterface $typeListInterface,
        countryOfManufacture $countryOfManufacture,
        layoutSource $layoutSource,
        stockRegistryInterface $stockRegistryInterface,
        productRepository $productRepository,
        resource $resource = null,
        resourceCollection $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry, 
            $salesLayerConn, 
            $synccatalogDataHelper,
            $slConnection,
            $slDebuger,
            $slJson,
            $synccatalogConfigHelper,
            $directoryListFilesystem,
            $categoryModel, 
            $productModel,
            $attribute,
            $attribute_set,
            $productAttributeManagementInterface,
            $indexer,
            $resourceConnection,
            $collectionOption,
            $cronSchedule,
            $scopeConfigInterface,
            $categoryUrlPathGenerator,
            $productUrlPathGenerator,
            $catalogInventoryConfiguration,
            $eavConfig,
            $typeListInterface,
            $countryOfManufacture,
            $layoutSource,
            $stockRegistryInterface,
            $productRepository,
            $resource,
            $resourceCollection,
            $data
        );

    }

    /**
     * Function to check current process time to avoid exceding the limit.
     *
     * @return void
     */
    private function check_process_time()
    {

        $current_process_time = microtime(1) - $this->sl_time_ini_sync_data_process;
        
        if ($current_process_time >= $this->max_execution_time) {

            $this->end_process = true;

        }

    }

    /**
     * Function to initialize catalogue vars to load before synchronizing.
     *
     * @return void
     */
    private function initialize_vars()
    {

        if (!$this->initialized_vars) {

            if (!$this->execute_slyr_load_functions()) {

                $this->slDebuger->debug('## Error. Could not load synchronization parameters. Please check error log.', 'syncdata');
                $this->end_process = true;

            }
            
            $this->category_fields = [
                'category_field_name',
                'category_field_url_key',
                'category_field_description',
                'category_field_image',
                'category_field_meta_title',
                'category_field_meta_keywords',
                'category_field_meta_description',
                'category_field_active',
                'category_images_sizes',
                'category_field_page_layout',
                'category_field_is_anchor'
            ];

            $this->product_fields = [
                'product_field_name',
                'product_field_description',
                'product_field_description_short',
                'product_field_price',
                'product_field_image',
                'product_field_sku',
                'product_field_qty',
                'product_field_inventory_backorders',
                'product_field_inventory_min_sale_qty',
                'product_field_inventory_max_sale_qty',
                'product_field_meta_title',
                'product_field_meta_keywords',
                'product_field_meta_description',
                'product_field_length',
                'product_field_width',
                'product_field_height',
                'product_field_weight',
                'product_field_status',
                'product_field_visibility',
                'product_field_related_references',
                'product_field_crosssell_references',
                'product_field_upsell_references',
                'product_field_attribute_set_id',
                'product_images_sizes',
                'main_image_extension',
                'product_field_tax_class_id',
                'product_field_country_of_manufacture',
                'product_field_special_price',
                'product_field_special_from_date',
                'product_field_special_to_date',
                'grouping_ref_field_linked'
            ];
            
            $this->product_format_fields = [
                'format_images_sizes',
                'main_image_extension',
                'format_field_sku',
                'format_name',
                'format_price',
                'format_quantity',
                'format_field_inventory_backorders',
                'format_field_inventory_min_sale_qty',
                'format_field_inventory_max_sale_qty',
                'format_image',
                'format_field_tax_class_id',
                'format_field_country_of_manufacture',
                'format_field_visibility',
                'format_field_special_price',
                'format_field_special_from_date',
                'format_field_special_to_date'
            ];

            $this->initialized_vars = true;

        }

    }

    /**
     * Function to check sql rows to delete from sync data table.
     *
     * @param  boolean $force_delete will force delete from database
     * @return void
     */
    private function check_sql_items_delete($force_delete = false)
    {

        if (count($this->sql_items_delete) >= 20 || ($force_delete && count($this->sql_items_delete) > 0)) {
            
            if ($this->test_one_item === false) {
        
                $this->slConnection->slDBDelete(
                    $this->slConnection->getTable($this->saleslayer_syncdata_table),
                    ['id IN ('.implode(',', $this->sql_items_delete).')']
                );

            }

            $this->sql_items_delete = [];

        }

    }

    /**
     * Function to check sync data pid flag in database and delete kill it if the process is stuck.
     *
     * @return void
     */
    private function check_sync_data_flag()
    {

        $items_to_process = $this->connection->fetchOne(
            $this->connection->select()->from(
                $this->slConnection->getTable($this->saleslayer_syncdata_table), 
                [new Expr('COUNT(*)')]
            )
        );
        
        if (isset($items_to_process) && $items_to_process > 0) {

            $current_flag = $this->connection->fetchRow(
                $this->connection->select()
                    ->from($this->slConnection->getTable($this->saleslayer_syncdata_flag_table))
                    ->order('id DESC')
                    ->limit(1)
            );
            
            $now = strtotime('now');
            $date_now = date('Y-m-d H:i:s', $now);

            if (empty($current_flag)) {

                $values_to_insert = [
                    'syncdata_pid' => $this->syncdata_pid,
                    'syncdata_last_date' => $date_now
                ];

                $this->slConnection->slDBInsert(
                    $this->slConnection->getTable($this->saleslayer_syncdata_flag_table),
                    $values_to_insert,
                    array_keys($values_to_insert)
                );

                return;
            }

                
            if ($current_flag['syncdata_pid'] == 0) {
            
                $values_to_update = array(
                    'syncdata_pid' => $this->syncdata_pid,
                    'syncdata_last_date' => $date_now,
                );

                $this->slConnection->slDBUpdate(
                    $this->slConnection->getTable($this->saleslayer_syncdata_flag_table), 
                    $values_to_update, 
                    'id = '.$current_flag['id']
                );

                return;

            }

            $interval  = abs($now - strtotime($current_flag['syncdata_last_date']));
            $minutes   = round($interval / 60);
            
            if ($minutes < 10) {
            
                $this->slDebuger->debug('Data is already being processed.', 'syncdata');
                $this->end_process = true;

                return;

            }
                
            if ($this->syncdata_pid === $current_flag['syncdata_pid']) {

                $this->slDebuger->debug('Pid is the same as current.', 'syncdata');

            }
            
            $values_to_update = array(
                'syncdata_pid' => $this->syncdata_pid,
                'syncdata_last_date' => $date_now,
            );

            $this->slConnection->slDBUpdate(
                $this->slConnection->getTable($this->saleslayer_syncdata_flag_table), 
                $values_to_update, 
                'id = '.$current_flag['id']
            );
            
        }

    }

    /**
     * Function to disable sync data pid flag in database.
     *
     * @return void
     */
    private function disable_sync_data_flag()
    {
        
        try{

            $current_flag = $this->connection->fetchRow(
                $this->connection->select()
                    ->from($this->slConnection->getTable($this->saleslayer_syncdata_flag_table))
                    ->order('id DESC')
                    ->limit(1)
            );
        
        }catch(\Exception $e){

            $this->slDebuger->debug('## Error. Reading current_flag: '.$e->getMessage(), 'syncdata');

        }

        if (!empty($current_flag)) {
        
            $values_to_update = array(
                'syncdata_pid' => 0,
                'syncdata_last_date' => date('Y-m-d H:i:s', strtotime('now')),
            );

            $this->slConnection->slDBUpdate(
                $this->slConnection->getTable($this->saleslayer_syncdata_flag_table), 
                $values_to_update, 
                'id = '.$current_flag['id'],
                'Deleting sync_data_flag',
                'syncdata'
            );
        
        }

    }

    /**
     * Function to delete registers that have more than 3 tries
     *
     * @return void
     */
    private function clearExcededAttemps()
    {
        
        $this->slConnection->slDBDelete(
            $this->slConnection->getTable($this->saleslayer_syncdata_table),
            ['sync_tries >= 3'],
            'Clearing exceeded attemps',
            'syncdata'
        );

    }

    /**
     * Function to check if the current hour is between the config synchronization hours
     *
     * @return void
     */
    private function testRecurrentExecution()
    {
        $hour_from = $this->sync_data_hour_from.':00';
        $hour_from_time = strtotime($hour_from);
        $hour_until = $this->sync_data_hour_until.':00';
        $hour_until_time = strtotime($hour_until);
        $hour_now = date('H').':00';
        $hour_now_time = strtotime($hour_now);
    
        if (($hour_from_time < $hour_until_time && $hour_now_time >= $hour_from_time && $hour_now_time <= $hour_until_time) 
            || ($hour_from_time > $hour_until_time && ($hour_now_time >= $hour_from_time || $hour_now_time <= $hour_until_time)) 
            ||  $hour_from_time == $hour_until_time
        ) {
            
            $this->slDebuger->debug('Current hour '.$hour_now.' for sync data process.', 'syncdata');
        
        } else {
        
            $this->end_process = true;
            $this->slDebuger->debug('Current hour '.$hour_now.' is not set between hour from '.$hour_from.' and hour until '.$hour_until.'. Finishing sync data process.', 'syncdata');
        
        }
    }

    /**
     * Function to synchronize Sales Layer stored data
     *
     * @return void
     */
    public function sync_data_connectors_db()
    {

        $this->sl_time_ini_sync_data_process = microtime(1);

        $this->loadConfigParameters();
        $this->load_magento_variables();

        if ($this->clean_main_debug_file) { file_put_contents($this->sl_logs_path.'_debbug_log_saleslayer_'.date('Y-m-d').'.dat', "");
        }

        $this->slDebuger->debug("==== Sync Data DB INIT ".date('Y-m-d H:i:s')." ====", 'syncdata');
        
        $this->clearExcededAttemps();

        $this->syncdata_pid = getmypid();

        $this->end_process = false;        
        if (!in_array($this->sync_data_hour_from, array('', null, 0)) || !in_array($this->sync_data_hour_until, array('', null, 0))) {
            
            $this->testRecurrentExecution();   

        }

        if (!$this->end_process) {

            $this->check_sync_data_flag();

            if (!$this->end_process) {

                $this->deleteItems();

                $this->updateAllTableItems();
                
            }

            $this->check_sql_items_delete(true);

            if (!$this->end_process) {

                $this->clearExcededAttemps();

            }       

            $this->disable_sync_data_flag();

        }

        $this->generateSummary();
        
        $this->slDebuger->debug('### time_all_syncdata_process: '.(microtime(1) - $this->sl_time_ini_sync_data_process).' seconds.', 'syncdata');

        $this->slDebuger->debug("==== Sync Data DB END ====", 'syncdata');

    }

    /**
     * Function to process update items
     *
     * @return void
     */
    private function updateAllTableItems()
    {

        $indexes = array('category', 'product', 'product_format', 'product_links', 'product__images');
        
        $old_index = reset($indexes);

        $this->cats_to_process = $this->cats_corrected = false;

        foreach ($indexes as $index) {

            if(!$this->updateItems($index)) {
                break;
            }
             
        }

        if (!empty($this->processed_items)) {

            $this->clean_cache();

        }

        if ($this->updated_product_formats) {

            $this->reindexAfterFormats();

        }

    }

    /**
     * Function to update items
     *
     * @param  string $index type of item to process
     * @return boolean                  result of update
     */
    private function updateItems($index)
    {
        
        do{

            if ($this->test_one_item !== false && is_numeric($this->test_one_item)) {

                $items_to_update = $this->connection->fetchAll(
                    $this->connection->select()
                        ->from(
                            $this->slConnection->getTable($this->saleslayer_syncdata_table)
                        )
                        ->where("sync_type = 'update'")
                        ->where("id = ? ", $this->test_one_item)
                        ->limit(1)
                );

            }else{
                
                $items_to_update = $this->connection->fetchAll(
                    $this->connection->select()
                        ->from(
                            $this->slConnection->getTable($this->saleslayer_syncdata_table)
                        )
                        ->where("sync_type = 'update'")
                        ->where("item_type = ? ", $index)
                        ->where('sync_tries <= 2')
                        ->order('sync_tries ASC')
                        ->order('level ASC')
                        ->order('id ASC')
                        ->limit(5)
                );

            }

            if ($index == 'category' && !$this->cats_to_process) {

                if (!empty($items_to_update)) {

                    $this->cats_to_process = true;

                }

            }else if ($index !== 'category') {

                if ($this->cats_to_process && !$this->cats_corrected) {

                    $this->correct_categories_core_data();
                    $this->cats_corrected = true;

                }

            }

            if (empty($items_to_update)) {
                return true;
            }

            $this->initialize_vars();

            foreach ($items_to_update as $item_to_update) {
                
                $this->check_process_time();

                if ($this->end_process) {

                    $this->slDebuger->debug('Breaking syncdata process due to time limit.', 'syncdata');
                    return false;

                }
             
                $this->updateItem($item_to_update);

                if ($this->test_one_item !== false) { $this->end_process = true;
                }
                if ($this->end_process) {

                    return false;

                }

            }

        }while(!empty($items_to_update));

        return true;
        
    }

    /**
     * Function to delete items
     *
     * @return void
     */
    private function deleteItems()
    {

        try {

            $items_to_delete = $this->connection->fetchAll(
                $this->connection->select()
                    ->from(
                        $this->slConnection->getTable($this->saleslayer_syncdata_table)
                    )
                    ->where("sync_type = 'delete'")
                    ->order('item_type ASC')
                    ->order('sync_tries ASC')
                    ->order('id ASC')
            );
            
            if (!empty($items_to_delete)) {
                
                $this->initialize_vars();

                foreach ($items_to_delete as $item_to_delete) {
                    
                    $this->check_process_time();
                    $this->check_sql_items_delete();

                    if ($this->end_process) {

                        $this->slDebuger->debug('Breaking syncdata process due to time limit.', 'syncdata');
                        break;

                    }

                    $this->deleteItem($item_to_delete);

                }

            }

        } catch (\Exception $e) {

            $this->slDebuger->debug('## Error. Deleting syncdata process: '.$e->getMessage(), 'syncdata');

        }

    }

    /**
     * Function to process delete item
     *
     * @param  string $item item to delete
     * @return void
     */
    private function deleteItem($item_to_delete)
    {

        $sync_tries = $item_to_delete['sync_tries'];

        $sync_params = $this->slJson->unserialize(stripslashes($item_to_delete['sync_params']));

        $result_delete = '';

        if ($sync_params !== false) {

            $this->processing_connector_id = $sync_params['conn_params']['connector_id'];

            if($this->comp_id != $sync_params['conn_params']['comp_id']) {
                $this->comp_id = $sync_params['conn_params']['comp_id'];
                $this->load_sl_multiconn_table_data(); 
            } 
            
            $this->store_view_ids = $sync_params['conn_params']['store_view_ids'];

            $sl_id = $this->slJson->unserialize(stripslashes($item_to_delete['item_data']));
            
            if ($sl_id !== false) {

                switch ($item_to_delete['item_type']) {
                case 'category':
                        
                    $result_delete = $this->delete_stored_category_db($sl_id);
                    break;
                    
                case 'product':
                        
                    $result_delete = $this->delete_stored_product_db($sl_id);
                    break;

                case 'product_format':
                        
                    $result_delete = $this->delete_stored_product_format_db($sl_id);
                    break;

                default:
                        
                    $this->slDebuger->debug('## Error. Incorrect item: '.print_r($item_to_delete, 1), 'syncdata');
                    break;
                }

                if ($result_delete == 'item_not_deleted') {

                    $sync_tries++;

                    $values_to_update = array(
                        'sync_tries' => $sync_tries
                    );

                    $this->slConnection->slDBUpdate(
                        $this->slConnection->getTable($this->saleslayer_syncdata_table), 
                        $values_to_update, 
                        'id = '.$item_to_delete['id']
                    );
                    
                }

            }

        }

        if ($result_delete !== 'item_not_deleted') {

            $this->sql_items_delete[] = $item_to_delete['id'];

        }

    }

    /**
     * Function to generate summary of current synchronization process.
     *
     * @return void
     */
    private function generateSummary()
    {

        if (!empty($this->processed_items)) {

            foreach ($this->processed_items as $processed_item_type => $processed_item_type_count) {
                
                $this->slDebuger->debug('- Processed_items - type: '.$processed_item_type.' count: '.$processed_item_type_count, 'syncdata');

            }

        }

    }

    /**
     * Function to clean Magento cache
     *
     * @return void
     */
    public function clean_cache()
    {

        $time_ini_clean_all_caches = microtime(1);

        $types = [
            \Magento\Framework\App\Cache\Type\Block::TYPE_IDENTIFIER,
            \Magento\PageCache\Model\Cache\Type::TYPE_IDENTIFIER
        ];

        foreach ($types as $type) {

            $time_ini_clean_cache = microtime(1);
            $this->typeListInterface->cleanType($type);
            if ($this->sl_DEBBUG > 1) { $this->slDebuger->debug('### time_clean_cache: ', 'timer', (microtime(1) - $time_ini_clean_cache));
            }

        }

        $this->slDebuger->debug('Cache cleaned for: '.print_r($types, 1));
        $this->slDebuger->debug('#### time_clean_all_caches: ', 'timer', (microtime(1) - $time_ini_clean_all_caches));

    }

    /**
     * Function to reindex Magento indexers after product format synchronization
     *
     * @return void
     */
    public function reindexAfterFormats()
    {

        $time_ini_reindex_after_formats = microtime(1);

        $indexLists = array('catalog_product_attribute', 'catalogrule_product');

        if (version_compare($this->slConnection->mg_version, '2.4.0') >= 0) {
        
            $indexLists[] = 'catalogsearch_fulltext';

        }

        foreach($indexLists as $indexList) {
            
            try{

                $time_ini_index_row = microtime(1);
                $categoryIndexer = $this->indexer->load($indexList);
           
                if (!$categoryIndexer->isScheduled()) {
                
                    if ($this->sl_DEBBUG > 0) { $this->slDebuger->debug('Reindexing indexer after product formats sync: '.$indexList, 'syncdata');
                    }
                    $categoryIndexer->reindexAll();
                                        
                }

            }catch(\Exception $e){

                $this->slDebuger->debug('## Error. Updating index row '.$indexList.' : '.print_r($e->getMessage(), 1), 'syncdata');

            }

            if ($this->sl_DEBBUG > 2) { $this->slDebuger->debug('## time_index_row '.$indexList.': ', 'timer', (microtime(1) - $time_ini_index_row));
            }

        }

        $this->slDebuger->debug('#### time_reindex_after_formats: ', 'timer', (microtime(1) - $time_ini_reindex_after_formats));

    }

    /**
     * Function to update item depending on type.
     *
     * @param  $item_to_update item date to update in Magento
     * @return void
     */
    private function updateItem($item_to_update)
    {
         
        $sync_tries = $item_to_update['sync_tries'];
        
        $item_data = $this->slJson->unserialize($item_to_update['item_data']);

        if ($item_data == '' || $item_data === false) {
        
            $this->slDebuger->debug("## Error. Decoding item's data: ".print_r($item_to_update['item_data'], 1), 'syncdata');
            $this->sql_items_delete[] = $item_to_update['id'];
            $this->check_sql_items_delete(true);
            return;

        }

        if ($item_to_update['sync_params'] != '') {

            $sync_params = $this->loadSyncParams($item_to_update);
            
            if ($sync_params === false) {

                $this->slDebuger->debug("## Error. Decoding sync params: ".print_r($item_to_update['sync_params'], 1), 'syncdata');
                $this->sql_items_delete[] = $item_to_update['id'];
                $this->check_sql_items_delete(true);
                return;

            }

        }

        if (!isset($this->processed_items[$item_to_update['item_type']])) {

            $this->processed_items[$item_to_update['item_type']] = 0;

        }

        $this->processed_items[$item_to_update['item_type']]++;
        
        switch ($item_to_update['item_type']) {
        case 'category':
                
            $result_update = $this->updateCategory($item_data, $sync_params);
            break;
            
        case 'product':
                
            $result_update = $this->updateProduct($item_data, $sync_params);
            break;

        case 'product_format':
                
            $result_update = $this->updateProductFormat($item_data, $sync_params);
            break;

        case 'product_links':
                
            $result_update = $this->updateProductLinks($item_data);
            break;

        case 'product__images':

            $result_update = $this->updateProductImages($item_data);
            break;

        default:
                
            $this->slDebuger->debug('## Error. Incorrect item: '.print_r($item_to_update, 1), 'syncdata');
            break;
        }

        
        if ($result_update != 'item_not_updated') {

            $this->sql_items_delete[] = $item_to_update['id'];
            $this->check_sql_items_delete(true);

            return;

        }
             
        $sync_tries++;

        $values_to_update = array(
            'sync_tries' => $sync_tries
        );
        
        if ($sync_tries == 2 && $item_to_update['item_type'] == 'category') {

            $resultado = $this->reorganize_category_parent_ids_db($item_data);

            $resultado_encoded = $this->slJson->serialize($resultado);

            if ($resultado_encoded !== false) {

                $values_to_update['item_data'] = $resultado_encoded;
      
            }

        }

        $this->slConnection->slDBUpdate(
            $this->slConnection->getTable($this->saleslayer_syncdata_table), 
            $values_to_update, 
            'id = '.$item_to_update['id']
        );

        $this->check_sql_items_delete(true);

    }

    /**
     * Function to update product images
     *
     * @param  array $item_data item data
     * @return string                           if the item has been updated or not
     */
    private function updateProductImages($item_data)
    {
                    
        if (!isset($item_data['product_id']) && !isset($item_data['format_id'])) {

            $this->slDebuger->debug('## Error. Updating item images - Unknown index: '.print_r($item_data, 1), 'syncdata');
            return 'item_updated';
        }

        $item_index = 'product';

        if (isset($item_data['format_id'])) {

            $item_index = 'format';

        }

        $time_ini_sync = microtime(1);
        $this->slDebuger->debug(' >> '.ucfirst($item_index).' images synchronization initialized << ');
        $this->sync_stored_product_images_db($item_data, $item_index);
        $this->slDebuger->debug(' >> '.ucfirst($item_index).' images synchronization finished << ');
        $this->slDebuger->debug('#### time_sync_stored_product_images: ', 'timer', (microtime(1) - $time_ini_sync));

        return 'item_updated';

    }

    /**
     * Function to update product links
     *
     * @param  array $item_data item data
     * @return string                   if the link has been updated or not
     */
    private function updateProductLinks($item_data)
    {

        $time_ini_sync = microtime(1);
        $this->slDebuger->debug(' >> Product links synchronization initialized << ');
        $this->sync_stored_product_links_db($item_data);
        $this->slDebuger->debug(' >> Product links synchronization finished << ');
        $this->slDebuger->debug('#### time_sync_stored_product_links: ', 'timer', (microtime(1) - $time_ini_sync));
        
        return 'item_updated';

    }

    /**
     * Function to update product format
     *
     * @param  array $item_data   item data
     * @param  array $sync_params synchronization params
     * @return boolean                      result of update
     */
    private function updateProductFormat($item_data, $sync_params)
    {

        $this->avoid_stock_update = $sync_params['avoid_stock_update'];
        $this->format_configurable_attributes = $sync_params['format_configurable_attributes'];
        if (isset($sync_params['add_sl_id_to_format_name'])){
            $this->add_sl_id_to_format_name = $sync_params['add_sl_id_to_format_name'];
        }

        foreach ($this->product_format_fields as $product_format_field) {
            
            if (isset($sync_params['product_format_fields'][$product_format_field])) {

                $this->$product_format_field = $sync_params['product_format_fields'][$product_format_field];

            }

        }

        if (isset($sync_params['format_additional_fields']) && !empty($sync_params['format_additional_fields'])) {

            foreach ($sync_params['format_additional_fields'] as $field_name => $field_name_value) {
                
                $this->format_additional_fields[$field_name] = $field_name_value;

            }

        }

        if (isset($sync_params['product_formats_media_field_names']) && !empty($sync_params['product_formats_media_field_names'])) {

            $this->media_field_names['product_formats'] = $sync_params['product_formats_media_field_names'];

        }
        
        $time_ini_sync = microtime(1);
        $this->slDebuger->debug(' >> Format synchronization initialized << ');
        $result_update = $this->sync_stored_format_db($item_data);
        $this->slDebuger->debug(' >> Format synchronization finished << ');
        $this->slDebuger->debug('#### time_sync_stored_product_format: ', 'timer', (microtime(1) - $time_ini_sync));

        $this->updated_product_formats = true;
        
        return $result_update;

    }

    /**
     * Function to update product
     *
     * @param  array $item_data   item data
     * @param  array $sync_params synchronization params
     * @return boolean                      result of update
     */
    private function updateProduct($item_data, $sync_params)
    {

        $this->attribute_set_collection = $sync_params['attribute_set_collection'];
        $this->default_attribute_set_id = $sync_params['default_attribute_set_id'];
        $this->avoid_stock_update = $sync_params['avoid_stock_update'];
        $this->products_previous_categories = $sync_params['products_previous_categories'];
        
        foreach ($this->product_fields as $product_field) {
            
            if (isset($sync_params['product_fields'][$product_field])) {

                $this->$product_field = $sync_params['product_fields'][$product_field];
                
            }

        }

        if (isset($sync_params['product_additional_fields']) && !empty($sync_params['product_additional_fields'])) {

            foreach ($sync_params['product_additional_fields'] as $field_name => $field_name_value) {
                
                $this->product_additional_fields[$field_name] = $field_name_value;

            }

        }

        if (isset($sync_params['products_media_field_names']) && !empty($sync_params['products_media_field_names'])) {

            $this->media_field_names['products'] = $sync_params['products_media_field_names'];

        }
        
        $time_ini_sync = microtime(1);
        $this->slDebuger->debug(' >> Product synchronization initialized << ');
        $result_update = $this->sync_stored_product_db($item_data);
        $this->slDebuger->debug(' >> Product synchronization finished << ');
        $this->slDebuger->debug('#### time_sync_stored_product: ', 'timer', (microtime(1) - $time_ini_sync));
        
        return $result_update;

    }

    /**
     * Function to update category
     *
     * @param  array $item_data   item data
     * @param  array $sync_params synchronization params
     * @return boolean                      result of update
     */
    private function updateCategory($item_data, $sync_params)
    {

        $this->default_category_id = $sync_params['default_category_id'];
        $this->category_is_anchor = $sync_params['category_is_anchor'];
        $this->category_page_layout = $sync_params['category_page_layout'];
        
        foreach ($this->category_fields as $category_field) {
            
            if (isset($sync_params['category_fields'][$category_field])) {

                $this->$category_field = $sync_params['category_fields'][$category_field];

            }

        }

        if (isset($sync_params['catalogue_media_field_names']) && !empty($sync_params['catalogue_media_field_names'])) {

            $this->media_field_names['catalogue'] = $sync_params['catalogue_media_field_names'];

        }
        
        $time_ini_sync = microtime(1);
        $this->slDebuger->debug(' >> Category synchronization initialized << ');
        $result_update = $this->sync_stored_category_db($item_data);
        $this->slDebuger->debug(' >> Category synchronization finished << ');
        $this->slDebuger->debug('#### time_sync_stored_category: ', 'timer', (microtime(1) - $time_ini_sync));

        return $result_update;
       
    }

    /**
     * Function to load synchonization params into class parameters
     *
     * @param  array $item_to_update item to update
     * @return array $sync_params                   decoded sync params
     */
    private function loadSyncParams($item_to_update)
    {

        $sync_params = $this->slJson->unserialize(stripslashes($item_to_update['sync_params']));

        if ($sync_params !== false) {

            $this->processing_connector_id = $sync_params['conn_params']['connector_id'];
            ($this->comp_id != $sync_params['conn_params']['comp_id']) ? $load_sl_multiconn_table_data = true : $load_sl_multiconn_table_data = false;
            $this->comp_id = $sync_params['conn_params']['comp_id'];
            if ($load_sl_multiconn_table_data) { $this->load_sl_multiconn_table_data(); 
            }
            $this->store_view_ids = $sync_params['conn_params']['store_view_ids'];
            $this->website_ids = $sync_params['conn_params']['website_ids'];

        }

        return $sync_params;
        
    }

}
