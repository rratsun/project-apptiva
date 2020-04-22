<?php
declare(strict_types=1);

namespace ProjectApptiva\Services;

use Pim\Services\Product;
use Treo\Core\Utils\Util;

/**
 * Class ApptivaProduct
 *
 * @author r.ratsun <r.ratsun@gmail.com>
 */
class ApptivaProduct extends Product
{
    /**
     * @param array $productsIds
     *
     * @return array
     */
    public function getAttributesForMassUpdate(array $productsIds): array
    {
        // prepare select
        $select = [
            'attributeId',
            ['attribute.name', 'name'],
            ['attribute.type', 'attributeType'],
            ['attribute.isMultilang', 'attributeIsMultilang'],
            ['attribute.typeValue', 'typeValue'],
        ];

        // for multiLang
        if ($this->getConfig()->get('isMultilangActive', false)) {
            foreach ($this->getConfig()->get('inputLanguageList', []) as $locale) {
                $key = ucfirst(Util::toCamelCase(strtolower($locale)));
                $select[] = ["attribute.typeValue$key", "typeValue$key"];
            }
        }

        $attributes = $this
            ->getEntityManager()
            ->getRepository('ProductAttributeValue')
            ->select($select)
            ->where(
                [
                    'productId' => $productsIds,
                    'scope'     => 'Global'
                ]
            )
            ->leftJoin(['attribute'])
            ->find()
            ->toArray();

        // prepare result
        $result = [];
        foreach ($attributes as $attribute) {
            $result[$attribute['attributeId']] = $attribute;
        }

        return ['attributes' => $result];
    }
}
