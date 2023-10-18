<?php
/**
 * Synccatalog Connection helper
 */
namespace Saleslayer\Synccatalog\Helper;

use Saleslayer\Synccatalog\Helper\slDebuger as slDebuger;
use \Magento\Framework\App\ResourceConnection as resourceConnection;
use \Magento\Framework\App\ProductMetadataInterface as productMetadata;
use \Magento\Framework\App\DeploymentConfig as deploymentConfig;
use Magento\Framework\Config\ConfigOptionsListConstants;
use Zend_Db_Expr as Expr;

class slConnection extends \Magento\Framework\App\Helper\AbstractHelper
{

    protected $slDebuger;
    protected $resourceConnection;
    protected $productMetadata;
    protected $deploymentConfig;
    protected $connection;
    public $mg_version = '';
    protected $mg_tables_23 = [];
    protected $table_prefix = null;

    /**
     * slConnection constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param Saleslayer\Synccatalog\Helper\slDebuger $slDebuger
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        slDebuger $slDebuger,
        resourceConnection $resourceConnection,
        productMetadata $productMetadata,
        deploymentConfig $deploymentConfig
    ) {
        parent::__construct($context);
        $this->slDebuger = $slDebuger;
        $this->resourceConnection = $resourceConnection;
        $this->productMetadata = $productMetadata;
        $this->deploymentConfig = $deploymentConfig;
        $this->mg_version = $this->productMetadata->getVersion();

        if (version_compare($this->mg_version, '2.3.0') < 0) $this->mg_tables_23[] = 'inventory_source_item';

    }

    /**
     * Function to load resource connection into a class variable
     * @return void
     */
    private function loadConnection(){

        if (!is_object($this->connection)) $this->connection = $this->resourceConnection->getConnection();

    }

    /**
     * Function to get table name with prefix.
     * @param string $table_name              table to search
     * @return string                         table in database with prefix
     */
    public function getTable($table_name){
        
        $this->loadConnection();

        $table_name_return = $this->connection->getTableName($table_name);

        if ($this->connection->isTableExists($table_name_return)){

            $table_prefix = $this->getTablePrefix();

            if ($table_prefix && strpos($table_name_return, $table_prefix) !== 0) {

                $table_name_return = $table_prefix . $table_name_return;

            }

            return $table_name_return;

        }

        if (!in_array($table_name, $this->mg_tables_23)){

            $this->slDebuger->debug('## Error. The table '.$table_name.' does not exist.');

        }

        return null;

    }

    /**
     * Function to get table prefix
     * @return string                        configuration table prefix
     */
    private function getTablePrefix(){

        if (null === $this->table_prefix) {
                
            $this->table_prefix = (string) $this->deploymentConfig->get(
                ConfigOptionsListConstants::CONFIG_PATH_DB_PREFIX
            );
        
        }
        
        return $this->table_prefix;
    }

    /**
     * Function to check if column identifier row_id exists
     * @param string $table_name        table to check
     * @param string $identifier        identifier to check
     * @return string $identifier       identifier of table
     */
    public function getColumnIdentifier($table_name, $identifier = 'entity_id'){
        
        $this->loadConnection();

        if ($this->connection->tableColumnExists($table_name, 'row_id')) {
            $identifier = 'row_id';
        }

        return $identifier;

    }

    /**
     * Function to obtain table status
     * @param  string   $table_name             table name
     * @param  string   $error_message          in case of exception, error message to print
     * @param  string   $sl_log_type            sl log type to print
     * @return array|boolean                    result of table status, false if error
     */
    public function slDBShowTableStatus($table_name, $error_message = '', $sl_log_type = ''){

        $this->loadConnection();

        try{

            return $this->connection->showTableStatus($table_name);
        
        }catch(\Exception $e){
            
            $this->slDebuger->debug('## Error. '.($error_message !== '' ? $error_message.': ' : '').print_r($e->getMessage(),1), $sl_log_type);

        }

        return false;

    }

    
    /**
     * Function to drop index
     * @param  string   $table_name             table name
     * @param  string   $index_name             index name
     * @param  string   $error_message          in case of exception, error message to print
     * @param  string   $sl_log_type            sl log type to print
     * @return void
     */
    public function slDBDropIndex($table_name, $index_name, $error_message = '', $sl_log_type = ''){

        $this->loadConnection();

        try{

            $this->connection->dropIndex(
                $table_name, 
                $index_name
            );

        }catch(\Exception $e){
            
            $this->slDebuger->debug('## Error. '.($error_message !== '' ? $error_message.': ' : '').print_r($e->getMessage(),1), $sl_log_type);

        }

    }

