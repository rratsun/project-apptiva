<?php
declare(strict_types=1);

namespace ProjectApptiva;

use Treo\Core\ModuleManager\AbstractModule;

/**
 * Class Module
 *
 * @author Roman Ratsun <r.ratsun@gmail.com>
 */
class Module extends AbstractModule
{
    /**
     * @inheritdoc
     */
    public static function getLoadOrder(): int
    {
        return 9999;
    }
}
