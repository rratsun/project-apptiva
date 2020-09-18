<?php

namespace ProjectApptiva\Listeners;

use Espo\ORM\Entity;
use Treo\Core\EventManager\Event;
use Treo\Core\Utils\Util;
use Treo\Listeners\AbstractListener;

/**
 * Class AttributeEntity
 *
 * @author r.ratsun <r.ratsun@gmail.com>
 */
class AttributeEntity extends AbstractListener
{
    /**
     * @param Event $event
     */
    public function beforeSave(Event $event)
    {
        /** @var Entity $entity */
        $entity = $event->getArgument('entity');

        if (!$entity->isNew() && in_array($entity->get('type'), ['enum', 'multiEnum'])) {
            $this->cascadeUpdate($entity);
        }
    }

    /**
     * @param Entity $entity
     */
    protected function cascadeUpdate(Entity $entity)
    {
        $attributeId = $entity->get('id');

        $fields[] = 'typeValue';
        if ($this->getConfig()->get('isMultilangActive', false)) {
            foreach ($this->getConfig()->get('inputLanguageList', []) as $locale) {
                $fields[] = 'typeValue' . ucfirst(Util::toCamelCase(strtolower($locale)));
            }
        }

        foreach ($fields as $field) {
            if ($entity->isAttributeChanged($field)) {
                $newValues = $entity->get($field);
                foreach ($entity->getFetched($field) as $k => $oldValue) {
                    if ($oldValue != $newValues[$k]) {
                        $column = str_replace('type_', '', Util::toUnderScore($field));

                        if ($entity->get('type') == 'enum') {
                            $this
                                ->getEntityManager()
                                ->nativeQuery("UPDATE product_attribute_value SET $column='{$newValues[$k]}' WHERE attribute_id='$attributeId' AND deleted=0");
                        } elseif ($entity->get('type') == 'multiEnum') {
                            // get product attribute values
                            $productAttributeValues = $this
                                ->getEntityManager()
                                ->nativeQuery("SELECT id, $column FROM product_attribute_value WHERE attribute_id='$attributeId' AND deleted=0 AND $column LIKE '%\"$oldValue\"%' ")
                                ->fetchAll(\PDO::FETCH_ASSOC);

                            foreach ($productAttributeValues as $productAttributeValue) {
                                $prId = $productAttributeValue['id'];
                                $prVal = str_replace("\"$oldValue\"", "\"{$newValues[$k]}\"", $productAttributeValue[$column]);

                                $this
                                    ->getEntityManager()
                                    ->nativeQuery("UPDATE product_attribute_value SET $column='$prVal' WHERE id='$prId'");
                            }
                        }
                    }
                }
            }
        }
    }
}
