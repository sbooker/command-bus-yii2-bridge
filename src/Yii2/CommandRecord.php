<?php

declare(strict_types=1);

namespace Sbooker\CommandBus\Infrastructure\Persistence\Yii2;

use yii\db\ActiveRecord;

/**
 * @property string $id
 * @property string $name
 * @property string $payload
 * @property string $status
 * @property string $changed_at
 * @property int $count
 * @property string $next_attempt_at
 * @property string | null $result
 */
class CommandRecord extends ActiveRecord
{
    public static function tableName()
    {
        return '{{command}}';
    }
}