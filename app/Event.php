<?php
declare(strict_types=1);

namespace ProjectApptiva;

use Treo\Core\ModuleManager\AbstractEvent;

/**
 * Class Event
 *
 * @author Roman Ratsun <r.ratsun@gmail.com>
 */
class Event extends AbstractEvent
{
    /**
     * @inheritdoc
     */
    public function afterInstall(): void
    {
    }

    /**
     * @inheritdoc
     */
    public function afterDelete(): void
    {
    }
}
