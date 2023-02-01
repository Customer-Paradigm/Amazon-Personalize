<?php
/**
 * CustomerParadigm_AmazonPersonalize
 *
 * @category   CustomerParadigm
 * @package    CustomerParadigm_AmazonPersonalize
 * @copyright  Copyright (c) 2023 Customer Paradigm (https://customerparadigm.com/)
 * @license    https://github.com/Customer-Paradigm/Amazon-Personalize/blob/master/LICENSE.md
 */

declare(strict_types=1);

namespace CustomerParadigm\AmazonPersonalize\Model;

use Magento\Config\Model\Config\Reader\Source\Deployed\SettingChecker;
use Magento\Config\Model\Config\Structure\Element\Group;
use Magento\Config\Model\Config\Structure\Element\Field;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ScopeInterface;
use Magento\Framework\App\ScopeResolverPool;
use Magento\Store\Model\ScopeInterface as StoreScopeInterface;
use Magento\Store\Model\ScopeTypeNormalizer;

/**
 * Backend config model
 *
 * Used to save configuration
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @api
 * @since 100.0.2
 * @method string getSection()
 * @method void setSection(string $section)
 * @method string getWebsite()
 * @method void setWebsite(string $website)
 * @method string getStore()
 * @method void setStore(string $store)
 * @method string getScope()
 * @method void setScope(string $scope)
 * @method int getScopeId()
 * @method void setScopeId(int $scopeId)
 * @method string getScopeCode()
 * @method void setScopeCode(string $scopeCode)
 */
class CoreConfigExtend extends \Magento\Config\Model\Config
{
    /**
     * @var Config\Reader\Source\Deployed\SettingChecker
     */
    private $settingChecker;

    /**
     * @var ScopeResolverPool
     */
    private $scopeResolverPool;

    /**
     * @var ScopeTypeNormalizer
     */
    private $scopeTypeNormalizer;

    /**
     * @var \Magento\Framework\MessageQueue\PoisonPill\PoisonPillPutInterface
     */
    private $pillPut;

    /**
     * @param \Magento\Framework\App\Config\ReinitableConfigInterface $config
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Config\Model\Config\Structure $configStructure
     * @param \Magento\Framework\DB\TransactionFactory $transactionFactory
     * @param \Magento\Config\Model\Config\Loader $configLoader
     * @param \Magento\Framework\App\Config\ValueFactory $configValueFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param Config\Reader\Source\Deployed\SettingChecker|null $settingChecker
     * @param array $data
     * @param ScopeResolverPool|null $scopeResolverPool
     * @param ScopeTypeNormalizer|null $scopeTypeNormalizer
     * @param \Magento\Framework\MessageQueue\PoisonPill\PoisonPillPutInterface|null $pillPut
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\App\Config\ReinitableConfigInterface $config,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Config\Model\Config\Structure $configStructure,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Magento\Config\Model\Config\Loader $configLoader,
        \Magento\Framework\App\Config\ValueFactory $configValueFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        SettingChecker $settingChecker = null,
        array $data = [],
        ScopeResolverPool $scopeResolverPool = null,
        ScopeTypeNormalizer $scopeTypeNormalizer = null,
        \Magento\Framework\MessageQueue\PoisonPill\PoisonPillPutInterface $pillPut = null
    ) {
        parent::__construct(
            $config,
            $eventManager,
            $configStructure,
            $transactionFactory,
            $configLoader,
            $configValueFactory,
            $storeManager,
            $settingChecker,
            $data,
            $scopeResolverPool,
            $scopeTypeNormalizer,
            $pillPut
        );
        $this->settingChecker = $settingChecker
            ?? ObjectManager::getInstance()->get(SettingChecker::class);
        $this->scopeResolverPool = $scopeResolverPool
            ?? ObjectManager::getInstance()->get(ScopeResolverPool::class);
        $this->scopeTypeNormalizer = $scopeTypeNormalizer
            ?? ObjectManager::getInstance()->get(ScopeTypeNormalizer::class);
        $this->pillPut = $pillPut ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\MessageQueue\PoisonPill\PoisonPillPutInterface::class);
    }

    /**
     * Map field name if they were cloned
     *
     * @param Group $group
     * @param string $fieldId
     * @return string
     */
    private function getOriginalFieldId(Group $group, string $fieldId): string
    {
        if ($group->shouldCloneFields()) {
            $cloneModel = $group->getCloneModel();

            /** @var \Magento\Config\Model\Config\Structure\Element\Field $field */
            foreach ($group->getChildren() as $field) {
                foreach ($cloneModel->getPrefixes() as $prefix) {
                    if ($prefix['field'] . $field->getId() === $fieldId) {
                        $fieldId = $field->getId();
                        break(2);
                    }
                }
            }
        }

        return $fieldId;
    }

