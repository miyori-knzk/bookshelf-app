<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * 画面を返すテストが、Vite のビルドなしで動くようにする。
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }
}
