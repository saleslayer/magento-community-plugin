<?php
/**
 * Synccatalog Json helper
 */
namespace Saleslayer\Synccatalog\Helper;

use Saleslayer\Synccatalog\Helper\slDebuger as slDebuger;
use \Magento\Framework\Serialize\Serializer\Json as json;

class slJson extends \Magento\Framework\App\Helper\AbstractHelper
{

    protected $slDebuger;
    protected $json;

    /**
     * slJson constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param Saleslayer\Synccatalog\Helper\slDebuger $slDebuger
     * @param \Magento\Framework\Serialize\Serializer\Json $json
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        slDebuger $slDebuger,
        Json $json
    ) {
        parent::__construct($context);
        $this->slDebuger = $slDebuger;
        $this->json = $json;

    }

    /**
     * Function to serialize data
     * @param  array    $data       data to serialize
     * @return string|boolean       serialized content, if not, false
     */
    public function serialize($data){

        try{

            return $this->json->serialize($data);

        }catch(\invalidArgumentException $e){
          
            $this->slDebuger->debug('## Error. '.$e->getMessage(). ' - Original data: '.print_r($data,1));

        }

        return false;

    }

    /**
     * Function to unserialize string
     * @param  string    $string     string to unserialize
     * @return array|boolean         unserialized content, if not, false
     */
    public function unserialize($string){

        if (is_string($string) && $string !== ''){
            
            try{

                return $this->json->unserialize($string);

            }catch(\invalidArgumentException $e){
                
                $this->slDebuger->debug('## Error. '.$e->getMessage(). ' - Original string: '.print_r($string,1));

            }

        }

        return false;

    }

}