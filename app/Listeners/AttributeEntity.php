<?php

namespace ProjectApptiva\Listeners;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Utils\Json;
use Espo\ORM\Entity;
use Espo\ORM\EntityCollection;
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

        if (!$entity->isNew()) {
            $this->cascadeEnumUpdate($entity, $deletedPositions);
            $this->cascadeMultiEnumUpdate($entity, $deletedPositions);
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
     *
     * @return bool
     * @throws BadRequest
     */
    protected function cascadeEnumUpdate(Entity $entity, array $deletedPositions): bool
    {
        if ($entity->get('type') != 'enum') {
            return true;
        }

        if (!$this->isEnumTypeValueValid($entity)) {
            return true;
        }

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

        return true;
    }

    /**
     * @param Entity $attribute
     * @param array  $deletedPositions
     *
     * @return bool
     * @throws BadRequest
     */
    protected function cascadeMultiEnumUpdate(Entity $attribute, array $deletedPositions): bool
    {
        if ($attribute->get('type') != 'multiEnum') {
            return true;
        }

        if (!$this->isEnumTypeValueValid($attribute)) {
            return true;
        }

        // old type value
        $oldTypeValue = $attribute->getFetched('typeValue');

        // delete
        foreach ($deletedPositions as $deletedPosition) {
            unset($oldTypeValue[$deletedPosition]);
        }

        // prepare became values
        $becameValues = [];
        foreach (array_values($oldTypeValue) as $k => $v) {
            $becameValues[$v] = $attribute->get('typeValue')[$k];
        }

        /** @var EntityCollection $pavs */
        $pavs = $attribute->get('productAttributeValues');

        if ($pavs->count() > 0) {
            foreach ($pavs as $pav) {
                /**
                 * First, prepare main value
                 */
                $values = !empty($pav->get('value')) ? Json::decode($pav->get('value'), true) : [];
                if (!empty($values)) {
                    $newValues = [];
                    foreach ($values as $value) {
                        if (isset($becameValues[$value])) {
                            $newValues[] = $becameValues[$value];
                        }
                    }
                    $pav->set('value', Json::encode($newValues));
                    $values = $newValues;
                }

                $sqlValues = ["value='" . $pav->get('value') . "'"];

                /**
                 * Second, update locales
                 */
                if ($this->getConfig()->get('isMultilangActive', false)) {
                    foreach ($this->getConfig()->get('inputLanguageList', []) as $language) {
                        $locale = ucfirst(Util::toCamelCase(strtolower($language)));
                        $localeValues = [];
                        foreach ($values as $value) {
                            $localeValues[] = $attribute->get("typeValue{$locale}")[array_search($value, $attribute->get('typeValue'))];
                        }
                        $pav->set("value{$locale}", Json::encode($localeValues));
                        $sqlValues[] = "value_" . strtolower($language) . "='" . $pav->get("value{$locale}") . "'";
                    }
                }

                /**
                 * Third, set to DB
                 */
                $this
                    ->getEntityManager()
                    ->nativeQuery("UPDATE product_attribute_value SET " . implode(",", $sqlValues) . " WHERE id='" . $pav->get('id') . "'");
            }
        }

        return true;
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
