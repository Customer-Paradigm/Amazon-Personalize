<?php

namespace CustomerParadigm\AmazonPersonalize\Model\Data;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Filesystem\DriverPool;
use Magento\Framework\Filesystem\File\WriteFactory;

abstract class AbstractGenerator
{
    CONST DEFAULT_EXPORT_SUB_DIR = "export/amazonpersonalize";

    CONST DEFAULT_NULL_DATA_VALUE = "none";

    protected $filename;

    protected $csvHeaders = [];

    protected $dataError = '';

    private $exportDir;

    private $writeFactory;

    private $directoryList;

    private $file;

    private $_lastCreatedFilePath;

    protected $writer;

    public function __construct(
        WriteFactory $writeFactory,
        DirectoryList $directoryList,
        File $file
    ){
        $this->writeFactory = $writeFactory;
        $this->directoryList = $directoryList;
        $this->file = $file;
        $this->exportDir = $this->directoryList->getPath(DirectoryList::VAR_DIR)
            . '/' . self::DEFAULT_EXPORT_SUB_DIR;
    }

    public function getFilePath()
    {
        return $this->exportDir . "/" . $this->filename . "-" . date('Ymd_His') . ".csv";
    }

    public function getLastCreatedFilePath()
    {
        return $this->_lastCreatedFilePath;
    }

    protected function createWriter()
    {
        if (!$this->file->isExists($this->exportDir)) {
            $this->file->createDirectory($this->exportDir);
        }

        $this->_lastCreatedFilePath = $this->getFilePath();

        $this->writer = $this->writeFactory->create($this->_lastCreatedFilePath, DriverPool::FILE, 'w');

        return $this;
    }

    protected function closeWriter()
    {
        $this->writer->close();

        return $this;
    }

    protected function parseNullData($data)
    {
        if (is_null($data)) {
            $data = self::DEFAULT_NULL_DATA_VALUE;
        }

        return $data;
    }

    protected function writeCollectionToCsv($dataCollection)
    {
        foreach ($dataCollection as $dataObject) {
            $writeData = [];
            foreach ($this->csvHeaders as $dataKey) {
                $data = $dataObject->getData(strtolower($dataKey));
                $writeData[] = $this->parseNullData($data);
            }
            $this->writer->writeCsv($writeData);
        }

        return $this;
    }

    protected function writeHeadersToCsv()
    {
        $this->writer->writeCsv($this->csvHeaders);

        return $this;
    }

    protected function setDataError($err)
    {
        $this->dataError = $err;
    }

    protected function getDataError()
    {
        return $this->dataError;
    }

    abstract function generateCsv();
}
