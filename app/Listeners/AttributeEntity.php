<?php

namespace ProjectApptiva\Listeners;

use Espo\Core\Exceptions\BadRequest;
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
     *
     * @throws BadRequest
     */
    public function beforeSave(Event $event)
    {
        /** @var Entity $entity */
        $entity = $event->getArgument('entity');

        // get deleted positions
        $deletedPositions = $this->getDeletedPositions($entity->get('typeValue'));

        // delete positions
        if (!empty($deletedPositions)) {
            $this->deletePositions($entity, $deletedPositions);
        }

        if (!$entity->isNew() && in_array($entity->get('type'), ['enum']) && $this->isEnumTypeValueValid($entity)) {
            $this->cascadeUpdate($entity, $deletedPositions);
        }
    }

    /**
     * @param $entity
     *
     * @return bool
     * @throws BadRequest
     */
    protected function isEnumTypeValueValid($entity): bool
    {
        if (!empty($entity->get('typeValue'))) {
            foreach (array_count_values($entity->get('typeValue')) as $count) {
                if ($count > 1) {
                    throw new BadRequest('Attribute value should be unique.');
                }
            }
        }

        return true;
    }

    /**
     * @param Entity $entity
     * @param array  $deletedPositions
     */
    protected function cascadeUpdate(Entity $entity, array $deletedPositions)
    {
        /** @var string $attributeId */
        $attributeId = $entity->get('id');

        $sql = [];

        foreach ($this->getTypeValuesFields() as $field) {
            // prepare column
            $column = str_replace('type_', '', Util::toUnderScore($field));

            // get fetched
            $fetchedTypeValue = $entity->getFetched($field);

            // get type value and remove deleted positions
            $typeValue = $entity->get($field);

            foreach ($entity->getFetched('typeValue') as $k => $value) {
                $oldValue = isset($fetchedTypeValue[$k]) ? $fetchedTypeValue[$k] : '';
                $newValue = (in_array($k, $deletedPositions)) ? '' : $typeValue[$k];
                if ($oldValue != $newValue) {
                    $rowSql = "UPDATE product_attribute_value SET $column='$newValue' WHERE attribute_id='$attributeId' AND deleted=0";
                    if ($field != 'typeValue') {
                        // prepare main value
                        $mainValue = (in_array($k, $deletedPositions)) ? '' : $entity->get('typeValue')[$k];

                        $rowSql .= " AND value='$mainValue'";
                    } else {
                        $rowSql .= " AND value='$oldValue'";
                    }

                    // push
                    $sql[] = $rowSql;
                }
            }
        }

        if (!empty($sql)) {
            $this->getEntityManager()->nativeQuery(implode(';', $sql));
        }
    }

    /**
     * @param array $typeValue
     *
     * @return array
     */
    protected function getDeletedPositions(array $typeValue): array
    {
        $deletedPositions = [];
        foreach ($typeValue as $pos => $value) {
            if ($value === 'todel') {
                $deletedPositions[] = $pos;
            }
        }

        return $deletedPositions;
    }

    /**
     * @param Entity $entity
     * @param array  $deletedPositions
     */
    protected function deletePositions(Entity $entity, array $deletedPositions): void
    {
        foreach ($this->getTypeValuesFields() as $field) {
            $typeValue = $entity->get($field);
            foreach ($deletedPositions as $pos) {
                unset($typeValue[$pos]);
            }
            $entity->set($field, array_values($typeValue));
        }
    }

    /**
     * @return array
     */
    protected function getTypeValuesFields(): array
    {
        $fields[] = 'typeValue';
        if ($this->getConfig()->get('isMultilangActive', false)) {
            foreach ($this->getConfig()->get('inputLanguageList', []) as $locale) {
                $fields[] = 'typeValue' . ucfirst(Util::toCamelCase(strtolower($locale)));
            }
        }

        return $fields;
    }
}