    /**
     * Get field object
     *
     * @param string $sectionId
     * @param string $groupId
     * @param string $fieldId
     * @return Field
     */
    private function getField(string $sectionId, string $groupId, string $fieldId): Field
    {
        /** @var \Magento\Config\Model\Config\Structure\Element\Group $group */
        $group = $this->_configStructure->getElement($sectionId . '/' . $groupId);
        $fieldPath = $group->getPath() . '/' . $this->getOriginalFieldId($group, $fieldId);
        $field = $this->_configStructure->getElement($fieldPath);

        return $field;
    }

    /**
     * Get field path
     *
     * @param Field $field
     * @param string $fieldId Need for support of clone_field feature
     * @param array $oldConfig Need for compatibility with _processGroup()
     * @param array $extraOldGroups Need for compatibility with _processGroup()
     * @return string
     */
    private function getFieldPath(Field $field, string $fieldId, array &$oldConfig, array &$extraOldGroups): string
    {
        $path = $field->getGroupPath() . '/' . $fieldId;

        /**
         * Look for custom defined field path
         */
        $configPath = $field->getConfigPath();
        if ($configPath && strrpos($configPath, '/') > 0) {
            // Extend old data with specified section group
            $configGroupPath = substr($configPath, 0, strrpos($configPath, '/'));
            if (!isset($extraOldGroups[$configGroupPath])) {
                $oldConfig = $this->extendConfig($configGroupPath, true, $oldConfig);
                $extraOldGroups[$configGroupPath] = true;
            }
            $path = $configPath;
        }

        return $path;
    }

    /**
     * Check is config value changed
     *
     * @param array $oldConfig
     * @param string $path
     * @param array $fieldData
     * @return bool
     */
    private function isValueChanged(array $oldConfig, string $path, array $fieldData): bool
    {
        if (isset($oldConfig[$path]['value'])) {
            $result = !isset($fieldData['value']) || $oldConfig[$path]['value'] !== $fieldData['value'];
        } else {
            $result = empty($fieldData['inherit']);
        }

        return $result;
    }

    /**
     * Get changed paths
     *
     * @param string $sectionId
     * @param string $groupId
     * @param array $groupData
     * @param array $oldConfig
     * @param array $extraOldGroups
     * @return array
     */
    private function getChangedPaths(
        string $sectionId,
        string $groupId,
        array $groupData,
        array &$oldConfig,
        array &$extraOldGroups
    ): array {
        $changedPaths = [];

        if (isset($groupData['fields'])) {
            foreach ($groupData['fields'] as $fieldId => $fieldData) {
                $field = $this->getField($sectionId, $groupId, $fieldId);
                $path = $this->getFieldPath($field, $fieldId, $oldConfig, $extraOldGroups);
                if ($this->isValueChanged($oldConfig, $path, $fieldData)) {
                    $changedPaths[] = $path;
                }
            }
        }

        if (isset($groupData['groups'])) {
            $subSectionId = $sectionId . '/' . $groupId;
            foreach ($groupData['groups'] as $subGroupId => $subGroupData) {
                $subGroupChangedPaths = $this->getChangedPaths(
                    $subSectionId,
                    $subGroupId,
                    $subGroupData,
                    $oldConfig,
                    $extraOldGroups
                );
                $changedPaths = \array_merge($changedPaths, $subGroupChangedPaths);
            }
        }

        return $changedPaths;
    }

    /**
     * Process group data
     *
     * @param string $groupId
     * @param array $groupData
     * @param array $groups
     * @param string $sectionPath
     * @param array $extraOldGroups
     * @param array $oldConfig
     * @param \Magento\Framework\DB\Transaction $saveTransaction
     * @param \Magento\Framework\DB\Transaction $deleteTransaction
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _processGroup(
        $groupId,
        array $groupData,
        array $groups,
        $sectionPath,
        array &$extraOldGroups,
        array &$oldConfig,
        \Magento\Framework\DB\Transaction $saveTransaction,
        \Magento\Framework\DB\Transaction $deleteTransaction
    ) {
        if (! ($sectionPath == "awsp_settings" || $sectionPath == "awsp_wizard")) {
            return parent::_processGroup(
                $groupId,
                $groupData,
                $groups,
                $sectionPath,
                $extraOldGroups,
                $oldConfig,
                $saveTransaction,
                $deleteTransaction
            );
        }

        $groupPath = $sectionPath . '/' . $groupId;

        if (isset($groupData['fields'])) {
            /** @var \Magento\Config\Model\Config\Structure\Element\Group $group */
            $group = $this->_configStructure->getElement($groupPath);

            // set value for group field entry by fieldname
            // use extra memory
            $fieldsetData = [];
            foreach ($groupData['fields'] as $fieldId => $fieldData) {
                $fieldsetData[$fieldId] = $fieldData['value'] ?? null;
            }

