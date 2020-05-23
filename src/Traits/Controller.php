<?php
namespace Smooler\Traits;

trait Controller
{
	protected function validate($rules) 
	{
        global $app;
        $app->validate->handle($rules);
	}

    protected function data($key = null) 
    {
        global $app;
        $data = &$app->context->getData();
        return $key ? ($data[$key] ?? '') : $data;
    }
}
