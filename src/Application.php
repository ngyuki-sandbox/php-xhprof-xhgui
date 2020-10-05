<?php
declare(strict_types=1);

namespace App;

class Application
{
    public static function main()
    {
        $app = new static();
        $app->run();
    }

    public function run()
    {
        echo 'hello';
    }
}