            foreach ($groupData['fields'] as $fieldId => $fieldData) {
                $isReadOnly = $this->settingChecker->isReadOnly(
                    $groupPath . '/' . $fieldId,
                    $this->getScope(),
                    $this->getScopeCode()
                );

                if ($isReadOnly) {
                    continue;
                }

                $field = $this->getField($sectionPath, $groupId, $fieldId);
                /** @var \Magento\Framework\App\Config\ValueInterface $backendModel */
                $backendModel = $field->hasBackendModel()
                    ? $field->getBackendModel()
                    : $this->_configValueFactory->create();

                $existingConfigVal = $this->getConfigDataValue($groupPath . "/" . $fieldId);
                if (array_key_exists('value', $fieldData) && ($fieldData['value'] == "saved"
                || $fieldData['value'] == "testing")
                ) {
                    $fieldData['value'] = $existingConfigVal;
                }

                if (!isset($fieldData['value'])) {
                    $fieldData['value'] = null;
                }

                if ($field->getType() == 'multiline' && is_array($fieldData['value'])) {
                    $fieldData['value'] = trim(implode(PHP_EOL, $fieldData['value']));
                }

                $data = [
                    'field' => $fieldId,
                    'groups' => $groups,
                    'group_id' => $group->getId(),
                    'scope' => $this->getScope(),
                    'scope_id' => $this->getScopeId(),
                    'scope_code' => $this->getScopeCode(),
                    'field_config' => $field->getData(),
                    'fieldset_data' => $fieldsetData,
                ];
                $backendModel->addData($data);
                $this->_checkSingleStoreMode($field, $backendModel);

                $path = $this->getFieldPath($field, $fieldId, $extraOldGroups, $oldConfig);
                $backendModel->setPath($path)->setValue($fieldData['value']);

                $inherit = !empty($fieldData['inherit']);
                if (isset($oldConfig[$path])) {
                    $backendModel->setConfigId($oldConfig[$path]['config_id']);

                    /**
                     * Delete config data if inherit
                     */
                    if (!$inherit) {
                        $saveTransaction->addObject($backendModel);
                    } else {
                        $deleteTransaction->addObject($backendModel);
                    }
                } elseif (!$inherit) {
                    $backendModel->unsConfigId();
                    $saveTransaction->addObject($backendModel);
                }
            }
        }
        if (isset($groupData['groups'])) {
            foreach ($groupData['groups'] as $subGroupId => $subGroupData) {
                $this->_processGroup(
                    $subGroupId,
                    $subGroupData,
                    $groups,
                    $groupPath,
                    $extraOldGroups,
                    $oldConfig,
                    $saveTransaction,
                    $deleteTransaction
                );
            }
        }
    }

    /**
     * Set scope data
     *
     * @return void
     */
    private function initScope()
    {
        if ($this->getSection() === null) {
            $this->setSection('');
        }

        $scope = $this->retrieveScope();
        $this->setScope($this->scopeTypeNormalizer->normalize($scope->getScopeType()));
        $this->setScopeCode($scope->getCode());
        $this->setScopeId($scope->getId());

        if ($this->getWebsite() === null) {
            $this->setWebsite(StoreScopeInterface::SCOPE_WEBSITES === $this->getScope() ? $scope->getId() : '');
        }
        if ($this->getStore() === null) {
            $this->setStore(StoreScopeInterface::SCOPE_STORES === $this->getScope() ? $scope->getId() : '');
        }
    }

    /**
     * Retrieve scope from initial data
     *
     * @return ScopeInterface
     */
    private function retrieveScope(): ScopeInterface
    {
        $scopeType = $this->getScope();
        if (!$scopeType) {
            switch (true) {
                case $this->getStore():
                    $scopeType = StoreScopeInterface::SCOPE_STORES;
                    $scopeIdentifier = $this->getStore();
                    break;
                case $this->getWebsite():
                    $scopeType = StoreScopeInterface::SCOPE_WEBSITES;
                    $scopeIdentifier = $this->getWebsite();
                    break;
                default:
                    $scopeType = ScopeInterface::SCOPE_DEFAULT;
                    $scopeIdentifier = null;
                    break;
            }
        } else {
            switch (true) {
                case $this->getScopeId() !== null:
                    $scopeIdentifier = $this->getScopeId();
                    break;
                case $this->getScopeCode() !== null:
                    $scopeIdentifier = $this->getScopeCode();
                    break;
                case $this->getStore() !== null:
                    $scopeIdentifier = $this->getStore();
                    break;
                case $this->getWebsite() !== null:
                    $scopeIdentifier = $this->getWebsite();
                    break;
                default:
                    $scopeIdentifier = null;
                    break;
            }
        }
        $scope = $this->scopeResolverPool->get($scopeType)
            ->getScope($scopeIdentifier);

        return $scope;
    }
}
