<?php
namespace Smartwave\Porto\Controller\Index;

class Export extends \Magento\Framework\App\Action\Action
{
	protected $fileFactory;
	protected $csvProcessor;
    protected $directoryList;

	protected $productCollectionFactory;
	protected $productStatus;
	protected $productVisibility;

	public function __construct(
        \Magento\Framework\App\Action\Context $context,
    	\Magento\Customer\Model\Session $customerSession,
    	\Magento\Framework\App\Response\Http\FileFactory $fileFactory,
    	\Magento\Framework\File\Csv $csvProcessor,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Model\Product\Attribute\Source\Status $productStatus,
        \Magento\Catalog\Model\Product\Visibility $productVisibility
	)
	{
    	$this->fileFactory = $fileFactory;
    	$this->csvProcessor = $csvProcessor;
        $this->directoryList = $directoryList;

        $this->productCollectionFactory = $productCollectionFactory;
        $this->productStatus = $productStatus;
        $this->productVisibility = $productVisibility;

    	parent::__construct($context, $customerSession);
	}

	public function execute()
	{
    	$fileName = 'products.csv';
    	$filePath = $this->directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR)
        	. "/" . $fileName;


    	$data = $this->getProductsData();

    	$this->csvProcessor
    	    ->setDelimiter(',')
        	->setEnclosure('"')
        	->saveData(
            	$filePath,
            	$data
        	);

    	return $this->fileFactory->create(
        	$fileName,
        	[
            	'type' => "filename",
            	'value' => $fileName,
            	'rm' => true,
        	],
            \Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR,
        	'application/octet-stream'
    	);
	}

    protected function getProductsData()
    {
        $collection = $this->productCollectionFactory->create();
        $collection->joinAttribute('status', 'catalog_product/status', 'entity_id', null, 'inner');
        $collection->joinAttribute('visibility', 'catalog_product/visibility', 'entity_id', null, 'inner');
        $collection->addAttributeToFilter('status', ['in' => $this->productStatus->getVisibleStatusIds()])
            ->addAttributeToFilter('visibility', ['in' => $this->productVisibility->getVisibleInSiteIds()]);
        $products = $collection->getItems();

    	$result[] = [
        	'product_id',
        	'sku',
        ];
        foreach ($products as $p) {
        	$result[] = [
                $p->getId(),
                $p->getSku(),
            ];
        }
        return $result;
    }

}