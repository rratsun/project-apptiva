<?php
declare(strict_types=1);

namespace ProjectApptiva;

use Espo\Core\Utils\Json;
use Treo\Core\ModuleManager\AbstractModule;
use Treo\Core\Utils\Config;
use Treo\Core\Utils\Util;

/**
 * Class Module
 *
 * @author Roman Ratsun <r.ratsun@gmail.com>
 */
class Module extends AbstractModule
{
    /**
     * @inheritdoc
     */
    public static function getLoadOrder(): int
    {
        return 9999;
    }

    /**
     * @return bool
     */
    public function isSystem(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function loadMetadata(\stdClass &$data)
    {
        parent::loadMetadata($data);

        // prepare result
        $result = Json::decode(Json::encode($data), true);

        foreach ($this->getInputLanguageList() as $locale => $key) {
            $result['clientDefs']['Attribute']['dynamicLogic']['fields']['typeValue' . $key]['visible']['conditionGroup'] = [
                [
                    'type'      => 'in',
                    'attribute' => 'type',
                    'value'     => ['no-such-type']
                ]
            ];
            $result['clientDefs']['Attribute']['dynamicLogic']['fields']['typeValue' . $key]['required']['conditionGroup'] = [
                [
                    'type'      => 'in',
                    'attribute' => 'type',
                    'value'     => ['no-such-type']
                ]
            ];
        }

        // set data
        $data = Json::decode(Json::encode($result));
    }

    /**
     * @return array
     */
    protected function getInputLanguageList(): array
    {
        $result = [];

        /** @var Config $config */
        $config = $this->container->get('config');

        if ($config->get('isMultilangActive', false)) {
            foreach ($config->get('inputLanguageList', []) as $locale) {
                $result[$locale] = ucfirst(Util::toCamelCase(strtolower($locale)));
            }
        }

        return $result;
    }
}