    /**
     * Function to insert values through resource connection
     * @param  string   $table_name             table name
     * @param  array    $values_to_insert       values to insert
     * @param  array    $field_names            field names
     * @param  string   $error_message          in case of exception, error message to print
     * @param  string   $sl_log_type            sl log type to print
     * @return boolean                          result of insert
     */
    public function slDBInsert($table_name, $values_to_insert, $field_names, $error_message = '', $sl_log_type = ''){

        $this->loadConnection();
        $this->connection->beginTransaction();

        try{

            $this->connection->insert(
                $table_name,
                $values_to_insert,
                $field_names
            );

            $this->connection->commit();

            return true;

        }catch(\Exception $e){
            
            $this->connection->rollBack();
            $this->slDebuger->debug('## Error. '.($error_message !== '' ? $error_message.': ' : '').print_r($e->getMessage(),1), $sl_log_type);

        }

        return false;

    }

    /**
     * Function to insert values on duplicate through resource connection
     * @param  string   $table_name             table name
     * @param  array    $values_to_insert       values to insert
     * @param  array    $field_names            field names
     * @param  string   $error_message          in case of exception, error message to print
     * @param  string   $sl_log_type            sl log type to print
     * @return boolean                          result of insert
     */
    public function slDBInsertOnDuplicate($table_name, $values_to_insert, $field_names, $error_message = '', $sl_log_type = ''){

        $this->loadConnection();
        $this->connection->beginTransaction();

        try{

            $this->connection->insertOnDuplicate(
                $table_name,
                $values_to_insert,
                $field_names
            );

            $this->connection->commit();

            return true;

        }catch(\Exception $e){
            
            $this->connection->rollBack();
            $this->slDebuger->debug('## Error. '.($error_message !== '' ? $error_message.': ' : '').print_r($e->getMessage(),1), $sl_log_type);

        }

        return false;

    }

    /**
     * Function to insert multiple values through resource connection
     * @param  string   $table_name             table name
     * @param  array    $values_to_insert       values to insert
     * @param  string   $error_message          in case of exception, error message to print
     * @param  string   $sl_log_type            sl log type to print
     * @return boolean                          result of insert
     */
    public function slDBInsertMultiple($table_name, $values_to_insert, $error_message = '', $sl_log_type = ''){

        $this->loadConnection();
        $this->connection->beginTransaction();

        try{

            $this->connection->insertMultiple(
                $table_name,
                $values_to_insert
            );

            $this->connection->commit();

            return true;

        }catch(\Exception $e){
            
            $this->connection->rollBack();
            $this->slDebuger->debug('## Error. '.($error_message !== '' ? $error_message.': ' : '').print_r($e->getMessage(),1), $sl_log_type);

        }

        return false;

    }

    /**
     * Function to update values through resource connection
     * @param  string   $table_name             table name
     * @param  array    $values_to_update       values to update
     * @param  array    $condition              conditions for update
     * @param  string   $error_message          in case of exception, error message to print
     * @param  string   $sl_log_type            sl log type to print
     * @return boolean                          result of update
     */
    public function slDBUpdate($table_name, $values_to_update, $condition = [], $error_message = '', $sl_log_type = ''){

        $this->loadConnection();
        $this->connection->beginTransaction();

        try{

            $this->connection->update(
                $table_name,
                $values_to_update,
                $condition
            );

            $this->connection->commit();

            return true;

        }catch(\Exception $e){
            
            $this->connection->rollBack();
            $this->slDebuger->debug('## Error. '.($error_message !== '' ? $error_message.': ' : '').print_r($e->getMessage(),1), $sl_log_type);

        }

        return false;

    }

    /**
     * Function to delete values through resource connection
     * @param  string   $table_name             table name
     * @param  array    $condition              conditions for delete
     * @param  string   $error_message          in case of exception, error message to print
     * @param  string   $sl_log_type            sl log type to print
     * @return boolean                          result of delete
     */
    public function slDBDelete($table_name, $condition = [], $error_message = '', $sl_log_type = ''){

        $this->loadConnection();
        $this->connection->beginTransaction();

        try{

            $this->connection->delete(
                $table_name,
                $condition
            );

            $this->connection->commit();

            return true;

        }catch(\Exception $e){
            
            $this->connection->rollBack();
            $this->slDebuger->debug('## Error. '.($error_message !== '' ? $error_message.': ' : '').print_r($e->getMessage(),1), $sl_log_type);

        }

        return false;

    }

}