<?php
declare(strict_types=1);

namespace ProjectApptiva\Listeners;

use Treo\Core\Utils\Util;
use Treo\Listeners\AbstractListener;
use Treo\Core\EventManager\Event;

/**
 * Class Metadata
 *
 * @author Roman Ratsun <r.ratsun@gmail.com>
 */
class Metadata extends AbstractListener
{
    /**
     * @param Event $event
     */
    public function modify(Event $event)
    {
        // prepare metadata
        $metadata = $event->getArgument('data');

        // replace view
        if (isset($metadata['clientDefs']['Product']['bottomPanels'])) {
            foreach ($metadata['clientDefs']['Product']['bottomPanels']['detail'] as $k => $row) {
                if ($row['name'] == 'variantConfiguration') {
                    $metadata['clientDefs']['Product']['bottomPanels']['detail'][$k]['view'] = 'project-apptiva:views/product/record/panels/variants-configuration';
                }
            }
        }

        // skip validation
        $metadata['productVariant']['validation']['skipAttributesValidation'] = true;

        // float as multiLang attribute
        $multiLangTypes = \Pim\Module::$multiLangTypes;
        $multiLangTypes[] = 'float';

        $metadata['clientDefs']['Attribute']['dynamicLogic']['fields']['isMultilang']['visible']['conditionGroup'] = [
            [
                'type'      => 'in',
                'attribute' => 'type',
                'value'     => $multiLangTypes
            ]
        ];
        foreach ($this->getInputLanguageList() as $locale => $key) {
            $metadata['clientDefs']['Attribute']['dynamicLogic']['fields']['name' . $key]['visible']['conditionGroup'] = [
                [
                    'type'      => 'in',
                    'attribute' => 'type',
                    'value'     => $multiLangTypes
                ],
                [
                    'type'      => 'isTrue',
                    'attribute' => 'isMultilang'
                ]
            ];
        }

        $event->setArgument('data', $metadata);
    }

    /**
     * @return array
     */
    protected function getInputLanguageList(): array
    {
        $result = [];

        $config = $this->getConfig();
        if ($config->get('isMultilangActive', false)) {
            foreach ($config->get('inputLanguageList', []) as $locale) {
                $result[$locale] = ucfirst(Util::toCamelCase(strtolower($locale)));
            }
        }

        return $result;
    }
}
