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
        $attributes = $this
            ->getEntityManager()
            ->getRepository('Attribute')
            ->find()
            ->toArray();

        // prepare result
        $result = [];
        foreach ($attributes as $attribute) {
            $result[$attribute['id']] = $attribute;
            $result[$attribute['id']]['attributeId'] = $attribute['id'];
            $result[$attribute['id']]['attributeType'] = $attribute['type'];
            $result[$attribute['id']]['attributeIsMultilang'] = $attribute['isMultilang'];
        }

        return ['attributes' => $result];
    }
}
