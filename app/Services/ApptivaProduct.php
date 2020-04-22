<?php
declare(strict_types=1);

namespace ProjectApptiva\Services;

use Pim\Services\Product;

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
        $attributes = $this
            ->getEntityManager()
            ->getRepository('ProductAttributeValue')
            ->select(
                [
                    'attributeId',
                    ['attribute.name', 'name'],
                    ['attribute.type', 'attributeType'],
                    ['attribute.isMultilang', 'attributeIsMultilang'],
                    ['attribute.typeValue', 'typeValue'],
                    ['attribute.typeValueDeDe', 'typeValueDeDe'],
                ]
            )
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
