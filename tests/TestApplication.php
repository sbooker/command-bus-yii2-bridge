<?php

declare(strict_types=1);

namespace Tests\Sbooker\CommandBus\Infrastructure\Persistence\Yii2;

use yii\base\Application;
use yii\base\ExitException;

final class TestApplication extends Application
{
    public function handleRequest($request)
    {
        // do nothing
    }
}