<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Trang chủ nằm sau middleware auth nên khách vãng lai bị đẩy về /login.
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_login_page_is_reachable(): void
    {
        $this->get('/login')->assertOk();
    }
}
