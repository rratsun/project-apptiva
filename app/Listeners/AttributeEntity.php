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
            if ($entity->isAttributeChanged('isMultilang') && !empty($entity->get('isMultilang'))) {
                $this->cascadeCreateMultiLangAttributes($entity);
            }

            if (!$entity->isAttributeChanged('isMultilang')) {
                $this->cascadeUpdate($entity);
            }
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
                            $newValue = !isset($newValues[$k]) ? 'NULL' : "'{$newValues[$k]}'";
                            $this
                                ->getEntityManager()
                                ->nativeQuery("UPDATE product_attribute_value SET $column=$newValue WHERE attribute_id='$attributeId' AND deleted=0");
                        } elseif ($entity->get('type') == 'multiEnum') {
                            // get product attribute values
                            $productAttributeValues = $this
                                ->getEntityManager()
                                ->nativeQuery("SELECT id, $column FROM product_attribute_value WHERE attribute_id='$attributeId' AND deleted=0 AND $column LIKE '%\"$oldValue\"%' ")
                                ->fetchAll(\PDO::FETCH_ASSOC);

                            foreach ($productAttributeValues as $productAttributeValue) {
                                $pavValueData = json_decode(str_replace("\"$oldValue\"", "\"{$newValues[$k]}\"", $productAttributeValue[$column]), true);
                                $pavValue = [];
                                foreach ($pavValueData as $v) {
                                    if ($v !== '') {
                                        $pavValue[] = $v;
                                    }
                                }
                                $pavValue = json_encode($pavValue);

                                $this
                                    ->getEntityManager()
                                    ->nativeQuery("UPDATE product_attribute_value SET $column='$pavValue' WHERE id='{$productAttributeValue['id']}'");
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @param Entity $entity
     */
    protected function cascadeCreateMultiLangAttributes(Entity $entity)
    {
        /** @var string $attributeId */
        $attributeId = $entity->get('id');

        /** @var array $typeValue */
        $typeValue = $entity->get('typeValue');

        /** @var array $productAttributeValues */
        $productAttributeValues = $this
            ->getEntityManager()
            ->nativeQuery("SELECT id, value FROM product_attribute_value WHERE attribute_id='$attributeId' AND deleted=0")
            ->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($productAttributeValues as $productAttributeValue) {
            if ($this->getConfig()->get('isMultilangActive', false)) {
                foreach ($this->getConfig()->get('inputLanguageList', []) as $locale) {
                    /** @var string $locale */
                    $locale = strtolower($locale);

                    /** @var array $localeTypeValue */
                    $localeTypeValue = $entity->get('typeValue' . ucfirst(Util::toCamelCase($locale)));

                    if ($entity->get('type') == 'enum') {
                        /** @var int $key */
                        $key = array_search($productAttributeValue['value'], $typeValue);

                        if (isset($localeTypeValue[$key])) {
                            $this
                                ->getEntityManager()
                                ->nativeQuery("UPDATE product_attribute_value SET value_{$locale}='{$localeTypeValue[$key]}' WHERE id='{$productAttributeValue['id']}'");
                        }
                    } elseif ($entity->get('type') == 'multiEnum') {
                        /** @var array $values */
                        $values = json_decode($productAttributeValue['value'], true);

                        // prepare locale values
                        $localeValues = [];
                        if (!empty($values) && is_array($values)) {
                            foreach ($values as $value) {
                                $key = array_search($value, $typeValue);
                                if (isset($localeTypeValue[$key])) {
                                    $localeValues[] = $localeTypeValue[$key];
                                }
                            }
                        }

                        if (!empty($localeValues)) {
                            $localeValues = json_encode($localeValues);
                            $this
                                ->getEntityManager()
                                ->nativeQuery("UPDATE product_attribute_value SET value_{$locale}='{$localeValues}' WHERE id='{$productAttributeValue['id']}'");
                        }
                    }
                }
            }
        }
    }
}
