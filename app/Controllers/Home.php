<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index()
    {
        if ($this->portal->currentUserId() && $this->portal->currentRole()) {
            return redirect()->to(site_url($this->portal->roleHome((string) $this->portal->currentRole())));
        }

        return redirect()->to(site_url('s/auth/login'));
    }
}
