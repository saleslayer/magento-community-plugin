<?php
namespace Saleslayer\Synccatalog\Controller\Adminhtml\Index;

class Downlogs extends \Magento\Backend\App\Action
{
    protected $modelo;
    /**
     * Constructor
     *
     * @return void
     */
    public function __construct(
        \Saleslayer\Synccatalog\Model\Synccatalog $modelo,
        \Magento\Backend\App\Action\Context $context
    )
    {
        $this->modelo                   = $modelo;
        parent::__construct($context);
    }

    public function execute(){
        
        $result = $this->modelo->downloadSLLogs();

        if (!$result){

            $this->messageManager->addError(__('Error downloading SL logs zip.'));
        
        }

        $this->_redirect('*/index/tools');  

    }

}