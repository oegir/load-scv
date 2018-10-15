<?php
namespace App;

use App\Base\BaseView;

class AjaxView extends BaseView
{
    public function beforeDisplay(): ?bool
    {
        header('Content-type: application/json');
        echo json_encode((Object) $this->getData());
        
        return FALSE;
    }
}