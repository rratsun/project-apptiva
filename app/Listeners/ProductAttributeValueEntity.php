<?php

namespace ProjectApptiva\Listeners;

use Espo\Core\Utils\Json;
use Espo\ORM\Entity;
use Treo\Core\EventManager\Event;
use Treo\Core\Utils\Util;
use Treo\Listeners\AbstractListener;

/**
 * Class ProductAttributeValueEntity
 *
 * @author r.ratsun <r.ratsun@gmail.com>
 */
class ProductAttributeValueEntity extends AbstractListener
{
    /**
     * @param Event $event
     */
    public function beforeSave(Event $event)
    {
        /** @var Entity $entity */
        $entity = $event->getArgument('entity');

        // insert attribute value if insert mode enabled
        if ($entity->get('massUpdateMode') == 'insert' && $entity->get('attributeType') == 'multiEnum' && !empty($data = $entity->getFetched('value'))) {
            $data = array_merge(Json::decode($data, true), Json::decode($entity->get('value'), true));
            $sortedData = [];
            foreach ($entity->get('attribute')->get('typeValue') as $v) {
                if (in_array($v, $data)) {
                    $sortedData[] = $v;
                }
            }

            $entity->set('value', Json::encode(array_values(array_unique($sortedData))));
        }

        // delete attribute value if delete mode enabled
        if ($entity->get('massUpdateMode') == 'delete') {
            if ($entity->get('attributeType') == 'multiEnum' && !empty($act = $entity->get('value')) && !empty($prev = $entity->getFetched('value'))) {
                $prev = Json::decode($prev, true);
                $act = Json::decode($act, true);

                foreach ($prev as $k => $v) {
                    foreach ($act as $v1) {
                        if ($v == $v1) {
                            unset($prev[$k]);
                        }
                    }
                }

                if (empty($prev)) {
                    $entity->set('value', null);
                } else {
                    $entity->set('value', Json::encode(array_values($prev)));
                }
            } else {
                $entity->set('value', null);

                // for multi-lang
                if ($this->getConfig()->get('isMultilangActive', false)) {
                    foreach ($this->getConfig()->get('inputLanguageList', []) as $locale) {
                        $entity->set('value' . ucfirst(Util::toCamelCase(strtolower($locale))), null);
                    }
                }
            }
        }
    }

    /**
     * @param Event $event
     */
    public function afterSave(Event $event)
    {
        /** @var Entity $entity */
        $entity = $event->getArgument('entity');

        // delete attribute if delete mode enabled and its custom attribute
        if ($entity->get('massUpdateMode') == 'delete' && empty($entity->get('value')) && empty($entity->get('productFamilyAttributeId'))) {
            $this
                ->getEntityManager()
                ->nativeQuery("UPDATE product_attribute_value SET deleted=1 WHERE id='" . $entity->get('id') . "'");
        }
    }
}
