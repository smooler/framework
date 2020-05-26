<?php
namespace Smooler\Traits;

trait Controller
{
	final protected function validate($rules) 
	{
        global $app;
        $app->validation->handle($rules);
	}

    final protected function data($key = null) 
    {
        global $app;
        $data = &$app->context->getData();
        return $key ? ($data[$key] ?? '') : $data;
    }
}
