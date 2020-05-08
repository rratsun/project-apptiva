<?php

namespace ProjectApptiva\Listeners;

use Espo\Core\Utils\Json;
use Espo\ORM\Entity;
use Treo\Core\EventManager\Event;
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

        if (!empty($entity->get('insertMode')) && $entity->get('attributeType') == 'multiEnum' && !empty($data = $entity->getFetched('value'))) {
            $data = array_merge(Json::decode($data, true), Json::decode($entity->get('value'), true));
            $sortedData = [];
            foreach ($entity->get('attribute')->get('typeValue') as $v) {
                if (in_array($v, $data)) {
                    $sortedData[] = $v;
                }
            }

            $entity->set('value', Json::encode(array_values(array_unique($sortedData))));
        }
    }
}
